import { Controller } from "@hotwired/stimulus";
import { getComponent } from "@symfony/ux-live-component";

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

  async handleActionClick(event: Event): Promise<void> {
    const origin = event.target;
    if (!(origin instanceof Element)) {
      return;
    }

    const trigger = origin.closest("[data-sylament-action-event], [data-sylament-action-close]");
    if (!(trigger instanceof HTMLElement) || !this.element.contains(trigger)) {
      return;
    }

    const name = trigger.dataset.sylamentActionEvent;
    if (name !== undefined && name !== "") {
      await this.#emitToLiveComponent(trigger, name);
    }

    if (trigger.dataset.sylamentActionClose !== undefined) {
      this.close();
    }
  }

  #expire(): void {
    if (this.element.matches(":hover")) {
      this.element.addEventListener("mouseleave", () => this.close(), { once: true });

      return;
    }

    this.close();
  }

  async #emitToLiveComponent(trigger: HTMLElement, name: string): Promise<void> {
    const host = document.querySelector('[data-controller~="live"]');
    if (!(host instanceof HTMLElement)) {
      return;
    }

    const raw = trigger.dataset.sylamentActionEventData;
    const data = raw === undefined ? {} : (JSON.parse(raw) as Record<string, unknown>);
    const to = trigger.dataset.sylamentActionDispatchTo;

    try {
      const component = await getComponent(host);
      component.emit(name, data, to === undefined || to === "" ? null : to);
    } catch {
      // getComponent rejects for an element not yet registered (lazy/boot race);
      // a dispatch with no reachable live component is silent, matching Livewire.
    }
  }
}
