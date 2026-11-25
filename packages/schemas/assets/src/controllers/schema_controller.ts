import { Controller } from "@hotwired/stimulus";

const SCROLL_DELAY_MS = 200;
const CONCEALED_VALIDATION_RETRY_RESET_MS = 100;

let isRetryingConcealedValidation = false;

type FormControl =
  | HTMLButtonElement
  | HTMLFieldSetElement
  | HTMLInputElement
  | HTMLObjectElement
  | HTMLOutputElement
  | HTMLSelectElement
  | HTMLTextAreaElement;

const isFormControl = (element: unknown): element is FormControl =>
  element instanceof HTMLButtonElement ||
  element instanceof HTMLFieldSetElement ||
  element instanceof HTMLInputElement ||
  element instanceof HTMLObjectElement ||
  element instanceof HTMLOutputElement ||
  element instanceof HTMLSelectElement ||
  element instanceof HTMLTextAreaElement;

const isVisibleControl = (control: FormControl): boolean =>
  control.offsetParent !== null && getComputedStyle(control).visibility !== "hidden";

// `invalid` events do not bubble, so they are only observable at the document level in the
// capture phase. A control inside a collapsed section, an inactive tab, a step not yet shown or
// a collapsed repeater item cannot be focused or carry the browser's validation bubble, so its
// containers are revealed through the same `expand` walk as `onValidationError()`.
document.addEventListener(
  "invalid",
  (event: Event): void => {
    const control = event.target;

    // Only controls that belong to a field are handled, so other forms on the page keep their
    // own validation behaviour.
    if (!isFormControl(control) || control.closest("[data-field-wrapper]") === null) {
      return;
    }

    // Visible controls use the default native validation UI.
    if (isVisibleControl(control)) {
      return;
    }

    event.preventDefault();

    // One reveal per validation burst, so a control that stays concealed cannot loop.
    if (isRetryingConcealedValidation) {
      return;
    }

    const form = control.form;

    // Keep visible errors available to fix before revealing another container; reading
    // `validity` dispatches no further `invalid` events.
    if (
      Array.from(form?.elements ?? []).some(
        (element) =>
          isFormControl(element) &&
          element.willValidate &&
          !element.validity.valid &&
          isVisibleControl(element),
      )
    ) {
      return;
    }

    isRetryingConcealedValidation = true;

    let elementToExpand: Node | null = control;

    while (elementToExpand !== null) {
      elementToExpand.dispatchEvent(new CustomEvent("expand"));

      elementToExpand = elementToExpand.parentNode;
    }

    // Re-run native validation once the reveal has painted, so the browser can focus the
    // control and show its validation bubble.
    requestAnimationFrame(() =>
      requestAnimationFrame(() => {
        form?.reportValidity();

        window.setTimeout(() => {
          isRetryingConcealedValidation = false;
        }, CONCEALED_VALIDATION_RETRY_RESET_MS);
      }),
    );
  },
  true,
);

/**
 * The root schema's client-side glue (Filament's `filamentSchema` Alpine data): after a
 * validation failure, open every concealing ancestor of the first error and scroll to its
 * field; after a client-side state reset, refocus the first visible autofocus field.
 */
export default class extends Controller<HTMLElement> {
  static values = { schemaKey: String };

  declare readonly schemaKeyValue: string;

  onValidationError(event: Event): void {
    if (!this.#isFromOwnHost(event)) {
      return;
    }

    queueMicrotask(() => {
      const error = this.element.querySelector("[data-validation-error]");

      if (error === null) {
        return;
      }

      let elementToExpand: Node | null = error;

      while (elementToExpand !== null) {
        elementToExpand.dispatchEvent(new CustomEvent("expand"));

        elementToExpand = elementToExpand.parentNode;
      }

      window.setTimeout(() => {
        (error.closest("[data-field-wrapper]") ?? error).scrollIntoView({
          behavior: "smooth",
          block: "start",
          inline: "start",
        });
      }, SCROLL_DELAY_MS);
    });
  }

  onStateReset(event: CustomEvent<{ schemaKey?: unknown }>): void {
    if (!this.#isFromOwnHost(event) || event.detail?.schemaKey !== this.schemaKeyValue) {
      return;
    }

    queueMicrotask(() => {
      for (const field of this.element.querySelectorAll<HTMLElement>("[autofocus]")) {
        // Skip a field hidden by an ancestor (an inactive tab or wizard step): the tabs and
        // wizard controllers refocus their own once the reset restores the active one.
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
  #isFromOwnHost(event: Event): boolean {
    return event.target === this.element.closest('[data-controller~="live"]');
  }
}
