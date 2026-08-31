import { Controller } from "@hotwired/stimulus";
import { type Component, getComponent } from "@symfony/ux-live-component";

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
 * The live component that owns `element`: its closest live root, resolved through UX Live's
 * registry, retried once after `live:connect` when the registry is not ready yet (the live
 * controller may connect after ours; `getComponent()` polls for ~55 ms and then rejects).
 *
 * A deliberate copy of schemas' `utils/live_host.ts`: the per-file build ships specifiers
 * verbatim, so a cross-package import would have to be a bare specifier every consuming app's
 * importmap resolves.
 */
async function resolveLiveHost(element: Element): Promise<Component> {
  const root = element.closest<HTMLElement>('[data-controller~="live"]');

  if (root === null) {
    throw new Error("No live component host above the element.");
  }

  try {
    return await getComponent(root);
  } catch {
    await new Promise<void>((resolve) => {
      const onConnect = (event: Event): void => {
        if (event.target !== root) {
          return;
        }

        root.removeEventListener("live:connect", onConnect);
        resolve();
      };

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

  try {
    const host = await resolveLiveHost(trigger);

    if (trigger.dataset.sylamentActionDispatchSelf !== undefined) {
      host.emitSelf(name, data);

      return;
    }

    host.emit(name, data, to === undefined || to === "" ? null : to);
  } catch {
    // resolveLiveHost throws for a trigger with no live ancestor, and getComponent rejects for
    // an element not yet registered (lazy/boot race); a dispatch with no reachable live
    // component is silent, matching Livewire.
  }
}
