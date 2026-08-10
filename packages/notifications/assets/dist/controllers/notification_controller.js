import { Controller } from "@hotwired/stimulus";
import { getComponent } from "@symfony/ux-live-component";
const REMOVE_FALLBACK_MS = 400;
class notification_controller_default extends Controller {
  static values = {
    duration: { type: Number, default: 0 }
  };
  #timer;
  connect() {
    if (this.durationValue > 0) {
      this.#timer = window.setTimeout(() => this.#expire(), this.durationValue);
    }
  }
  disconnect() {
    window.clearTimeout(this.#timer);
  }
  close() {
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
  async handleActionClick(event) {
    const origin = event.target;
    if (!(origin instanceof Element)) {
      return;
    }
    const trigger = origin.closest("[data-sylament-action-event], [data-sylament-action-close]");
    if (!(trigger instanceof HTMLElement) || !this.element.contains(trigger)) {
      return;
    }
    const name = trigger.dataset.sylamentActionEvent;
    if (name !== void 0 && name !== "") {
      await this.#emitToLiveComponent(trigger, name);
    }
    if (trigger.dataset.sylamentActionClose !== void 0) {
      this.close();
    }
  }
  #expire() {
    if (this.element.matches(":hover")) {
      this.element.addEventListener("mouseleave", () => this.close(), { once: true });
      return;
    }
    this.close();
  }
  async #emitToLiveComponent(trigger, name) {
    const host = document.querySelector('[data-controller~="live"]');
    if (!(host instanceof HTMLElement)) {
      return;
    }
    const raw = trigger.dataset.sylamentActionEventData;
    const data = raw === void 0 ? {} : JSON.parse(raw);
    const to = trigger.dataset.sylamentActionDispatchTo;
    try {
      const component = await getComponent(host);
      component.emit(name, data, to === void 0 || to === "" ? null : to);
    } catch {
    }
  }
}
export {
  notification_controller_default as default
};
//# sourceMappingURL=notification_controller.js.map
