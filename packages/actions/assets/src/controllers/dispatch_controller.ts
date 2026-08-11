import { Controller } from "@hotwired/stimulus";
import { getComponent } from "@symfony/ux-live-component";

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

  try {
    if (trigger.dataset.sylamentActionDispatchSelf !== undefined) {
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

    (await getComponent(host)).emit(name, data, to === undefined || to === "" ? null : to);
  } catch {
    // getComponent rejects for an element not yet registered (lazy/boot race);
    // a dispatch with no reachable live component is silent, matching Livewire.
  }
}
