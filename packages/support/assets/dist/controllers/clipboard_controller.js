import { Controller } from "@hotwired/stimulus";
const LIVE_REGION_CLASS = "sy-copyable-live-region";
function liveRegion() {
  const existing = document.querySelector(`.${LIVE_REGION_CLASS}`);
  if (existing instanceof HTMLElement) {
    return existing;
  }
  const region = document.createElement("div");
  region.className = LIVE_REGION_CLASS;
  region.setAttribute("aria-live", "polite");
  document.body.appendChild(region);
  return region;
}
class clipboard_controller_default extends Controller {
  static values = {
    text: String,
    message: String,
    failureMessage: String,
    duration: { type: Number, default: 2e3 }
  };
  #timer;
  #shown;
  connect() {
    this.element.setAttribute("tabindex", "0");
  }
  disconnect() {
    window.clearTimeout(this.#timer);
    this.#reset();
  }
  async copy(event) {
    event.preventDefault();
    event.stopPropagation();
    try {
      await navigator.clipboard.writeText(this.textValue);
    } catch {
      this.#show(this.failureMessageValue);
      return;
    }
    this.#show(this.messageValue);
  }
  #show(message) {
    this.#shown = message;
    this.element.setAttribute("data-copy-message", message);
    this.element.setAttribute("data-copied", "");
    liveRegion().textContent = message;
    window.clearTimeout(this.#timer);
    this.#timer = window.setTimeout(() => this.#reset(), this.durationValue);
  }
  #reset() {
    this.element.removeAttribute("data-copied");
    this.element.removeAttribute("data-copy-message");
    const region = document.querySelector(`.${LIVE_REGION_CLASS}`);
    if (region instanceof HTMLElement && region.textContent === this.#shown) {
      region.textContent = "";
    }
    this.#shown = void 0;
  }
}
export {
  clipboard_controller_default as default
};
//# sourceMappingURL=clipboard_controller.js.map
