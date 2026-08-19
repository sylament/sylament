import { computePosition, flip, offset, shift } from "@floating-ui/dom";
import Sortable from "sortablejs";

export type SelectOption = { label: string; value: string; isDisabled?: boolean };
export type SelectOptionGroup = { label: string; options: SelectOption[] };
export type SelectOptionList = (SelectOption | SelectOptionGroup)[];
export type SelectState = string | string[] | null;
export type SelectLabel = { value: string; label: string };

export type SelectOptions = {
  ariaLabel?: string | null;
  badgeClass: string;
  canOptionLabelsWrap?: boolean;
  canSelectPlaceholder?: boolean;
  element: HTMLElement;
  getOptionLabelUsing?: (() => Promise<unknown>) | null;
  getOptionLabelsUsing?: (() => Promise<unknown>) | null;
  getOptionsUsing?: (() => Promise<unknown>) | null;
  getSearchResultsUsing?: ((query: string) => Promise<unknown>) | null;
  hasDynamicOptions?: boolean;
  hasDynamicSearchResults?: boolean;
  hasInitialNoOptionsMessage?: boolean;
  id?: string | null;
  initialOptionLabel?: string | null;
  initialOptionLabels?: SelectLabel[] | null;
  initialState?: SelectState;
  isAutofocused?: boolean;
  isDisabled?: boolean;
  isHtmlAllowed?: boolean;
  isMultiple?: boolean;
  isReorderable?: boolean;
  isSearchable?: boolean;
  loadingMessage?: string;
  maxItems?: number | null;
  maxItemsMessage?: string;
  noOptionsMessage?: string;
  noSearchResultsMessage?: string;
  onStateChange?: (state: SelectState) => void;
  options: SelectOptionList;
  optionsLimit?: number | null;
  placeholder?: string;
  position?: string | null;
  searchableOptionFields?: string[];
  searchDebounce?: number;
  searchingMessage?: string;
  searchPrompt?: string;
  state: SelectState;
  statePath?: string | null;
};

const X_MARK_16 =
  '<svg class="sy sy-icon sy-size-xs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M5.28 4.22a.75.75 0 0 0-1.06 1.06L6.94 8l-2.72 2.72a.75.75 0 1 0 1.06 1.06L8 9.06l2.72 2.72a.75.75 0 1 0 1.06-1.06L9.06 8l2.72-2.72a.75.75 0 0 0-1.06-1.06L8 6.94 5.28 4.22Z"/></svg>';
const X_MARK_20 =
  '<svg class="sy sy-icon sy-size-sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>';

function blank(value: unknown): boolean {
  return (
    value === null ||
    value === undefined ||
    value === "" ||
    (typeof value === "string" && value.trim() === "")
  );
}

function filled<T>(value: T): value is NonNullable<T> {
  return !blank(value);
}

function isGroup(entry: SelectOption | SelectOptionGroup): entry is SelectOptionGroup {
  return Array.isArray((entry as SelectOptionGroup).options);
}

/** A callback result is either the option list itself or `{options: [...]}`; anything else is empty. */
function normalizeOptions(results: unknown): SelectOptionList {
  if (Array.isArray(results)) {
    return results as SelectOptionList;
  }

  if (
    typeof results === "object" &&
    results !== null &&
    Array.isArray((results as { options?: unknown }).options)
  ) {
    return (results as { options: SelectOptionList }).options;
  }

  return [];
}

/** A label-callback result, kept to the `{value, label}` entries it is contracted to carry. */
function normalizeLabels(results: unknown): SelectLabel[] {
  if (!Array.isArray(results)) {
    return [];
  }

  const labels: SelectLabel[] = [];

  for (const entry of results) {
    if (typeof entry !== "object" || entry === null) {
      continue;
    }

    const { value, label } = entry as { value?: unknown; label?: unknown };

    if (value !== undefined && label !== undefined) {
      labels.push({ value: String(value), label: String(label) });
    }
  }

  return labels;
}

function clone(options: SelectOptionList): SelectOptionList {
  return JSON.parse(JSON.stringify(options)) as SelectOptionList;
}

function randomSuffix(): string {
  return Math.random().toString(36).substring(2, 11);
}

export class Select {
  ariaLabel: string | null;
  badgeClass: string;
  canOptionLabelsWrap: boolean;
  canSelectPlaceholder: boolean;
  element: HTMLElement;
  getOptionLabelUsing: (() => Promise<unknown>) | null;
  getOptionLabelsUsing: (() => Promise<unknown>) | null;
  getOptionsUsing: (() => Promise<unknown>) | null;
  getSearchResultsUsing: ((query: string) => Promise<unknown>) | null;
  hasDynamicOptions: boolean;
  hasDynamicSearchResults: boolean;
  hasInitialNoOptionsMessage: boolean;
  id: string | null;
  initialOptionLabel: string | null;
  initialOptionLabels: SelectLabel[] | null;
  initialState: SelectState;
  isAutofocused: boolean;
  isDisabled: boolean;
  isHtmlAllowed: boolean;
  isMultiple: boolean;
  isReorderable: boolean;
  isSearchable: boolean;
  loadingMessage: string;
  maxItems: number | null;
  maxItemsMessage: string;
  noOptionsMessage: string;
  noSearchResultsMessage: string;
  onStateChange: (state: SelectState) => void;
  options: SelectOptionList;
  optionsLimit: number | null;
  originalOptions: SelectOptionList;
  placeholder: string;
  position: string | null;
  searchableOptionFields: string[];
  searchDebounce: number;
  searchingMessage: string;
  searchPrompt: string;
  state: SelectState;
  statePath: string | null;

  activeSearchId = 0;
  labelRepository: Record<string, string> = {};
  isOpen = false;
  selectedIndex = -1;
  searchQuery = "";
  searchTimeout: ReturnType<typeof setTimeout> | null = null;
  isSearching = false;
  maxItemsMessageElement: HTMLDivElement | null = null;
  selectedDisplayVersion = 0;

  container = document.createElement("div");
  selectButton = document.createElement("button");
  selectedDisplay = document.createElement("div");
  dropdown = document.createElement("div");
  optionsList = document.createElement("ul");
  statusRegion = document.createElement("div");
  dropdownId = "";
  searchContainer: HTMLDivElement | null = null;
  searchInput: HTMLInputElement | null = null;

  sortable: Sortable | null = null;
  buttonClickListener: (() => void) | null = null;
  documentClickListener: ((event: MouseEvent) => void) | null = null;
  buttonKeydownListener: ((event: KeyboardEvent) => void) | null = null;
  dropdownKeydownListener: ((event: KeyboardEvent) => void) | null = null;
  resizeListener: (() => void) | null = null;
  scrollListener: (() => void) | null = null;
  positioningRequestAnimationFrame: number | null = null;

  constructor({
    ariaLabel = null,
    badgeClass,
    canOptionLabelsWrap = true,
    canSelectPlaceholder = true,
    element,
    getOptionLabelUsing = null,
    getOptionLabelsUsing = null,
    getOptionsUsing = null,
    getSearchResultsUsing = null,
    hasDynamicOptions = false,
    hasDynamicSearchResults = true,
    hasInitialNoOptionsMessage = false,
    id = null,
    initialOptionLabel = null,
    initialOptionLabels = null,
    initialState = null,
    isAutofocused = false,
    isDisabled = false,
    isHtmlAllowed = false,
    isMultiple = false,
    isReorderable = false,
    isSearchable = false,
    loadingMessage = "Loading...",
    maxItems = null,
    maxItemsMessage = "Maximum number of items selected",
    noOptionsMessage = "No options available",
    noSearchResultsMessage = "No results found",
    onStateChange = () => {},
    options,
    optionsLimit = null,
    placeholder = "",
    position = null,
    searchableOptionFields = ["label"],
    searchDebounce = 1000,
    searchingMessage = "Searching...",
    searchPrompt = "Search...",
    state,
    statePath = null,
  }: SelectOptions) {
    this.ariaLabel = ariaLabel;
    this.badgeClass = badgeClass;
    this.canOptionLabelsWrap = canOptionLabelsWrap;
    this.canSelectPlaceholder = canSelectPlaceholder;
    this.element = element;
    this.getOptionLabelUsing = getOptionLabelUsing;
    this.getOptionLabelsUsing = getOptionLabelsUsing;
    this.getOptionsUsing = getOptionsUsing;
    this.getSearchResultsUsing = getSearchResultsUsing;
    this.hasDynamicOptions = hasDynamicOptions;
    this.hasDynamicSearchResults = hasDynamicSearchResults;
    this.hasInitialNoOptionsMessage = hasInitialNoOptionsMessage;
    this.id = id;
    this.initialOptionLabel = initialOptionLabel;
    this.initialOptionLabels = initialOptionLabels;
    this.initialState = initialState;
    this.isAutofocused = isAutofocused;
    this.isDisabled = isDisabled;
    this.isHtmlAllowed = isHtmlAllowed;
    this.isMultiple = isMultiple;
    this.isReorderable = isReorderable;
    this.isSearchable = isSearchable;
    this.loadingMessage = loadingMessage;
    this.maxItems = maxItems;
    this.maxItemsMessage = maxItemsMessage;
    this.noOptionsMessage = noOptionsMessage;
    this.noSearchResultsMessage = noSearchResultsMessage;
    this.onStateChange = onStateChange;
    this.options = options;
    this.optionsLimit = optionsLimit;
    this.originalOptions = clone(options);
    this.placeholder = placeholder;
    this.position = position;
    this.searchableOptionFields = Array.isArray(searchableOptionFields)
      ? searchableOptionFields
      : ["label"];
    this.searchDebounce = searchDebounce;
    this.searchingMessage = searchingMessage;
    this.searchPrompt = searchPrompt;
    this.state = state;
    this.statePath = statePath;

    this.render();
    this.setUpEventListeners();

    if (this.isAutofocused) {
      this.selectButton.focus();
    }
  }

  populateLabelRepositoryFromOptions(options: SelectOptionList): void {
    if (!Array.isArray(options)) {
      return;
    }

    for (const option of options) {
      if (isGroup(option)) {
        this.populateLabelRepositoryFromOptions(option.options);
      } else {
        this.labelRepository[option.value] = option.label;
      }
    }
  }

  render(): void {
    this.populateLabelRepositoryFromOptions(this.options);

    this.container.className = "sy-fo-select-ctn";

    if (!this.canOptionLabelsWrap) {
      this.container.classList.add("sy-fo-select-ctn-labels-not-wrapped");
    }

    this.selectButton.className = "sy-fo-select-btn";
    this.selectButton.type = "button";
    this.selectButton.setAttribute("role", "combobox");
    this.selectButton.setAttribute("aria-haspopup", "listbox");
    this.selectButton.setAttribute("aria-expanded", "false");

    if (filled(this.id)) {
      this.selectButton.id = this.id;
    }

    if (filled(this.ariaLabel)) {
      this.selectButton.setAttribute("aria-label", this.ariaLabel);
    }

    this.selectedDisplay.className = "sy-fo-select-value-ctn";

    void this.updateSelectedDisplay();

    this.selectButton.appendChild(this.selectedDisplay);

    this.dropdown.className = "sy-dropdown-panel sy-fo-select-dropdown";
    this.dropdown.setAttribute("role", "listbox");
    this.dropdown.setAttribute("tabindex", "-1");
    this.dropdown.style.display = "none";

    this.dropdownId = `sy-fo-select-dropdown-${randomSuffix()}`;
    this.dropdown.id = this.dropdownId;
    this.selectButton.setAttribute("aria-controls", this.dropdownId);

    if (this.isMultiple) {
      this.dropdown.setAttribute("aria-multiselectable", "true");
    }

    if (this.isSearchable) {
      const searchContainer = document.createElement("div");
      searchContainer.className = "sy-fo-select-search-ctn";

      const searchInput = document.createElement("input");
      searchInput.className = "sy-fo-input";
      searchInput.type = "text";
      searchInput.placeholder = this.searchPrompt;
      searchInput.setAttribute("aria-label", "Search");

      searchContainer.appendChild(searchInput);
      this.dropdown.appendChild(searchContainer);

      this.searchContainer = searchContainer;
      this.searchInput = searchInput;

      searchInput.addEventListener("input", () => {
        if (this.isDisabled) {
          return;
        }

        this.handleSearch(searchInput.value);
      });

      searchInput.addEventListener("keydown", (event) => {
        if (this.isDisabled) {
          return;
        }

        if (event.key === "Tab") {
          event.preventDefault();

          const options = this.getVisibleOptions();
          if (options.length === 0) return;

          if (event.shiftKey) {
            this.selectedIndex = options.length - 1;
          } else {
            this.selectedIndex = 0;
          }

          for (const option of options) {
            option.classList.remove("sy-selected");
          }

          options[this.selectedIndex].classList.add("sy-selected");
          options[this.selectedIndex].focus();
        } else if (event.key === "ArrowDown") {
          event.preventDefault();
          event.stopPropagation();

          const options = this.getVisibleOptions();
          if (options.length === 0) return;

          this.selectedIndex = -1;
          searchInput.blur();
          this.focusNextOption();
        } else if (event.key === "ArrowUp") {
          event.preventDefault();
          event.stopPropagation();

          const options = this.getVisibleOptions();
          if (options.length === 0) return;

          this.selectedIndex = options.length - 1;
          searchInput.blur();

          options[this.selectedIndex].classList.add("sy-selected");
          options[this.selectedIndex].focus();

          if (options[this.selectedIndex].id !== "") {
            this.dropdown.setAttribute("aria-activedescendant", options[this.selectedIndex].id);
          }

          this.scrollOptionIntoView(options[this.selectedIndex]);
        } else if (event.key === "Enter") {
          event.preventDefault();
          event.stopPropagation();

          if (this.isSearching) {
            return;
          }

          const options = this.getVisibleOptions();
          if (options.length === 0) {
            return;
          }

          const firstEnabled = options.find((option) => {
            const ariaDisabled = option.getAttribute("aria-disabled") === "true";
            const hasDisabledClass = option.classList.contains("sy-disabled");
            const isHidden = option.offsetParent === null;
            return !(ariaDisabled || hasDisabledClass || isHidden);
          });

          if (firstEnabled === undefined) {
            return;
          }

          const value = firstEnabled.getAttribute("data-value");
          if (value === null) {
            return;
          }

          this.selectOption(value);
        }
      });
    }

    this.statusRegion.className = "sy-fo-sr-only";
    this.statusRegion.setAttribute("role", "status");
    this.statusRegion.setAttribute("aria-live", "polite");
    this.statusRegion.setAttribute("aria-atomic", "true");

    this.renderOptions();

    this.container.appendChild(this.selectButton);
    this.container.appendChild(this.dropdown);
    this.container.appendChild(this.statusRegion);

    this.element.insertAdjacentElement("afterend", this.container);

    this.applyDisabledState();
  }

  renderOptions(): void {
    this.optionsList.innerHTML = "";

    let totalRenderedCount = 0;

    const optionsToRender = this.options;
    let optionsCount = 0;

    let hasGroupedOptions = false;

    for (const option of this.options) {
      if (isGroup(option)) {
        optionsCount += option.options.length;
        hasGroupedOptions = true;
      } else {
        optionsCount++;
      }
    }

    if (hasGroupedOptions) {
      this.optionsList.className = "sy-fo-select-options-ctn";
    } else if (optionsCount > 0) {
      this.optionsList.className = "sy-dropdown-list";
    }

    let ungroupedList: HTMLUListElement | null = hasGroupedOptions ? null : this.optionsList;

    let renderedCount = 0;

    for (const option of optionsToRender) {
      if (
        this.optionsLimit !== null &&
        this.optionsLimit > 0 &&
        renderedCount >= this.optionsLimit
      ) {
        break;
      }

      if (isGroup(option)) {
        let groupOptions = option.options;

        const state = this.state;

        if (this.isMultiple && Array.isArray(state) && state.length > 0) {
          groupOptions = option.options.filter((groupOption) => !state.includes(groupOption.value));
        }

        if (groupOptions.length > 0) {
          if (this.optionsLimit !== null && this.optionsLimit > 0) {
            const remainingSlots = this.optionsLimit - renderedCount;
            if (remainingSlots < groupOptions.length) {
              groupOptions = groupOptions.slice(0, remainingSlots);
            }
          }

          this.renderOptionGroup(option.label, groupOptions);
          renderedCount += groupOptions.length;
          totalRenderedCount += groupOptions.length;
        }
      } else {
        if (this.isMultiple && Array.isArray(this.state) && this.state.includes(option.value)) {
          continue;
        }

        if (ungroupedList === null) {
          ungroupedList = document.createElement("ul");
          ungroupedList.className = "sy-dropdown-list";
          this.optionsList.appendChild(ungroupedList);
        }

        const optionElement = this.createOptionElement(option.value, option);
        ungroupedList.appendChild(optionElement);
        renderedCount++;
        totalRenderedCount++;
      }
    }

    if (totalRenderedCount === 0) {
      if (this.searchQuery !== "") {
        this.showNoResultsMessage();
      } else if (this.hasInitialNoOptionsMessage || this.hasDynamicOptions) {
        this.showNoOptionsMessage();
      } else if (this.isMultiple && this.isOpen && !this.isSearchable) {
        this.closeDropdown();
      }

      if (this.optionsList.parentNode === this.dropdown) {
        this.dropdown.removeChild(this.optionsList);
      }
    } else {
      this.hideLoadingState();

      if (this.optionsList.parentNode !== this.dropdown) {
        this.dropdown.appendChild(this.optionsList);
      }
    }
  }

  renderOptionGroup(label: string, options: SelectOption[]): void {
    if (options.length === 0) {
      return;
    }

    const optionGroup = document.createElement("li");
    optionGroup.className = "sy-fo-select-option-group";

    const optionGroupLabel = document.createElement("div");
    optionGroupLabel.className = "sy-fo-select-option-group-label";
    optionGroupLabel.textContent = label;

    const groupOptionsList = document.createElement("ul");
    groupOptionsList.className = "sy-dropdown-list";

    for (const option of options) {
      groupOptionsList.appendChild(this.createOptionElement(option.value, option));
    }

    optionGroup.appendChild(optionGroupLabel);
    optionGroup.appendChild(groupOptionsList);
    this.optionsList.appendChild(optionGroup);
  }

  createOptionElement(value: string, option: SelectOption): HTMLLIElement {
    const optionValue = value;
    const optionLabel = option.label;
    const isDisabled = option.isDisabled ?? false;

    const element = document.createElement("li");
    element.className = "sy-dropdown-item sy-fo-select-option";

    if (isDisabled) {
      element.classList.add("sy-disabled");
    }

    element.id = `sy-fo-select-option-${randomSuffix()}`;

    element.setAttribute("role", "option");
    element.setAttribute("data-value", optionValue);
    element.setAttribute("tabindex", "0");

    if (isDisabled) {
      element.setAttribute("aria-disabled", "true");
    }

    if (this.isHtmlAllowed) {
      const tempDiv = document.createElement("div");
      tempDiv.innerHTML = optionLabel;
      element.setAttribute("aria-label", tempDiv.textContent || tempDiv.innerText || optionLabel);
    }

    const isSelected = this.isMultiple
      ? Array.isArray(this.state) && this.state.includes(optionValue)
      : this.state === optionValue;

    element.setAttribute("aria-selected", isSelected ? "true" : "false");

    if (isSelected) {
      element.classList.add("sy-selected");
    }

    const labelSpan = document.createElement("span");
    labelSpan.className = "sy-fo-select-option-label";

    if (this.isHtmlAllowed) {
      labelSpan.innerHTML = optionLabel;
    } else {
      labelSpan.textContent = optionLabel;
    }

    element.appendChild(labelSpan);

    if (!isDisabled) {
      element.addEventListener("click", (event) => {
        event.preventDefault();
        event.stopPropagation();
        this.selectOption(optionValue);

        if (this.isMultiple) {
          const searchInput = this.searchInput;

          if (this.isSearchable && searchInput !== null) {
            setTimeout(() => {
              searchInput.focus();
            }, 0);
          } else {
            setTimeout(() => {
              element.focus();
            }, 0);
          }
        }
      });
    }

    return element;
  }

  async updateSelectedDisplay(): Promise<void> {
    this.selectedDisplayVersion = this.selectedDisplayVersion + 1;
    const renderVersion = this.selectedDisplayVersion;

    const fragment = document.createDocumentFragment();

    if (this.isMultiple) {
      if (!Array.isArray(this.state) || this.state.length === 0) {
        this.sortable?.destroy();
        this.sortable = null;

        const placeholderSpan = document.createElement("span");
        placeholderSpan.textContent = this.placeholder;
        placeholderSpan.classList.add("sy-fo-select-placeholder");
        fragment.appendChild(placeholderSpan);
      } else {
        const selectedLabels = await this.getLabelsForMultipleSelection();
        if (renderVersion !== this.selectedDisplayVersion) return;
        this.addBadgesForSelectedOptions(selectedLabels, fragment);
      }

      if (renderVersion === this.selectedDisplayVersion) {
        this.selectedDisplay.replaceChildren(fragment);
        if (this.isOpen) {
          this.deferPositionDropdown();
        }
      }
      return;
    }

    if (this.state === null || this.state === "") {
      const placeholderSpan = document.createElement("span");
      placeholderSpan.textContent = this.placeholder;
      placeholderSpan.classList.add("sy-fo-select-placeholder");
      fragment.appendChild(placeholderSpan);

      if (renderVersion === this.selectedDisplayVersion) {
        this.selectedDisplay.replaceChildren(fragment);

        const existingRemoveButton = this.container.querySelector(".sy-fo-select-value-remove-btn");
        if (existingRemoveButton !== null) {
          existingRemoveButton.remove();
        }
        this.container.classList.remove("sy-fo-select-ctn-clearable");
      }
      return;
    }

    const selectedLabel = await this.getLabelForSingleSelection();
    if (renderVersion !== this.selectedDisplayVersion) return;

    this.addSingleSelectionDisplay(selectedLabel, fragment);

    if (renderVersion === this.selectedDisplayVersion) {
      this.selectedDisplay.replaceChildren(fragment);
    }
  }

  async getLabelsForMultipleSelection(): Promise<string[]> {
    const selectedLabels = this.getSelectedOptionLabels();

    const missingValues: string[] = [];
    if (Array.isArray(this.state)) {
      for (const value of this.state) {
        if (filled(this.labelRepository[value])) {
          continue;
        }

        if (filled(selectedLabels[value])) {
          this.labelRepository[value] = selectedLabels[value];
          continue;
        }

        missingValues.push(value);
      }
    }

    if (
      missingValues.length > 0 &&
      filled(this.initialOptionLabels) &&
      JSON.stringify(this.state) === JSON.stringify(this.initialState)
    ) {
      for (const initialOption of this.initialOptionLabels) {
        if (filled(initialOption) && missingValues.includes(initialOption.value)) {
          this.labelRepository[initialOption.value] = initialOption.label;
        }
      }
    } else if (missingValues.length > 0 && this.getOptionLabelsUsing !== null) {
      try {
        const fetchedOptions = normalizeLabels(await this.getOptionLabelsUsing());

        for (const option of fetchedOptions) {
          this.labelRepository[option.value] = option.label;
        }
      } catch (error) {
        console.error("Error fetching option labels:", error);
      }
    }

    const result: string[] = [];
    if (Array.isArray(this.state)) {
      for (const value of this.state) {
        if (filled(this.labelRepository[value])) {
          result.push(this.labelRepository[value]);
        } else if (filled(selectedLabels[value])) {
          result.push(selectedLabels[value]);
        } else {
          result.push(value);
        }
      }
    }

    return result;
  }

  createBadgeElement(value: string | null, label: string): HTMLSpanElement {
    const badge = document.createElement("span");
    badge.className = this.badgeClass;

    if (filled(value)) {
      badge.setAttribute("data-value", value);
    }

    const labelContainer = document.createElement("span");
    labelContainer.className = "sy-badge-label-ctn";

    const labelElement = document.createElement("span");
    labelElement.className = "sy-badge-label";

    if (this.canOptionLabelsWrap) {
      labelElement.classList.add("sy-wrapped");
    }

    if (this.isHtmlAllowed) {
      labelElement.innerHTML = label;
    } else {
      labelElement.textContent = label;
    }

    labelContainer.appendChild(labelElement);
    badge.appendChild(labelContainer);

    badge.appendChild(this.createRemoveButton(value, label));

    return badge;
  }

  createRemoveButton(value: string | null, label: string): HTMLButtonElement {
    const removeButton = document.createElement("button");
    removeButton.type = "button";
    removeButton.className = "sy-badge-delete-btn";
    removeButton.innerHTML = X_MARK_16;
    removeButton.setAttribute(
      "aria-label",
      `Remove ${this.isHtmlAllowed ? label.replace(/<[^>]*>/g, "") : label}`,
    );

    if (this.isDisabled) {
      removeButton.setAttribute("disabled", "disabled");
      removeButton.classList.add("sy-disabled");
    }

    removeButton.addEventListener("click", (event) => {
      event.stopPropagation();
      if (filled(value)) {
        this.selectOption(value);
      }
    });

    removeButton.addEventListener("keydown", (event) => {
      if (event.key === " " || event.key === "Enter") {
        event.preventDefault();
        event.stopPropagation();
        if (filled(value)) {
          this.selectOption(value);
        }
      }
    });

    return removeButton;
  }

  addBadgesForSelectedOptions(selectedLabels: string[], target: Node = this.selectedDisplay): void {
    const badgesContainer = document.createElement("div");
    badgesContainer.className = "sy-fo-select-value-badges-ctn";

    selectedLabels.forEach((label, index) => {
      const value = Array.isArray(this.state) ? this.state[index] : null;
      badgesContainer.appendChild(this.createBadgeElement(value, label));
    });

    target.appendChild(badgesContainer);

    this.sortable?.destroy();
    this.sortable = null;

    if (this.isReorderable) {
      badgesContainer.addEventListener("click", (event) => {
        event.stopPropagation();
      });

      badgesContainer.addEventListener("mousedown", (event) => {
        event.stopPropagation();
      });

      this.sortable = new Sortable(badgesContainer, {
        animation: 150,
        onEnd: () => {
          const newState: string[] = [];

          for (const badge of badgesContainer.querySelectorAll<HTMLElement>("[data-value]")) {
            newState.push(badge.getAttribute("data-value") ?? "");
          }

          this.state = newState;
          this.onStateChange(this.state);
        },
      });
    }
  }

  async getLabelForSingleSelection(): Promise<string> {
    const state = typeof this.state === "string" ? this.state : "";

    let selectedLabel = this.labelRepository[state];

    if (blank(selectedLabel)) {
      selectedLabel = this.getSelectedOptionLabel(state);
    }

    if (
      blank(selectedLabel) &&
      filled(this.initialOptionLabel) &&
      this.state === this.initialState
    ) {
      selectedLabel = this.initialOptionLabel;

      if (filled(state)) {
        this.labelRepository[state] = selectedLabel;
      }
    } else if (blank(selectedLabel) && this.getOptionLabelUsing !== null) {
      try {
        const fetched = await this.getOptionLabelUsing();
        selectedLabel = fetched === null || fetched === undefined ? "" : String(fetched);

        if (filled(selectedLabel) && filled(state)) {
          this.labelRepository[state] = selectedLabel;
        }
      } catch (error) {
        console.error("Error fetching option label:", error);
        selectedLabel = state;
      }
    } else if (blank(selectedLabel)) {
      selectedLabel = state;
    }

    return selectedLabel;
  }

  addSingleSelectionDisplay(selectedLabel: string, target: Node = this.selectedDisplay): void {
    const labelContainer = document.createElement("span");
    labelContainer.className = "sy-fo-select-value-label";

    if (this.isHtmlAllowed) {
      labelContainer.innerHTML = selectedLabel;
    } else {
      labelContainer.textContent = selectedLabel;
    }

    target.appendChild(labelContainer);

    if (!this.canSelectPlaceholder) {
      return;
    }

    if (this.container.querySelector(".sy-fo-select-value-remove-btn") !== null) {
      return;
    }

    const removeButton = document.createElement("button");
    removeButton.type = "button";
    removeButton.className = "sy-fo-select-value-remove-btn";
    removeButton.innerHTML = X_MARK_20;
    removeButton.setAttribute("aria-label", "Clear selection");

    if (this.isDisabled) {
      removeButton.setAttribute("disabled", "disabled");
      removeButton.classList.add("sy-disabled");
    }

    removeButton.addEventListener("click", (event) => {
      event.stopPropagation();
      this.selectOption("");
    });

    removeButton.addEventListener("keydown", (event) => {
      if (event.key === " " || event.key === "Enter") {
        event.preventDefault();
        event.stopPropagation();
        this.selectOption("");
      }
    });

    this.container.appendChild(removeButton);
    this.container.classList.add("sy-fo-select-ctn-clearable");
  }

  getSelectedOptionLabel(value: string): string {
    if (filled(this.labelRepository[value])) {
      return this.labelRepository[value];
    }

    let selectedLabel = "";

    for (const option of this.options) {
      if (isGroup(option)) {
        for (const groupOption of option.options) {
          if (groupOption.value === value) {
            selectedLabel = groupOption.label;
            this.labelRepository[value] = selectedLabel;
            break;
          }
        }
      } else if (option.value === value) {
        selectedLabel = option.label;
        this.labelRepository[value] = selectedLabel;
        break;
      }
    }

    return selectedLabel;
  }

  setUpEventListeners(): void {
    this.buttonClickListener = () => {
      this.toggleDropdown();
    };

    this.documentClickListener = (event: MouseEvent) => {
      const target = event.target;

      if (!this.container.contains(target instanceof Node ? target : null) && this.isOpen) {
        this.closeDropdown();
      }
    };

    this.buttonKeydownListener = (event: KeyboardEvent) => {
      if (this.isDisabled) {
        return;
      }

      this.handleSelectButtonKeydown(event);
    };

    this.dropdownKeydownListener = (event: KeyboardEvent) => {
      if (this.isDisabled) {
        return;
      }

      if (
        this.isSearchable &&
        document.activeElement === this.searchInput &&
        !["Tab", "Escape"].includes(event.key)
      ) {
        return;
      }

      this.handleDropdownKeydown(event);
    };

    this.selectButton.addEventListener("click", this.buttonClickListener);

    document.addEventListener("click", this.documentClickListener);

    this.selectButton.addEventListener("keydown", this.buttonKeydownListener);

    this.dropdown.addEventListener("keydown", this.dropdownKeydownListener);
  }

  updateOptionLabelInList(value: string, newLabel: string): void {
    this.labelRepository[value] = newLabel;

    const options = this.getVisibleOptions();
    for (const option of options) {
      if (option.getAttribute("data-value") === value) {
        option.innerHTML = "";

        const labelSpan = document.createElement("span");
        labelSpan.className = "sy-fo-select-option-label";

        if (this.isHtmlAllowed) {
          labelSpan.innerHTML = newLabel;
        } else {
          labelSpan.textContent = newLabel;
        }

        option.appendChild(labelSpan);

        break;
      }
    }

    for (const option of this.options) {
      if (isGroup(option)) {
        for (const groupOption of option.options) {
          if (groupOption.value === value) {
            groupOption.label = newLabel;
            break;
          }
        }
      } else if (option.value === value) {
        option.label = newLabel;
        break;
      }
    }

    for (const option of this.originalOptions) {
      if (isGroup(option)) {
        for (const groupOption of option.options) {
          if (groupOption.value === value) {
            groupOption.label = newLabel;
            break;
          }
        }
      } else if (option.value === value) {
        option.label = newLabel;
        break;
      }
    }
  }

  handleSelectButtonKeydown(event: KeyboardEvent): void {
    switch (event.key) {
      case "ArrowDown":
        event.preventDefault();
        event.stopPropagation();
        if (!this.isOpen) {
          void this.openDropdown();
        } else {
          this.focusNextOption();
        }
        break;
      case "ArrowUp":
        event.preventDefault();
        event.stopPropagation();
        if (!this.isOpen) {
          void this.openDropdown();
        } else {
          this.focusPreviousOption();
        }
        break;
      case " ": {
        event.preventDefault();
        if (this.isOpen) {
          if (this.selectedIndex >= 0) {
            const focusedOption = this.getVisibleOptions()[this.selectedIndex];
            if (focusedOption) {
              focusedOption.click();
            }
          }
        } else {
          void this.openDropdown();
        }
        break;
      }
      case "Enter":
        break;
      case "Escape":
        if (this.isOpen) {
          event.preventDefault();
          this.closeDropdown();
        }
        break;
      case "Tab":
        if (this.isOpen) {
          this.closeDropdown();
        }
        break;
      default: {
        if (
          this.isSearchable &&
          !event.ctrlKey &&
          !event.metaKey &&
          !event.altKey &&
          typeof event.key === "string" &&
          event.key.length === 1
        ) {
          event.preventDefault();
          const char = event.key;

          if (!this.isOpen) {
            void this.openDropdown();
          }

          const searchInput = this.searchInput;

          if (searchInput !== null) {
            searchInput.focus();
            searchInput.value = searchInput.value + char;
            searchInput.dispatchEvent(new Event("input", { bubbles: true }));
          }
        }
        break;
      }
    }
  }

  handleDropdownKeydown(event: KeyboardEvent): void {
    switch (event.key) {
      case "ArrowDown":
        event.preventDefault();
        event.stopPropagation();
        this.focusNextOption();
        break;
      case "ArrowUp":
        event.preventDefault();
        event.stopPropagation();
        this.focusPreviousOption();
        break;
      case " ": {
        event.preventDefault();
        if (this.selectedIndex >= 0) {
          const focusedOption = this.getVisibleOptions()[this.selectedIndex];
          if (focusedOption) {
            focusedOption.click();
          }
        }
        break;
      }
      case "Enter": {
        event.preventDefault();
        if (this.selectedIndex >= 0) {
          const focusedOption = this.getVisibleOptions()[this.selectedIndex];
          if (focusedOption) {
            focusedOption.click();
          }
        } else {
          const form = this.element.closest("form");
          if (form) {
            form.requestSubmit();
          }
        }
        break;
      }
      case "Escape":
        event.preventDefault();
        this.closeDropdown();
        this.selectButton.focus();
        break;
      case "Tab":
        this.closeDropdown();
        break;
      default: {
        if (
          this.isSearchable &&
          !event.ctrlKey &&
          !event.metaKey &&
          !event.altKey &&
          typeof event.key === "string" &&
          event.key.length === 1
        ) {
          event.preventDefault();
          const char = event.key;

          const searchInput = this.searchInput;

          if (searchInput !== null) {
            searchInput.focus();
            searchInput.value = searchInput.value + char;
            searchInput.dispatchEvent(new Event("input", { bubbles: true }));
          }
        }
        break;
      }
    }
  }

  toggleDropdown(): void {
    if (this.isDisabled) {
      return;
    }

    if (this.isOpen) {
      this.closeDropdown();
      return;
    }

    if (this.isMultiple && !this.isSearchable && !this.hasAvailableOptions()) {
      return;
    }

    void this.openDropdown();
  }

  hasAvailableOptions(): boolean {
    for (const option of this.options) {
      if (isGroup(option)) {
        for (const groupOption of option.options) {
          if (!Array.isArray(this.state) || !this.state.includes(groupOption.value)) {
            return true;
          }
        }
      } else if (!Array.isArray(this.state) || !this.state.includes(option.value)) {
        return true;
      }
    }

    return false;
  }

  async openDropdown(): Promise<void> {
    this.dropdown.style.display = "block";
    this.dropdown.style.opacity = "0";

    const useFixedPositioning =
      this.selectButton.closest(".sy-fixed-positioning-context") !== null &&
      this.selectButton.closest(".sy-absolute-positioning-context") === null;
    this.dropdown.style.position = useFixedPositioning ? "fixed" : "absolute";
    this.dropdown.style.width = `${this.selectButton.offsetWidth}px`;
    this.selectButton.setAttribute("aria-expanded", "true");
    this.isOpen = true;

    this.positionDropdown();

    if (this.resizeListener === null) {
      this.resizeListener = () => {
        this.dropdown.style.width = `${this.selectButton.offsetWidth}px`;
        this.positionDropdown();
      };
      window.addEventListener("resize", this.resizeListener);
    }

    if (this.scrollListener === null) {
      this.scrollListener = () => this.positionDropdown();
      window.addEventListener("scroll", this.scrollListener, true);
    }

    this.dropdown.style.opacity = "1";

    const searchInput = this.searchInput;

    if (this.isSearchable && searchInput !== null) {
      searchInput.value = "";
      this.searchQuery = "";

      if (!this.hasDynamicOptions) {
        this.options = clone(this.originalOptions);
        this.renderOptions();
      }
    }

    if (this.hasDynamicOptions && this.getOptionsUsing !== null) {
      this.showLoadingState(false);

      try {
        const normalizedFetched = normalizeOptions(await this.getOptionsUsing());

        this.options = normalizedFetched;
        this.originalOptions = clone(normalizedFetched);

        this.populateLabelRepositoryFromOptions(normalizedFetched);

        if (
          this.isSearchable &&
          searchInput !== null &&
          (searchInput.value.trim() !== "" || this.searchQuery.trim() !== "")
        ) {
          const query = (searchInput.value !== "" ? searchInput.value : this.searchQuery)
            .trim()
            .toLowerCase();

          this.hideLoadingState();
          this.filterOptions(query);
        } else {
          this.renderOptions();
        }
      } catch (error) {
        console.error("Error fetching options:", error);

        this.hideLoadingState();
      }
    } else if (!this.hasInitialNoOptionsMessage || this.searchQuery !== "") {
      this.hideLoadingState();
    }

    if (this.isSearchable && searchInput !== null) {
      searchInput.focus();
    } else {
      this.selectedIndex = -1;

      const options = this.getVisibleOptions();

      if (this.isMultiple) {
        if (Array.isArray(this.state) && this.state.length > 0) {
          for (let i = 0; i < options.length; i++) {
            const value = options[i].getAttribute("data-value");

            if (value !== null && this.state.includes(value)) {
              this.selectedIndex = i;
              break;
            }
          }
        }
      } else {
        for (let i = 0; i < options.length; i++) {
          if (options[i].getAttribute("data-value") === this.state) {
            this.selectedIndex = i;
            break;
          }
        }
      }

      if (this.selectedIndex === -1 && options.length > 0) {
        this.selectedIndex = 0;
      }

      if (this.selectedIndex >= 0) {
        options[this.selectedIndex].classList.add("sy-selected");
        options[this.selectedIndex].focus();
      }
    }
  }

  positionDropdown(): void {
    const placement = this.position === "top" ? "top-start" : "bottom-start";
    const middleware = [offset(4), shift({ padding: 5 })];

    if (this.position !== "top" && this.position !== "bottom") {
      middleware.push(flip());
    }

    const useFixedPositioning =
      this.selectButton.closest(".sy-fixed-positioning-context") !== null &&
      this.selectButton.closest(".sy-absolute-positioning-context") === null;

    void computePosition(this.selectButton, this.dropdown, {
      placement: placement,
      middleware: middleware,
      strategy: useFixedPositioning ? "fixed" : "absolute",
    }).then(({ x, y }) => {
      Object.assign(this.dropdown.style, {
        left: `${x}px`,
        top: `${y}px`,
      });
    });
  }

  deferPositionDropdown(): void {
    if (!this.isOpen) return;

    if (this.positioningRequestAnimationFrame !== null) {
      cancelAnimationFrame(this.positioningRequestAnimationFrame);
      this.positioningRequestAnimationFrame = null;
    }

    this.positioningRequestAnimationFrame = requestAnimationFrame(() => {
      this.positionDropdown();

      this.positioningRequestAnimationFrame = null;
    });
  }

  closeDropdown(): void {
    this.dropdown.style.display = "none";
    this.selectButton.setAttribute("aria-expanded", "false");
    this.isOpen = false;

    if (this.searchTimeout !== null) {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = null;
    }

    this.activeSearchId++;
    this.isSearching = false;

    this.hideLoadingState();
    this.hideMaxItemsMessage();

    if (this.resizeListener !== null) {
      window.removeEventListener("resize", this.resizeListener);
      this.resizeListener = null;
    }

    if (this.scrollListener !== null) {
      window.removeEventListener("scroll", this.scrollListener, true);
      this.scrollListener = null;
    }

    for (const option of this.getVisibleOptions()) {
      option.classList.remove("sy-selected");
    }

    this.dropdown.removeAttribute("aria-activedescendant");
  }

  focusNextOption(): void {
    const options = this.getVisibleOptions();
    if (options.length === 0) return;

    if (this.selectedIndex >= 0 && this.selectedIndex < options.length) {
      options[this.selectedIndex].classList.remove("sy-selected");
    }

    const searchInput = this.searchInput;

    if (this.selectedIndex === options.length - 1 && this.isSearchable && searchInput !== null) {
      this.selectedIndex = -1;
      searchInput.focus();
      this.dropdown.removeAttribute("aria-activedescendant");
      return;
    }

    this.selectedIndex = (this.selectedIndex + 1) % options.length;
    options[this.selectedIndex].classList.add("sy-selected");
    options[this.selectedIndex].focus();

    if (options[this.selectedIndex].id !== "") {
      this.dropdown.setAttribute("aria-activedescendant", options[this.selectedIndex].id);
    }

    this.scrollOptionIntoView(options[this.selectedIndex]);
  }

  focusPreviousOption(): void {
    const options = this.getVisibleOptions();
    if (options.length === 0) return;

    if (this.selectedIndex >= 0 && this.selectedIndex < options.length) {
      options[this.selectedIndex].classList.remove("sy-selected");
    }

    const searchInput = this.searchInput;

    if (
      (this.selectedIndex === 0 || this.selectedIndex === -1) &&
      this.isSearchable &&
      searchInput !== null
    ) {
      this.selectedIndex = -1;
      searchInput.focus();
      this.dropdown.removeAttribute("aria-activedescendant");
      return;
    }

    this.selectedIndex = (this.selectedIndex - 1 + options.length) % options.length;
    options[this.selectedIndex].classList.add("sy-selected");
    options[this.selectedIndex].focus();

    if (options[this.selectedIndex].id !== "") {
      this.dropdown.setAttribute("aria-activedescendant", options[this.selectedIndex].id);
    }

    this.scrollOptionIntoView(options[this.selectedIndex]);
  }

  scrollOptionIntoView(option: HTMLElement | undefined): void {
    if (!option) return;

    const dropdownRect = this.dropdown.getBoundingClientRect();
    const optionRect = option.getBoundingClientRect();

    if (optionRect.bottom > dropdownRect.bottom) {
      this.dropdown.scrollTop += optionRect.bottom - dropdownRect.bottom;
    } else if (optionRect.top < dropdownRect.top) {
      this.dropdown.scrollTop -= dropdownRect.top - optionRect.top;
    }
  }

  getVisibleOptions(): HTMLElement[] {
    let ungroupedOptions: HTMLElement[] = [];

    if (this.optionsList.classList.contains("sy-dropdown-list")) {
      ungroupedOptions = Array.from(
        this.optionsList.querySelectorAll<HTMLElement>(':scope > li[role="option"]'),
      );
    } else {
      ungroupedOptions = Array.from(
        this.optionsList.querySelectorAll<HTMLElement>(
          ':scope > ul.sy-dropdown-list > li[role="option"]',
        ),
      );
    }

    const groupOptions = Array.from(
      this.optionsList.querySelectorAll<HTMLElement>(
        'li.sy-fo-select-option-group > ul > li[role="option"]',
      ),
    );

    return [...ungroupedOptions, ...groupOptions];
  }

  getSelectedOptionLabels(): Record<string, string> {
    if (!Array.isArray(this.state) || this.state.length === 0) {
      return {};
    }

    const labels: Record<string, string> = {};

    for (const value of this.state) {
      let found = false;
      for (const option of this.options) {
        if (isGroup(option)) {
          for (const groupOption of option.options) {
            if (groupOption.value === value) {
              labels[value] = groupOption.label;
              found = true;
              break;
            }
          }
          if (found) break;
        } else if (option.value === value) {
          labels[value] = option.label;
          break;
        }
      }
    }

    return labels;
  }

  handleSearch(rawQuery: string): void {
    const query = rawQuery.trim();
    this.searchQuery = query;

    if (this.searchTimeout !== null) {
      clearTimeout(this.searchTimeout);
    }

    if (query === "") {
      this.activeSearchId++;
      this.isSearching = false;
      this.options = clone(this.originalOptions);
      this.renderOptions();
      return;
    }

    const getSearchResultsUsing = this.getSearchResultsUsing;

    if (getSearchResultsUsing === null || !this.hasDynamicSearchResults) {
      this.filterOptions(query);
      return;
    }

    this.searchTimeout = setTimeout(async () => {
      this.searchTimeout = null;

      const searchId = ++this.activeSearchId;
      this.isSearching = true;

      try {
        this.showLoadingState(true);

        const results = await getSearchResultsUsing(query);

        if (searchId !== this.activeSearchId || !this.isOpen) {
          return;
        }

        this.options = normalizeOptions(results);

        this.populateLabelRepositoryFromOptions(this.options);

        this.hideLoadingState();
        this.renderOptions();

        if (this.isOpen) {
          this.deferPositionDropdown();
        }

        if (this.options.length === 0) {
          this.showNoResultsMessage();
        }
      } catch (error) {
        if (searchId === this.activeSearchId) {
          console.error("Error fetching search results:", error);

          this.hideLoadingState();
          this.options = clone(this.originalOptions);
          this.renderOptions();
        }
      } finally {
        if (searchId === this.activeSearchId) {
          this.isSearching = false;
        }
      }
    }, this.searchDebounce);
  }

  showLoadingState(isSearching = false): void {
    if (this.optionsList.parentNode === this.dropdown) {
      this.dropdown.removeChild(this.optionsList);
    }

    this.hideLoadingState();

    const loadingItem = document.createElement("div");
    loadingItem.className = "sy-fo-select-message";
    loadingItem.textContent = isSearching ? this.searchingMessage : this.loadingMessage;
    this.dropdown.appendChild(loadingItem);

    if (this.isOpen) {
      this.deferPositionDropdown();

      this.statusRegion.textContent = loadingItem.textContent;
    }
  }

  hideLoadingState(): void {
    const loadingItem = this.dropdown.querySelector(".sy-fo-select-message");

    if (loadingItem !== null) {
      loadingItem.remove();

      this.statusRegion.textContent = "";
    }
  }

  showNoOptionsMessage(): void {
    if (this.optionsList.parentNode === this.dropdown) {
      this.dropdown.removeChild(this.optionsList);
    }

    this.hideLoadingState();

    const noOptionsItem = document.createElement("div");
    noOptionsItem.className = "sy-fo-select-message";
    noOptionsItem.textContent = this.noOptionsMessage;
    this.dropdown.appendChild(noOptionsItem);

    if (this.isOpen) {
      this.deferPositionDropdown();

      this.statusRegion.textContent = this.noOptionsMessage;
    }
  }

  showNoResultsMessage(): void {
    if (this.optionsList.parentNode === this.dropdown) {
      this.dropdown.removeChild(this.optionsList);
    }

    this.hideLoadingState();

    const noResultsItem = document.createElement("div");
    noResultsItem.className = "sy-fo-select-message";
    noResultsItem.textContent = this.noSearchResultsMessage;
    this.dropdown.appendChild(noResultsItem);

    if (this.isOpen) {
      this.deferPositionDropdown();

      this.statusRegion.textContent = this.noSearchResultsMessage;
    }
  }

  showMaxItemsMessage(): void {
    this.hideMaxItemsMessage();

    const maxItemsMessageElement = document.createElement("div");
    maxItemsMessageElement.className = "sy-fo-select-max-items-message";
    maxItemsMessageElement.textContent = this.maxItemsMessage;
    this.maxItemsMessageElement = maxItemsMessageElement;

    if (this.optionsList.parentNode === this.dropdown) {
      this.dropdown.insertBefore(maxItemsMessageElement, this.optionsList);
    } else {
      this.dropdown.appendChild(maxItemsMessageElement);
    }

    this.deferPositionDropdown();

    this.statusRegion.textContent = this.maxItemsMessage;
  }

  hideMaxItemsMessage(): void {
    if (this.maxItemsMessageElement === null) {
      return;
    }

    this.maxItemsMessageElement.remove();
    this.maxItemsMessageElement = null;

    if (this.statusRegion.textContent === this.maxItemsMessage) {
      this.statusRegion.textContent = "";
    }
  }

  filterOptions(query: string): void {
    const searchInLabel = this.searchableOptionFields.includes("label");
    const searchInValue = this.searchableOptionFields.includes("value");

    const search = query.toLowerCase();

    const filteredOptions: SelectOptionList = [];

    for (const option of this.originalOptions) {
      if (isGroup(option)) {
        const filteredGroupOptions = option.options.filter(
          (groupOption) =>
            (searchInLabel && groupOption.label.toLowerCase().includes(search)) ||
            (searchInValue && groupOption.value.toLowerCase().includes(search)),
        );

        if (filteredGroupOptions.length > 0) {
          filteredOptions.push({
            label: option.label,
            options: filteredGroupOptions,
          });
        }
      } else if (
        (searchInLabel && option.label.toLowerCase().includes(search)) ||
        (searchInValue && option.value.toLowerCase().includes(search))
      ) {
        filteredOptions.push(option);
      }
    }

    this.options = filteredOptions;

    this.renderOptions();

    if (this.options.length === 0) {
      this.showNoResultsMessage();
    }

    if (this.isOpen) {
      this.positionDropdown();
    }
  }

  selectOption(value: string): void {
    if (this.isDisabled) {
      return;
    }

    if (!this.isMultiple) {
      this.state = value;
      void this.updateSelectedDisplay();
      this.renderOptions();
      this.closeDropdown();
      this.selectButton.focus();
      this.onStateChange(this.state);
      return;
    }

    let newState = Array.isArray(this.state) ? [...this.state] : [];

    if (newState.includes(value)) {
      const badgeToRemove = this.selectedDisplay.querySelector(
        `[data-value="${CSS.escape(value)}"]`,
      );

      if (filled(badgeToRemove)) {
        const badgesContainer = badgeToRemove.parentElement;

        if (filled(badgesContainer) && badgesContainer.children.length === 1) {
          newState = newState.filter((entry) => entry !== value);
          this.state = newState;
          void this.updateSelectedDisplay();
        } else {
          badgeToRemove.remove();

          newState = newState.filter((entry) => entry !== value);
          this.state = newState;
        }
      } else {
        newState = newState.filter((entry) => entry !== value);
        this.state = newState;
        void this.updateSelectedDisplay();
      }

      this.hideMaxItemsMessage();

      this.renderOptions();

      if (this.isOpen) {
        this.deferPositionDropdown();
      }

      this.maintainFocusInMultipleMode();
      this.onStateChange(this.state);
      return;
    }

    if (this.maxItems !== null && newState.length >= this.maxItems) {
      if (this.maxItemsMessage !== "") {
        this.showMaxItemsMessage();
      }
      return;
    }

    this.hideMaxItemsMessage();

    newState.push(value);
    this.state = newState;

    const existingBadgesContainer = this.selectedDisplay.querySelector<HTMLElement>(
      ".sy-fo-select-value-badges-ctn",
    );

    if (!filled(existingBadgesContainer)) {
      void this.updateSelectedDisplay();
    } else {
      void this.addSingleBadge(value, existingBadgesContainer);
    }

    this.renderOptions();

    if (this.isOpen) {
      this.deferPositionDropdown();
    }

    this.maintainFocusInMultipleMode();
    this.onStateChange(this.state);
  }

  async addSingleBadge(value: string, badgesContainer: HTMLElement): Promise<void> {
    let label = this.labelRepository[value];

    if (blank(label)) {
      label = this.getSelectedOptionLabel(value);

      if (filled(label)) {
        this.labelRepository[value] = label;
      }
    }

    if (blank(label) && this.getOptionLabelsUsing !== null) {
      try {
        const fetchedOptions = normalizeLabels(await this.getOptionLabelsUsing());

        for (const option of fetchedOptions) {
          if (option.value === value) {
            label = option.label;
            this.labelRepository[value] = label;
            break;
          }
        }
      } catch (error) {
        console.error("Error fetching option label:", error);
      }
    }

    if (blank(label)) {
      label = value;
    }

    badgesContainer.appendChild(this.createBadgeElement(value, label));
  }

  maintainFocusInMultipleMode(): void {
    const searchInput = this.searchInput;

    if (this.isSearchable && searchInput !== null) {
      searchInput.focus();
      return;
    }

    const options = this.getVisibleOptions();
    if (options.length === 0) {
      return;
    }

    this.selectedIndex = -1;
    if (Array.isArray(this.state) && this.state.length > 0) {
      for (let i = 0; i < options.length; i++) {
        const value = options[i].getAttribute("data-value");

        if (value !== null && this.state.includes(value)) {
          this.selectedIndex = i;
          break;
        }
      }
    }

    if (this.selectedIndex === -1) {
      this.selectedIndex = 0;
    }

    options[this.selectedIndex].classList.add("sy-selected");
    options[this.selectedIndex].focus();
  }

  disable(): void {
    if (this.isDisabled) return;

    this.isDisabled = true;
    this.applyDisabledState();

    if (this.isOpen) {
      this.closeDropdown();
    }
  }

  enable(): void {
    if (!this.isDisabled) return;

    this.isDisabled = false;
    this.applyDisabledState();
  }

  applyDisabledState(): void {
    if (this.isDisabled) {
      this.selectButton.setAttribute("disabled", "disabled");
      this.selectButton.setAttribute("aria-disabled", "true");
      this.selectButton.classList.add("sy-disabled");

      if (this.isMultiple) {
        for (const button of this.container.querySelectorAll(".sy-badge-delete-btn")) {
          button.setAttribute("disabled", "disabled");
          button.classList.add("sy-disabled");
        }
      }

      if (!this.isMultiple && this.canSelectPlaceholder) {
        const removeButton = this.container.querySelector(".sy-fo-select-value-remove-btn");
        if (removeButton) {
          removeButton.setAttribute("disabled", "disabled");
          removeButton.classList.add("sy-disabled");
        }
      }

      if (this.isSearchable && this.searchInput !== null) {
        this.searchInput.setAttribute("disabled", "disabled");
        this.searchInput.classList.add("sy-disabled");
      }
    } else {
      this.selectButton.removeAttribute("disabled");
      this.selectButton.removeAttribute("aria-disabled");
      this.selectButton.classList.remove("sy-disabled");

      if (this.isMultiple) {
        for (const button of this.container.querySelectorAll(".sy-badge-delete-btn")) {
          button.removeAttribute("disabled");
          button.classList.remove("sy-disabled");
        }
      }

      if (!this.isMultiple && this.canSelectPlaceholder) {
        const removeButton = this.container.querySelector(".sy-fo-select-value-remove-btn");
        if (removeButton) {
          removeButton.removeAttribute("disabled");
          removeButton.classList.remove("sy-disabled");
        }
      }

      if (this.isSearchable && this.searchInput !== null) {
        this.searchInput.removeAttribute("disabled");
        this.searchInput.classList.remove("sy-disabled");
      }
    }
  }

  destroy(): void {
    if (this.buttonClickListener !== null) {
      this.selectButton.removeEventListener("click", this.buttonClickListener);
    }

    if (this.documentClickListener !== null) {
      document.removeEventListener("click", this.documentClickListener);
    }

    if (this.buttonKeydownListener !== null) {
      this.selectButton.removeEventListener("keydown", this.buttonKeydownListener);
    }

    if (this.dropdownKeydownListener !== null) {
      this.dropdown.removeEventListener("keydown", this.dropdownKeydownListener);
    }

    if (this.resizeListener !== null) {
      window.removeEventListener("resize", this.resizeListener);
      this.resizeListener = null;
    }

    if (this.scrollListener !== null) {
      window.removeEventListener("scroll", this.scrollListener, true);
      this.scrollListener = null;
    }

    if (this.isOpen) {
      this.closeDropdown();
    }

    if (this.searchTimeout !== null) {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = null;
    }

    if (this.positioningRequestAnimationFrame !== null) {
      cancelAnimationFrame(this.positioningRequestAnimationFrame);
      this.positioningRequestAnimationFrame = null;
    }

    this.sortable?.destroy();
    this.sortable = null;

    this.container.remove();
  }

  /**
   * Replace the server-rendered pool. While a search query is typed, only the fallback pool is
   * replaced — the results on screen stay — and the fresh pool takes over on the next clear; idle,
   * the list re-renders only when the pool actually changed, so an unrelated host render does not
   * drop keyboard focus from an open list.
   */
  replaceOptions(options: SelectOptionList): void {
    const next = clone(options);
    const changed = JSON.stringify(next) !== JSON.stringify(this.originalOptions);

    this.originalOptions = next;
    this.populateLabelRepositoryFromOptions(next);

    if (this.searchQuery !== "" || !changed) {
      return;
    }

    this.options = clone(next);
    this.renderOptions();
  }

  rememberLabels(entries: SelectLabel[]): void {
    for (const entry of entries) {
      this.labelRepository[entry.value] = entry.label;
    }
  }

  /** Repaint the selection and its option row from the repository, which the host's render has just refilled. */
  refreshLabels(): void {
    void this.updateSelectedDisplay();

    if (!this.isMultiple && typeof this.state === "string" && filled(this.state)) {
      const label = this.labelRepository[this.state];

      if (label !== undefined) {
        this.updateOptionLabelInList(this.state, label);
      }
    }
  }
}
