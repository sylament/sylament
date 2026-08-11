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
    teleport: { type: Boolean, default: false },
  };

  declare readonly triggerTarget: HTMLElement;
  declare readonly panelTarget: HTMLElement;
  declare readonly hasPanelTarget: boolean;
  declare readonly placementValue: string;
  declare readonly offsetValue: number;
  declare readonly flipValue: boolean;
  declare readonly teleportValue: boolean;

  #isOpen = false;
  #stopPositioning: (() => void) | null = null;
  #panelObserver: MutationObserver | null = null;

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

  #onPanelMutated = (): void => {
    if (!this.#isOpen || !this.hasPanelTarget || !this.panelTarget.hidden) {
      return;
    }

    this.#showPanel(this.panelTarget);
  };

  connect(): void {
    this.#focusableTrigger()?.setAttribute("aria-haspopup", "true");
    this.#syncExpanded(this.#isOpen);
  }

  disconnect(): void {
    this.close();

    this.#panelObserver?.disconnect();
    this.#panelObserver = null;
  }

  panelTargetConnected(panel: HTMLElement): void {
    this.#panelObserver?.disconnect();
    this.#panelObserver = new MutationObserver(this.#onPanelMutated);
    this.#panelObserver.observe(panel, { attributeFilter: ["hidden"] });

    if (!this.#isOpen) {
      return;
    }

    this.#showPanel(panel);
  }

  panelTargetDisconnected(panel: HTMLElement): void {
    if (this.hasPanelTarget && this.panelTarget !== panel) {
      return;
    }

    this.#panelObserver?.disconnect();
    this.#panelObserver = null;

    this.#stopPositioning?.();
    this.#stopPositioning = null;
  }

  toggle(): void {
    if (this.#isOpen) {
      this.close();

      return;
    }

    this.open();
  }

  open(): void {
    if (this.#isOpen) {
      return;
    }

    this.#isOpen = true;
    this.#showPanel(this.panelTarget);

    document.addEventListener("pointerdown", this.#onPointerDown);
    document.addEventListener("keydown", this.#onKeyDown);
  }

  close(): void {
    if (!this.#isOpen) {
      return;
    }

    this.#isOpen = false;

    this.#stopPositioning?.();
    this.#stopPositioning = null;

    document.removeEventListener("pointerdown", this.#onPointerDown);
    document.removeEventListener("keydown", this.#onKeyDown);

    if (this.hasPanelTarget) {
      this.panelTarget.hidden = true;
    }

    this.#syncExpanded(false);
  }

  #showPanel(panel: HTMLElement): void {
    panel.hidden = false;
    this.#syncExpanded(true);
    this.#startPositioning(panel);
  }

  #startPositioning(panel: HTMLElement): void {
    this.#stopPositioning?.();
    this.#stopPositioning = autoUpdate(this.triggerTarget, panel, () => {
      void this.#position(panel);
    });
  }

  async #position(panel: HTMLElement): Promise<void> {
    panel.style.position = this.teleportValue ? "fixed" : "";

    const { x, y } = await computePosition(this.triggerTarget, panel, {
      placement: this.placementValue as Placement,
      strategy: this.teleportValue ? "fixed" : "absolute",
      middleware: [
        offsetMiddleware(this.offsetValue),
        ...(this.flipValue ? [flipMiddleware()] : []),
        shiftMiddleware({ padding: 8 }),
      ],
    });

    panel.style.left = `${x}px`;
    panel.style.top = `${y}px`;
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
