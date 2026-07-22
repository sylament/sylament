import { Controller } from "@hotwired/stimulus";
import { uncloak } from "../utils/cloak.js";

const COLLAPSED_CLASS = "sy-collapsed";

export default class extends Controller<HTMLElement> {
  static targets = ["button", "content"];

  static values = {
    id: String,
    collapsed: Boolean,
    persist: Boolean,
    expandLabel: { type: String, default: "Expand section" },
    collapseLabel: { type: String, default: "Collapse section" },
    liveComponentId: String,
    schemaKey: String,
  };

  declare readonly buttonTarget: HTMLElement;
  declare readonly hasButtonTarget: boolean;
  declare readonly contentTarget: HTMLElement;
  declare readonly hasContentTarget: boolean;
  declare readonly idValue: string;
  declare readonly collapsedValue: boolean;
  declare readonly persistValue: boolean;
  declare readonly expandLabelValue: string;
  declare readonly collapseLabelValue: string;
  declare readonly liveComponentIdValue: string;
  declare readonly schemaKeyValue: string;

  connect(): void {
    if (this.persistValue) {
      this.#setCollapsed(this.#readPersisted() ?? this.collapsedValue);
    }

    if (this.hasContentTarget) {
      uncloak(this.contentTarget);
    }
  }

  collapse(event: CustomEvent<{ id?: unknown }>): void {
    if (this.#matches(event)) {
      this.#setCollapsed(true);
    }
  }

  expand(event: CustomEvent<{ id?: unknown }>): void {
    if (this.#matches(event)) {
      this.#setCollapsed(false);
    }
  }

  toggle(event: CustomEvent<{ id?: unknown }>): void {
    if (this.#matches(event)) {
      this.#setCollapsed(!this.#collapsed);
    }
  }

  reveal(): void {
    this.#setCollapsed(false);
  }

  toggleFromHeader(event: Event): void {
    if (event.target instanceof Element && event.target.closest(".sy-section-header-after-ctn")) {
      return;
    }

    this.#setCollapsed(!this.#collapsed);
  }

  toggleFromButton(event: Event): void {
    // Stimulus' `:stop` action option needs 3.2+; the peer range allows 3.0.
    event.stopPropagation();
    this.#setCollapsed(!this.#collapsed);
  }

  resetState(event: CustomEvent<{ livewireId?: unknown; schemaKey?: unknown }>): void {
    if (this.liveComponentIdValue === "" || this.schemaKeyValue === "") {
      return;
    }

    if (
      event.detail?.livewireId !== this.liveComponentIdValue ||
      event.detail?.schemaKey !== this.schemaKeyValue
    ) {
      return;
    }

    this.#setCollapsed(this.collapsedValue);
  }

  get #collapsed(): boolean {
    return this.element.classList.contains(COLLAPSED_CLASS);
  }

  get #collapseId(): string {
    return this.idValue !== "" ? this.idValue : this.element.id;
  }

  #matches(event: CustomEvent<{ id?: unknown }>): boolean {
    const eventId = event.detail?.id;

    return eventId != null && String(eventId) === this.#collapseId;
  }

  #setCollapsed(collapsed: boolean): void {
    this.element.classList.toggle(COLLAPSED_CLASS, collapsed);

    if (this.hasButtonTarget) {
      this.buttonTarget.setAttribute("aria-expanded", String(!collapsed));
      this.buttonTarget.setAttribute(
        "aria-label",
        collapsed ? this.expandLabelValue : this.collapseLabelValue,
      );
    }

    if (this.persistValue) {
      this.#writePersisted(collapsed);
    }
  }

  #storageKey(): string {
    return `section-${this.#collapseId}-isCollapsed`;
  }

  #readPersisted(): boolean | null {
    try {
      const stored = window.localStorage.getItem(this.#storageKey());

      if (stored === "true") {
        return true;
      }

      if (stored === "false") {
        return false;
      }
    } catch {
      // Storage unavailable (private mode / disabled): fall back to the server default.
    }

    return null;
  }

  #writePersisted(collapsed: boolean): void {
    try {
      window.localStorage.setItem(this.#storageKey(), JSON.stringify(collapsed));
    } catch {
      // Storage unavailable: collapsing still works, it just won't persist.
    }
  }
}
