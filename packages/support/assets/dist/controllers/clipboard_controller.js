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
    message: { type: String, default: "Copied" },
    duration: { type: Number, default: 2e3 }
  };
  #timer;
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
    if (!navigator.clipboard) {
      return;
    }
    try {
      await navigator.clipboard.writeText(this.textValue);
    } catch {
      return;
    }
    this.#confirm();
  }
  #confirm() {
    this.element.setAttribute("data-copy-message", this.messageValue);
    this.element.setAttribute("data-copied", "");
    liveRegion().textContent = this.messageValue;
    window.clearTimeout(this.#timer);
    this.#timer = window.setTimeout(() => this.#reset(), this.durationValue);
  }
  #reset() {
    this.element.removeAttribute("data-copied");
    this.element.removeAttribute("data-copy-message");
    const region = document.querySelector(`.${LIVE_REGION_CLASS}`);
    if (region instanceof HTMLElement && region.textContent === this.messageValue) {
      region.textContent = "";
    }
  }
}
export {
  clipboard_controller_default as default
};
//# sourceMappingURL=clipboard_controller.js.map
