import { Controller } from "@hotwired/stimulus";
import { resolveLiveHost } from "../utils/live_host.js";
const VALUE_ATTRIBUTE = "data-value";
const SORTABLE_ITEM_ATTRIBUTE = "data-sortable-item";
const CHIP_SELECTOR = `[${VALUE_ATTRIBUTE}]`;
const LABEL_SELECTOR = ".sy-fo-tags-input-tag-label";
const DELETE_BUTTON_SELECTOR = ".sy-badge-delete-btn";
const TAG_PLACEHOLDER = ":tag";
const ANNOUNCEMENT_DURATION = 3e3;
function parseBinding(directive) {
  const binding = { render: true, debounce: 0 };
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
function strings(values) {
  return values.filter((value) => typeof value === "string");
}
function tagList(values) {
  const tags = [];
  for (const value of values) {
    if (typeof value === "string") {
      tags.push(value);
    } else if (typeof value === "number" || typeof value === "bigint") {
      tags.push(String(value));
    }
  }
  return tags;
}
function same(left, right) {
  return left.length === right.length && left.every((tag, index) => tag === right[index]);
}
class tags_input_controller_default extends Controller {
  static targets = ["input", "chips", "chipTemplate", "datalist", "liveRegion"];
  static values = {
    statePath: String,
    modelBinding: String,
    splitKeys: Array,
    suggestions: Array,
    tagAddedMessage: String,
    tagRemovedMessage: String
  };
  #component = null;
  #binding = { render: true, debounce: 0 };
  #unsubscribe = null;
  #announcementTimeout = null;
  #generation = 0;
  async connect() {
    const generation = ++this.#generation;
    let component;
    try {
      component = await resolveLiveHost(this.element);
    } catch {
      return;
    }
    if (generation !== this.#generation) {
      return;
    }
    this.#component = component;
    this.#binding = parseBinding(this.modelBindingValue);
    const onRender = () => this.#sync();
    component.on("render:finished", onRender);
    this.#unsubscribe = () => component.off("render:finished", onRender);
    this.#sync();
  }
  disconnect() {
    this.#generation++;
    this.#unsubscribe?.();
    this.#unsubscribe = null;
    this.#clearAnnouncement();
    this.#component = null;
  }
  suggestionsValueChanged() {
    this.#renderSuggestions(this.#tags());
  }
  createTag() {
    const input = this.inputTarget;
    if (input.disabled) {
      return;
    }
    input.value = input.value.trim();
    const tag = input.value;
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
  keydown(event) {
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
  paste() {
    if (this.inputTarget.disabled) {
      return;
    }
    const generation = this.#generation;
    window.setTimeout(() => {
      if (generation === this.#generation) {
        this.#splitPastedText();
      }
    }, 0);
  }
  deleteTag(event) {
    if (this.inputTarget.disabled) {
      return;
    }
    const target = event.target;
    const chip = target instanceof Element ? target.closest(CHIP_SELECTOR) : null;
    if (chip === null) {
      return;
    }
    const tag = chip.getAttribute(VALUE_ATTRIBUTE) ?? "";
    this.#write(this.#tags().filter((each) => each !== tag));
    this.#announce(this.tagRemovedMessageValue, tag);
  }
  reorder(event) {
    if (this.inputTarget.disabled) {
      return;
    }
    const items = event.detail?.items;
    if (!Array.isArray(items)) {
      return;
    }
    this.#write(strings(items));
  }
  /** The host's list for this field, or — with no host — whatever is on screen, so nothing moves. */
  #tags() {
    const component = this.#component;
    if (component === null) {
      return this.#renderedTags();
    }
    const value = component.valueStore.get(this.statePathValue);
    return Array.isArray(value) ? tagList(value) : [];
  }
  #renderedTags() {
    if (!this.hasChipsTarget) {
      return [];
    }
    return [...this.chipsTarget.querySelectorAll(CHIP_SELECTOR)].map(
      (chip) => chip.getAttribute(VALUE_ATTRIBUTE) ?? ""
    );
  }
  #sync() {
    const tags = this.#tags();
    if (!same(tags, this.#renderedTags())) {
      this.#renderChips(tags);
    }
    this.#renderSuggestions(tags);
  }
  #write(tags) {
    const component = this.#component;
    if (component === null) {
      return;
    }
    this.#renderChips(tags);
    this.#renderSuggestions(tags);
    component.set(this.statePathValue, [...tags], this.#binding.render, this.#binding.debounce);
  }
  /** Reuses the node of a chip still in the list: a chip removed under the cursor is a delete click lost. */
  #renderChips(tags) {
    if (!this.hasChipsTarget || !this.hasChipTemplateTarget) {
      return;
    }
    const spare = /* @__PURE__ */ new Map();
    for (const chip of this.chipsTarget.querySelectorAll(CHIP_SELECTOR)) {
      const value = chip.getAttribute(VALUE_ATTRIBUTE) ?? "";
      const bucket = spare.get(value);
      if (bucket === void 0) {
        spare.set(value, [chip]);
      } else {
        bucket.push(chip);
      }
    }
    let previous = null;
    for (const tag of tags) {
      const chip = spare.get(tag)?.shift() ?? this.#buildChip(tag);
      if (chip === null) {
        continue;
      }
      const expected = previous === null ? this.chipsTarget.querySelector(CHIP_SELECTOR) : previous.nextElementSibling;
      if (chip !== expected) {
        this.chipsTarget.insertBefore(chip, expected);
      }
      previous = chip;
    }
    for (const unclaimed of spare.values()) {
      for (const chip of unclaimed) {
        chip.remove();
      }
    }
  }
  #buildChip(tag) {
    const blueprint = this.chipTemplateTarget.content.firstElementChild;
    const chip = blueprint === null ? null : blueprint.cloneNode(true);
    if (!(chip instanceof HTMLElement)) {
      return null;
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
    const deleteLabel = deleteButton?.getAttribute("aria-label") ?? null;
    if (deleteButton !== null && deleteLabel !== null) {
      deleteButton.setAttribute("aria-label", `${deleteLabel}: ${tag}`);
    }
    return chip;
  }
  #renderSuggestions(tags) {
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
  #splitPastedText() {
    const splitKeys = strings(this.splitKeysValue);
    if (splitKeys.length === 0) {
      this.createTag();
      return;
    }
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
  #announce(message, tag) {
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
  #clearAnnouncement() {
    if (this.#announcementTimeout === null) {
      return;
    }
    window.clearTimeout(this.#announcementTimeout);
    this.#announcementTimeout = null;
  }
}
export {
  tags_input_controller_default as default
};
//# sourceMappingURL=tags_input_controller.js.map
