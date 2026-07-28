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
  #openAttributeObserver = null;
  connect() {
    this.#observeOpenAttribute();
    this.#normalizeModality();
  }
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
      return;
    }
    this.#teardownEscapeListener();
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
   * Emitted only for client-initiated closes (ESC, click-away, close button, cancel):
   * a server-driven close flips the open value to false before calling close(), so the
   * value still being true here means the server does not know yet. The `close` event
   * arrives from a queued task, so a dialog re-shown in the meantime is not a dismissal.
   */
  notifyDismissed() {
    if (this.element.open) {
      return;
    }
    this.#teardownEscapeListener();
    if (this.openValue) {
      this.dispatch("dismissed");
    }
  }
  restoreFocus() {
    if (this.element.open) {
      return;
    }
    this.#restoreOpenerFocus();
  }
  /**
   * The element left the DOM: the frame is gone rather than hidden, so whatever the
   * server-driven close skipped is settled here. A dialog removed while still open
   * never fires `close`, and the browser drops focus to the body, so this is the only
   * place a fully server-closed stack can hand focus back to its opener.
   */
  disconnect() {
    this.#openAttributeObserver?.disconnect();
    this.#openAttributeObserver = null;
    this.#teardownEscapeListener();
    this.#restoreOpenerFocus();
  }
  #show() {
    this.#captureOpener();
    if (this.element.open) {
      if (this.clickThroughValue) {
        this.#setupEscapeListener();
        return;
      }
      this.#normalizeModality();
      return;
    }
    if (this.clickThroughValue) {
      this.element.show();
      this.#setupEscapeListener();
      return;
    }
    this.element.showModal();
  }
  /**
   * Never overwrites: a frame that stays open across a morph — or is hidden under a
   * child and later re-opened — keeps the trigger it was opened from, rather than
   * picking up whatever a child dialog left focused. Cleared in #restoreOpenerFocus()
   * once the frame really closes or leaves the DOM, so the next open captures fresh.
   */
  #captureOpener() {
    if (this.#opener === null) {
      this.#opener = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    }
  }
  #restoreOpenerFocus() {
    if (this.returnsFocusValue) {
      this.#opener?.focus();
    }
    this.#opener = null;
  }
  /**
   * The `open` attribute can change without any Stimulus callback firing: after a
   * client-side dismissal the open value is still true in the DOM, so a live re-render
   * landing before the `dismissed` round-trip completes morphs the attribute back onto
   * this same element — no connect(), no openValueChanged(), and the dialog reappears
   * open but not modal (no top layer, no backdrop, no focus trap, no `cancel`, and a
   * dead closeOnClickAway — there is no backdrop for its target check to match). An
   * attribute observer is the only hook that fires for that. It converges rather than
   * loops: the removeAttribute()/showModal() pair that normalizing performs queues one
   * more callback, and that one finds the dialog already `:modal` and returns.
   */
  #observeOpenAttribute() {
    if (this.#openAttributeObserver !== null) {
      return;
    }
    this.#openAttributeObserver = new MutationObserver(() => {
      this.#normalizeModality();
    });
    this.#openAttributeObserver.observe(this.element, {
      attributes: true,
      attributeFilter: ["open"]
    });
  }
  /**
   * A dialog whose `open` attribute arrived with the markup — a freshly morphed-in
   * stack frame — is open but not modal: no top layer, no focus trap, no `cancel`
   * event (the close watcher the setup steps install is only enabled while `:modal`
   * is true, so ESC is a no-op until this promotes it). Removing the attribute
   * (rather than calling close()) runs the dialog cleanup steps without firing
   * `close`, so re-showing it modally emits no spurious dismissal and never paints
   * a closed frame.
   *
   * Those cleanup steps — here, and whenever a parent is hidden by a morph while a
   * child is on top — only drop the dialog from the open-dialogs list and destroy
   * its close watcher; they do not clear the `is modal` flag or remove it from the
   * top layer (only close()'s algorithm does that). A hidden parent frame therefore
   * sits at `:modal` true, still top-layered, just unrendered by the UA stylesheet's
   * `dialog:not([open]) { display: none }`. That is what makes re-opening it cheap:
   * this method sees `element.open` and `matches(":modal")` both true and returns
   * early, with no removeAttribute()/showModal() round trip, because the frame was
   * never de-modalised, only un-rendered, and the setup steps re-run when `open` is
   * re-added restore its close watcher on their own.
   */
  #normalizeModality() {
    if (!this.element.open || this.clickThroughValue || this.element.matches(":modal")) {
      return;
    }
    this.element.removeAttribute("open");
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
