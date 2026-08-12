import { Controller } from "@hotwired/stimulus";

const STORAGE_KEY = "sylament.panels.sidebar.collapsedGroups";
const OPEN_CLASS = "sy-pa-sidebar-open";
const COLLAPSED_CLASS = "sy-collapsed";
const GROUP_BUTTON_SELECTOR = ".sy-pa-sidebar-group-btn[aria-expanded]";

export default class extends Controller<HTMLElement> {
  static targets = ["sidebar", "toggleButton"];

  declare readonly sidebarTarget: HTMLElement;
  declare readonly toggleButtonTarget: HTMLElement;
  declare readonly hasToggleButtonTarget: boolean;

  connect(): void {
    this.#applyCollapsedGroups();
  }

  toggle(): void {
    this.#syncToggleButton(this.element.classList.toggle(OPEN_CLASS));
  }

  close(): void {
    this.element.classList.remove(OPEN_CLASS);
    this.#syncToggleButton(false);
  }

  closeOnEscape(event: KeyboardEvent): void {
    if (event.key === "Escape") {
      this.close();
    }
  }

  toggleGroup(event: Event): void {
    const group = (event.currentTarget as HTMLElement).closest<HTMLElement>("[data-group-label]");

    if (group === null) {
      return;
    }

    const collapsed = group.classList.toggle(COLLAPSED_CLASS);
    this.#syncGroupButton(group, collapsed);
    this.#persistCollapsedGroups();
  }

  #applyCollapsedGroups(): void {
    const collapsed = this.#readCollapsedGroups();

    if (collapsed === null) {
      this.#persistCollapsedGroups();

      return;
    }

    for (const group of this.#collapsibleGroupElements()) {
      const label = group.dataset.groupLabel ?? "";
      const isCollapsed = collapsed.includes(label);
      group.classList.toggle(COLLAPSED_CLASS, isCollapsed);
      this.#syncGroupButton(group, isCollapsed);
    }
  }

  #persistCollapsedGroups(): void {
    const labels = this.#collapsibleGroupElements()
      .filter((group) => group.classList.contains(COLLAPSED_CLASS))
      .map((group) => group.dataset.groupLabel ?? "");

    try {
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify(labels));
    } catch {
      // Storage unavailable: collapsing still works, it just won't persist.
    }
  }

  /**
   * Null means "no usable stored state" — absent key, unavailable storage, or an unreadable
   * value — and the caller seeds the key from the server-rendered state instead of applying one.
   */
  #readCollapsedGroups(): string[] | null {
    let raw: string | null = null;

    try {
      raw = window.localStorage.getItem(STORAGE_KEY);
    } catch {
      return null;
    }

    if (raw === null) {
      return null;
    }

    let parsed: unknown = null;

    try {
      parsed = JSON.parse(raw);
    } catch {
      return null;
    }

    return Array.isArray(parsed)
      ? parsed.filter((label): label is string => typeof label === "string")
      : null;
  }

  /**
   * A non-collapsible group renders the same label class on a plain element with no disclosure
   * wiring, so the button filter is what keeps stored state off it: collapsing one would hide its
   * items with no affordance to reopen them.
   */
  #collapsibleGroupElements(): HTMLElement[] {
    return Array.from(
      this.sidebarTarget.querySelectorAll<HTMLElement>("[data-group-label]"),
    ).filter((group) => this.#groupButton(group) !== null);
  }

  #groupButton(group: HTMLElement): Element | null {
    return group.querySelector(GROUP_BUTTON_SELECTOR);
  }

  #syncGroupButton(group: HTMLElement, collapsed: boolean): void {
    this.#groupButton(group)?.setAttribute("aria-expanded", collapsed ? "false" : "true");
  }

  #syncToggleButton(open: boolean): void {
    if (this.hasToggleButtonTarget) {
      this.toggleButtonTarget.setAttribute("aria-expanded", String(open));
    }
  }
}
