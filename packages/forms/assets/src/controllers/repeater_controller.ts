import { Controller } from "@hotwired/stimulus";
import { type Component, getComponent } from "@symfony/ux-live-component";
import { uncloak } from "../utils/cloak.js";
import { resolveLiveHost } from "../utils/live_host.js";
import { orderBeforeDrag, ReorderAnnouncer, readMoveControl } from "../utils/reorder_announcer.js";

const IDENTIFIER = "sylament--forms--repeater";
const COLLAPSED_CLASS = "sy-collapsed";
const COLLAPSED_ATTRIBUTE = "data-collapsed";
const ITEM_KEY_ATTRIBUTE = "data-item-key";
const HEADER_ACTIONS_SELECTOR =
  ".sy-fo-repeater-item-header-start-actions, .sy-fo-repeater-item-header-end-actions";
const TAKE_OVER_ATTRIBUTE = "data-reorder-action";
const HIDDEN_CONTROL_ATTRIBUTE = "data-reorder-hidden";
const LIVE_PARAM = /^data-live-(.+)-param$/;

type SortableEndDetail = {
  items?: unknown;
  item?: unknown;
  oldDraggableIndex?: unknown;
  newDraggableIndex?: unknown;
};

/**
 * The repeater's client-side chrome: each item's collapse state, owned by the item once this
 * controller has stamped it (`data-collapsed` — a recorded attribute the live morph replays, where
 * the server's `sy-collapsed` class is first paint only), collapse-all/expand-all over this
 * repeater's own items, the `expand` reveal the schema root dispatches after a validation failure,
 * the drag-and-drop bridge that turns the sortable control's new order into the host's `reorder`
 * action, and the announcement of a landed move, `reordering.js`'s screen-reader half.
 */
export default class extends Controller<HTMLElement> {
  static targets = ["item", "reorderStatus", "reorderDescription"];

  static values = {
    key: String,
    persist: Boolean,
    movedMessage: String,
  };

  declare readonly itemTargets: HTMLElement[];
  declare readonly hasReorderStatusTarget: boolean;
  declare readonly reorderStatusTarget: HTMLElement;
  declare readonly reorderDescriptionTargets: HTMLElement[];
  declare readonly keyValue: string;
  declare readonly persistValue: boolean;
  declare readonly hasMovedMessageValue: boolean;
  declare readonly movedMessageValue: string;

  #component: Component | null = null;
  #announcer: ReorderAnnouncer | null = null;
  #clickController: AbortController | null = null;
  #generation = 0;

  async connect(): Promise<void> {
    for (const item of this.itemTargets) {
      this.#stamp(item);
    }

    uncloak(this.element);

    const generation = ++this.#generation;

    if (!this.hasMovedMessageValue) {
      return;
    }

    this.#watchMoveClicks();

    let component: Component;

    try {
      component = await resolveLiveHost(this.element);
    } catch {
      // No live host answered, so no move can be sent or announced: a move control's own click
      // then reaches UX, which reports the missing host itself, and a drag throws in `reorder()`.
      return;
    }

    if (generation !== this.#generation) {
      return;
    }

    this.#component = component;
    this.#announcer = new ReorderAnnouncer(component, {
      descriptions: () => this.reorderDescriptionTargets,
      status: () => (this.hasReorderStatusTarget ? this.reorderStatusTarget : null),
      movedMessage: () => this.movedMessageValue,
      restoreFocus: true,
    });
  }

  disconnect(): void {
    this.#generation++;

    this.#clickController?.abort();
    this.#clickController = null;

    this.#announcer?.disconnect();
    this.#announcer = null;

    this.#component = null;
  }

  itemTargetConnected(item: HTMLElement): void {
    this.#stamp(item);
  }

  toggleItemFromHeader(event: Event): void {
    if (event.target instanceof Element && event.target.closest(HEADER_ACTIONS_SELECTOR) !== null) {
      return;
    }

    this.#toggle(event.currentTarget);
  }

  toggleItemFromButton(event: Event): void {
    // Stimulus' `:stop` action option needs 3.2+; the peer range allows 3.0.
    event.stopPropagation();
    this.#toggle(event.currentTarget);
  }

  expandItem(event: Event): void {
    const item = this.#itemOf(event.currentTarget);

    if (item !== null) {
      this.#setCollapsed(item, false);
    }
  }

  collapseAll(): void {
    for (const item of this.itemTargets) {
      this.#setCollapsed(item, true);
    }
  }

  expandAll(): void {
    for (const item of this.itemTargets) {
      this.#setCollapsed(item, false);
    }
  }

  async reorder(event: Event): Promise<void> {
    const detail = (event as CustomEvent<SortableEndDetail>).detail;
    const items = detail?.items;

    if (!Array.isArray(items)) {
      return;
    }

    const component = this.#component ?? (await this.#host());
    const request = () =>
      component.action("mountAction", {
        name: "reorder",
        arguments: { items },
        context: { schemaComponent: this.keyValue },
      });
    const { item, oldDraggableIndex, newDraggableIndex } = detail;

    if (
      this.#announcer === null ||
      typeof item !== "string" ||
      typeof oldDraggableIndex !== "number" ||
      typeof newDraggableIndex !== "number"
    ) {
      await request();

      return;
    }

    const order = items.filter((key): key is string => typeof key === "string");

    await this.#announcer.track(
      {
        item,
        direction: "drag",
        before: orderBeforeDrag(order, item, oldDraggableIndex, newDraggableIndex),
      },
      request,
    );
  }

  async #host(): Promise<Component> {
    const root = this.element.closest<HTMLElement>('[data-controller~="live"]');

    if (root === null) {
      throw new Error(
        "The sylament--forms--repeater controller needs a live component host to reorder.",
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
  #watchMoveClicks(): void {
    this.#clickController?.abort();
    this.#clickController = new AbortController();

    this.element.addEventListener(
      "click",
      (event: Event): void => {
        const control =
          event.target instanceof Element
            ? event.target.closest<HTMLElement>(`[${TAKE_OVER_ATTRIBUTE}]`)
            : null;
        const component = this.#component;
        const announcer = this.#announcer;

        if (
          control === null ||
          component === null ||
          announcer === null ||
          control.closest(`[data-controller~="${IDENTIFIER}"]`) !== this.element ||
          control.matches(":disabled")
        ) {
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
            control: control.hasAttribute(HIDDEN_CONTROL_ATTRIBUTE) ? undefined : control,
          },
          () => component.action("mountAction", mount),
        );
      },
      { capture: true, signal: this.#clickController.signal },
    );
  }

  #toggle(target: EventTarget | null): void {
    const item = this.#itemOf(target);

    if (item !== null) {
      this.#setCollapsed(item, !this.#isCollapsed(item));
    }
  }

  #itemOf(target: EventTarget | null): HTMLElement | null {
    return target instanceof Element
      ? target.closest<HTMLElement>(`[${ITEM_KEY_ATTRIBUTE}]`)
      : null;
  }

  #isCollapsed(item: HTMLElement): boolean {
    const stamped = item.getAttribute(COLLAPSED_ATTRIBUTE);

    return stamped === null ? item.classList.contains(COLLAPSED_CLASS) : stamped === "true";
  }

  #stamp(item: HTMLElement): void {
    if (item.hasAttribute(COLLAPSED_ATTRIBUTE)) {
      return;
    }

    const persisted = this.persistValue ? this.#readPersisted(item) : null;

    item.setAttribute(
      COLLAPSED_ATTRIBUTE,
      String(persisted ?? item.classList.contains(COLLAPSED_CLASS)),
    );
  }

  #setCollapsed(item: HTMLElement, collapsed: boolean): void {
    item.setAttribute(COLLAPSED_ATTRIBUTE, String(collapsed));

    if (this.persistValue) {
      this.#writePersisted(item, collapsed);
    }
  }

  #storageKey(item: HTMLElement): string {
    return `repeater-${this.keyValue}-${item.getAttribute(ITEM_KEY_ATTRIBUTE) ?? ""}-isCollapsed`;
  }

  #readPersisted(item: HTMLElement): boolean | null {
    try {
      const stored = window.localStorage.getItem(this.#storageKey(item));

      if (stored === "true") {
        return true;
      }

      if (stored === "false") {
        return false;
      }
    } catch {
      // Storage unavailable (private mode / disabled): fall back to the server default.
    }

    return null;
  }

  #writePersisted(item: HTMLElement, collapsed: boolean): void {
    try {
      window.localStorage.setItem(this.#storageKey(item), JSON.stringify(collapsed));
    } catch {
      // Storage unavailable: collapsing still works, it just won't persist.
    }
  }
}

/**
 * The arguments a plain move control's `live#action` would send: its `data-live-*-param`
 * attributes read as Stimulus reads them, the `mountAction` they name dropped; `null` when the
 * control's wiring no longer mounts the move the server named in `data-reorder-action`.
 */
function readMount(control: Element): Record<string, unknown> | null {
  const isLiveAction = (control.getAttribute("data-action") ?? "")
    .split(/\s+/)
    .some((descriptor) => descriptor === "live#action" || descriptor.endsWith("->live#action"));
  const params: Record<string, unknown> = {};

  for (const { name, value } of Array.from(control.attributes)) {
    const key = LIVE_PARAM.exec(name)?.[1];

    if (key !== undefined) {
      params[key.replace(/[-_]([a-z0-9])/g, (_, character: string) => character.toUpperCase())] =
        typecast(value);
    }
  }

  const { action, ...mount } = params;

  return isLiveAction &&
    action === "mountAction" &&
    mount.name === control.getAttribute(TAKE_OVER_ATTRIBUTE)
    ? mount
    : null;
}

function typecast(value: string): unknown {
  try {
    return JSON.parse(value);
  } catch {
    // Stimulus' own param cast: a value that is not JSON is sent as the string it is.
    return value;
  }
}
