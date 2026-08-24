import { Controller } from "@hotwired/stimulus";
import Sortable from "sortablejs";

const ITEM_SELECTOR = "[data-sortable-item]";
const HANDLE_SELECTOR = "[data-sortable-handle]";
const ITEM_KEY_ATTRIBUTE = "data-sortable-item";
const GHOST_CLASS = "sy-sortable-ghost";

/** The detail of the `sylament--support--sortable:end` event. */
export type SortableEndDetail = {
  /** Every draggable child's key, in the new DOM order. */
  items: string[];
  /** The dragged child's key. */
  item: string | null;
  /** Positions among the draggable children only (the list may interleave chrome). */
  oldDraggableIndex: number;
  newDraggableIndex: number;
};

/**
 * Filament's `x-sortable` directive as a Stimulus controller: one SortableJS instance on the
 * list element, children marked `data-sortable-item="<key>"` dragged from their
 * `[data-sortable-handle]`, and every real move announced as a non-bubbling
 * `sylament--support--sortable:end` event the consumer binds on this same element. The control
 * never talks to a live component — the consumer owns the write.
 */
export default class extends Controller<HTMLElement> {
  static values = {
    animationDuration: { type: Number, default: 300 },
    group: String,
  };

  declare readonly animationDurationValue: number;
  declare readonly groupValue: string;

  #sortable: Sortable | null = null;

  connect(): void {
    this.#sortable = Sortable.create(this.element, {
      group: this.groupValue === "" ? undefined : this.groupValue,
      draggable: ITEM_SELECTOR,
      handle: HANDLE_SELECTOR,
      dataIdAttr: ITEM_KEY_ATTRIBUTE,
      animation: this.animationDurationValue,
      ghostClass: GHOST_CLASS,
      onEnd: this.#onEnd,
    });
  }

  disconnect(): void {
    this.#sortable?.destroy();
    this.#sortable = null;
  }

  #onEnd = (event: Sortable.SortableEvent): void => {
    const { item, from, to, oldDraggableIndex, newDraggableIndex } = event;
    const reindexed = oldDraggableIndex !== newDraggableIndex;
    const moved = from !== to || reindexed;

    // filamentphp/filament#17402: non-draggable siblings shift Sortable's insertion point away
    // from the draggable-only index; re-seat the node after the draggable that should precede it.
    if (reindexed && newDraggableIndex !== undefined && newDraggableIndex > 0) {
      const previous = to.querySelectorAll(`:scope > ${ITEM_SELECTOR}`)[newDraggableIndex - 1];

      if (previous !== undefined) {
        to.insertBefore(item, previous.nextSibling);
      }
    }

    if (!moved || this.#sortable === null) {
      return;
    }

    const detail: SortableEndDetail = {
      items: this.#sortable.toArray(),
      item: item.getAttribute(ITEM_KEY_ATTRIBUTE),
      oldDraggableIndex: oldDraggableIndex ?? 0,
      newDraggableIndex: newDraggableIndex ?? 0,
    };

    this.dispatch("end", { detail, bubbles: false });
  };
}
