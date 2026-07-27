import { Controller } from "@hotwired/stimulus";

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
