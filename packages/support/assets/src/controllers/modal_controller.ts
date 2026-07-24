import { Controller } from "@hotwired/stimulus";

/**
 * Drives a native `<dialog>` modal. Id-matched window events open and close it,
 * and focus returns to the opener on close. The platform supplies focus-trap,
 * ESC, top-layer stacking and `::backdrop` — this controller only bridges the
 * events to `showModal()` / `close()`.
 */
export default class extends Controller<HTMLDialogElement> {
  static values = {
    id: String,
    returnsFocus: { type: Boolean, default: true },
  };

  declare readonly idValue: string;
  declare readonly returnsFocusValue: boolean;

  #opener: HTMLElement | null = null;

  open(event: CustomEvent<{ id?: unknown }>): void {
    if (!this.#matches(event)) {
      return;
    }

    this.#opener = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    this.element.showModal();
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

  #matches(event: CustomEvent<{ id?: unknown }>): boolean {
    const eventId = event.detail?.id;
    const modalId = this.idValue !== "" ? this.idValue : this.element.id;

    return eventId != null && String(eventId) === modalId;
  }
}
