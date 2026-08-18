import { Controller } from "@hotwired/stimulus";
import { type Component, getComponent } from "@symfony/ux-live-component";
import TomSelect from "tom-select/base";
import type {
  RecursivePartial,
  TomItem,
  TomLoadCallback,
  TomOption,
  TomSettings,
} from "tom-select/dist/esm/types/index.js";
import clear_button from "tom-select/plugins/clear_button/plugin.js";
import dropdown_input from "tom-select/plugins/dropdown_input/plugin.js";
import { callSchemaComponentMethod } from "../utils/schema_component_method.js";

TomSelect.define("dropdown_input", dropdown_input);
TomSelect.define("clear_button", clear_button);

const REFRESH_EVENT = "sylament:select-refresh-selected-option-label";
const SEARCH_METHOD = "getSearchResultsForJs";
const CLEAR_BUTTON_CLASS = "sy-fo-select-clear-btn";
const X_MARK_16 =
  '<svg class="sy sy-icon sy-size-xs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M5.28 4.22a.75.75 0 0 0-1.06 1.06L6.94 8l-2.72 2.72a.75.75 0 1 0 1.06 1.06L8 9.06l2.72 2.72a.75.75 0 1 0 1.06-1.06L9.06 8l2.72-2.72a.75.75 0 0 0-1.06-1.06L8 6.94 5.28 4.22Z"/></svg>';
const X_MARK_20 =
  '<svg class="sy sy-icon sy-size-sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>';

type PoolOption = { value: string; label: string; isDisabled: boolean; optgroup?: string };
type PoolGroup = { value: string; label: string };
type Pool = { options: PoolOption[]; groups: PoolGroup[] };
type Binding = { render: boolean; debounce: number };
type Escape = (value: string) => string;

/**
 * Tom Select over the JS-branch `<select>`, bound to the host live component by hand: the
 * `<select>` carries no `data-model`, so this controller owns the write-back (`component.set()`
 * on Tom Select's `change`), the search round-trip (`callSchemaComponentMethod` →
 * `getSearchResultsForJs`) and the re-sync after every host render (`render:finished`), reading
 * the pool and the selection back from the just-morphed Stimulus values and the value store.
 */
export default class extends Controller<HTMLSelectElement> {
  static values = {
    key: String,
    statePath: String,
    modelBinding: String,
    options: Array,
    selectedOptions: Array,
    canSelectPlaceholder: Boolean,
    searchable: Boolean,
    dynamicSearchResults: Boolean,
    initialNoOptionsMessage: Boolean,
    searchDebounce: Number,
    optionsLimit: Number,
    maxItems: Number,
    maxItemsMessage: String,
    htmlAllowed: Boolean,
    labelsWrap: Boolean,
    position: String,
    searchFields: Array,
    placeholder: String,
    searchPrompt: String,
    searchingMessage: String,
    noSearchResultsMessage: String,
    noOptionsMessage: String,
    loadingMessage: String,
    badgeClass: String,
  };

  declare readonly keyValue: string;
  declare readonly statePathValue: string;
  declare readonly modelBindingValue: string;
  declare readonly optionsValue: unknown[];
  declare readonly selectedOptionsValue: unknown[];
  declare readonly canSelectPlaceholderValue: boolean;
  declare readonly searchableValue: boolean;
  declare readonly dynamicSearchResultsValue: boolean;
  declare readonly initialNoOptionsMessageValue: boolean;
  declare readonly searchDebounceValue: number;
  declare readonly optionsLimitValue: number;
  declare readonly hasMaxItemsValue: boolean;
  declare readonly maxItemsValue: number;
  declare readonly maxItemsMessageValue: string;
  declare readonly htmlAllowedValue: boolean;
  declare readonly labelsWrapValue: boolean;
  declare readonly positionValue: string;
  declare readonly searchFieldsValue: string[];
  declare readonly placeholderValue: string;
  declare readonly searchPromptValue: string;
  declare readonly searchingMessageValue: string;
  declare readonly noSearchResultsMessageValue: string;
  declare readonly noOptionsMessageValue: string;
  declare readonly loadingMessageValue: string;
  declare readonly badgeClassValue: string;

  #generation = 0;
  #component: Component | null = null;
  #ts: TomSelect | null = null;
  #binding: Binding = { render: true, debounce: 0 };
  #pool: Pool = { options: [], groups: [] };
  #searchId = 0;
  #visibleValues: Set<string> | null = null;
  #status: HTMLElement | null = null;
  #maxItemsMessage: HTMLElement | null = null;

  connect(): void {
    void this.#connect(++this.#generation);
  }

  disconnect(): void {
    this.#generation++;

    const component = this.#component;
    const ts = this.#ts;
    this.#component = null;
    this.#ts = null;
    this.#status = null;
    this.#maxItemsMessage = null;

    if (component !== null) {
      component.off("render:finished", this.#onRender);
      component.element.removeEventListener(REFRESH_EVENT, this.#onRefreshLabel);
    }

    if (ts !== null) {
      // destroy() reverts the <select> to its construction-time markup, stale after every
      // skip-morph render; the current markup is what stays valid.
      const html = this.element.innerHTML;
      ts.destroy();
      this.element.innerHTML = html;
      this.#restoreLabel();
    }
  }

  async #connect(generation: number): Promise<void> {
    const root = this.element.closest('[data-controller~="live"]');

    if (!(root instanceof HTMLElement)) {
      throw new Error("The sylament--forms--select controller needs a live component host.");
    }

    // The host's ExternalMutationTracker has to be observing when Tom Select inserts its wrapper,
    // or the first morph removes it — so the control is built only once the host resolved.
    const component = await resolveHost(root);

    if (generation !== this.#generation) {
      return;
    }

    this.#component = component;
    this.#binding = parseBinding(this.modelBindingValue);
    this.#pool = this.#serverPool();

    const ts = new TomSelect(this.element, this.#settings(component));
    this.#ts = ts;
    this.#decorate(ts);
    this.#syncAffordances();

    component.on("render:finished", this.#onRender);
    component.element.addEventListener(REFRESH_EVENT, this.#onRefreshLabel);
  }

  #settings(component: Component): RecursivePartial<TomSettings> {
    const multiple = this.element.multiple;
    const plugins: Record<string, object> = {};

    if (this.searchableValue) {
      plugins.dropdown_input = {};
    }

    if (!multiple && this.canSelectPlaceholderValue) {
      plugins.clear_button = {
        className: CLEAR_BUTTON_CLASS,
        html: () =>
          `<button type="button" class="${CLEAR_BUTTON_CLASS}" aria-label="Clear selection">${X_MARK_20}</button>`,
      };
    }

    return {
      valueField: "value",
      labelField: "label",
      disabledField: "isDisabled",
      optgroupField: "optgroup",
      optgroupLabelField: "label",
      optgroupValueField: "value",
      options: this.#pool.options.map((option) => ({ ...option })),
      optgroups: this.#pool.groups.map((group) => ({ ...group })),
      items: this.#items(component),
      maxItems: multiple ? (this.hasMaxItemsValue ? this.maxItemsValue : null) : 1,
      maxOptions: this.optionsLimitValue > 0 ? this.optionsLimitValue : null,
      plugins,
      openOnFocus: true,
      hidePlaceholder: true,
      hideSelected: multiple,
      ...(multiple ? {} : { closeAfterSelect: true }),
      searchField: [...this.searchFieldsValue],
      ...(this.placeholderValue === "" ? {} : { placeholder: this.placeholderValue }),
      wrapperClass: [
        "sy-fo-select-ctn",
        "ts-wrapper",
        this.labelsWrapValue ? "" : "sy-fo-select-ctn-labels-not-wrapped",
      ].join(" "),
      controlClass: "sy-fo-select-control ts-control",
      dropdownClass: [
        "sy-dropdown-panel",
        "sy-fo-select-dropdown",
        "ts-dropdown",
        this.positionValue === "top" ? "sy-position-top" : "",
      ].join(" "),
      dropdownContentClass: "sy-dropdown-list sy-fo-select-options ts-dropdown-content",
      itemClass: "sy-fo-select-item item",
      optionClass: "sy-dropdown-item sy-fo-select-option option",
      render: {
        option: (data: TomOption, escapeHtml: Escape) =>
          `<div><span class="sy-fo-select-option-label">${this.#labelHtml(data, escapeHtml)}</span></div>`,
        item: multiple
          ? (data: TomOption) => this.#chip(data)
          : (data: TomOption, escapeHtml: Escape) =>
              `<div><span class="sy-fo-select-item-label">${this.#labelHtml(data, escapeHtml)}</span></div>`,
        optgroup: (data: TomOption) => {
          const group = document.createElement("div");
          group.className = "sy-fo-select-option-group optgroup";
          group.appendChild(data.options);

          return group;
        },
        optgroup_header: (data: TomOption, escapeHtml: Escape) =>
          `<div class="sy-fo-select-option-group-label optgroup-header">${escapeHtml(String(data.label ?? ""))}</div>`,
        no_results: (_data: TomOption, escapeHtml: Escape) =>
          message(escapeHtml, this.noSearchResultsMessageValue),
        loading: (_data: TomOption, escapeHtml: Escape) =>
          message(escapeHtml, this.searchingMessageValue),
        not_loading: (_data: TomOption, escapeHtml: Escape) =>
          this.initialNoOptionsMessageValue && !this.#hasSelectableOption()
            ? message(escapeHtml, this.noOptionsMessageValue)
            : null,
      },
      onChange: () => this.#onChange(),
      onItemAdd: () => this.#syncAffordances(),
      onItemRemove: () => this.#syncAffordances(),
      ...(this.dynamicSearchResultsValue
        ? {
            load: this.#load,
            loadThrottle: this.searchDebounceValue,
            shouldLoad: (query: string) => query.length > 0,
            score: () => this.#score,
            onType: (query: string) => {
              if (query === "") {
                this.#restorePool();
              }
            },
            onDropdownClose: () => this.#restorePool(),
          }
        : {}),
    };
  }

  #decorate(ts: TomSelect): void {
    if (this.searchableValue) {
      ts.control_input.placeholder = this.searchPromptValue;
      ts.control_input.setAttribute("aria-label", "Search");
    } else {
      // Filament's non-searchable control has no text box: keep Tom Select's input for the
      // placeholder and the keyboard model, but never let it take typing.
      ts.control_input.readOnly = true;
      ts.control_input.setAttribute("inputmode", "none");
    }

    if (this.element.multiple) {
      ts.dropdown_content.setAttribute("aria-multiselectable", "true");

      const maxItemsMessage = document.createElement("div");
      maxItemsMessage.className = "sy-fo-select-max-items-message";
      maxItemsMessage.textContent = this.maxItemsMessageValue;
      maxItemsMessage.hidden = true;
      ts.control.insertAdjacentElement("afterend", maxItemsMessage);
      this.#maxItemsMessage = maxItemsMessage;
    }

    const status = document.createElement("div");
    status.className = "sy-fo-sr-only";
    status.setAttribute("role", "status");
    status.setAttribute("aria-live", "polite");
    status.setAttribute("aria-atomic", "true");
    ts.wrapper.append(status);
    this.#status = status;
  }

  #onChange(): void {
    const ts = this.#ts;
    const component = this.#component;

    if (ts === null || component === null) {
      return;
    }

    const value = ts.getValue();

    // Tom Select hands back its live `items` array; ValueStore.set() compares by reference.
    component.set(
      this.statePathValue,
      Array.isArray(value) ? [...value] : value,
      this.#binding.render,
      this.#binding.debounce,
    );
  }

  #onRender = (): void => {
    const ts = this.#ts;
    const component = this.#component;

    if (ts === null || component === null) {
      return;
    }

    this.#pool = this.#serverPool();
    const items = this.#items(component);

    if (this.dynamicSearchResultsValue && ts.inputValue() !== "") {
      // An unrelated render must not discard the search results on screen; only the selection's
      // own options are topped up so the value below can resolve.
      for (const option of this.#pool.options) {
        if (items.includes(option.value) && ts.options[option.value] === undefined) {
          ts.addOption({ ...option });
        }
      }
    } else {
      this.#syncPool(ts);
    }

    if (!sameList(items, ts.items)) {
      ts.setValue(items, true);
    }

    if (ts.isDisabled !== this.element.disabled) {
      if (this.element.disabled) {
        ts.disable();
      } else {
        ts.enable();
      }
    }

    this.#syncAffordances();
  };

  #onRefreshLabel = (event: Event): void => {
    const component = this.#component;

    if (
      component === null ||
      event.target !== component.element ||
      !(event instanceof CustomEvent)
    ) {
      return;
    }

    const detail: unknown = event.detail;

    if (isRecord(detail) && detail.statePath === this.statePathValue) {
      this.#onRender();
    }
  };

  #load = async (query: string, callback: TomLoadCallback): Promise<void> => {
    const ts = this.#ts;
    const component = this.#component;

    if (ts === null || component === null) {
      return;
    }

    if (query !== ts.inputValue()) {
      // The query was emptied (or the dropdown closed) while the debounce was pending — Tom
      // Select cancels a pending load only for a newer one — so this search is already moot.
      delete ts.loadedSearches[query];
      settleLoad(ts);

      return;
    }

    const searchId = ++this.#searchId;
    this.#visibleValues = new Set();

    if (ts.isOpen) {
      ts.refreshOptions(false);
    }

    this.#announce(this.searchingMessageValue);

    let result: unknown;

    try {
      result = await callSchemaComponentMethod(component, this.keyValue, SEARCH_METHOD, {
        search: query,
      });
    } catch {
      // The host has already surfaced the failure (a `response:error` renders the live error
      // overlay); the control falls back to the server-rendered pool, as Filament's does.
      if (this.#ts !== ts) {
        return;
      }

      if (searchId === this.#searchId) {
        this.#visibleValues = null;
      }

      settleLoad(ts);

      return;
    }

    if (this.#ts !== ts) {
      return;
    }

    if (searchId !== this.#searchId) {
      settleLoad(ts);

      return;
    }

    const { options, groups } = flatten(Array.isArray(result) ? result : []);
    this.#visibleValues = new Set(options.map((option) => option.value));
    ts.clearOptions();
    callback(options, groups);
    this.#announce(options.length === 0 ? this.noSearchResultsMessageValue : "");
  };

  /**
   * The server decides what a query matches: an option scores only while it belongs to the
   * last search response, or always while no search is active. Sifter's own scoring would
   * re-filter the response by its text, and a constant score would keep the selected option
   * in every result list — hiding `no_results` behind it.
   */
  #score = (...args: TomOption[]): number => {
    const option = args[0];

    if (this.#visibleValues === null) {
      return 1;
    }

    return option !== undefined && this.#visibleValues.has(String(option.value)) ? 1 : 0;
  };

  #restorePool(): void {
    const ts = this.#ts;

    if (ts === null) {
      return;
    }

    this.#searchId++;
    this.#visibleValues = null;
    this.#syncPool(ts);
  }

  /** Replace Tom Select's option pool with the cached server pool, re-rendering only what changed. */
  #syncPool(ts: TomSelect): void {
    ts.clearOptions();
    ts.clearOptionGroups();

    for (const group of this.#pool.groups) {
      ts.addOptionGroup(group.value, { label: group.label });
    }

    for (const option of this.#pool.options) {
      const existing = ts.options[option.value];

      if (existing === undefined) {
        ts.addOption({ ...option });
      } else if (!sameOption(existing, option)) {
        ts.updateOption(option.value, { ...option });
      }
    }

    if (ts.isOpen) {
      ts.refreshOptions(false);
    }
  }

  #syncAffordances(): void {
    const ts = this.#ts;

    if (ts === null) {
      return;
    }

    const clearButton = ts.control.querySelector<HTMLElement>(`.${CLEAR_BUTTON_CLASS}`);

    if (clearButton !== null) {
      clearButton.hidden = ts.items.length === 0;
    }

    // dropdown_input's placeholder box stands in for the selection until there is one.
    const itemsPlaceholder = ts.control.querySelector<HTMLElement>(".items-placeholder");

    if (itemsPlaceholder !== null) {
      itemsPlaceholder.hidden = ts.items.length > 0;
    }

    const maxItemsMessage = this.#maxItemsMessage;

    if (maxItemsMessage === null) {
      return;
    }

    const full = ts.isFull();

    if (full && maxItemsMessage.hidden) {
      maxItemsMessage.hidden = false;
      this.#announce(this.maxItemsMessageValue);
    } else if (!full && !maxItemsMessage.hidden) {
      maxItemsMessage.hidden = true;

      if (this.#status?.textContent === this.maxItemsMessageValue) {
        this.#announce("");
      }
    }
  }

  #serverPool(): Pool {
    const pool = flatten(this.optionsValue);
    const known = new Set(pool.options.map((option) => option.value));

    for (const entry of this.selectedOptionsValue) {
      if (!isRecord(entry)) {
        continue;
      }

      const value = String(entry.value ?? "");

      if (!known.has(value)) {
        known.add(value);
        pool.options.push({ value, label: String(entry.label ?? ""), isDisabled: false });
      }
    }

    return pool;
  }

  #items(component: Component): string[] {
    const raw: unknown = component.valueStore.get(this.statePathValue);

    if (Array.isArray(raw)) {
      return raw.filter((value) => !isBlank(value)).map(String);
    }

    return isBlank(raw) ? [] : [String(raw)];
  }

  #hasSelectableOption(): boolean {
    const ts = this.#ts;

    if (ts === null) {
      return false;
    }

    const multiple = this.element.multiple;

    return this.#pool.options.some((option) => !multiple || !ts.items.includes(option.value));
  }

  #labelHtml(data: TomOption, escapeHtml: Escape): string {
    const label = String(data.label ?? "");

    return this.htmlAllowedValue ? label : escapeHtml(label);
  }

  #plainLabel(data: TomOption): string {
    const label = String(data.label ?? "");

    if (!this.htmlAllowedValue) {
      return label;
    }

    const template = document.createElement("template");
    template.innerHTML = label;

    return template.content.textContent ?? "";
  }

  #chip(data: TomOption): HTMLElement {
    const chip = document.createElement("div");
    chip.className = this.badgeClassValue;

    const labelContainer = document.createElement("span");
    labelContainer.className = "sy-badge-label-ctn";

    const label = document.createElement("span");
    label.className = this.labelsWrapValue ? "sy-badge-label sy-wrapped" : "sy-badge-label";

    if (this.htmlAllowedValue) {
      label.innerHTML = String(data.label ?? "");
    } else {
      label.textContent = String(data.label ?? "");
    }

    const remove = document.createElement("button");
    remove.type = "button";
    remove.className = "sy-badge-delete-btn";
    remove.setAttribute("aria-label", `Remove ${this.#plainLabel(data)}`);
    remove.innerHTML = X_MARK_16;
    remove.addEventListener("mousedown", (event) => {
      event.preventDefault();
      event.stopPropagation();
    });
    remove.addEventListener("click", (event) => {
      event.preventDefault();
      event.stopPropagation();
      this.#removeChip(remove);
    });

    labelContainer.append(label);
    chip.append(labelContainer, remove);

    return chip;
  }

  #removeChip(button: HTMLElement): void {
    const ts = this.#ts;
    const item = button.closest("[data-ts-item]");

    if (ts === null || ts.isLocked || !isTomItem(item)) {
      return;
    }

    ts.removeItem(item);
    ts.refreshOptions(false);
    ts.inputState();
  }

  #restoreLabel(): void {
    const id = this.element.id;

    if (id === "") {
      return;
    }

    // Tom Select re-points the field's <label for> at its own control and never points it back.
    document
      .querySelector(`label[for="${CSS.escape(`${id}-ts-control`)}"]`)
      ?.setAttribute("for", id);
  }

  #announce(text: string): void {
    if (this.#status !== null) {
      this.#status.textContent = text;
    }
  }
}

async function resolveHost(root: HTMLElement): Promise<Component> {
  try {
    return await getComponent(root);
  } catch {
    // Not registered yet (controller registration order, a lazy live controller): wait for the
    // host's own `live:connect` — a nested host's bubbles through here too, hence the target
    // check — and ask once more.
    await new Promise<void>((resolve) => {
      const onConnect = (event: Event): void => {
        if (event.target !== root) {
          return;
        }

        root.removeEventListener("live:connect", onConnect);
        resolve();
      };

      root.addEventListener("live:connect", onConnect);
    });

    return getComponent(root);
  }
}

/**
 * The `getDataModel()` directive, read once for its modifiers: `norender|…`, `on(change)|…`,
 * `debounce(N)|…` or the bare path. The path itself is never taken from here — `statePath` is
 * the only string handed to the value store.
 */
function parseBinding(directive: string): Binding {
  const binding: Binding = { render: true, debounce: 0 };
  const modifiers = directive.split("|");
  modifiers.pop();

  for (const modifier of modifiers) {
    const match = /^(\w+)(?:\((.*)\))?$/.exec(modifier.trim());

    if (match === null) {
      continue;
    }

    const [, name, argument = ""] = match;

    if (name === "norender") {
      binding.render = false;
    } else if (name === "debounce") {
      binding.debounce = Number.parseInt(argument, 10) || 0;
    }
  }

  return binding;
}

/** Filament's option payload — flat `{label, value, isDisabled}` entries and `{label, options}` groups — as Tom Select's flat list plus optgroups keyed by group label. */
function flatten(entries: unknown[]): Pool {
  const options: PoolOption[] = [];
  const groups: PoolGroup[] = [];
  const groupValues = new Set<string>();

  const add = (entry: unknown, optgroup?: string): void => {
    if (!isRecord(entry)) {
      return;
    }

    const option: PoolOption = {
      value: String(entry.value ?? ""),
      label: String(entry.label ?? ""),
      isDisabled: Boolean(entry.isDisabled),
    };

    if (optgroup !== undefined) {
      option.optgroup = optgroup;
    }

    options.push(option);
  };

  for (const entry of entries) {
    if (!isRecord(entry)) {
      continue;
    }

    if (Array.isArray(entry.options)) {
      const label = String(entry.label ?? "");

      if (!groupValues.has(label)) {
        groupValues.add(label);
        groups.push({ value: label, label });
      }

      for (const option of entry.options) {
        add(option, label);
      }
    } else {
      add(entry);
    }
  }

  return { options, groups };
}

function message(escapeHtml: Escape, text: string): string {
  return `<div class="sy-fo-select-message">${escapeHtml(text)}</div>`;
}

/**
 * Balance a discarded search the way `loadCallback()` would, minus its focus-driven
 * `refreshOptions(true)`, which would reopen a dropdown the user has since closed.
 */
function settleLoad(ts: TomSelect): void {
  ts.loading = Math.max(ts.loading - 1, 0);

  if (ts.loading === 0) {
    ts.wrapper.classList.remove(ts.settings.loadingClass);
  }

  if (ts.isOpen) {
    ts.refreshOptions(false);
  }
}

function sameOption(existing: TomOption, option: PoolOption): boolean {
  return (
    existing.label === option.label &&
    Boolean(existing.isDisabled) === option.isDisabled &&
    (existing.optgroup ?? undefined) === option.optgroup
  );
}

function sameList(a: string[], b: string[]): boolean {
  return a.length === b.length && a.every((value, index) => value === b[index]);
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === "object" && value !== null;
}

function isBlank(value: unknown): boolean {
  return value === null || value === undefined || value === "";
}

function isTomItem(element: Element | null): element is TomItem {
  return element instanceof HTMLElement && typeof element.dataset.value === "string";
}
