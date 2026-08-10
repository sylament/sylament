import { Controller } from "@hotwired/stimulus";

/** Appends server-rendered toast HTML delivered by the `sylament:notification` browser event. */
export default class extends Controller<HTMLElement> {
  receive(event: CustomEvent<{ html?: unknown }>): void {
    const html = event.detail?.html;

    if (typeof html !== "string") {
      return;
    }

    this.element.insertAdjacentHTML("beforeend", html);
  }
}
