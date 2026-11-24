import { Controller } from "@hotwired/stimulus";
import type { Component } from "@symfony/ux-live-component";

import { resolveLiveHost } from "../utils/live_host.js";

const APPLE_PLATFORM = /Mac|iPhone|iPad|iPod/;
const OPEN_MODAL_SELECTOR = 'dialog:modal, [aria-modal="true"]';
const FOCUSABLE_RESULT_SELECTOR = "a[href], button:not([disabled])";
const LOADING_DELAY = 350;
const FAILURE_TIMEOUT = 30000;

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
  static targets = ["input", "resultsPanel", "status"];

  static values = {
    keyBindings: { type: Array, default: [] },
    loadingMessage: String,
    failureMessage: String,
  };

  declare readonly inputTarget: HTMLInputElement;
  declare readonly resultsPanelTarget: HTMLElement;
  declare readonly hasResultsPanelTarget: boolean;
  declare readonly statusTarget: HTMLElement;
  declare readonly keyBindingsValue: string[];
  declare readonly loadingMessageValue: string;
  declare readonly failureMessageValue: string;

  #bindings: ParsedBinding[] = [];
  #unsubscribe: (() => void) | null = null;
  #generation = 0;
  /** Bumped by every keystroke, so a render answers the search only when its request carried the latest one. */
  #revision = 0;
  #requestRevision: number | null = null;
  #dismissed = false;
  #loadingTimeout: number | undefined;
  #failureTimeout: number | undefined;

  async connect(): Promise<void> {
    this.#bindings = this.keyBindingsValue.map(parseBinding);
    document.addEventListener("keydown", this.#onKeydown);
    document.addEventListener("pointerdown", this.#onPointerDown);

    const generation = ++this.#generation;
    const component = await resolveLiveHost(this.element);

    if (generation !== this.#generation) {
      return;
    }

    this.#subscribe(component);
  }

  disconnect(): void {
    this.#generation++;
    this.#unsubscribe?.();
    this.#unsubscribe = null;
    this.#clearTimeouts();
    document.removeEventListener("keydown", this.#onKeydown);
    document.removeEventListener("pointerdown", this.#onPointerDown);
  }

  close(): void {
    this.#dismissed = true;
    this.#clearTimeouts();
    this.#announce("");

    if (this.hasResultsPanelTarget) {
      this.resultsPanelTarget.hidden = true;
    }
  }

  inputChanged(): void {
    this.#revision++;
    this.#dismissed = false;
    window.clearTimeout(this.#loadingTimeout);
    this.#announce("");

    // A running failure timeout stays armed: UX holds the next request back until the pending one
    // answers, so for a request that never answers this timeout is the only report.
    if (!this.#hasTerm()) {
      window.clearTimeout(this.#failureTimeout);
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

  #subscribe(component: Component): void {
    const onRequestStarted = (): void => {
      this.#requestRevision = this.#revision;
      this.#clearTimeouts();

      if (this.#dismissed || !this.#hasTerm()) {
        return;
      }

      this.#loadingTimeout = window.setTimeout(() => {
        if (!this.#dismissed) {
          this.#announce(this.loadingMessageValue);
        }
      }, LOADING_DELAY);
      this.#failureTimeout = window.setTimeout(() => this.#fail(), FAILURE_TIMEOUT);
    };
    const onRenderFinished = (): void => {
      // UX re-applies an attribute the client set to the morphed element, so the panel's
      // visibility is re-derived here: a keystroke reopens it, a close survives a late response.
      if (this.hasResultsPanelTarget) {
        this.resultsPanelTarget.hidden = this.#dismissed;
      }

      this.#clearTimeouts();

      if (this.#requestRevision !== this.#revision) {
        return;
      }

      const isOpen = this.hasResultsPanelTarget && !this.resultsPanelTarget.hidden;

      this.#announce(isOpen ? (this.resultsPanelTarget.dataset.message ?? "") : "");
    };
    const onResponseError = (): void => {
      this.#clearTimeouts();

      if (this.#requestRevision === this.#revision && this.#hasTerm()) {
        this.#fail();
      }
    };

    component.on("request:started", onRequestStarted);
    component.on("render:finished", onRenderFinished);
    component.on("response:error", onResponseError);
    this.#unsubscribe = (): void => {
      component.off("request:started", onRequestStarted);
      component.off("render:finished", onRenderFinished);
      component.off("response:error", onResponseError);
    };
  }

  #fail(): void {
    if (this.#dismissed) {
      return;
    }

    this.#clearTimeouts();
    this.#announce(this.failureMessageValue);
  }

  #announce(message: string): void {
    if (this.statusTarget.textContent !== message) {
      this.statusTarget.textContent = message;
    }
  }

  #hasTerm(): boolean {
    return this.inputTarget.value.trim() !== "";
  }

  #clearTimeouts(): void {
    window.clearTimeout(this.#loadingTimeout);
    window.clearTimeout(this.#failureTimeout);
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
