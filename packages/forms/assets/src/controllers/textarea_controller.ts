import { Controller } from "@hotwired/stimulus";

/**
 * Grows the sizer around an autosizing textarea to the height of its content.
 *
 * The measurement writes the sizer, never the control: the control is `height: 100%`
 * of the sizer, so writing its own height would fight the rule that lets it fill.
 */
export default class extends Controller<HTMLElement> {
  static targets = ["textarea"];
  static values = { initialHeight: Number };

  declare readonly textareaTarget: HTMLTextAreaElement;
  declare readonly initialHeightValue: number;

  #textarea: HTMLTextAreaElement | undefined;
  #observer: IntersectionObserver | undefined;

  connect(): void {
    this.#textarea = this.textareaTarget;
    this.#textarea.addEventListener("input", this.#onResize);
    window.addEventListener("resize", this.#onResize);

    this.#setInitialHeight();

    // A textarea that connects inside a hidden container measures zero, so the first
    // resize waits for the element to be on screen rather than running on connect.
    this.#observer = new IntersectionObserver((entries) => {
      if (!entries.some((entry) => entry.isIntersecting)) {
        return;
      }

      this.#stopObserving();
      this.#resize();
    });
    this.#observer.observe(this.element);
  }

  disconnect(): void {
    this.#textarea?.removeEventListener("input", this.#onResize);
    this.#textarea = undefined;
    window.removeEventListener("resize", this.#onResize);
    this.#stopObserving();
  }

  #onResize = (): void => {
    this.#resize();
  };

  #setInitialHeight(): void {
    if (this.textareaTarget.scrollHeight <= 0) {
      return;
    }

    this.element.style.height = `${this.initialHeightValue}rem`;
  }

  #resize(): void {
    const textarea = this.textareaTarget;

    if (textarea.scrollHeight <= 0) {
      return;
    }

    // The control is measured at zero height so `scrollHeight` reports the content
    // rather than the box it currently sits in, then put back before the browser paints.
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

  #stopObserving(): void {
    this.#observer?.disconnect();
    this.#observer = undefined;
  }
}
