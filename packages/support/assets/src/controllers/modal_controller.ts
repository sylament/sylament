import { Controller } from "@hotwired/stimulus";

export default class extends Controller<HTMLDialogElement> {
  static values = {
    id: String,
    open: Boolean,
    returnsFocus: { type: Boolean, default: true },
  };

  declare readonly idValue: string;
  declare readonly openValue: boolean;
  declare readonly returnsFocusValue: boolean;

  #opener: HTMLElement | null = null;

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

  restoreFocus(): void {
    if (this.returnsFocusValue) {
      this.#opener?.focus();
    }

    this.#opener = null;
  }

  #show(): void {
    if (this.element.open) {
      return;
    }

    this.#opener = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    this.element.showModal();
  }

  #matches(event: CustomEvent<{ id?: unknown }>): boolean {
    const eventId = event.detail?.id;
    const modalId = this.idValue !== "" ? this.idValue : this.element.id;

    return eventId != null && String(eventId) === modalId;
  }
}
