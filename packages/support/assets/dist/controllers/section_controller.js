import { Controller } from "@hotwired/stimulus";
import { uncloak } from "../utils/cloak.js";
const COLLAPSED_CLASS = "sy-collapsed";
class section_controller_default extends Controller {
  static targets = ["button", "content"];
  static values = {
    id: String,
    collapsed: Boolean,
    persist: Boolean,
    expandLabel: { type: String, default: "Expand section" },
    collapseLabel: { type: String, default: "Collapse section" },
    liveComponentId: String,
    schemaKey: String
  };
  connect() {
    if (this.persistValue) {
      this.#setCollapsed(this.#readPersisted() ?? this.collapsedValue);
    }
    if (this.hasContentTarget) {
      uncloak(this.contentTarget);
    }
  }
  collapse(event) {
    if (this.#matches(event)) {
      this.#setCollapsed(true);
    }
  }
  expand(event) {
    if (this.#matches(event)) {
      this.#setCollapsed(false);
    }
  }
  toggle(event) {
    if (this.#matches(event)) {
      this.#setCollapsed(!this.#collapsed);
    }
  }
  reveal() {
    this.#setCollapsed(false);
  }
  toggleFromHeader(event) {
    if (event.target instanceof Element && event.target.closest(".sy-section-header-after-ctn")) {
      return;
    }
    this.#setCollapsed(!this.#collapsed);
  }
  toggleFromButton(event) {
    event.stopPropagation();
    this.#setCollapsed(!this.#collapsed);
  }
  resetState(event) {
    if (this.liveComponentIdValue === "" || this.schemaKeyValue === "") {
      return;
    }
    if (event.detail?.livewireId !== this.liveComponentIdValue || event.detail?.schemaKey !== this.schemaKeyValue) {
      return;
    }
    this.#setCollapsed(this.collapsedValue);
  }
  get #collapsed() {
    return this.element.classList.contains(COLLAPSED_CLASS);
  }
  get #collapseId() {
    return this.idValue !== "" ? this.idValue : this.element.id;
  }
  #matches(event) {
    const eventId = event.detail?.id;
    return eventId != null && String(eventId) === this.#collapseId;
  }
  #setCollapsed(collapsed) {
    this.element.classList.toggle(COLLAPSED_CLASS, collapsed);
    if (this.hasButtonTarget) {
      this.buttonTarget.setAttribute("aria-expanded", String(!collapsed));
      this.buttonTarget.setAttribute(
        "aria-label",
        collapsed ? this.expandLabelValue : this.collapseLabelValue
      );
    }
    if (this.persistValue) {
      this.#writePersisted(collapsed);
    }
  }
  #storageKey() {
    return `section-${this.#collapseId}-isCollapsed`;
  }
  #readPersisted() {
    try {
      const stored = window.localStorage.getItem(this.#storageKey());
      if (stored === "true") {
        return true;
      }
      if (stored === "false") {
        return false;
      }
    } catch {
    }
    return null;
  }
  #writePersisted(collapsed) {
    try {
      window.localStorage.setItem(this.#storageKey(), JSON.stringify(collapsed));
    } catch {
    }
  }
}
export {
  section_controller_default as default
};
//# sourceMappingURL=section_controller.js.map
