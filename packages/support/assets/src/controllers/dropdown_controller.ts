import type { Placement } from "@floating-ui/dom";
import {
  autoUpdate,
  computePosition,
  flip as flipMiddleware,
  offset as offsetMiddleware,
  shift as shiftMiddleware,
} from "@floating-ui/dom";
import { Controller } from "@hotwired/stimulus";

export default class extends Controller<HTMLElement> {
  static targets = ["trigger", "panel"];

  static values = {
    placement: { type: String, default: "bottom-start" },
    offset: { type: Number, default: 8 },
    flip: { type: Boolean, default: true },
  };

  declare readonly triggerTarget: HTMLElement;
  declare readonly panelTarget: HTMLElement;
  declare readonly placementValue: string;
  declare readonly offsetValue: number;
  declare readonly flipValue: boolean;

  #stopPositioning: (() => void) | null = null;

  #onPointerDown = (event: PointerEvent): void => {
    if (event.target instanceof Node && !this.element.contains(event.target)) {
      this.close();
    }
  };

  #onKeyDown = (event: KeyboardEvent): void => {
    if (event.key === "Escape") {
      this.close();
      this.#focusableTrigger()?.focus();
    }
  };

  connect(): void {
    this.#focusableTrigger()?.setAttribute("aria-haspopup", "true");
    this.#syncExpanded(false);
  }

  disconnect(): void {
    this.close();
  }

  toggle(): void {
    if (this.panelTarget.hidden) {
      this.open();

      return;
    }

    this.close();
  }

  open(): void {
    if (!this.panelTarget.hidden) {
      return;
    }

    this.panelTarget.hidden = false;
    this.#syncExpanded(true);

    this.#stopPositioning = autoUpdate(this.triggerTarget, this.panelTarget, () => {
      void this.#position();
    });

    document.addEventListener("pointerdown", this.#onPointerDown);
    document.addEventListener("keydown", this.#onKeyDown);
  }

  close(): void {
    if (this.#stopPositioning === null) {
      return;
    }

    this.#stopPositioning?.();
    this.#stopPositioning = null;

    document.removeEventListener("pointerdown", this.#onPointerDown);
    document.removeEventListener("keydown", this.#onKeyDown);

    this.panelTarget.hidden = true;
    this.#syncExpanded(false);
  }

  async #position(): Promise<void> {
    const { x, y } = await computePosition(this.triggerTarget, this.panelTarget, {
      placement: this.placementValue as Placement,
      middleware: [
        offsetMiddleware(this.offsetValue),
        ...(this.flipValue ? [flipMiddleware()] : []),
        shiftMiddleware({ padding: 8 }),
      ],
    });

    this.panelTarget.style.left = `${x}px`;
    this.panelTarget.style.top = `${y}px`;
  }

  #syncExpanded(isOpen: boolean): void {
    this.#focusableTrigger()?.setAttribute("aria-expanded", isOpen ? "true" : "false");
  }

  #focusableTrigger(): HTMLElement | null {
    return (
      this.triggerTarget.querySelector<HTMLElement>("button, a, [tabindex]") ?? this.triggerTarget
    );
  }
}
