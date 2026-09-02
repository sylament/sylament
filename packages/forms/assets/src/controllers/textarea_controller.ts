import { Controller } from "@hotwired/stimulus";

/** Grows the sizer around an autosizing textarea to the height of the control's content. */
export default class extends Controller<HTMLElement> {
  static targets = ["textarea"];
  static values = { initialHeight: Number };

  declare readonly textareaTarget: HTMLTextAreaElement;
  declare readonly initialHeightValue: number;

  #observer: IntersectionObserver | undefined;

  connect(): void {
    window.addEventListener("resize", this.#handleWindowResize);

    this.#setInitialHeight();

    // A control that connects inside a hidden container measures zero, so the first
    // resize waits for the field to reach the screen rather than running on connect.
    this.#observer = new IntersectionObserver((entries) => {
      if (!entries.some((entry) => entry.isIntersecting)) {
        return;
      }

      this.#stopObserving();
      this.resize();
    });
    this.#observer.observe(this.element);
  }

  disconnect(): void {
    window.removeEventListener("resize", this.#handleWindowResize);
    this.#stopObserving();
  }

  resize(): void {
    const textarea = this.textareaTarget;

    if (textarea.scrollHeight <= 0) {
      return;
    }

    // Zeroed first so `scrollHeight` reports the content rather than the box the
    // control currently sits in, then put back before the browser paints.
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

  #handleWindowResize = (): void => {
    this.resize();
  };

  #setInitialHeight(): void {
    if (this.textareaTarget.scrollHeight <= 0) {
      return;
    }

    this.element.style.height = `${this.initialHeightValue}rem`;
  }

  #stopObserving(): void {
    this.#observer?.disconnect();
    this.#observer = undefined;
  }
}
