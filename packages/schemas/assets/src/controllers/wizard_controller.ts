import { Controller } from "@hotwired/stimulus";
import type { Component } from "@symfony/ux-live-component";
import { resolveLiveHost } from "../utils/live_host.js";
import { callSchemaComponentMethod } from "../utils/schema_component_method.js";

const ACTIVE_CLASS = "sy-active";
const COMPLETED_CLASS = "sy-completed";
const HIDDEN_CLASS = "sy-hidden";
const NEXT_STEP_METHOD = "nextStep";
const COMPLETED_TEXT = "Completed";
const NOT_COMPLETED_TEXT = "Not completed";

/** Enter advances the step from these input types only; everything else keeps its own behaviour. */
const TEXT_INPUT_TYPES = new Set([
  "text",
  "email",
  "number",
  "password",
  "search",
  "tel",
  "url",
  "date",
  "datetime-local",
  "month",
  "time",
  "week",
]);

/**
 * The active step, client-side (Filament's `wizardSchemaComponent` Alpine data): the server
 * renders the start step and this controller owns every move after that, asking the host to
 * validate the current step before advancing (`nextStep` through the schema-component seam) and
 * staying put when that request answers with an error.
 */
export default class extends Controller<HTMLElement> {
  static targets = [
    "header",
    "headerStep",
    "headerButton",
    "stepStatus",
    "step",
    "previousSlot",
    "cancelSlot",
    "nextSlot",
    "submitSlot",
  ];

  static values = {
    key: String,
    schemaKey: String,
    startStep: { type: Number, default: 1 },
    skippable: Boolean,
    queryStringKey: String,
  };

  declare readonly headerTarget: HTMLElement;
  declare readonly hasHeaderTarget: boolean;
  declare readonly headerStepTargets: HTMLElement[];
  declare readonly headerButtonTargets: HTMLButtonElement[];
  declare readonly stepStatusTargets: HTMLElement[];
  declare readonly stepTargets: HTMLElement[];
  declare readonly previousSlotTarget: HTMLElement;
  declare readonly hasPreviousSlotTarget: boolean;
  declare readonly cancelSlotTarget: HTMLElement;
  declare readonly hasCancelSlotTarget: boolean;
  declare readonly nextSlotTarget: HTMLElement;
  declare readonly hasNextSlotTarget: boolean;
  declare readonly submitSlotTarget: HTMLElement;
  declare readonly hasSubmitSlotTarget: boolean;

  declare readonly keyValue: string;
  declare readonly schemaKeyValue: string;
  declare readonly startStepValue: number;
  declare readonly skippableValue: boolean;
  declare readonly queryStringKeyValue: string;

  #step: string | null = null;
  #host: Promise<Component> | null = null;
  #connected = false;

  connect(): void {
    this.#step = this.#stepKeys().at(this.startStepValue - 1) ?? null;

    this.#apply();
    this.#autofocus(true);

    this.#connected = true;
  }

  disconnect(): void {
    this.#connected = false;
  }

  /**
   * Ask the host to validate the current step and advance: it answers with the
   * `next-wizard-step` browser event this controller listens for, or with a validation error —
   * which rejects the call and leaves the wizard where it is.
   */
  async requestNextStep(): Promise<void> {
    try {
      await callSchemaComponentMethod(await this.#liveHost(), this.keyValue, NEXT_STEP_METHOD, {
        currentStepIndex: this.#stepIndex(this.#step),
      });
    } catch {
      // A 422 (the step did not validate) or a failed request: stay on this step.
    }
  }

  goToPreviousStep(): void {
    const step = this.#stepKeys().at(this.#stepIndex(this.#step) - 1);

    if (this.#isFirstStep() || step === undefined) {
      return;
    }

    this.#setStep(step);
  }

  goToStepFromHeader(event: Event): void {
    const target = event.currentTarget;

    if (target instanceof HTMLElement) {
      this.#goToStep(target.dataset.stepKey ?? "");
    }
  }

  onNextStepEvent(event: CustomEvent<{ key?: unknown }>): void {
    if (event.detail?.key !== this.keyValue) {
      return;
    }

    this.#goToNextStep();
  }

  onGoToStepEvent(event: CustomEvent<{ key?: unknown; step?: unknown }>): void {
    const step = event.detail?.step;

    if (event.detail?.key !== this.keyValue || typeof step !== "string") {
      return;
    }

    this.#goToStep(step);
  }

  expand(event: Event): void {
    const target = event.currentTarget;

    if (!(target instanceof HTMLElement)) {
      return;
    }

    const step = target.dataset.stepKey ?? "";

    if (!this.#isStepAccessible(step)) {
      return;
    }

    this.#goToStep(step);
  }

  resetState(event: CustomEvent<{ schemaKey?: unknown }>): void {
    if (this.queryStringKeyValue !== "") {
      return;
    }

    if (event.target !== this.element.closest('[data-controller~="live"]')) {
      return;
    }

    if (event.detail?.schemaKey !== this.schemaKeyValue) {
      return;
    }

    const step = this.#stepKeys().at(this.startStepValue - 1);

    if (step !== undefined) {
      this.#setStep(step);
    }
  }

  /**
   * Enter inside a text-like field of a non-last step advances the step instead of submitting
   * the host's form — Filament's `alpineSubmitHandler()`/`formWrapper()` behaviour without the
   * nested `<form>` those would need.
   */
  onKeydown(event: KeyboardEvent): void {
    if (event.key !== "Enter" || event.defaultPrevented || event.isComposing) {
      return;
    }

    const target = event.target;

    if (!(target instanceof HTMLInputElement) || !TEXT_INPUT_TYPES.has(target.type)) {
      return;
    }

    if (this.#activeStepElement()?.contains(target) !== true || this.#isLastStep()) {
      return;
    }

    event.preventDefault();

    void this.requestNextStep();
  }

  stepTargetConnected(): void {
    this.#revalidateStep();
  }

  stepTargetDisconnected(): void {
    this.#revalidateStep();
  }

  headerStepTargetConnected(): void {
    if (this.#connected) {
      this.#apply();
    }
  }

  #liveHost(): Promise<Component> {
    this.#host ??= resolveLiveHost(this.element);

    return this.#host;
  }

  /** The step panels' keys, in DOM order (Filament's `stepsData` JSON blob). */
  #stepKeys(): string[] {
    return this.stepTargets.map((step) => step.dataset.stepKey ?? "").filter((key) => key !== "");
  }

  /** Filament's `getStepIndex`: an unknown step reads as the first one. */
  #stepIndex(step: string | null): number {
    const index = this.#stepKeys().indexOf(step ?? "");

    return index === -1 ? 0 : index;
  }

  #isFirstStep(): boolean {
    return this.#stepIndex(this.#step) <= 0;
  }

  #isLastStep(): boolean {
    return this.#stepIndex(this.#step) + 1 >= this.#stepKeys().length;
  }

  #isStepAccessible(step: string): boolean {
    return this.skippableValue || this.#stepIndex(this.#step) > this.#stepIndex(step);
  }

  #goToNextStep(): void {
    const steps = this.#stepKeys();
    const index = this.#stepIndex(this.#step) + 1;
    const step = steps.at(index);

    if (index >= steps.length || step === undefined) {
      return;
    }

    this.#setStep(step);
  }

  #goToStep(step: string): void {
    const index = this.#stepKeys().indexOf(step);

    if (index <= -1) {
      return;
    }

    if (!this.skippableValue && index > this.#stepIndex(this.#step)) {
      return;
    }

    this.#setStep(step);
  }

  #setStep(step: string): void {
    this.#step = step;

    this.#apply();
    this.#updateQueryString();
    this.#autofocus();
    this.#scroll();
  }

  /** Filament's `interceptMessage` equivalent: a step the server no longer renders falls back. */
  #revalidateStep(): void {
    if (!this.#connected) {
      return;
    }

    const steps = this.#stepKeys();

    if (this.#step === null || !steps.includes(this.#step)) {
      this.#step = steps.at(this.startStepValue - 1) ?? this.#step;
    }

    this.#apply();
  }

  #apply(): void {
    const current = this.#stepIndex(this.#step);
    const isFirst = this.#isFirstStep();
    const isLast = this.#isLastStep();
    const isPreviousDisabled =
      this.hasPreviousSlotTarget && !this.previousSlotTarget.hasAttribute("data-action");

    for (const step of this.stepTargets) {
      step.classList.toggle(
        ACTIVE_CLASS,
        this.#step !== null && step.dataset.stepKey === this.#step,
      );
      step.tabIndex = step.querySelector("[autofocus]") === null ? 0 : -1;
    }

    this.headerStepTargets.forEach((headerStep, index) => {
      headerStep.classList.toggle(ACTIVE_CLASS, index === current);
      headerStep.classList.toggle(COMPLETED_CLASS, index < current);
    });

    for (const button of this.headerButtonTargets) {
      const step = button.dataset.stepKey ?? "";

      if (this.#step !== null && step === this.#step) {
        button.setAttribute("aria-current", "step");
      } else {
        button.removeAttribute("aria-current");
      }

      button.disabled = !this.#isStepAccessible(step) || isPreviousDisabled;
    }

    this.stepStatusTargets.forEach((status, index) => {
      status.textContent = stepStatusText(index, current);
    });

    if (this.hasPreviousSlotTarget) {
      this.previousSlotTarget.classList.toggle(HIDDEN_CLASS, isFirst);
    }

    if (this.hasCancelSlotTarget) {
      this.cancelSlotTarget.classList.toggle(HIDDEN_CLASS, !isFirst);
    }

    if (this.hasNextSlotTarget) {
      this.nextSlotTarget.classList.toggle(HIDDEN_CLASS, isLast);
    }

    if (this.hasSubmitSlotTarget) {
      this.submitSlotTarget.classList.toggle(HIDDEN_CLASS, !isLast);
    }
  }

  #activeStepElement(): HTMLElement | null {
    if (this.#step === null) {
      return null;
    }

    return this.stepTargets.find((step) => step.dataset.stepKey === this.#step) ?? null;
  }

  #scroll(): void {
    queueMicrotask(() => {
      if (!this.hasHeaderTarget) {
        return;
      }

      this.headerTarget.children
        .item(this.#stepIndex(this.#step))
        ?.scrollIntoView({ behavior: "smooth", block: "start" });
    });
  }

  #updateQueryString(): void {
    if (this.queryStringKeyValue === "" || this.#step === null) {
      return;
    }

    const url = new URL(window.location.href);
    url.searchParams.set(this.queryStringKeyValue, this.#step);

    history.replaceState(null, document.title, url.toString());
  }

  #autofocus(respectCurrentFocus = false): void {
    queueMicrotask(() => {
      const active = document.activeElement;

      if (
        respectCurrentFocus &&
        active !== null &&
        active !== document.body &&
        (this.element.compareDocumentPosition(active) & Node.DOCUMENT_POSITION_PRECEDING) !== 0
      ) {
        return;
      }

      const step = this.#activeStepElement();

      if (step === null) {
        return;
      }

      for (const field of step.querySelectorAll<HTMLElement>("[autofocus]")) {
        field.focus();

        if (document.activeElement === field) {
          break;
        }
      }
    });
  }
}

function stepStatusText(index: number, current: number): string {
  if (index < current) {
    return COMPLETED_TEXT;
  }

  return index === current ? "" : NOT_COMPLETED_TEXT;
}
