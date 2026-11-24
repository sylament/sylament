import { Controller } from "@hotwired/stimulus";
import { getComponent } from "@symfony/ux-live-component";
import { uncloak } from "../utils/cloak.js";
import { resolveLiveHost } from "../utils/live_host.js";
import { orderBeforeDrag, ReorderAnnouncer, readMoveControl } from "../utils/reorder_announcer.js";
const IDENTIFIER = "sylament--forms--repeater";
const COLLAPSED_CLASS = "sy-collapsed";
const COLLAPSED_ATTRIBUTE = "data-collapsed";
const ITEM_KEY_ATTRIBUTE = "data-item-key";
const HEADER_ACTIONS_SELECTOR = ".sy-fo-repeater-item-header-start-actions, .sy-fo-repeater-item-header-end-actions";
const TAKE_OVER_ATTRIBUTE = "data-reorder-action";
const HIDDEN_CONTROL_ATTRIBUTE = "data-reorder-hidden";
const LIVE_PARAM = /^data-live-(.+)-param$/;
class repeater_controller_default extends Controller {
  static targets = ["item", "reorderStatus", "reorderDescription"];
  static values = {
    key: String,
    persist: Boolean,
    movedMessage: String
  };
  #component = null;
  #announcer = null;
  #clickController = null;
  #generation = 0;
  async connect() {
    for (const item of this.itemTargets) {
      this.#stamp(item);
    }
    uncloak(this.element);
    const generation = ++this.#generation;
    if (!this.hasMovedMessageValue) {
      return;
    }
    this.#watchMoveClicks();
    let component;
    try {
      component = await resolveLiveHost(this.element);
    } catch {
      return;
    }
    if (generation !== this.#generation) {
      return;
    }
    this.#component = component;
    this.#announcer = new ReorderAnnouncer(component, {
      descriptions: () => this.reorderDescriptionTargets,
      status: () => this.hasReorderStatusTarget ? this.reorderStatusTarget : null,
      movedMessage: () => this.movedMessageValue,
      restoreFocus: true
    });
  }
  disconnect() {
    this.#generation++;
    this.#clickController?.abort();
    this.#clickController = null;
    this.#announcer?.disconnect();
    this.#announcer = null;
    this.#component = null;
  }
  itemTargetConnected(item) {
    this.#stamp(item);
  }
  toggleItemFromHeader(event) {
    if (event.target instanceof Element && event.target.closest(HEADER_ACTIONS_SELECTOR) !== null) {
      return;
    }
    this.#toggle(event.currentTarget);
  }
  toggleItemFromButton(event) {
    event.stopPropagation();
    this.#toggle(event.currentTarget);
  }
  expandItem(event) {
    const item = this.#itemOf(event.currentTarget);
    if (item !== null) {
      this.#setCollapsed(item, false);
    }
  }
  collapseAll() {
    for (const item of this.itemTargets) {
      this.#setCollapsed(item, true);
    }
  }
  expandAll() {
    for (const item of this.itemTargets) {
      this.#setCollapsed(item, false);
    }
  }
  async reorder(event) {
    const detail = event.detail;
    const items = detail?.items;
    if (!Array.isArray(items)) {
      return;
    }
    const component = this.#component ?? await this.#host();
    const request = () => component.action("mountAction", {
      name: "reorder",
      arguments: { items },
      context: { schemaComponent: this.keyValue }
    });
    const { item, oldDraggableIndex, newDraggableIndex } = detail;
    if (this.#announcer === null || typeof item !== "string" || typeof oldDraggableIndex !== "number" || typeof newDraggableIndex !== "number") {
      await request();
      return;
    }
    const order = items.filter((key) => typeof key === "string");
    await this.#announcer.track(
      {
        item,
        direction: "drag",
        before: orderBeforeDrag(order, item, oldDraggableIndex, newDraggableIndex)
      },
      request
    );
  }
  async #host() {
    const root = this.element.closest('[data-controller~="live"]');
    if (root === null) {
      throw new Error(
        "The sylament--forms--repeater controller needs a live component host to reorder."
      );
    }
    return getComponent(root);
  }
  /**
   * `reordering.js`'s take-over of a plain move control: a click on one of this repeater's move
   * controls whose own wiring still mounts the move the server named is stopped before it reaches
   * UX, and the same `mountAction` call is sent through the announcer instead, which holds its
   * request; while a move waits, the announcer drops the next one. Any other control keeps its
   * own path, unannounced.
   */
  #watchMoveClicks() {
    this.#clickController?.abort();
    this.#clickController = new AbortController();
    this.element.addEventListener(
      "click",
      (event) => {
        const control = event.target instanceof Element ? event.target.closest(`[${TAKE_OVER_ATTRIBUTE}]`) : null;
        const component = this.#component;
        const announcer = this.#announcer;
        if (control === null || component === null || announcer === null || control.closest(`[data-controller~="${IDENTIFIER}"]`) !== this.element || control.matches(":disabled")) {
          return;
        }
        const move = readMoveControl(control);
        const mount = readMount(control);
        if (move === null || mount === null) {
          return;
        }
        event.preventDefault();
        event.stopImmediatePropagation();
        void announcer.track(
          {
            ...move,
            control: control.hasAttribute(HIDDEN_CONTROL_ATTRIBUTE) ? void 0 : control
          },
          () => component.action("mountAction", mount)
        );
      },
      { capture: true, signal: this.#clickController.signal }
    );
  }
  #toggle(target) {
    const item = this.#itemOf(target);
    if (item !== null) {
      this.#setCollapsed(item, !this.#isCollapsed(item));
    }
  }
  #itemOf(target) {
    return target instanceof Element ? target.closest(`[${ITEM_KEY_ATTRIBUTE}]`) : null;
  }
  #isCollapsed(item) {
    const stamped = item.getAttribute(COLLAPSED_ATTRIBUTE);
    return stamped === null ? item.classList.contains(COLLAPSED_CLASS) : stamped === "true";
  }
  #stamp(item) {
    if (item.hasAttribute(COLLAPSED_ATTRIBUTE)) {
      return;
    }
    const persisted = this.persistValue ? this.#readPersisted(item) : null;
    item.setAttribute(
      COLLAPSED_ATTRIBUTE,
      String(persisted ?? item.classList.contains(COLLAPSED_CLASS))
    );
  }
  #setCollapsed(item, collapsed) {
    item.setAttribute(COLLAPSED_ATTRIBUTE, String(collapsed));
    if (this.persistValue) {
      this.#writePersisted(item, collapsed);
    }
  }
  #storageKey(item) {
    return `repeater-${this.keyValue}-${item.getAttribute(ITEM_KEY_ATTRIBUTE) ?? ""}-isCollapsed`;
  }
  #readPersisted(item) {
    try {
      const stored = window.localStorage.getItem(this.#storageKey(item));
      if (stored === "true") {
        return true;
      }
      if (stored === "false") {
        return false;
      }
    } catch {
    }
    return null;
  }
  #writePersisted(item, collapsed) {
    try {
      window.localStorage.setItem(this.#storageKey(item), JSON.stringify(collapsed));
    } catch {
    }
  }
}
function readMount(control) {
  const isLiveAction = (control.getAttribute("data-action") ?? "").split(/\s+/).some((descriptor) => descriptor === "live#action" || descriptor.endsWith("->live#action"));
  const params = {};
  for (const { name, value } of Array.from(control.attributes)) {
    const key = LIVE_PARAM.exec(name)?.[1];
    if (key !== void 0) {
      params[key.replace(/[-_]([a-z0-9])/g, (_, character) => character.toUpperCase())] = typecast(value);
    }
  }
  const { action, ...mount } = params;
  return isLiveAction && action === "mountAction" && mount.name === control.getAttribute(TAKE_OVER_ATTRIBUTE) ? mount : null;
}
function typecast(value) {
  try {
    return JSON.parse(value);
  } catch {
    return value;
  }
}
export {
  repeater_controller_default as default
};
//# sourceMappingURL=repeater_controller.js.map
