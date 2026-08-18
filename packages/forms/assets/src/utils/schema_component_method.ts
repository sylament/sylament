import type { Component } from "@symfony/ux-live-component";

const RESULT_EVENT = "sylament:schema-component-method-result";
const ACTION = "callSchemaComponentMethod";

type Pending = {
  resolve: (result: unknown) => void;
  reject: (reason: Error) => void;
};

type ResultDetail = {
  componentKey?: unknown;
  method?: unknown;
  result?: unknown;
};

/** Pending calls per component, keyed `${componentKey}\0${method}`, resolved FIFO. */
const queues = new WeakMap<Component, Map<string, Pending[]>>();

/**
 * Call an `#[ExposedLiveComponentMethod]` on the schema component `componentKey` names, through
 * the host's `callSchemaComponentMethod` live action, and resolve with the result the host hands
 * back in the `sylament:schema-component-method-result` browser event.
 *
 * Results arrive in call order — a live component sends one request at a time and the server
 * dispatches its events in action order — so a FIFO per component/method pair is the whole
 * matching rule. Every pending call rejects on `response:error`, or when the action's own request
 * settled without its result event (a redirect, a non-live response).
 */
export function callSchemaComponentMethod(
  component: Component,
  componentKey: string,
  method: string,
  args: Record<string, unknown> = {},
): Promise<unknown> {
  const queue = queueFor(component, `${componentKey}\0${method}`);

  return new Promise<unknown>((resolve, reject) => {
    const entry: Pending = { resolve, reject };
    queue.push(entry);

    const settle = (reason: Error): void => {
      const index = queue.indexOf(entry);

      if (index !== -1) {
        queue.splice(index, 1);
        reject(reason);
      }
    };

    component.action(ACTION, { componentKey, method, arguments: args }).then(
      () => settle(new Error(`${ACTION}(${componentKey}, ${method}) answered without a result.`)),
      (reason: unknown) =>
        settle(reason instanceof Error ? reason : new Error(`${ACTION} request failed.`)),
    );
  });
}

function queueFor(component: Component, key: string): Pending[] {
  let byKey = queues.get(component);

  if (byKey === undefined) {
    byKey = new Map();
    queues.set(component, byKey);
    listen(component, byKey);
  }

  let queue = byKey.get(key);

  if (queue === undefined) {
    queue = [];
    byKey.set(key, queue);
  }

  return queue;
}

function listen(component: Component, byKey: Map<string, Pending[]>): void {
  component.element.addEventListener(RESULT_EVENT, (event: Event) => {
    if (event.target !== component.element || !(event instanceof CustomEvent)) {
      return;
    }

    const detail = (event.detail ?? {}) as ResultDetail;
    const entry = byKey.get(`${String(detail.componentKey)}\0${String(detail.method)}`)?.shift();

    entry?.resolve(detail.result);
  });

  component.on("response:error", () => {
    const reason = new Error(`${ACTION} request failed.`);

    for (const queue of byKey.values()) {
      for (const entry of queue.splice(0)) {
        entry.reject(reason);
      }
    }
  });
}
