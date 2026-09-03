import { Controller } from "@hotwired/stimulus";
import { resolveLiveHost } from "../utils/live_host.js";

type SortableEndDetail = { items?: unknown };

/**
 * The key-value field's two client-side jobs. The drag-and-drop bridge turns the sortable
 * control's new order into the host's `reorder` action, as the repeater's does. The focus guard
 * blurs whatever is focused before a click inside the table reaches a button: WebKit does not focus
 * a clicked `<button>`, so without it the live morph's focused-element carry-over restores the
 * pre-request value of the row the user was typing in — into whichever row now sits at that index.
 */
export default class extends Controller<HTMLElement> {
  static values = {
    key: String,
  };

  declare readonly keyValue: string;

  connect(): void {
    this.element.addEventListener("mousedown", this.#blurBeforeButtonPress, true);
  }

  disconnect(): void {
    this.element.removeEventListener("mousedown", this.#blurBeforeButtonPress, true);
  }

  async reorder(event: Event): Promise<void> {
    const items = (event as CustomEvent<SortableEndDetail>).detail?.items;

    if (!Array.isArray(items)) {
      return;
    }

    const component = await resolveLiveHost(this.element);

    await component.action("mountAction", {
      name: "reorder",
      arguments: { items },
      context: { schemaComponent: this.keyValue },
    });
  }

  #blurBeforeButtonPress = (event: Event): void => {
    if (!(event.target instanceof Element) || event.target.closest("button") === null) {
      return;
    }

    const active = document.activeElement;

    if (active instanceof HTMLElement) {
      active.blur();
    }
  };
}
