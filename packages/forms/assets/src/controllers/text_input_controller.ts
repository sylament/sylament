import { Controller } from "@hotwired/stimulus";

const REVEALED_CLASS = "sy-revealed";

export default class extends Controller<HTMLElement> {
  static targets = ["input", "copyMessage"];

  declare readonly inputTarget: HTMLInputElement;
  declare readonly hasCopyMessageTarget: boolean;
  declare readonly copyMessageTarget: HTMLElement;

  #timer: ReturnType<typeof setTimeout> | undefined;

  disconnect(): void {
    clearTimeout(this.#timer);
  }

  reveal(): void {
    this.inputTarget.type = "text";
    this.element.classList.add(REVEALED_CLASS);
  }

  conceal(): void {
    this.inputTarget.type = "password";
    this.element.classList.remove(REVEALED_CLASS);
  }

  /**
   * The input's **live** value, not the state the server rendered — what the user sees is what
   * lands on the clipboard. `navigator.clipboard` is undefined outside a secure context, and a
   * denied permission rejects, so both arms leave the field untouched rather than throwing.
   */
  async copy({ params }: { params: { message?: string; duration?: number } }): Promise<void> {
    if (navigator.clipboard === undefined) {
      return;
    }

    try {
      await navigator.clipboard.writeText(this.inputTarget.value);
    } catch {
      return;
    }

    this.#announce(params.message ?? "Copied", params.duration ?? 2000);
  }

  #announce(message: string, duration: number): void {
    if (!this.hasCopyMessageTarget) {
      return;
    }

    this.copyMessageTarget.textContent = message;
    this.copyMessageTarget.hidden = false;

    clearTimeout(this.#timer);
    this.#timer = setTimeout(() => {
      if (this.hasCopyMessageTarget) {
        this.copyMessageTarget.hidden = true;
      }
    }, duration);
  }
}
