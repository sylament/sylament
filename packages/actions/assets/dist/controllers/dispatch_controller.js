import { Controller } from "@hotwired/stimulus";
import { getComponent } from "@symfony/ux-live-component";
let listening = false;
class dispatch_controller_default extends Controller {
  static afterLoad() {
    if (listening) {
      return;
    }
    listening = true;
    document.addEventListener("click", (event) => void handleDispatchClick(event), {
      capture: true
    });
  }
}
async function handleDispatchClick(event) {
  const origin = event.target;
  if (!(origin instanceof Element)) {
    return;
  }
  const trigger = origin.closest("[data-sylament-action-event]");
  if (!(trigger instanceof HTMLElement)) {
    return;
  }
  const name = trigger.dataset.sylamentActionEvent;
  if (name === void 0 || name === "") {
    return;
  }
  const raw = trigger.dataset.sylamentActionEventData;
  const data = raw === void 0 ? {} : JSON.parse(raw);
  const to = trigger.dataset.sylamentActionDispatchTo;
  try {
    if (trigger.dataset.sylamentActionDispatchSelf !== void 0) {
      const self = trigger.closest('[data-controller~="live"]');
      if (self instanceof HTMLElement) {
        (await getComponent(self)).emitSelf(name, data);
        return;
      }
    }
    const host = document.querySelector('[data-controller~="live"]');
    if (!(host instanceof HTMLElement)) {
      return;
    }
    (await getComponent(host)).emit(name, data, to === void 0 || to === "" ? null : to);
  } catch {
  }
}
export {
  dispatch_controller_default as default
};
//# sourceMappingURL=dispatch_controller.js.map
