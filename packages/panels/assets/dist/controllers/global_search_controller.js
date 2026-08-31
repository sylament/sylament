import { Controller } from "@hotwired/stimulus";
const APPLE_PLATFORM = /Mac|iPhone|iPad|iPod/;
const OPEN_MODAL_SELECTOR = 'dialog:modal, [aria-modal="true"]';
const FOCUSABLE_RESULT_SELECTOR = "a[href], button:not([disabled])";
const parseBinding = (binding) => {
  const isApple = APPLE_PLATFORM.test(navigator.platform);
  const parsed = { alt: false, ctrl: false, meta: false, shift: false, key: "" };
  for (const part of binding.toLowerCase().split("+")) {
    switch (part) {
      case "mod":
        parsed[isApple ? "meta" : "ctrl"] = true;
        break;
      case "command":
      case "meta":
        parsed.meta = true;
        break;
      case "ctrl":
      case "control":
        parsed.ctrl = true;
        break;
      case "alt":
      case "option":
        parsed.alt = true;
        break;
      case "shift":
        parsed.shift = true;
        break;
      default:
        parsed.key = part;
    }
  }
  return parsed;
};
class global_search_controller_default extends Controller {
  static targets = ["input", "resultsPanel"];
  static values = {
    keyBindings: { type: Array, default: [] }
  };
  #bindings = [];
  connect() {
    this.#bindings = this.keyBindingsValue.map(parseBinding);
    document.addEventListener("keydown", this.#onKeydown);
    document.addEventListener("pointerdown", this.#onPointerDown);
  }
  disconnect() {
    document.removeEventListener("keydown", this.#onKeydown);
    document.removeEventListener("pointerdown", this.#onPointerDown);
  }
  close() {
    if (this.hasResultsPanelTarget) {
      this.resultsPanelTarget.hidden = true;
    }
  }
  focusFirstResult(event) {
    const [first] = this.#focusables();
    if (first) {
      event.preventDefault();
      event.stopPropagation();
      first.focus();
    }
  }
  navigate(event) {
    if (event.key !== "ArrowDown" && event.key !== "ArrowUp") {
      return;
    }
    const focusables = this.#focusables();
    if (focusables.length === 0) {
      return;
    }
    event.preventDefault();
    const index = focusables.indexOf(document.activeElement);
    const delta = event.key === "ArrowDown" ? 1 : -1;
    const next = focusables[(index + delta + focusables.length) % focusables.length];
    next?.focus();
  }
  #onKeydown = (event) => {
    if (event.key === "Escape") {
      this.close();
      return;
    }
    if (!this.#matchesBinding(event) || this.#blockedByOpenModal()) {
      return;
    }
    event.preventDefault();
    this.inputTarget.focus();
  };
  #onPointerDown = (event) => {
    if (event.target instanceof Node && !this.element.contains(event.target)) {
      this.close();
    }
  };
  #focusables() {
    if (!this.hasResultsPanelTarget || this.resultsPanelTarget.hidden) {
      return [];
    }
    return [...this.resultsPanelTarget.querySelectorAll(FOCUSABLE_RESULT_SELECTOR)];
  }
  /**
   * Stimulus' own key filters demand an exact modifier match, so a single cross-platform
   * `mod+k` binding cannot be expressed as one `keydown.k` descriptor — the document
   * listener tests the modifiers itself instead.
   */
  #matchesBinding(event) {
    return this.#bindings.some(
      (binding) => event.altKey === binding.alt && event.ctrlKey === binding.ctrl && event.metaKey === binding.meta && event.shiftKey === binding.shift && event.key.toLowerCase() === binding.key
    );
  }
  #blockedByOpenModal() {
    const modal = [...document.querySelectorAll(OPEN_MODAL_SELECTOR)].find(
      (element) => getComputedStyle(element).display !== "none"
    );
    return modal !== void 0 && !modal.contains(this.element);
  }
}
export {
  global_search_controller_default as default
};
//# sourceMappingURL=global_search_controller.js.map
