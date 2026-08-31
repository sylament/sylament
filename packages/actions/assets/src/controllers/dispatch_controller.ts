import { Controller } from "@hotwired/stimulus";
import { type Component, getComponent } from "@symfony/ux-live-component";

const LIVE_CONNECT_TIMEOUT_MS = 2000;

let listening = false;

export default class extends Controller<HTMLElement> {
  static afterLoad(): void {
    if (listening) {
      return;
    }
    listening = true;

    document.addEventListener("click", (event) => void handleDispatchClick(event), {
      capture: true,
    });
  }
}

/**
 * The live component rooted at `root`, retried once after that element's `live:connect` when UX
 * Live's registry is not ready yet (the live controller may connect after ours; `getComponent()`
 * polls for ~55 ms and then rejects). The wait is bounded: a root whose live controller never
 * connects rejects instead of leaving a listener and a pending promise behind for every click.
 */
async function resolveComponent(root: HTMLElement): Promise<Component> {
  try {
    return await getComponent(root);
  } catch {
    await new Promise<void>((resolve, reject) => {
      const timer = window.setTimeout(() => {
        root.removeEventListener("live:connect", onConnect);
        reject(new Error("The live controller above the dispatch trigger never connected."));
      }, LIVE_CONNECT_TIMEOUT_MS);

      function onConnect(event: Event): void {
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

async function handleDispatchClick(event: Event): Promise<void> {
  const origin = event.target;
  if (!(origin instanceof Element)) {
    return;
  }

  const trigger = origin.closest("[data-sylament-action-event]");
  if (!(trigger instanceof HTMLElement)) {
    return;
  }

  const name = trigger.dataset.sylamentActionEvent;
  if (name === undefined || name === "") {
    return;
  }

  const raw = trigger.dataset.sylamentActionEventData;
  const data = raw === undefined ? {} : (JSON.parse(raw) as Record<string, unknown>);
  const to = trigger.dataset.sylamentActionDispatchTo;
  const nearest = trigger.closest<HTMLElement>('[data-controller~="live"]');

  try {
    if (trigger.dataset.sylamentActionDispatchSelf !== undefined && nearest !== null) {
      (await resolveComponent(nearest)).emitSelf(name, data);

      return;
    }

    const host = nearest ?? document.querySelector<HTMLElement>('[data-controller~="live"]');
    if (host === null) {
      return;
    }

    (await resolveComponent(host)).emit(name, data, to === undefined || to === "" ? null : to);
  } catch {
    // resolveComponent rejects for an element the live application never registers (a lazy or
    // boot race); a dispatch with no reachable live component is silent, matching Livewire.
  }
}
