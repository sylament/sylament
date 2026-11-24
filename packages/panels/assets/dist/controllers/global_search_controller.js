import { Controller } from "@hotwired/stimulus";
import { resolveLiveHost } from "../utils/live_host.js";
const APPLE_PLATFORM = /Mac|iPhone|iPad|iPod/;
const OPEN_MODAL_SELECTOR = 'dialog:modal, [aria-modal="true"]';
const FOCUSABLE_RESULT_SELECTOR = "a[href], button:not([disabled])";
const LOADING_DELAY = 350;
const FAILURE_TIMEOUT = 3e4;
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
  static targets = ["input", "resultsPanel", "status"];
  static values = {
    keyBindings: { type: Array, default: [] },
    loadingMessage: String,
    failureMessage: String
  };
  #bindings = [];
  #unsubscribe = null;
  #generation = 0;
  /** Bumped by every keystroke, so a render answers the search only when its request carried the latest one. */
  #revision = 0;
  #requestRevision = null;
  #dismissed = false;
  #loadingTimeout;
  #failureTimeout;
  async connect() {
    this.#bindings = this.keyBindingsValue.map(parseBinding);
    document.addEventListener("keydown", this.#onKeydown);
    document.addEventListener("pointerdown", this.#onPointerDown);
    const generation = ++this.#generation;
    const component = await resolveLiveHost(this.element);
    if (generation !== this.#generation) {
      return;
    }
    this.#subscribe(component);
  }
  disconnect() {
    this.#generation++;
    this.#unsubscribe?.();
    this.#unsubscribe = null;
    this.#clearTimeouts();
    document.removeEventListener("keydown", this.#onKeydown);
    document.removeEventListener("pointerdown", this.#onPointerDown);
  }
  close() {
    this.#dismissed = true;
    this.#clearTimeouts();
    this.#announce("");
    if (this.hasResultsPanelTarget) {
      this.resultsPanelTarget.hidden = true;
    }
  }
  inputChanged() {
    this.#revision++;
    this.#dismissed = false;
    window.clearTimeout(this.#loadingTimeout);
    this.#announce("");
    if (!this.#hasTerm()) {
      window.clearTimeout(this.#failureTimeout);
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
  #subscribe(component) {
    const onRequestStarted = () => {
      this.#requestRevision = this.#revision;
      this.#clearTimeouts();
      if (this.#dismissed || !this.#hasTerm()) {
        return;
      }
      this.#loadingTimeout = window.setTimeout(() => {
        if (!this.#dismissed) {
          this.#announce(this.loadingMessageValue);
        }
      }, LOADING_DELAY);
      this.#failureTimeout = window.setTimeout(() => this.#fail(), FAILURE_TIMEOUT);
    };
    const onRenderFinished = () => {
      if (this.hasResultsPanelTarget) {
        this.resultsPanelTarget.hidden = this.#dismissed;
      }
      this.#clearTimeouts();
      if (this.#requestRevision !== this.#revision) {
        return;
      }
      const isOpen = this.hasResultsPanelTarget && !this.resultsPanelTarget.hidden;
      this.#announce(isOpen ? this.resultsPanelTarget.dataset.message ?? "" : "");
    };
    const onResponseError = () => {
      this.#clearTimeouts();
      if (this.#requestRevision === this.#revision && this.#hasTerm()) {
        this.#fail();
      }
    };
    component.on("request:started", onRequestStarted);
    component.on("render:finished", onRenderFinished);
    component.on("response:error", onResponseError);
    this.#unsubscribe = () => {
      component.off("request:started", onRequestStarted);
      component.off("render:finished", onRenderFinished);
      component.off("response:error", onResponseError);
    };
  }
  #fail() {
    if (this.#dismissed) {
      return;
    }
    this.#clearTimeouts();
    this.#announce(this.failureMessageValue);
  }
  #announce(message) {
    if (this.statusTarget.textContent !== message) {
      this.statusTarget.textContent = message;
    }
  }
  #hasTerm() {
    return this.inputTarget.value.trim() !== "";
  }
  #clearTimeouts() {
    window.clearTimeout(this.#loadingTimeout);
    window.clearTimeout(this.#failureTimeout);
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
