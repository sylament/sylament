import { Controller } from "@hotwired/stimulus";

export default class extends Controller<HTMLDialogElement> {
  static values = {
    id: String,
    open: Boolean,
    returnsFocus: { type: Boolean, default: true },
    closeByEscaping: { type: Boolean, default: true },
    closeByClickingAway: { type: Boolean, default: true },
    clickThrough: { type: Boolean, default: false },
  };

  declare readonly idValue: string;
  declare readonly openValue: boolean;
  declare readonly returnsFocusValue: boolean;
  declare readonly closeByEscapingValue: boolean;
  declare readonly closeByClickingAwayValue: boolean;
  declare readonly clickThroughValue: boolean;

  #opener: HTMLElement | null = null;

  #pointerDownOnBackdrop = false;

  #escapeListener: ((event: KeyboardEvent) => void) | null = null;

  connect(): void {
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
  openValueChanged(isOpen: boolean): void {
    if (isOpen) {
      this.#show();

      return;
    }

    if (this.element.open) {
      this.element.close();
    }
  }

  open(event: CustomEvent<{ id?: unknown }>): void {
    if (this.#matches(event)) {
      this.#show();
    }
  }

  close(event: CustomEvent<{ id?: unknown }>): void {
    if (this.#matches(event)) {
      this.element.close();
    }
  }

  dismiss(): void {
    this.element.close();
  }

  handleCancel(event: Event): void {
    if (!this.closeByEscapingValue) {
      event.preventDefault();
    }
  }

  trackPointerDown(event: PointerEvent): void {
    this.#pointerDownOnBackdrop = event.target === this.element;
  }

  /**
   * Closes on a backdrop click only when the press also began there: after a
   * text-selection drag that starts inside the window and releases outside it, the
   * `click` targets the common ancestor (this element), but the `pointerdown` did not.
   */
  closeOnClickAway(event: MouseEvent): void {
    if (
      this.closeByClickingAwayValue &&
      this.#pointerDownOnBackdrop &&
      event.target === this.element
    ) {
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
  notifyDismissed(): void {
    if (this.element.open) {
      return;
    }

    this.#teardownEscapeListener();

    if (this.openValue) {
      this.dispatch("dismissed");
    }
  }

  restoreFocus(): void {
    if (this.element.open) {
      return;
    }

    if (this.returnsFocusValue) {
      this.#opener?.focus();
    }

    this.#opener = null;
  }

  disconnect(): void {
    this.#teardownEscapeListener();
  }

  #show(): void {
    this.#captureOpener();

    if (this.element.open) {
      if (this.clickThroughValue) {
        // A parse-time `open` attribute already leaves a click-through dialog in exactly
        // the non-modal state show() would produce, so there is nothing to re-show — only
        // the Escape listener (never persisted in markup) is still missing.
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
   * Never overwrites: a frame that stays open across a morph keeps the trigger it was
   * opened from, rather than picking up whatever a child dialog left focused. Cleared
   * in restoreFocus() once the dialog actually closes, so the next open captures fresh.
   */
  #captureOpener(): void {
    if (this.#opener === null) {
      this.#opener = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    }
  }

  /**
   * A dialog whose `open` attribute arrived with the markup — a freshly morphed-in
   * stack frame — is open but not modal: no top layer, no focus trap, no `cancel`
   * event. Removing the attribute (rather than calling close()) runs the dialog
   * cleanup steps without firing `close`, so re-showing it modally emits no spurious
   * dismissal and never paints a closed frame.
   */
  #normalizeModality(): void {
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
  #setupEscapeListener(): void {
    if (!this.closeByEscapingValue || this.#escapeListener !== null) {
      return;
    }

    this.#escapeListener = (event: KeyboardEvent): void => {
      if (event.key === "Escape") {
        this.element.close();
      }
    };
    document.addEventListener("keydown", this.#escapeListener);
  }

  #teardownEscapeListener(): void {
    if (this.#escapeListener !== null) {
      document.removeEventListener("keydown", this.#escapeListener);
      this.#escapeListener = null;
    }
  }

  #matches(event: CustomEvent<{ id?: unknown }>): boolean {
    const eventId = event.detail?.id;
    const modalId = this.idValue !== "" ? this.idValue : this.element.id;

    return eventId != null && String(eventId) === modalId;
  }
}
