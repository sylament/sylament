import { Controller } from "@hotwired/stimulus";
class modal_controller_default extends Controller {
  static values = {
    id: String,
    open: Boolean,
    returnsFocus: { type: Boolean, default: true }
  };
  #opener = null;
  /**
   * Server-driven open state, and the only path that works for a modal rendered by the
   * same response that asks it to open. A `sylament-open-modal` event dispatched then is
   * lost: the live controller morphs the DOM and dispatches synchronously, while Stimulus
   * binds listeners from a MutationObserver callback, so this element is not listening
   * yet. Stimulus invokes this callback on connect and on every later attribute change,
   * whenever that happens, so it cannot be raced.
   */
  openValueChanged(isOpen) {
    if (isOpen) {
      this.#show();
      return;
    }
    if (this.element.open) {
      this.element.close();
    }
  }
  open(event) {
    if (this.#matches(event)) {
      this.#show();
    }
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
  #show() {
    if (this.element.open) {
      return;
    }
    this.#opener = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    this.element.showModal();
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
