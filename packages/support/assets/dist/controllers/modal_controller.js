import { Controller } from "@hotwired/stimulus";
class modal_controller_default extends Controller {
  static values = {
    id: String,
    open: Boolean,
    returnsFocus: { type: Boolean, default: true },
    closeByEscaping: { type: Boolean, default: true },
    closeByClickingAway: { type: Boolean, default: true },
    clickThrough: { type: Boolean, default: false }
  };
  #opener = null;
  #pointerDownOnBackdrop = false;
  #escapeListener = null;
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
  dismiss() {
    this.element.close();
  }
  handleCancel(event) {
    if (!this.closeByEscapingValue) {
      event.preventDefault();
    }
  }
  trackPointerDown(event) {
    this.#pointerDownOnBackdrop = event.target === this.element;
  }
  /**
   * Closes on a backdrop click only when the press also began there: after a
   * text-selection drag that starts inside the window and releases outside it, the
   * `click` targets the common ancestor (this element), but the `pointerdown` did not.
   */
  closeOnClickAway(event) {
    if (this.closeByClickingAwayValue && this.#pointerDownOnBackdrop && event.target === this.element) {
      this.element.close();
    }
    this.#pointerDownOnBackdrop = false;
  }
  /**
   * Emitted only for client-initiated closes (ESC, click-away, close button): a
   * server-driven close flips the open value to false before calling close(), so the
   * value still being true here means the server does not know yet.
   */
  notifyDismissed() {
    this.#teardownEscapeListener();
    if (this.openValue) {
      this.dispatch("dismissed");
    }
  }
  restoreFocus() {
    if (this.returnsFocusValue) {
      this.#opener?.focus();
    }
    this.#opener = null;
  }
  disconnect() {
    this.#teardownEscapeListener();
  }
  #show() {
    if (this.element.open) {
      return;
    }
    this.#opener = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    if (this.clickThroughValue) {
      this.element.show();
      this.#setupEscapeListener();
      return;
    }
    this.element.showModal();
  }
  /**
   * A non-modal dialog (click-through uses show()) receives no `cancel` close request,
   * so Escape is reimplemented at the document level while it is open.
   */
  #setupEscapeListener() {
    if (!this.closeByEscapingValue || this.#escapeListener !== null) {
      return;
    }
    this.#escapeListener = (event) => {
      if (event.key === "Escape") {
        this.element.close();
      }
    };
    document.addEventListener("keydown", this.#escapeListener);
  }
  #teardownEscapeListener() {
    if (this.#escapeListener !== null) {
      document.removeEventListener("keydown", this.#escapeListener);
      this.#escapeListener = null;
    }
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
