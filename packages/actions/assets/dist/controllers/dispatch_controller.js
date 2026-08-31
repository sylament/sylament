import { Controller } from "@hotwired/stimulus";
import { getComponent } from "@symfony/ux-live-component";
const LIVE_CONNECT_TIMEOUT_MS = 2e3;
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
async function resolveComponent(root) {
  try {
    return await getComponent(root);
  } catch {
    await new Promise((resolve, reject) => {
      const timer = window.setTimeout(() => {
        root.removeEventListener("live:connect", onConnect);
        reject(new Error("The live controller above the dispatch trigger never connected."));
      }, LIVE_CONNECT_TIMEOUT_MS);
      function onConnect(event) {
        if (event.target !== root) {
          return;
        }
        window.clearTimeout(timer);
        root.removeEventListener("live:connect", onConnect);
        resolve();
      }
      root.addEventListener("live:connect", onConnect);
    });
    return getComponent(root);
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
  const nearest = trigger.closest('[data-controller~="live"]');
  try {
    if (trigger.dataset.sylamentActionDispatchSelf !== void 0 && nearest !== null) {
      (await resolveComponent(nearest)).emitSelf(name, data);
      return;
    }
    const host = nearest ?? document.querySelector('[data-controller~="live"]');
    if (host === null) {
      return;
    }
    (await resolveComponent(host)).emit(name, data, to === void 0 || to === "" ? null : to);
  } catch {
  }
}
export {
  dispatch_controller_default as default
};
//# sourceMappingURL=dispatch_controller.js.map
