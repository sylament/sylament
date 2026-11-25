import { Controller } from "@hotwired/stimulus";
const SCROLL_DELAY_MS = 200;
const CONCEALED_VALIDATION_RETRY_RESET_MS = 100;
let isRetryingConcealedValidation = false;
const isFormControl = (element) => element instanceof HTMLButtonElement || element instanceof HTMLFieldSetElement || element instanceof HTMLInputElement || element instanceof HTMLObjectElement || element instanceof HTMLOutputElement || element instanceof HTMLSelectElement || element instanceof HTMLTextAreaElement;
const isVisibleControl = (control) => control.offsetParent !== null && getComputedStyle(control).visibility !== "hidden";
document.addEventListener(
  "invalid",
  (event) => {
    const control = event.target;
    if (!isFormControl(control) || control.closest("[data-field-wrapper]") === null) {
      return;
    }
    if (isVisibleControl(control)) {
      return;
    }
    event.preventDefault();
    if (isRetryingConcealedValidation) {
      return;
    }
    const form = control.form;
    if (Array.from(form?.elements ?? []).some(
      (element) => isFormControl(element) && element.willValidate && !element.validity.valid && isVisibleControl(element)
    )) {
      return;
    }
    isRetryingConcealedValidation = true;
    let elementToExpand = control;
    while (elementToExpand !== null) {
      elementToExpand.dispatchEvent(new CustomEvent("expand"));
      elementToExpand = elementToExpand.parentNode;
    }
    requestAnimationFrame(
      () => requestAnimationFrame(() => {
        form?.reportValidity();
        window.setTimeout(() => {
          isRetryingConcealedValidation = false;
        }, CONCEALED_VALIDATION_RETRY_RESET_MS);
      })
    );
  },
  true
);
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
