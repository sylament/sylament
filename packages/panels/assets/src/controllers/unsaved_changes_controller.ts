import { Controller } from "@hotwired/stimulus";
import type { Component } from "@symfony/ux-live-component";

import { resolveLiveHost } from "../utils/live_host.js";

const MOUNTED_ACTIONS_MODEL = "mountedActions";

const toArray = (value: unknown): unknown[] => (Array.isArray(value) ? value : []);

/** Guards navigation away from a page whose live state, or whose mounted action, is unsaved. */
export default class extends Controller<HTMLElement> {
  static values = {
    savedHash: { type: String, default: "" },
    dataModel: { type: String, default: "" },
    guardActions: { type: Boolean, default: false },
  };

  declare readonly savedHashValue: string;
  declare readonly dataModelValue: string;
  declare readonly guardActionsValue: boolean;

  #component: Component | null = null;
  #baseline: string | null = null;
  #navigating = false;
  #unsubscribe: (() => void) | null = null;
  #generation = 0;

  async connect(): Promise<void> {
    const generation = ++this.#generation;
    let component: Component;

    try {
      component = await resolveLiveHost(this.element);
    } catch {
      // No live host answered, so there is no state to guard: stay inert rather
      // than prompting on every navigation over an empty baseline.
      return;
    }

    if (generation !== this.#generation) {
      return;
    }

    this.#component = component;
    this.#baseline = this.#serialise();

    const onRenderStarted = (_html: string, backendResponse: { response: Response }): void => {
      this.#navigating = backendResponse.response.headers.get("Location") !== null;
    };
    const onRenderFinished = (): void => {
      this.#navigating = false;
    };

    component.on("render:started", onRenderStarted);
    component.on("render:finished", onRenderFinished);
    this.#unsubscribe = (): void => {
      component.off("render:started", onRenderStarted);
      component.off("render:finished", onRenderFinished);
    };

    window.addEventListener("beforeunload", this.#onBeforeUnload);
  }

  disconnect(): void {
    this.#generation++;
    this.#unsubscribe?.();
    this.#unsubscribe = null;
    window.removeEventListener("beforeunload", this.#onBeforeUnload);
    this.#component = null;
    this.#baseline = null;
  }

  /** A morph re-stamps the marker in place without reconnecting the controller, so the re-arm rides this callback. */
  savedHashValueChanged(): void {
    if (this.#component !== null) {
      this.#baseline = this.#serialise();
    }
  }

  #onBeforeUnload = (event: BeforeUnloadEvent): void => {
    if (this.#navigating || !this.#isDirty()) {
      return;
    }

    event.preventDefault();
    event.returnValue = true;
  };

  #isDirty(): boolean {
    const component = this.#component;

    if (component === null) {
      return false;
    }

    if (this.dataModelValue !== "") {
      const model = this.dataModelValue;
      const dirtyKeys = Object.keys(component.valueStore.getDirtyProps());

      if (dirtyKeys.some((key) => key === model || key.startsWith(`${model}.`))) {
        return true;
      }

      if (this.#serialise() !== this.#baseline) {
        return true;
      }
    }

    return this.guardActionsValue && toArray(component.getData(MOUNTED_ACTIONS_MODEL)).length > 0;
  }

  #serialise(): string | null {
    if (this.#component === null || this.dataModelValue === "") {
      return null;
    }

    return JSON.stringify(this.#component.getData(this.dataModelValue));
  }
}
