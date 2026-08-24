import { Controller } from "@hotwired/stimulus";
import type { Component } from "@symfony/ux-live-component";

import { resolveLiveHost } from "../utils/live_host.js";

const SELECTED_MODEL = "selectedTableRecords";
const DESELECTED_MODEL = "deselectedTableRecords";
const TRACKING_MODEL = "isTrackingDeselectedTableRecords";
const SELECT_ALL_ACTION = "selectAllTableRecords";

const RECORD_CHECKBOX_SELECTOR = ".sy-ta-record-checkbox";
const BULK_ONLY_SELECTOR = "[data-sy-table-bulk-only]";
const COUNT_PLACEHOLDER = ":count";

/** The client working copy of the host's three selection props (Filament's two `Set`s + flag). */
type SelectionState = {
  selected: Set<string>;
  deselected: Set<string>;
  tracking: boolean;
};

/**
 * Record selection over the live host's ValueStore (Filament's `filamentTable` Alpine data): every
 * tick writes the three selection models with `reRender: false`, so N ticks cost zero requests and
 * the next action carries the whole selection, and the checkboxes, the header's tri-state box, the
 * selection banner and every bulk-only trigger are repainted from that store on connect and after
 * every morph.
 *
 * Two select-all strategies, as Filament: with deselect-tracking available the flag flips and both
 * lists empty — "all 40 000 selected" for free — otherwise the host's `selectAllTableRecords` live
 * action enumerates the keys server-side (our divergence from Filament's value-returning call).
 */
export default class extends Controller<HTMLElement> {
  static targets = [
    "recordCheckbox",
    "pageCheckbox",
    "banner",
    "count",
    "selectAll",
    "deselectAll",
  ];

  static values = {
    canTrack: { type: Boolean, default: false },
    pageOnly: { type: Boolean, default: false },
    allSelectableCount: Number,
    countOne: String,
    countMany: String,
  };

  declare readonly recordCheckboxTargets: HTMLInputElement[];
  declare readonly hasPageCheckboxTarget: boolean;
  declare readonly pageCheckboxTarget: HTMLInputElement;
  declare readonly hasBannerTarget: boolean;
  declare readonly bannerTarget: HTMLElement;
  declare readonly hasCountTarget: boolean;
  declare readonly countTarget: HTMLElement;
  declare readonly hasSelectAllTarget: boolean;
  declare readonly selectAllTarget: HTMLElement;

  declare readonly canTrackValue: boolean;
  declare readonly pageOnlyValue: boolean;
  declare readonly hasAllSelectableCountValue: boolean;
  declare readonly allSelectableCountValue: number;
  declare readonly countOneValue: string;
  declare readonly countManyValue: string;

  #component: Component | null = null;
  #unsubscribe: (() => void) | null = null;
  #clickController: AbortController | null = null;
  #lastChecked: HTMLInputElement | null = null;
  #generation = 0;

  async connect(): Promise<void> {
    const generation = ++this.#generation;
    let component: Component;

    try {
      component = await resolveLiveHost(this.element);
    } catch {
      // No live host answered, so there is nowhere to write a selection: leave the checkboxes
      // inert rather than throwing on every tick. Selection needs both halves, and the missing
      // half is the one this controller cannot supply.
      return;
    }

    if (generation !== this.#generation) {
      return;
    }

    this.#component = component;

    const onRender = (): void => this.#repaint();
    component.on("render:finished", onRender);
    this.#unsubscribe = (): void => component.off("render:finished", onRender);

    this.#watchShiftClicks();
    this.#repaint();
  }

  disconnect(): void {
    this.#generation++;

    this.#unsubscribe?.();
    this.#unsubscribe = null;

    this.#clickController?.abort();
    this.#clickController = null;

    this.#component = null;
    this.#lastChecked = null;
  }

  /** The ceiling is re-stamped by every morph, and the select-all link's visibility rides on it. */
  allSelectableCountValueChanged(): void {
    this.#repaint();
  }

  toggleRecord(event: Event): void {
    const checkbox = event.target;

    if (!(checkbox instanceof HTMLInputElement)) {
      return;
    }

    const state = this.#state();

    if (this.#isSelected(state, checkbox.value)) {
      this.#deselectRecords(state, [checkbox.value]);
    } else {
      this.#selectRecords(state, [checkbox.value]);
    }

    this.#push(state);
    this.#repaint();
  }

  togglePage(): void {
    const state = this.#state();
    const keys = this.#pageKeys();

    if (this.#areSelected(state, keys)) {
      this.#deselectRecords(state, keys);
    } else {
      this.#selectRecords(state, keys);
    }

    this.#push(state);
    this.#repaint();
  }

  selectAll(): void {
    if (this.canTrackValue && !this.pageOnlyValue) {
      this.#push({ selected: new Set(), deselected: new Set(), tracking: true });
      this.#repaint();

      return;
    }

    if (this.pageOnlyValue) {
      const state = this.#state();
      this.#selectRecords(state, this.#pageKeys());
      this.#push(state);
      this.#repaint();

      return;
    }

    this.#component?.action(SELECT_ALL_ACTION);
  }

  deselectAll(): void {
    this.#push({ selected: new Set(), deselected: new Set(), tracking: false });
    this.#repaint();
  }

  #state(): SelectionState {
    const component = this.#component;

    if (component === null) {
      return { selected: new Set(), deselected: new Set(), tracking: false };
    }

    return {
      selected: new Set(toKeys(component.getData(SELECTED_MODEL))),
      deselected: new Set(toKeys(component.getData(DESELECTED_MODEL))),
      tracking: component.getData(TRACKING_MODEL) === true,
    };
  }

  #push(next: SelectionState): void {
    const component = this.#component;

    if (component === null) {
      return;
    }

    component.set(TRACKING_MODEL, next.tracking, false);
    component.set(SELECTED_MODEL, [...next.selected], false);
    component.set(DESELECTED_MODEL, [...next.deselected], false);
  }

  #selectRecords(state: SelectionState, keys: string[]): void {
    for (const key of keys) {
      if (state.tracking) {
        state.deselected.delete(key);
      } else {
        state.selected.add(key);
      }
    }
  }

  #deselectRecords(state: SelectionState, keys: string[]): void {
    for (const key of keys) {
      if (state.tracking) {
        state.deselected.add(key);
      } else {
        state.selected.delete(key);
      }
    }
  }

  #isSelected(state: SelectionState, key: string): boolean {
    return state.tracking ? !state.deselected.has(key) : state.selected.has(key);
  }

  /** Filament's `areRecordsSelected`: an empty page reads as selected, so callers guard on length. */
  #areSelected(state: SelectionState, keys: string[]): boolean {
    return keys.every((key) => this.#isSelected(state, key));
  }

  #pageKeys(): string[] {
    return this.recordCheckboxTargets.map((checkbox) => checkbox.value);
  }

  #selectedCount(state: SelectionState): number {
    if (!state.tracking) {
      return state.selected.size;
    }

    if (this.hasAllSelectableCountValue) {
      return Math.max(this.allSelectableCountValue - state.deselected.size, 0);
    }

    return this.#pageKeys().filter((key) => this.#isSelected(state, key)).length;
  }

  #canSelectAll(state: SelectionState): boolean {
    if (this.pageOnlyValue) {
      const keys = this.#pageKeys();

      return keys.length > 0 && !this.#areSelected(state, keys);
    }

    if (!this.hasAllSelectableCountValue || this.allSelectableCountValue === 0) {
      return false;
    }

    return this.allSelectableCountValue !== this.#selectedCount(state);
  }

  #countLabel(count: number): string {
    const template = count === 1 ? this.countOneValue : this.countManyValue;

    return template.replaceAll(COUNT_PLACEHOLDER, new Intl.NumberFormat().format(count));
  }

  #repaint(): void {
    if (this.#component === null) {
      return;
    }

    const state = this.#state();

    for (const checkbox of this.recordCheckboxTargets) {
      checkbox.checked = this.#isSelected(state, checkbox.value);
    }

    if (this.hasPageCheckboxTarget) {
      const keys = this.#pageKeys();
      const selectedOnPage = keys.filter((key) => this.#isSelected(state, key)).length;

      // `indeterminate` is IDL-only — no markup can carry it, which is this controller's floor.
      this.pageCheckboxTarget.checked = keys.length > 0 && selectedOnPage === keys.length;
      this.pageCheckboxTarget.indeterminate = selectedOnPage > 0 && selectedOnPage < keys.length;
    }

    const count = this.#selectedCount(state);

    if (this.hasBannerTarget) {
      this.bannerTarget.hidden = count === 0;
    }

    if (this.hasCountTarget) {
      this.countTarget.textContent = this.#countLabel(count);
    }

    if (this.hasSelectAllTarget) {
      this.selectAllTarget.hidden = !this.#canSelectAll(state);
    }

    for (const element of this.element.querySelectorAll<HTMLElement>(BULK_ONLY_SELECTOR)) {
      element.hidden = count === 0;
    }
  }

  #watchShiftClicks(): void {
    this.#clickController?.abort();
    this.#clickController = new AbortController();

    this.element.addEventListener(
      "click",
      (event: Event): void => {
        const checkbox = event.target;

        if (checkbox instanceof HTMLInputElement && checkbox.matches(RECORD_CHECKBOX_SELECTOR)) {
          this.#handleCheckboxClick(event, checkbox);
        }
      },
      { signal: this.#clickController.signal },
    );
  }

  /**
   * Filament's `handleCheckboxClick`, minus the clicked box itself: there the inline click handler
   * toggles it during the same event, here `toggleRecord` runs on the `change` that follows this
   * one, so leaving it out of the range is what keeps the two from cancelling each other.
   */
  #handleCheckboxClick(event: Event, checkbox: HTMLInputElement): void {
    const last = this.#lastChecked;
    this.#lastChecked = checkbox;

    if (last === null || !(event instanceof MouseEvent) || !event.shiftKey) {
      return;
    }

    const checkboxes = this.recordCheckboxTargets;
    const start = checkboxes.indexOf(last);
    const end = checkboxes.indexOf(checkbox);

    // A morph replaced the remembered element: the range has no anchor, so it self-heals to none.
    if (start === -1 || end === -1) {
      return;
    }

    const [from, to] = [start, end].sort((a, b) => a - b);
    const keys = checkboxes
      .slice(from, to + 1)
      .filter((element) => element !== checkbox)
      .map((element) => element.value);

    const state = this.#state();

    if (checkbox.checked) {
      this.#selectRecords(state, keys);
    } else {
      this.#deselectRecords(state, keys);
    }

    this.#push(state);
    this.#repaint();
  }
}

/** The store's models are client-writable, so anything but a list of strings reads as empty. */
function toKeys(value: unknown): string[] {
  return Array.isArray(value) ? value.filter((key): key is string => typeof key === "string") : [];
}
