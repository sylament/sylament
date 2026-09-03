import { Controller } from "@hotwired/stimulus";
class textarea_controller_default extends Controller {
  static targets = ["textarea"];
  static values = { initialHeight: Number };
  #observer;
  connect() {
    window.addEventListener("resize", this.#handleWindowResize);
    this.#setInitialHeight();
    this.#observer = new IntersectionObserver((entries) => {
      if (!entries.some((entry) => entry.isIntersecting)) {
        return;
      }
      this.#stopObserving();
      this.resize();
    });
    this.#observer.observe(this.element);
  }
  disconnect() {
    window.removeEventListener("resize", this.#handleWindowResize);
    this.#stopObserving();
  }
  resize() {
    const textarea = this.textareaTarget;
    if (textarea.scrollHeight <= 0) {
      return;
    }
    const previousHeight = textarea.style.height;
    textarea.style.height = "0px";
    const contentHeight = textarea.scrollHeight;
    textarea.style.height = previousHeight;
    const rootFontSize = Number.parseFloat(getComputedStyle(document.documentElement).fontSize);
    const newHeight = `${Math.max(contentHeight, this.initialHeightValue * rootFontSize)}px`;
    if (this.element.style.height === newHeight) {
      return;
    }
    this.element.style.height = newHeight;
  }
  #handleWindowResize = () => {
    this.resize();
  };
  #setInitialHeight() {
    if (this.textareaTarget.scrollHeight <= 0) {
      return;
    }
    this.element.style.height = `${this.initialHeightValue}rem`;
  }
  #stopObserving() {
    this.#observer?.disconnect();
    this.#observer = void 0;
  }
}
export {
  textarea_controller_default as default
};
//# sourceMappingURL=textarea_controller.js.map
