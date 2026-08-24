import { Controller } from "@hotwired/stimulus";
import { getComponent } from "@symfony/ux-live-component";
import { uncloak } from "../utils/cloak.js";

const COLLAPSED_CLASS = "sy-collapsed";
const COLLAPSED_ATTRIBUTE = "data-collapsed";
const ITEM_KEY_ATTRIBUTE = "data-item-key";
const HEADER_ACTIONS_SELECTOR =
  ".sy-fo-repeater-item-header-start-actions, .sy-fo-repeater-item-header-end-actions";

type SortableEndDetail = { items?: unknown };

/**
 * The repeater's client-side chrome: each item's collapse state, owned by the item once this
 * controller has stamped it (`data-collapsed` — a recorded attribute the live morph replays, where
 * the server's `sy-collapsed` class is first paint only), collapse-all/expand-all over this
 * repeater's own items, the `expand` reveal the schema root dispatches after a validation failure,
 * and the drag-and-drop bridge that turns the sortable control's new order into the host's
 * `reorder` action.
 */
export default class extends Controller<HTMLElement> {
  static targets = ["item"];

  static values = {
    key: String,
    persist: Boolean,
  };

  declare readonly itemTargets: HTMLElement[];
  declare readonly keyValue: string;
  declare readonly persistValue: boolean;

  connect(): void {
    for (const item of this.itemTargets) {
      this.#stamp(item);
    }

    uncloak(this.element);
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
    const items = (event as CustomEvent<SortableEndDetail>).detail?.items;

    if (!Array.isArray(items)) {
      return;
    }

    const root = this.element.closest<HTMLElement>('[data-controller~="live"]');

    if (root === null) {
      throw new Error(
        "The sylament--forms--repeater controller needs a live component host to reorder.",
      );
    }

    const component = await getComponent(root);

    await component.action("mountAction", {
      name: "reorder",
      arguments: { items },
      context: { schemaComponent: this.keyValue },
    });
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
