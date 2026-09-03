import { Controller } from "@hotwired/stimulus";

const LIVE_REGION_CLASS = "sy-copyable-live-region";

function liveRegion(): HTMLElement {
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

export default class extends Controller<HTMLElement> {
  static values = {
    text: String,
    message: { type: String, default: "Copied" },
    duration: { type: Number, default: 2000 },
  };

  declare readonly textValue: string;
  declare readonly messageValue: string;
  declare readonly durationValue: number;

  #timer: number | undefined;

  connect(): void {
    // Never server-rendered: a `tabindex` inside a record `<a>` is invalid markup.
    this.element.setAttribute("tabindex", "0");
  }

  disconnect(): void {
    window.clearTimeout(this.#timer);
    this.#reset();
  }

  async copy(event: Event): Promise<void> {
    event.preventDefault();
    event.stopPropagation();

    if (!navigator.clipboard) {
      return;
    }

    try {
      await navigator.clipboard.writeText(this.textValue);
    } catch {
      // A denied permission or an unfocused document: nothing was written, so
      // nothing is confirmed.
      return;
    }

    this.#confirm();
  }

  #confirm(): void {
    this.element.setAttribute("data-copy-message", this.messageValue);
    this.element.setAttribute("data-copied", "");
    liveRegion().textContent = this.messageValue;

    window.clearTimeout(this.#timer);
    this.#timer = window.setTimeout(() => this.#reset(), this.durationValue);
  }

  #reset(): void {
    this.element.removeAttribute("data-copied");
    this.element.removeAttribute("data-copy-message");

    const region = document.querySelector(`.${LIVE_REGION_CLASS}`);

    if (region instanceof HTMLElement && region.textContent === this.messageValue) {
      region.textContent = "";
    }
  }
}
