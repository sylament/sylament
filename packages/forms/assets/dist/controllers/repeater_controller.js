import { Controller } from "@hotwired/stimulus";
import { getComponent } from "@symfony/ux-live-component";
import { uncloak } from "../utils/cloak.js";
const COLLAPSED_CLASS = "sy-collapsed";
const COLLAPSED_ATTRIBUTE = "data-collapsed";
const ITEM_KEY_ATTRIBUTE = "data-item-key";
const HEADER_ACTIONS_SELECTOR = ".sy-fo-repeater-item-header-start-actions, .sy-fo-repeater-item-header-end-actions";
class repeater_controller_default extends Controller {
  static targets = ["item"];
  static values = {
    key: String,
    persist: Boolean
  };
  connect() {
    for (const item of this.itemTargets) {
      this.#stamp(item);
    }
    uncloak(this.element);
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
    const items = event.detail?.items;
    if (!Array.isArray(items)) {
      return;
    }
    const root = this.element.closest('[data-controller~="live"]');
    if (root === null) {
      throw new Error(
        "The sylament--forms--repeater controller needs a live component host to reorder."
      );
    }
    const component = await getComponent(root);
    await component.action("mountAction", {
      name: "reorder",
      arguments: { items },
      context: { schemaComponent: this.keyValue }
    });
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
export {
  repeater_controller_default as default
};
//# sourceMappingURL=repeater_controller.js.map
