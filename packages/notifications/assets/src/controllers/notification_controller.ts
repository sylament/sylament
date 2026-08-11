import { Controller } from "@hotwired/stimulus";

const REMOVE_FALLBACK_MS = 400;

export default class extends Controller<HTMLElement> {
  static values = {
    duration: { type: Number, default: 0 },
  };

  declare readonly durationValue: number;

  #timer: number | undefined;

  connect(): void {
    if (this.durationValue > 0) {
      this.#timer = window.setTimeout(() => this.#expire(), this.durationValue);
    }
  }

  disconnect(): void {
    window.clearTimeout(this.#timer);
  }

  close(): void {
    const element = this.element;

    element.style.height = `${element.offsetHeight}px`;
    void element.offsetHeight;

    element.classList.add("sy-closing");
    element.style.height = "0";

    element.addEventListener("transitionend", (event) => {
      if (event.target === element) {
        element.remove();
      }
    });
    window.setTimeout(() => element.remove(), REMOVE_FALLBACK_MS);
  }

  handleActionClick(event: Event): void {
    const origin = event.target;
    if (!(origin instanceof Element)) {
      return;
    }

    const trigger = origin.closest("[data-sylament-action-close]");
    if (!(trigger instanceof HTMLElement) || !this.element.contains(trigger)) {
      return;
    }

    this.close();
  }

  #expire(): void {
    if (this.element.matches(":hover")) {
      this.element.addEventListener("mouseleave", () => this.close(), { once: true });

      return;
    }

    this.close();
  }
}
