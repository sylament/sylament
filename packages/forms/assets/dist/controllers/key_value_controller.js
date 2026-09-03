import { Controller } from "@hotwired/stimulus";
import { resolveLiveHost } from "../utils/live_host.js";
class key_value_controller_default extends Controller {
  static values = {
    key: String
  };
  connect() {
    this.element.addEventListener("mousedown", this.#blurBeforeButtonPress, true);
  }
  disconnect() {
    this.element.removeEventListener("mousedown", this.#blurBeforeButtonPress, true);
  }
  async reorder(event) {
    const items = event.detail?.items;
    if (!Array.isArray(items)) {
      return;
    }
    const component = await resolveLiveHost(this.element);
    await component.action("mountAction", {
      name: "reorder",
      arguments: { items },
      context: { schemaComponent: this.keyValue }
    });
  }
  #blurBeforeButtonPress = (event) => {
    if (!(event.target instanceof Element) || event.target.closest("button") === null) {
      return;
    }
    const active = document.activeElement;
    if (active instanceof HTMLElement) {
      active.blur();
    }
  };
}
export {
  key_value_controller_default as default
};
//# sourceMappingURL=key_value_controller.js.map
