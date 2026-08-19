import { Controller } from "@hotwired/stimulus";
import { uncloak } from "../utils/cloak.js";
import { resolveLiveHost } from "../utils/live_host.js";
import { callSchemaComponentMethod } from "../utils/schema_component_method.js";
const ACTIVE_CLASS = "sy-active";
const SELECTED_CLASS = "sy-selected";
const INVISIBLE_CLASS = "sy-invisible";
const RESIZE_DEBOUNCE_MS = 150;
const BADGES_METHOD = "getDeferredTabBadges";
const DEFAULT_TAB_ITEM_GAP = 8;
class tabs_controller_default extends Controller {
  static targets = [
    "nav",
    "tab",
    "panel",
    "badgePlaceholder",
    "dropdown",
    "triggerTab",
    "moreButton",
    "dropdownItem"
  ];
  static values = {
    activeTab: { type: Number, default: 1 },
    key: String,
    schemaKey: String,
    persist: Boolean,
    persistId: String,
    queryStringKey: String,
    scrollable: { type: Boolean, default: true },
    deferredBadges: Boolean
  };
  #tab = null;
  #withinDropdownIndex = null;
  #withinDropdownMounted = false;
  #resizeTimer = null;
  #badges = /* @__PURE__ */ new Map();
  #badgesLoaded = false;
  #connected = false;
  #generation = 0;
  connect() {
    const generation = ++this.#generation;
    const tabs = this.#tabKeys();
    this.#tab = this.#fromQueryString(tabs) ?? this.#fromStorage(tabs) ?? tabs.at(this.activeTabValue - 1) ?? null;
    this.#apply();
    this.#autofocus(true);
    uncloak(this.element);
    if (!this.scrollableValue) {
      this.#updateTabsWithinDropdown();
    }
    if (this.deferredBadgesValue) {
      void this.#loadDeferredBadges(generation);
    }
    this.#connected = true;
  }
  disconnect() {
    this.#connected = false;
    this.#generation++;
    if (this.#resizeTimer !== null) {
      window.clearTimeout(this.#resizeTimer);
      this.#resizeTimer = null;
    }
  }
  select(event) {
    this.#selectFrom(event.currentTarget);
  }
  selectFromDropdown(event) {
    this.#selectFrom(event.currentTarget);
  }
  expand(event) {
    this.#selectFrom(event.currentTarget);
  }
  resetState(event) {
    if (this.persistValue || this.queryStringKeyValue !== "") {
      return;
    }
    if (event.target !== this.element.closest('[data-controller~="live"]')) {
      return;
    }
    if (event.detail?.schemaKey !== this.schemaKeyValue) {
      return;
    }
    const tab = this.#tabKeys().at(this.activeTabValue - 1);
    if (tab !== void 0) {
      this.#setTab(tab);
    }
  }
  resize() {
    if (this.#resizeTimer !== null) {
      window.clearTimeout(this.#resizeTimer);
    }
    this.#resizeTimer = window.setTimeout(() => {
      this.#resizeTimer = null;
      this.#updateTabsWithinDropdown();
    }, RESIZE_DEBOUNCE_MS);
  }
  tabTargetConnected() {
    this.#revalidateTab();
  }
  tabTargetDisconnected() {
    this.#revalidateTab();
  }
  panelTargetConnected() {
    if (this.#connected) {
      this.#apply();
    }
  }
  badgePlaceholderTargetConnected(element) {
    this.#applyBadge(element);
  }
  #selectFrom(target) {
    if (!(target instanceof HTMLElement)) {
      return;
    }
    const key = target.dataset.tabKey;
    if (key === void 0 || key === "") {
      return;
    }
    this.#setTab(key);
  }
  #setTab(key) {
    this.#tab = key;
    this.#apply();
    this.#updateQueryString();
    this.#persistTab();
    this.#autofocus();
  }
  /** The inline tab buttons' keys, in DOM order (Filament's `tabsData` JSON blob). */
  #tabKeys() {
    return this.tabTargets.map((tab) => tab.dataset.tabKey ?? "").filter((key) => key !== "");
  }
  /** Filament's `interceptMessage` hook: a tab the server no longer renders falls back to the default. */
  #revalidateTab() {
    if (!this.#connected) {
      return;
    }
    const tabs = this.#tabKeys();
    if (this.#tab === null || !tabs.includes(this.#tab)) {
      this.#tab = tabs.at(this.activeTabValue - 1) ?? this.#tab;
    }
    this.#apply();
  }
  #apply() {
    for (const tab of this.tabTargets) {
      const isActive = this.#isActive(tab);
      tab.classList.toggle(ACTIVE_CLASS, isActive);
      tab.setAttribute("aria-selected", String(isActive));
    }
    for (const panel of this.panelTargets) {
      panel.classList.toggle(ACTIVE_CLASS, this.#isActive(panel));
    }
    for (const triggerTab of this.triggerTabTargets) {
      const isActive = this.#isActive(triggerTab);
      triggerTab.classList.toggle(ACTIVE_CLASS, isActive);
      triggerTab.setAttribute("aria-selected", String(isActive));
    }
    for (const item of this.dropdownItemTargets) {
      item.classList.toggle(SELECTED_CLASS, this.#isActive(item));
    }
    this.#applyOverflowVisibility();
  }
  #isActive(element) {
    return this.#tab !== null && element.dataset.tabKey === this.#tab;
  }
  #fromQueryString(tabs) {
    if (this.queryStringKeyValue === "") {
      return null;
    }
    const tab = new URL(window.location.href).searchParams.get(this.queryStringKeyValue);
    return tab !== null && tabs.includes(tab) ? tab : null;
  }
  #fromStorage(tabs) {
    const tab = this.#readPersisted();
    return tab !== null && tabs.includes(tab) ? tab : null;
  }
  #updateQueryString() {
    if (this.queryStringKeyValue === "" || this.#tab === null) {
      return;
    }
    const url = new URL(window.location.href);
    url.searchParams.set(this.queryStringKeyValue, this.#tab);
    history.replaceState(null, document.title, url.toString());
  }
  #storageKey() {
    return `tabs-${this.persistIdValue}`;
  }
  #persistTab() {
    if (!this.persistValue || this.persistIdValue === "" || this.#tab === null) {
      return;
    }
    try {
      window.localStorage.setItem(this.#storageKey(), this.#tab);
    } catch {
    }
  }
  #readPersisted() {
    if (!this.persistValue || this.persistIdValue === "") {
      return null;
    }
    try {
      return window.localStorage.getItem(this.#storageKey());
    } catch {
      return null;
    }
  }
  #autofocus(respectCurrentFocus = false) {
    queueMicrotask(() => {
      const active = document.activeElement;
      if (respectCurrentFocus && active !== null && active !== document.body && (this.element.compareDocumentPosition(active) & Node.DOCUMENT_POSITION_PRECEDING) !== 0) {
        return;
      }
      const fields = this.element.querySelectorAll(
        `.sy-sc-tabs-tab.${ACTIVE_CLASS} [autofocus]`
      );
      for (const field of fields) {
        field.focus();
        if (document.activeElement === field) {
          break;
        }
      }
    });
  }
  #updateTabsWithinDropdown() {
    if (!this.hasNavTarget || !this.hasMoreButtonTarget) {
      return;
    }
    this.#withinDropdownIndex = null;
    this.#withinDropdownMounted = false;
    const nav = this.navTarget;
    const tabElements = Array.from(nav.children).slice(0, -1).filter((element) => element instanceof HTMLElement);
    const firstTab = tabElements.at(0);
    if (firstTab === void 0) {
      this.#withinDropdownMounted = true;
      this.#apply();
      return;
    }
    const originalDisplays = tabElements.map((element) => element.style.display);
    for (const element of tabElements) {
      element.style.display = "";
    }
    void nav.offsetHeight;
    const overflowIndex = this.#findOverflowIndex(
      tabElements,
      this.#calculateAvailableWidth(nav),
      this.#calculateContainerGap(nav),
      this.#calculateTabItemGap(firstTab),
      this.#calculateTabItemPadding(firstTab),
      this.#calculateDropdownIconWidth(this.moreButtonTarget)
    );
    tabElements.forEach((element, index) => {
      element.style.display = originalDisplays[index] ?? "";
    });
    if (overflowIndex !== -1) {
      this.#withinDropdownIndex = overflowIndex;
    }
    this.#withinDropdownMounted = true;
    this.#apply();
  }
  #findOverflowIndex(tabElements, availableWidth, containerGap, tabItemGap, tabItemPadding, dropdownIconWidth) {
    const tabWidths = tabElements.map((element) => Math.ceil(element.clientWidth));
    const tabContentWidths = tabElements.map((element) => {
      const labelWidth = Math.ceil(element.querySelector(".sy-tabs-item-label")?.clientWidth ?? 0);
      const badgeWidth = Math.ceil(element.querySelector(".sy-badge")?.clientWidth ?? 0);
      return labelWidth + (badgeWidth > 0 ? tabItemGap + badgeWidth : 0);
    });
    for (let index = 0; index < tabElements.length; index++) {
      const visibleTabsWidth = tabWidths.slice(0, index + 1).reduce((sum, width) => sum + width, 0);
      const gapsBetweenVisibleTabs = index * containerGap;
      const collapsedContents = tabContentWidths.slice(index + 1);
      const hasCollapsedTabs = collapsedContents.length > 0;
      const triggerWidth = hasCollapsedTabs ? tabItemPadding + Math.max(...collapsedContents) + tabItemGap + dropdownIconWidth + containerGap : 0;
      if (visibleTabsWidth + gapsBetweenVisibleTabs + triggerWidth > availableWidth) {
        return index;
      }
    }
    return -1;
  }
  #calculateAvailableWidth(container) {
    const styles = window.getComputedStyle(container);
    return Math.floor(container.clientWidth) - Math.ceil(Number.parseFloat(styles.paddingLeft) || 0) * 2;
  }
  #calculateContainerGap(container) {
    return Math.ceil(Number.parseFloat(window.getComputedStyle(container).columnGap) || 0);
  }
  #calculateDropdownIconWidth(trigger) {
    return Math.ceil(trigger.querySelector(".sy-icon")?.clientWidth ?? 0);
  }
  #calculateTabItemGap(tab) {
    return Math.ceil(
      Number.parseFloat(window.getComputedStyle(tab).columnGap) || DEFAULT_TAB_ITEM_GAP
    );
  }
  #calculateTabItemPadding(tab) {
    const styles = window.getComputedStyle(tab);
    return Math.ceil(Number.parseFloat(styles.paddingLeft) || 0) + Math.ceil(Number.parseFloat(styles.paddingRight) || 0);
  }
  /** Filament's `isDropdownButtonVisible` getter. */
  #isDropdownButtonVisible() {
    if (!this.#withinDropdownMounted) {
      return true;
    }
    if (this.#withinDropdownIndex === null) {
      return false;
    }
    return this.#tabKeys().indexOf(this.#tab ?? "") < this.#withinDropdownIndex;
  }
  /** Filament's `x-show` arms over the overflow state, as inline `display`. */
  #applyOverflowVisibility() {
    if (this.scrollableValue) {
      return;
    }
    const mounted = this.#withinDropdownMounted;
    const index = this.#withinDropdownIndex;
    this.tabTargets.forEach((tab, position) => {
      setVisible(tab, !mounted || index === null || position < index);
    });
    this.triggerTabTargets.forEach((triggerTab, position) => {
      setVisible(
        triggerTab,
        mounted && index !== null && position >= index && this.#isActive(triggerTab)
      );
    });
    this.dropdownItemTargets.forEach((item, position) => {
      setVisible(item, index !== null && position >= index);
    });
    if (this.hasMoreButtonTarget) {
      setVisible(this.moreButtonTarget, this.#isDropdownButtonVisible());
    }
    if (this.hasNavTarget) {
      this.navTarget.classList.toggle(INVISIBLE_CLASS, !mounted);
    }
  }
  async #loadDeferredBadges(generation) {
    try {
      const host = await resolveLiveHost(this.element);
      const badges = await callSchemaComponentMethod(host, this.keyValue, BADGES_METHOD);
      if (isRecord(badges)) {
        for (const [key, entry] of Object.entries(badges)) {
          this.#badges.set(key, isRecord(entry) ? entry : null);
        }
      }
    } catch {
      this.#badges.clear();
    }
    if (generation !== this.#generation) {
      return;
    }
    this.#badgesLoaded = true;
    for (const placeholder of this.badgePlaceholderTargets) {
      this.#applyBadge(placeholder);
    }
  }
  /**
   * Replace a placeholder's loading indicator with the badge the host answered with, and stamp
   * `data-live-ignore` so later morphs leave the client-built DOM alone.
   */
  #applyBadge(placeholder) {
    if (!this.#badgesLoaded) {
      return;
    }
    const key = placeholder.dataset.tabKey;
    const entry = key === void 0 ? null : this.#badges.get(key) ?? null;
    placeholder.replaceChildren();
    if (entry !== null && typeof entry.badge === "string" && entry.badge !== "") {
      placeholder.append(buildBadge(entry, entry.badge));
    }
    placeholder.setAttribute("data-live-ignore", "");
  }
}
function setVisible(element, visible) {
  element.style.display = visible ? "" : "none";
}
function isRecord(value) {
  return typeof value === "object" && value !== null && !Array.isArray(value);
}
function stringOf(value) {
  return typeof value === "string" ? value : "";
}
function buildBadge(entry, text) {
  const badge = document.createElement("span");
  badge.className = ["sy", "sy-badge", "sy-size-sm", stringOf(entry.badgeColorClasses)].filter((token) => token !== "").join(" ");
  const styles = stringOf(entry.badgeColorStyles);
  if (styles !== "") {
    badge.setAttribute("style", styles);
  }
  const tooltip = stringOf(entry.badgeTooltip);
  if (tooltip !== "") {
    badge.setAttribute("title", tooltip);
  }
  const iconHtml = stringOf(entry.badgeIconHtml);
  const isIconAfter = stringOf(entry.badgeIconPosition) === "after";
  if (iconHtml !== "" && !isIconAfter) {
    badge.insertAdjacentHTML("beforeend", iconHtml);
  }
  const labelContainer = document.createElement("span");
  labelContainer.className = "sy-badge-label-ctn";
  const label = document.createElement("span");
  label.className = "sy-badge-label";
  label.textContent = text;
  labelContainer.append(label);
  badge.append(labelContainer);
  if (iconHtml !== "" && isIconAfter) {
    badge.insertAdjacentHTML("beforeend", iconHtml);
  }
  return badge;
}
export {
  tabs_controller_default as default
};
//# sourceMappingURL=tabs_controller.js.map
