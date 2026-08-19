import { Controller } from "@hotwired/stimulus";
import { callSchemaComponentMethod } from "@sylament/schemas/utils/schema_component_method.js";
import { type Component, getComponent } from "@symfony/ux-live-component";
import {
  Select,
  type SelectLabel,
  type SelectOption,
  type SelectOptionGroup,
  type SelectOptionList,
  type SelectState,
} from "../utils/select.js";

const REFRESH_EVENT = "sylament:select-refresh-selected-option-label";
const SEARCH_METHOD = "getSearchResultsForJs";
const INLINE_PREFIX_CLASS = "sy-fo-select-has-inline-prefix";
const CONTAINER_INLINE_PREFIX_CLASS = "sy-fo-select-ctn-has-inline-prefix";

type Binding = { render: boolean; debounce: number };

/**
 * Filament's select utility (`utils/select.ts`) over the JS-branch `<select>`, bound to the host
 * live component by hand: the `<select>` carries no `data-model`, so this controller owns the
 * write-back (`component.set()` on the engine's state change), the search round-trip
 * (`callSchemaComponentMethod` → `getSearchResultsForJs`) and the re-sync after every host render
 * (`render:finished`), feeding the engine the pool, the labels and the value the morph just
 * re-rendered. The `<select>` itself is hidden and lends its `id` to the engine's combobox button.
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
    reorderable: Boolean,
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
  declare readonly hasPositionValue: boolean;
  declare readonly positionValue: string;
  declare readonly reorderableValue: boolean;
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
  #select: Select | null = null;
  #binding: Binding = { render: true, debounce: 0 };
  #id = "";

  connect(): void {
    void this.#connect(++this.#generation);
  }

  disconnect(): void {
    this.#generation++;

    const component = this.#component;
    const select = this.#select;
    this.#component = null;
    this.#select = null;

    if (component !== null) {
      component.off("render:finished", this.#onRender);
      component.element.removeEventListener(REFRESH_EVENT, this.#onRefreshLabel);
    }

    select?.destroy();

    if (this.#id !== "") {
      this.element.id = this.#id;
      this.#id = "";
    }

    this.element.style.removeProperty("display");
  }

  reorderableValueChanged(): void {
    const select = this.#select;

    if (select === null) {
      return;
    }

    select.isReorderable = this.reorderableValue;
    void select.updateSelectedDisplay();
  }

  async #connect(generation: number): Promise<void> {
    const root = this.element.closest('[data-controller~="live"]');

    if (!(root instanceof HTMLElement)) {
      throw new Error("The sylament--forms--select controller needs a live component host.");
    }

    const component = await resolveHost(root);

    if (generation !== this.#generation) {
      return;
    }

    this.#component = component;
    this.#binding = parseBinding(this.modelBindingValue);

    const multiple = this.element.multiple;
    const state = this.#state(component);
    const selectedOptions = labelsOf(this.selectedOptionsValue);

    this.#id = this.element.id;
    this.element.removeAttribute("id");
    this.element.style.display = "none";

    const select = new Select({
      element: this.element,
      id: this.#id === "" ? null : this.#id,
      badgeClass: this.badgeClassValue,
      canOptionLabelsWrap: this.labelsWrapValue,
      canSelectPlaceholder: this.canSelectPlaceholderValue,
      getOptionLabelUsing: null,
      getOptionLabelsUsing: null,
      getOptionsUsing: null,
      getSearchResultsUsing: this.dynamicSearchResultsValue
        ? (query: string) =>
            callSchemaComponentMethod(component, this.keyValue, SEARCH_METHOD, {
              search: query,
            }).then((results) => optionsOf(Array.isArray(results) ? results : []))
        : null,
      hasDynamicOptions: false,
      hasDynamicSearchResults: this.dynamicSearchResultsValue,
      hasInitialNoOptionsMessage: this.initialNoOptionsMessageValue,
      initialOptionLabel:
        !multiple && typeof state === "string" && state !== ""
          ? (selectedOptions.find((entry) => entry.value === state)?.label ?? null)
          : null,
      initialOptionLabels: multiple ? selectedOptions : [],
      initialState: state,
      isAutofocused: this.element.autofocus,
      isDisabled: this.element.disabled,
      isHtmlAllowed: this.htmlAllowedValue,
      isMultiple: multiple,
      isReorderable: this.reorderableValue,
      isSearchable: this.searchableValue,
      loadingMessage: this.loadingMessageValue,
      maxItems: this.hasMaxItemsValue ? this.maxItemsValue : null,
      maxItemsMessage: this.maxItemsMessageValue,
      noOptionsMessage: this.noOptionsMessageValue,
      noSearchResultsMessage: this.noSearchResultsMessageValue,
      onStateChange: (value) => this.#onStateChange(value),
      options: optionsOf(this.optionsValue),
      optionsLimit: this.optionsLimitValue,
      placeholder: this.placeholderValue,
      position: this.hasPositionValue ? this.positionValue : null,
      searchableOptionFields: [...this.searchFieldsValue],
      searchDebounce: this.searchDebounceValue,
      searchingMessage: this.searchingMessageValue,
      searchPrompt: this.searchPromptValue,
      state,
      statePath: this.statePathValue,
    });

    select.rememberLabels(selectedOptions);

    if (this.element.classList.contains(INLINE_PREFIX_CLASS)) {
      select.container.classList.add(CONTAINER_INLINE_PREFIX_CLASS);
    }

    this.#mirrorAria(select);
    this.#select = select;

    component.on("render:finished", this.#onRender);
    component.element.addEventListener(REFRESH_EVENT, this.#onRefreshLabel);
  }

  #onStateChange(value: SelectState): void {
    const component = this.#component;

    if (component === null) {
      return;
    }

    component.set(
      this.statePathValue,
      Array.isArray(value) ? [...value] : (value ?? ""),
      this.#binding.render,
      this.#binding.debounce,
    );
  }

  #onRender = (): void => {
    const select = this.#select;
    const component = this.#component;

    if (select === null || component === null) {
      return;
    }

    select.replaceOptions(optionsOf(this.optionsValue));
    select.rememberLabels(labelsOf(this.selectedOptionsValue));

    const state = this.#state(component);

    if (!sameState(state, select.state, this.element.multiple, this.reorderableValue)) {
      select.state = state;
      void select.updateSelectedDisplay();
      select.renderOptions();
    }

    if (this.element.disabled) {
      select.disable();
    } else {
      select.enable();
    }

    this.#mirrorAria(select);
  };

  /** The hidden `<select>` is what the server marks invalid or required; the combobox button is what the user operates. */
  #mirrorAria(select: Select): void {
    for (const name of ["aria-invalid", "aria-required"]) {
      const value = this.element.getAttribute(name);

      if (value === null) {
        select.selectButton.removeAttribute(name);
      } else {
        select.selectButton.setAttribute(name, value);
      }
    }
  }

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
      this.#select?.refreshLabels();
    }
  };

  /** The value store's entry for the state path, as the strings the engine compares. */
  #state(component: Component): SelectState {
    const raw: unknown = component.valueStore.get(this.statePathValue);

    if (this.element.multiple) {
      if (Array.isArray(raw)) {
        return raw.filter((value) => !isBlank(value)).map(String);
      }

      return isBlank(raw) ? [] : [String(raw)];
    }

    return isBlank(raw) ? "" : String(raw);
  }
}

async function resolveHost(root: HTMLElement): Promise<Component> {
  try {
    return await getComponent(root);
  } catch {
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

/** Filament's option payload — `{label, value, isDisabled}` entries and `{label, options}` groups — as the engine's list; a malformed entry is dropped. */
function optionsOf(entries: unknown[]): SelectOptionList {
  const list: SelectOptionList = [];

  for (const entry of entries) {
    if (!isRecord(entry)) {
      continue;
    }

    if (Array.isArray(entry.options)) {
      const group: SelectOptionGroup = { label: String(entry.label ?? ""), options: [] };

      for (const option of entry.options) {
        if (isRecord(option)) {
          group.options.push(optionOf(option));
        }
      }

      list.push(group);
    } else {
      list.push(optionOf(entry));
    }
  }

  return list;
}

function optionOf(entry: Record<string, unknown>): SelectOption {
  return {
    label: String(entry.label ?? ""),
    value: String(entry.value ?? ""),
    isDisabled: Boolean(entry.isDisabled),
  };
}

function labelsOf(entries: unknown[]): SelectLabel[] {
  const labels: SelectLabel[] = [];

  for (const entry of entries) {
    if (isRecord(entry)) {
      labels.push({ value: String(entry.value ?? ""), label: String(entry.label ?? "") });
    }
  }

  return labels;
}

/**
 * A multiple selection compares as a list when its order is state (`reorderable()`) and as a
 * multiset otherwise, so an unrelated render does not rebuild the chips over a mere reordering.
 */
function sameState(a: SelectState, b: SelectState, multiple: boolean, ordered: boolean): boolean {
  if (!multiple) {
    return a === b;
  }

  const left = Array.isArray(a) ? (ordered ? a : [...a].sort()) : [];
  const right = Array.isArray(b) ? (ordered ? b : [...b].sort()) : [];

  return left.length === right.length && left.every((value, index) => value === right[index]);
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === "object" && value !== null;
}

function isBlank(value: unknown): boolean {
  return value === null || value === undefined || value === "";
}
