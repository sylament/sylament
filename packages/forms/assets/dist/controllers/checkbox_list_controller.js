import { Controller } from "@hotwired/stimulus";
const LABEL_SELECTOR = ".sy-fo-checkbox-list-option-label";
const DESCRIPTION_SELECTOR = ".sy-fo-checkbox-list-option-description";
const BOX_SELECTOR = "input[type=checkbox]";
const SATISFIED_SELECTOR = "input[type=checkbox]:checked, input[type=checkbox]:disabled";
class checkbox_list_controller_default extends Controller {
  static targets = ["search", "option", "selectAllWrap", "deselectAllWrap", "noResults"];
  static values = { debounce: { type: Number, default: 0 } };
  #timer;
  #observer;
  #togglingAll = false;
  connect() {
    this.#refresh();
    this.#observer = new MutationObserver(() => this.#refresh());
    this.#observer.observe(this.element, { childList: true, subtree: true });
    if (this.hasSearchTarget) {
      this.searchTarget.addEventListener("input", this.#onSearchInput);
    }
    this.element.addEventListener("change", this.#onChange);
  }
  disconnect() {
    this.#observer?.disconnect();
    if (this.hasSearchTarget) {
      this.searchTarget.removeEventListener("input", this.#onSearchInput);
    }
    this.element.removeEventListener("change", this.#onChange);
    clearTimeout(this.#timer);
  }
  refresh() {
    this.#refresh();
  }
  toggleAll() {
    const visible = this.#visibleOptions();
    const target = !this.#allSatisfied(visible);
    this.#togglingAll = true;
    try {
      for (const option of visible) {
        const box = option.querySelector(BOX_SELECTOR);
        if (box === null || box.disabled || box.checked === target) {
          continue;
        }
        box.checked = target;
        box.dispatchEvent(new Event("change", { bubbles: true }));
      }
    } finally {
      this.#togglingAll = false;
    }
    this.#refresh();
  }
  #onChange = (event) => {
    if (this.#togglingAll) {
      return;
    }
    const target = event.target;
    if (target instanceof HTMLInputElement && target.type === "checkbox") {
      this.#refresh();
    }
  };
  #onSearchInput = () => {
    clearTimeout(this.#timer);
    this.#timer = setTimeout(() => this.#refresh(), this.debounceValue);
  };
  #refresh() {
    const query = this.#query();
    for (const option of this.optionTargets) {
      option.hidden = query !== "" && !this.#matches(option, query);
    }
    const visible = this.#visibleOptions();
    if (this.hasNoResultsTarget) {
      this.noResultsTarget.hidden = query === "" || visible.length > 0;
    }
    const satisfied = this.#allSatisfied(visible);
    if (this.hasSelectAllWrapTarget) {
      this.selectAllWrapTarget.hidden = satisfied;
    }
    if (this.hasDeselectAllWrapTarget) {
      this.deselectAllWrapTarget.hidden = !satisfied;
    }
  }
  /**
   * What the user can currently see — the filter `#refresh()` last applied, never a fresh read of
   * the search input. The two diverge inside a non-zero `searchDebounce`, and every consumer here
   * (the bulk toggle above all) means the set on screen.
   */
  #visibleOptions() {
    return this.optionTargets.filter((option) => !option.hidden);
  }
  /**
   * A case-insensitive substring of the option's rendered label or description text — never of its
   * value, which vendor does not search either.
   */
  #matches(option, query) {
    return this.#text(option, LABEL_SELECTOR).includes(query) || this.#text(option, DESCRIPTION_SELECTOR).includes(query);
  }
  /**
   * A disabled option counts as satisfied, vendor-exact: it can never be reached by a click, so
   * without the rule one disabled-and-unchecked option would pin the label to "Select all"
   * forever. An empty set reports unsatisfied, so a search matching nothing rests on "Select all"
   * rather than offering a "Deselect all" with nothing to deselect.
   */
  #allSatisfied(options) {
    return options.length > 0 && options.every((option) => option.querySelector(SATISFIED_SELECTOR) !== null);
  }
  #query() {
    return this.hasSearchTarget ? this.searchTarget.value.trim().toLowerCase() : "";
  }
  #text(root, selector) {
    return root.querySelector(selector)?.innerText.toLowerCase() ?? "";
  }
}
export {
  checkbox_list_controller_default as default
};
//# sourceMappingURL=checkbox_list_controller.js.map
