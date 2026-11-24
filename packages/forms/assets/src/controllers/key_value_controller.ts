import { Controller } from "@hotwired/stimulus";
import type { Component } from "@symfony/ux-live-component";
import { resolveLiveHost } from "../utils/live_host.js";
import {
  orderBeforeDrag,
  ReorderAnnouncer,
  type ReorderDirection,
  readMoveControl,
} from "../utils/reorder_announcer.js";

type SortableEndDetail = {
  items?: unknown;
  oldDraggableIndex?: unknown;
  newDraggableIndex?: unknown;
};

/** A row moved from one index to another; the server renumbers the rows once it lands. */
type RowMove = { from: number; to: number; direction: ReorderDirection };

/**
 * The key-value field's client-side jobs. The reorder bridge turns the sortable control's new
 * order, or a screen-reader move button's, into the host's `reorder` action, and announces the
 * landed move, `key-value.js`'s `moveRow()` over a server-owned row list. The focus guard blurs
 * whatever is focused before a click inside the table reaches a button: WebKit does not focus a
 * clicked `<button>`, so without it the live morph's focused-element carry-over restores the
 * pre-request value of the row the user was typing in — into whichever row now sits at that index.
 */
export default class extends Controller<HTMLElement> {
  static targets = ["reorderStatus", "reorderDescription"];

  static values = {
    key: String,
    movedMessage: String,
  };

  declare readonly hasReorderStatusTarget: boolean;
  declare readonly reorderStatusTarget: HTMLElement;
  declare readonly reorderDescriptionTargets: HTMLElement[];
  declare readonly keyValue: string;
  declare readonly hasMovedMessageValue: boolean;
  declare readonly movedMessageValue: string;

  #component: Component | null = null;
  #announcer: ReorderAnnouncer | null = null;
  #generation = 0;

  async connect(): Promise<void> {
    this.element.addEventListener("mousedown", this.#blurBeforeButtonPress, true);

    const generation = ++this.#generation;

    if (!this.hasMovedMessageValue) {
      return;
    }

    let component: Component;

    try {
      component = await resolveLiveHost(this.element);
    } catch {
      // No live host answered, so no move can be announced: a move or a drag then resolves the
      // host itself, which throws.
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
    });
  }

  disconnect(): void {
    this.#generation++;

    this.element.removeEventListener("mousedown", this.#blurBeforeButtonPress, true);

    this.#announcer?.disconnect();
    this.#announcer = null;

    this.#component = null;
  }

  async reorder(event: Event): Promise<void> {
    const detail = (event as CustomEvent<SortableEndDetail>).detail;
    const items = detail?.items;

    if (!Array.isArray(items)) {
      return;
    }

    const { oldDraggableIndex: from, newDraggableIndex: to } = detail;

    await this.#reorder(
      items,
      typeof from === "number" && typeof to === "number" ? { from, to, direction: "drag" } : null,
    );
  }

  /** `key-value.js`'s `moveRow()` for a move button: the row one place up or down. */
  async move(event: Event): Promise<void> {
    const control = event.currentTarget;

    if (!(control instanceof HTMLElement) || control.matches(":disabled")) {
      return;
    }

    const move = readMoveControl(control);

    if (move === null) {
      return;
    }

    const count = this.reorderDescriptionTargets.length;
    const from = Number(move.item);
    const to = move.direction === "up" ? from - 1 : from + 1;

    if (!Number.isInteger(from) || to < 0 || to >= count) {
      return;
    }

    blurActiveElement();

    const items = Array.from({ length: count }, (_, index) => index);

    items.splice(to, 0, ...items.splice(from, 1));

    await this.#reorder(items, { from, to, direction: move.direction });
  }

  /**
   * Mounts the `reorder` action with the new order, through the announcer when the move is known.
   * The rows are keyed by index, so the moved row is named by the index it lands at, and the order
   * before the move is rebuilt in those landed indexes.
   */
  async #reorder(items: unknown[], move: RowMove | null): Promise<void> {
    const component = this.#component ?? (await resolveLiveHost(this.element));
    const request = () =>
      component.action("mountAction", {
        name: "reorder",
        arguments: { items },
        context: { schemaComponent: this.keyValue },
      });

    if (this.#announcer === null || move === null) {
      await request();

      return;
    }

    const landed = String(move.to);
    const indexes = items.map((_, index) => String(index));

    await this.#announcer.track(
      {
        item: landed,
        direction: move.direction,
        before: orderBeforeDrag(indexes, landed, move.from, move.to),
      },
      request,
    );
  }

  #blurBeforeButtonPress = (event: Event): void => {
    if (!(event.target instanceof Element) || event.target.closest("button") === null) {
      return;
    }

    blurActiveElement();
  };
}

function blurActiveElement(): void {
  const active = document.activeElement;

  if (active instanceof HTMLElement) {
    active.blur();
  }
}
