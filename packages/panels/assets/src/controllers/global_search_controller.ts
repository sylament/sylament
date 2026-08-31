import { Controller } from "@hotwired/stimulus";

const APPLE_PLATFORM = /Mac|iPhone|iPad|iPod/;
const OPEN_MODAL_SELECTOR = 'dialog:modal, [aria-modal="true"]';
const FOCUSABLE_RESULT_SELECTOR = "a[href], button:not([disabled])";

type ParsedBinding = {
  alt: boolean;
  ctrl: boolean;
  meta: boolean;
  shift: boolean;
  key: string;
};

const parseBinding = (binding: string): ParsedBinding => {
  const isApple = APPLE_PLATFORM.test(navigator.platform);
  const parsed: ParsedBinding = { alt: false, ctrl: false, meta: false, shift: false, key: "" };

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

export default class extends Controller<HTMLElement> {
  static targets = ["input", "resultsPanel"];

  static values = {
    keyBindings: { type: Array, default: [] },
  };

  declare readonly inputTarget: HTMLInputElement;
  declare readonly resultsPanelTarget: HTMLElement;
  declare readonly hasResultsPanelTarget: boolean;
  declare readonly keyBindingsValue: string[];

  #bindings: ParsedBinding[] = [];

  connect(): void {
    this.#bindings = this.keyBindingsValue.map(parseBinding);
    document.addEventListener("keydown", this.#onKeydown);
    document.addEventListener("pointerdown", this.#onPointerDown);
  }

  disconnect(): void {
    document.removeEventListener("keydown", this.#onKeydown);
    document.removeEventListener("pointerdown", this.#onPointerDown);
  }

  close(): void {
    if (this.hasResultsPanelTarget) {
      this.resultsPanelTarget.hidden = true;
    }
  }

  focusFirstResult(event: KeyboardEvent): void {
    const [first] = this.#focusables();

    if (first) {
      event.preventDefault();
      event.stopPropagation();
      first.focus();
    }
  }

  navigate(event: KeyboardEvent): void {
    if (event.key !== "ArrowDown" && event.key !== "ArrowUp") {
      return;
    }

    const focusables = this.#focusables();

    if (focusables.length === 0) {
      return;
    }

    event.preventDefault();

    const index = focusables.indexOf(document.activeElement as HTMLElement);
    const delta = event.key === "ArrowDown" ? 1 : -1;
    const next = focusables[(index + delta + focusables.length) % focusables.length];

    next?.focus();
  }

  #onKeydown = (event: KeyboardEvent): void => {
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

  #onPointerDown = (event: PointerEvent): void => {
    if (event.target instanceof Node && !this.element.contains(event.target)) {
      this.close();
    }
  };

  #focusables(): HTMLElement[] {
    if (!this.hasResultsPanelTarget || this.resultsPanelTarget.hidden) {
      return [];
    }

    return [...this.resultsPanelTarget.querySelectorAll<HTMLElement>(FOCUSABLE_RESULT_SELECTOR)];
  }

  /**
   * Stimulus' own key filters demand an exact modifier match, so a single cross-platform
   * `mod+k` binding cannot be expressed as one `keydown.k` descriptor — the document
   * listener tests the modifiers itself instead.
   */
  #matchesBinding(event: KeyboardEvent): boolean {
    return this.#bindings.some(
      (binding) =>
        event.altKey === binding.alt &&
        event.ctrlKey === binding.ctrl &&
        event.metaKey === binding.meta &&
        event.shiftKey === binding.shift &&
        event.key.toLowerCase() === binding.key,
    );
  }

  #blockedByOpenModal(): boolean {
    const modal = [...document.querySelectorAll<HTMLElement>(OPEN_MODAL_SELECTOR)].find(
      (element) => getComputedStyle(element).display !== "none",
    );

    return modal !== undefined && !modal.contains(this.element);
  }
}
