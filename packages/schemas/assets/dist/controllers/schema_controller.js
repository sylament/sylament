import { Controller } from "@hotwired/stimulus";
const SCROLL_DELAY_MS = 200;
class schema_controller_default extends Controller {
  static values = { schemaKey: String };
  onValidationError(event) {
    if (!this.#isFromOwnHost(event)) {
      return;
    }
    queueMicrotask(() => {
      const error = this.element.querySelector("[data-validation-error]");
      if (error === null) {
        return;
      }
      let elementToExpand = error;
      while (elementToExpand !== null) {
        elementToExpand.dispatchEvent(new CustomEvent("expand"));
        elementToExpand = elementToExpand.parentNode;
      }
      window.setTimeout(() => {
        (error.closest("[data-field-wrapper]") ?? error).scrollIntoView({
          behavior: "smooth",
          block: "start",
          inline: "start"
        });
      }, SCROLL_DELAY_MS);
    });
  }
  onStateReset(event) {
    if (!this.#isFromOwnHost(event) || event.detail?.schemaKey !== this.schemaKeyValue) {
      return;
    }
    queueMicrotask(() => {
      for (const field of this.element.querySelectorAll("[autofocus]")) {
        if (field.offsetParent === null) {
          continue;
        }
        field.focus();
        if (document.activeElement === field) {
          break;
        }
      }
    });
  }
  /** The host that dispatched is the host that owns this schema (no live component id is exposed). */
  #isFromOwnHost(event) {
    return event.target === this.element.closest('[data-controller~="live"]');
  }
}
export {
  schema_controller_default as default
};
//# sourceMappingURL=schema_controller.js.map
