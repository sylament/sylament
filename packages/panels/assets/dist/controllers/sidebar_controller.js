import { Controller } from "@hotwired/stimulus";
const STORAGE_KEY = "sylament.panels.sidebar.collapsedGroups";
const OPEN_CLASS = "sy-pa-sidebar-open";
const COLLAPSED_CLASS = "sy-collapsed";
class sidebar_controller_default extends Controller {
  static targets = ["sidebar", "toggleButton"];
  connect() {
    this.#applyCollapsedGroups();
  }
  toggle() {
    this.#syncToggleButton(this.element.classList.toggle(OPEN_CLASS));
  }
  close() {
    this.element.classList.remove(OPEN_CLASS);
    this.#syncToggleButton(false);
  }
  closeOnEscape(event) {
    if (event.key === "Escape") {
      this.close();
    }
  }
  toggleGroup(event) {
    const group = event.currentTarget.closest("[data-group-label]");
    if (group === null) {
      return;
    }
    const collapsed = group.classList.toggle(COLLAPSED_CLASS);
    this.#syncGroupButton(group, collapsed);
    this.#persistCollapsedGroups();
  }
  #applyCollapsedGroups() {
    const collapsed = this.#readCollapsedGroups();
    if (collapsed === null) {
      this.#persistCollapsedGroups();
      return;
    }
    for (const group of this.#groupElements()) {
      const label = group.dataset.groupLabel ?? "";
      const isCollapsed = collapsed.includes(label);
      group.classList.toggle(COLLAPSED_CLASS, isCollapsed);
      this.#syncGroupButton(group, isCollapsed);
    }
  }
  #persistCollapsedGroups() {
    const labels = this.#groupElements().filter((group) => group.classList.contains(COLLAPSED_CLASS)).map((group) => group.dataset.groupLabel ?? "");
    try {
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify(labels));
    } catch {
    }
  }
  /**
   * Null means "no usable stored state" — absent key, unavailable storage, or an unreadable
   * value — and the caller seeds the key from the server-rendered state instead of applying one.
   */
  #readCollapsedGroups() {
    let raw = null;
    try {
      raw = window.localStorage.getItem(STORAGE_KEY);
    } catch {
      return null;
    }
    if (raw === null) {
      return null;
    }
    let parsed = null;
    try {
      parsed = JSON.parse(raw);
    } catch {
      return null;
    }
    return Array.isArray(parsed) ? parsed.filter((label) => typeof label === "string") : null;
  }
  #groupElements() {
    return Array.from(this.sidebarTarget.querySelectorAll("[data-group-label]"));
  }
  /**
   * The `[aria-expanded]` filter is what keeps a non-collapsible group out: it renders the same
   * label class on a plain element with no disclosure wiring, which must not gain the state.
   */
  #syncGroupButton(group, collapsed) {
    group.querySelector(".sy-pa-sidebar-group-btn[aria-expanded]")?.setAttribute("aria-expanded", collapsed ? "false" : "true");
  }
  #syncToggleButton(open) {
    if (this.hasToggleButtonTarget) {
      this.toggleButtonTarget.setAttribute("aria-expanded", String(open));
    }
  }
}
export {
  sidebar_controller_default as default
};
//# sourceMappingURL=sidebar_controller.js.map
