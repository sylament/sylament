import { Controller } from "@hotwired/stimulus";
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
  #expire() {
    if (this.element.matches(":hover")) {
      this.element.addEventListener("mouseleave", () => this.close(), { once: true });
      return;
    }
    this.close();
  }
}
export {
  notification_controller_default as default
};
//# sourceMappingURL=notification_controller.js.map
