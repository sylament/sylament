import { Controller } from "@hotwired/stimulus";
import { resolveLiveHost } from "../utils/live_host.js";
const MOUNTED_ACTIONS_MODEL = "mountedActions";
const toArray = (value) => Array.isArray(value) ? value : [];
class unsaved_changes_controller_default extends Controller {
  static values = {
    savedHash: { type: String, default: "" },
    dataModel: { type: String, default: "" },
    guardActions: { type: Boolean, default: false }
  };
  #component = null;
  #baseline = null;
  #navigating = false;
  #unsubscribe = null;
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
    this.#baseline = this.#serialise();
    const onRenderStarted = (_html, backendResponse) => {
      this.#navigating = backendResponse.response.headers.get("Location") !== null;
    };
    const onRenderFinished = () => {
      this.#navigating = false;
    };
    component.on("render:started", onRenderStarted);
    component.on("render:finished", onRenderFinished);
    this.#unsubscribe = () => {
      component.off("render:started", onRenderStarted);
      component.off("render:finished", onRenderFinished);
    };
    window.addEventListener("beforeunload", this.#onBeforeUnload);
  }
  disconnect() {
    this.#generation++;
    this.#unsubscribe?.();
    this.#unsubscribe = null;
    window.removeEventListener("beforeunload", this.#onBeforeUnload);
    this.#component = null;
    this.#baseline = null;
  }
  /** A morph re-stamps the marker in place without reconnecting the controller, so the re-arm rides this callback. */
  savedHashValueChanged() {
    if (this.#component !== null) {
      this.#baseline = this.#serialise();
    }
  }
  #onBeforeUnload = (event) => {
    if (this.#navigating || !this.#isDirty()) {
      return;
    }
    event.preventDefault();
    event.returnValue = true;
  };
  #isDirty() {
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
  #serialise() {
    if (this.#component === null || this.dataModelValue === "") {
      return null;
    }
    return JSON.stringify(this.#component.getData(this.dataModelValue));
  }
}
export {
  unsaved_changes_controller_default as default
};
//# sourceMappingURL=unsaved_changes_controller.js.map
