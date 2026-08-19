import { Controller } from "@hotwired/stimulus";

const SCROLL_DELAY_MS = 200;

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
