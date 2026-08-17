import { Controller } from "@hotwired/stimulus";

const LABEL_SELECTOR = ".sy-fo-checkbox-list-option-label";
const DESCRIPTION_SELECTOR = ".sy-fo-checkbox-list-option-description";
const BOX_SELECTOR = "input[type=checkbox]";
const SATISFIED_SELECTOR = "input[type=checkbox]:checked, input[type=checkbox]:disabled";

export default class extends Controller<HTMLElement> {
  static targets = ["search", "option", "selectAllWrap", "deselectAllWrap", "noResults"];
  static values = { debounce: { type: Number, default: 0 } };

  declare readonly hasSearchTarget: boolean;
  declare readonly searchTarget: HTMLInputElement;
  declare readonly optionTargets: HTMLElement[];
  declare readonly hasSelectAllWrapTarget: boolean;
  declare readonly selectAllWrapTarget: HTMLElement;
  declare readonly hasDeselectAllWrapTarget: boolean;
  declare readonly deselectAllWrapTarget: HTMLElement;
  declare readonly hasNoResultsTarget: boolean;
  declare readonly noResultsTarget: HTMLElement;
  declare readonly debounceValue: number;

  #timer: ReturnType<typeof setTimeout> | undefined;
  #observer: MutationObserver | undefined;
  #togglingAll = false;

  connect(): void {
    this.#refresh();

    // `childList` only: `#refresh()` writes `hidden` attributes, so an observer that watched
    // attributes would be fed its own output.
    this.#observer = new MutationObserver(() => this.#refresh());
    this.#observer.observe(this.element, { childList: true, subtree: true });

    if (this.hasSearchTarget) {
      this.searchTarget.addEventListener("input", this.#onSearchInput);
    }

    this.element.addEventListener("change", this.#onChange);
  }

  disconnect(): void {
    this.#observer?.disconnect();

    if (this.hasSearchTarget) {
      this.searchTarget.removeEventListener("input", this.#onSearchInput);
    }

    this.element.removeEventListener("change", this.#onChange);
    clearTimeout(this.#timer);
  }

  refresh(): void {
    this.#refresh();
  }

  toggleAll(): void {
    const visible = this.#visibleOptions();
    const target = !this.#allSatisfied(visible);

    this.#togglingAll = true;

    try {
      for (const option of visible) {
        const box = option.querySelector<HTMLInputElement>(BOX_SELECTOR);

        if (box === null || box.disabled || box.checked === target) {
          continue;
        }

        box.checked = target;

        // `bubbles` is load-bearing: the live component's model listener sits on its own root and
        // reads `event.target`, so vendor's non-bubbling default would never reach the state.
        box.dispatchEvent(new Event("change", { bubbles: true }));
      }
    } finally {
      this.#togglingAll = false;
    }

    this.#refresh();
  }

  #onChange = (event: Event): void => {
    if (this.#togglingAll) {
      return;
    }

    const target = event.target;

    if (target instanceof HTMLInputElement && target.type === "checkbox") {
      this.#refresh();
    }
  };

  #onSearchInput = (): void => {
    clearTimeout(this.#timer);
    this.#timer = setTimeout(() => this.#refresh(), this.debounceValue);
  };

  #refresh(): void {
    const query = this.#query();
    const visible = this.#visibleOptions(query, true);

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
   * The options matching the current search, hiding the rest when `apply` is set. An empty query
   * matches everything; a non-empty one is a case-insensitive substring of the option's rendered
   * label or description text — never of its value, which vendor does not search either.
   */
  #visibleOptions(query?: string, apply = false): HTMLElement[] {
    const needle = query ?? this.#query();

    return this.optionTargets.filter((option) => {
      const matches =
        needle === "" ||
        this.#text(option, LABEL_SELECTOR).includes(needle) ||
        this.#text(option, DESCRIPTION_SELECTOR).includes(needle);

      if (apply) {
        option.hidden = !matches;
      }

      return matches;
    });
  }

  /**
   * A disabled option counts as satisfied, vendor-exact: it can never be reached by a click, so
   * without the rule one disabled-and-unchecked option would pin the label to "Select all"
   * forever. An empty set reports unsatisfied, so a search matching nothing rests on "Select all"
   * rather than offering a "Deselect all" with nothing to deselect.
   */
  #allSatisfied(options: HTMLElement[]): boolean {
    return (
      options.length > 0 &&
      options.every((option) => option.querySelector(SATISFIED_SELECTOR) !== null)
    );
  }

  #query(): string {
    return this.hasSearchTarget ? this.searchTarget.value.trim().toLowerCase() : "";
  }

  #text(root: HTMLElement, selector: string): string {
    return root.querySelector<HTMLElement>(selector)?.innerText.toLowerCase() ?? "";
  }
}
