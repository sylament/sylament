import { Controller } from "@hotwired/stimulus";
import { resolveLiveHost } from "../utils/live_host.js";
import {
  orderBeforeDrag,
  ReorderAnnouncer,
  readMoveControl
} from "../utils/reorder_announcer.js";
class key_value_controller_default extends Controller {
  static targets = ["reorderStatus", "reorderDescription"];
  static values = {
    key: String,
    movedMessage: String
  };
  #component = null;
  #announcer = null;
  #generation = 0;
  async connect() {
    this.element.addEventListener("mousedown", this.#blurBeforeButtonPress, true);
    const generation = ++this.#generation;
    if (!this.hasMovedMessageValue) {
      return;
    }
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
      movedMessage: () => this.movedMessageValue
    });
  }
  disconnect() {
    this.#generation++;
    this.element.removeEventListener("mousedown", this.#blurBeforeButtonPress, true);
    this.#announcer?.disconnect();
    this.#announcer = null;
    this.#component = null;
  }
  async reorder(event) {
    const detail = event.detail;
    const items = detail?.items;
    if (!Array.isArray(items)) {
      return;
    }
    const { oldDraggableIndex: from, newDraggableIndex: to } = detail;
    await this.#reorder(
      items,
      typeof from === "number" && typeof to === "number" ? { from, to, direction: "drag" } : null
    );
  }
  /** `key-value.js`'s `moveRow()` for a move button: the row one place up or down. */
  async move(event) {
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
  async #reorder(items, move) {
    const component = this.#component ?? await resolveLiveHost(this.element);
    const request = () => component.action("mountAction", {
      name: "reorder",
      arguments: { items },
      context: { schemaComponent: this.keyValue }
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
        before: orderBeforeDrag(indexes, landed, move.from, move.to)
      },
      request
    );
  }
  #blurBeforeButtonPress = (event) => {
    if (!(event.target instanceof Element) || event.target.closest("button") === null) {
      return;
    }
    blurActiveElement();
  };
}
function blurActiveElement() {
  const active = document.activeElement;
  if (active instanceof HTMLElement) {
    active.blur();
  }
}
export {
  key_value_controller_default as default
};
//# sourceMappingURL=key_value_controller.js.map
