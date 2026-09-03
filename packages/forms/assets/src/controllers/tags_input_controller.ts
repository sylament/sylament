import { Controller } from "@hotwired/stimulus";
import type { Component } from "@symfony/ux-live-component";

import { resolveLiveHost } from "../utils/live_host.js";

const VALUE_ATTRIBUTE = "data-value";
const SORTABLE_ITEM_ATTRIBUTE = "data-sortable-item";
const CHIP_SELECTOR = `[${VALUE_ATTRIBUTE}]`;
const LABEL_SELECTOR = ".sy-fo-tags-input-tag-label";
const DELETE_BUTTON_SELECTOR = ".sy-badge-delete-btn";
const TAG_PLACEHOLDER = ":tag";
const ANNOUNCEMENT_DURATION = 3000;

type Binding = { render: boolean; debounce: number };

type SortableEndDetail = { items?: unknown };

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

function strings(values: unknown[]): string[] {
  return values.filter((value): value is string => typeof value === "string");
}

function same(left: string[], right: string[]): boolean {
  return left.length === right.length && left.every((tag, index) => tag === right[index]);
}

/**
 * Filament's `tagsInputFormComponent` as a Stimulus controller. The text input is scratch state
 * (Filament's `newTag`) carrying no `data-model`, so this controller owns the write: every change
 * goes to the host's value store through `component.set()` with the modifiers the field's binding
 * directive declares. The chips live inside a `data-live-ignore` container the morph never visits,
 * so they are repainted here — by cloning the server-rendered `<template>` chip — on every
 * `render:finished` whose list differs from the one on screen.
 */
export default class extends Controller<HTMLElement> {
  static targets = ["input", "chips", "chipTemplate", "datalist", "liveRegion"];

  static values = {
    statePath: String,
    modelBinding: String,
    splitKeys: Array,
    suggestions: Array,
    tagAddedMessage: String,
    tagRemovedMessage: String,
  };

  declare readonly inputTarget: HTMLInputElement;
  declare readonly hasChipsTarget: boolean;
  declare readonly chipsTarget: HTMLElement;
  declare readonly hasChipTemplateTarget: boolean;
  declare readonly chipTemplateTarget: HTMLTemplateElement;
  declare readonly hasDatalistTarget: boolean;
  declare readonly datalistTarget: HTMLDataListElement;
  declare readonly hasLiveRegionTarget: boolean;
  declare readonly liveRegionTarget: HTMLElement;

  declare readonly statePathValue: string;
  declare readonly modelBindingValue: string;
  declare readonly splitKeysValue: unknown[];
  declare readonly suggestionsValue: unknown[];
  declare readonly tagAddedMessageValue: string;
  declare readonly tagRemovedMessageValue: string;

  #component: Component | null = null;
  #binding: Binding = { render: true, debounce: 0 };
  #unsubscribe: (() => void) | null = null;
  #announcementTimeout: ReturnType<typeof window.setTimeout> | null = null;
  #generation = 0;

  async connect(): Promise<void> {
    const generation = ++this.#generation;
    let component: Component;

    try {
      component = await resolveLiveHost(this.element);
    } catch {
      // No live host answered, so there is nowhere to write a tag list: leave the chips exactly
      // as the server rendered them rather than repainting a list this controller cannot save.
      return;
    }

    if (generation !== this.#generation) {
      return;
    }

    this.#component = component;
    this.#binding = parseBinding(this.modelBindingValue);

    const onRender = (): void => this.#sync();
    component.on("render:finished", onRender);
    this.#unsubscribe = (): void => component.off("render:finished", onRender);

    this.#sync();
  }

  disconnect(): void {
    this.#generation++;

    this.#unsubscribe?.();
    this.#unsubscribe = null;

    this.#clearAnnouncement();

    this.#component = null;
  }

  suggestionsValueChanged(): void {
    this.#renderSuggestions(this.#tags());
  }

  createTag(): void {
    const input = this.inputTarget;

    if (input.disabled) {
      return;
    }

    const tag = input.value.trim();

    if (tag === "") {
      return;
    }

    const tags = this.#tags();
    input.value = "";

    if (tags.includes(tag)) {
      return;
    }

    this.#write([...tags, tag]);
    this.#announce(this.tagAddedMessageValue, tag);
  }

  keydown(event: KeyboardEvent): void {
    if (this.inputTarget.disabled) {
      return;
    }

    if (event.key !== "Enter" && !strings(this.splitKeysValue).includes(event.key)) {
      return;
    }

    event.preventDefault();
    event.stopPropagation();

    this.createTag();
  }

  paste(): void {
    if (this.inputTarget.disabled) {
      return;
    }

    // The pasted text is not in the input yet while the event is being handled, and the
    // element may be gone by the time it is — a disconnect bumps the generation.
    const generation = this.#generation;

    window.setTimeout(() => {
      if (generation === this.#generation) {
        this.#splitPastedText();
      }
    }, 0);
  }

  deleteTag(event: Event): void {
    if (this.inputTarget.disabled) {
      return;
    }

    const target = event.target;
    const chip = target instanceof Element ? target.closest<HTMLElement>(CHIP_SELECTOR) : null;

    if (chip === null) {
      return;
    }

    const tag = chip.getAttribute(VALUE_ATTRIBUTE) ?? "";

    this.#write(this.#tags().filter((each) => each !== tag));
    this.#announce(this.tagRemovedMessageValue, tag);
  }

  reorder(event: Event): void {
    if (this.inputTarget.disabled) {
      return;
    }

    const items = (event as CustomEvent<SortableEndDetail>).detail?.items;

    if (!Array.isArray(items)) {
      return;
    }

    this.#write(strings(items));
  }

  /** The host's list for this field, or — with no host — whatever is on screen, so nothing moves. */
  #tags(): string[] {
    const component = this.#component;

    if (component === null) {
      return this.#renderedTags();
    }

    const value: unknown = component.valueStore.get(this.statePathValue);

    return Array.isArray(value) ? strings(value) : [];
  }

  #renderedTags(): string[] {
    if (!this.hasChipsTarget) {
      return [];
    }

    return [...this.chipsTarget.querySelectorAll(CHIP_SELECTOR)].map(
      (chip) => chip.getAttribute(VALUE_ATTRIBUTE) ?? "",
    );
  }

  #sync(): void {
    const tags = this.#tags();

    if (!same(tags, this.#renderedTags())) {
      this.#renderChips(tags);
    }

    this.#renderSuggestions(tags);
  }

  #write(tags: string[]): void {
    const component = this.#component;

    if (component === null) {
      return;
    }

    this.#renderChips(tags);
    this.#renderSuggestions(tags);

    component.set(this.statePathValue, [...tags], this.#binding.render, this.#binding.debounce);
  }

  #renderChips(tags: string[]): void {
    if (!this.hasChipsTarget || !this.hasChipTemplateTarget) {
      return;
    }

    for (const chip of this.chipsTarget.querySelectorAll(CHIP_SELECTOR)) {
      chip.remove();
    }

    const blueprint = this.chipTemplateTarget.content.firstElementChild;

    if (blueprint === null) {
      return;
    }

    for (const tag of tags) {
      const chip = blueprint.cloneNode(true);

      if (!(chip instanceof HTMLElement)) {
        continue;
      }

      chip.setAttribute(VALUE_ATTRIBUTE, tag);

      if (chip.hasAttribute(SORTABLE_ITEM_ATTRIBUTE)) {
        chip.setAttribute(SORTABLE_ITEM_ATTRIBUTE, tag);
      }

      const label = chip.querySelector(LABEL_SELECTOR);

      if (label !== null) {
        label.textContent = tag;
      }

      const deleteButton = chip.querySelector(DELETE_BUTTON_SELECTOR);

      const deleteLabel = deleteButton === null ? null : deleteButton.getAttribute("aria-label");

      if (deleteButton !== null && deleteLabel !== null) {
        deleteButton.setAttribute("aria-label", `${deleteLabel}: ${tag}`);
      }

      this.chipsTarget.appendChild(chip);
    }
  }

  #renderSuggestions(tags: string[]): void {
    if (!this.hasDatalistTarget) {
      return;
    }

    const datalist = this.datalistTarget;
    datalist.replaceChildren();

    for (const suggestion of strings(this.suggestionsValue)) {
      if (tags.includes(suggestion)) {
        continue;
      }

      const option = document.createElement("option");
      option.value = suggestion;
      datalist.appendChild(option);
    }
  }

  #splitPastedText(): void {
    const splitKeys = strings(this.splitKeysValue);

    if (splitKeys.length === 0) {
      this.createTag();

      return;
    }

    // The vendor's escaping, character for character (`tags-input.js:93`).
    const pattern = splitKeys.map((key) => key.replace(/[/\-\\^$*+?.()|[\]{}]/g, "\\$&")).join("|");
    const input = this.inputTarget;

    for (const tag of input.value.split(new RegExp(pattern, "g"))) {
      input.value = tag;
      this.createTag();
    }
  }

  /**
   * `:tag` is substituted through a replacement function so a `$&` inside a tag stays literal
   * rather than being read as a substitution pattern, and the announcement is cleared once it
   * has been read so a stale message is not left under the screen reader's virtual cursor.
   */
  #announce(message: string, tag: string): void {
    if (!this.hasLiveRegionTarget || message === "") {
      return;
    }

    const liveRegion = this.liveRegionTarget;

    this.#clearAnnouncement();
    liveRegion.textContent = message.replace(TAG_PLACEHOLDER, () => tag);

    this.#announcementTimeout = window.setTimeout(() => {
      liveRegion.textContent = "";
      this.#announcementTimeout = null;
    }, ANNOUNCEMENT_DURATION);
  }

  #clearAnnouncement(): void {
    if (this.#announcementTimeout === null) {
      return;
    }

    window.clearTimeout(this.#announcementTimeout);
    this.#announcementTimeout = null;
  }
}
