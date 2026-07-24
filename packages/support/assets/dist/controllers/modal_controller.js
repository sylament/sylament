import { Controller } from "@hotwired/stimulus";
class modal_controller_default extends Controller {
  static values = {
    id: String,
    returnsFocus: { type: Boolean, default: true }
  };
  #opener = null;
  open(event) {
    if (!this.#matches(event)) {
      return;
    }
    this.#opener = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    this.element.showModal();
  }
  close(event) {
    if (this.#matches(event)) {
      this.element.close();
    }
  }
  restoreFocus() {
    if (this.returnsFocusValue) {
      this.#opener?.focus();
    }
    this.#opener = null;
  }
  #matches(event) {
    const eventId = event.detail?.id;
    const modalId = this.idValue !== "" ? this.idValue : this.element.id;
    return eventId != null && String(eventId) === modalId;
  }
}
export {
  modal_controller_default as default
};
//# sourceMappingURL=modal_controller.js.map
