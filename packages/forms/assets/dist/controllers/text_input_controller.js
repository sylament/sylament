import { Controller } from "@hotwired/stimulus";
const REVEALED_CLASS = "sy-revealed";
class text_input_controller_default extends Controller {
  static targets = ["input", "copyMessage"];
  #timer;
  disconnect() {
    clearTimeout(this.#timer);
  }
  reveal() {
    this.inputTarget.type = "text";
    this.element.classList.add(REVEALED_CLASS);
  }
  conceal() {
    this.inputTarget.type = "password";
    this.element.classList.remove(REVEALED_CLASS);
  }
  /**
   * The input's **live** value, not the state the server rendered — what the user sees is what
   * lands on the clipboard. `navigator.clipboard` is undefined outside a secure context, and a
   * denied permission rejects, so both arms leave the field untouched rather than throwing.
   *
   * Stimulus JSON-parses every action param, so a message of `'null'` or `'2024'` arrives as
   * `null` or a number; both are coerced back to the string the action configured.
   */
  async copy({ params }) {
    if (navigator.clipboard === void 0) {
      return;
    }
    try {
      await navigator.clipboard.writeText(this.inputTarget.value);
    } catch {
      return;
    }
    this.#announce(
      params.message === void 0 ? "Copied" : String(params.message),
      typeof params.duration === "number" ? params.duration : 2e3
    );
  }
  /**
   * Only the text moves: a live region has to already be in the accessibility tree when its
   * content changes, so the span is never hidden — CSS hides it while it is `:empty`.
   */
  #announce(message, duration) {
    if (!this.hasCopyMessageTarget) {
      return;
    }
    this.copyMessageTarget.textContent = message;
    clearTimeout(this.#timer);
    this.#timer = setTimeout(() => {
      if (this.hasCopyMessageTarget) {
        this.copyMessageTarget.textContent = "";
      }
    }, duration);
  }
}
export {
  text_input_controller_default as default
};
//# sourceMappingURL=text_input_controller.js.map
