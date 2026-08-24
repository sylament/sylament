import type { Component } from "@symfony/ux-live-component";

const RESULT_EVENT = "sylament:schema-component-method-result";
const ACTION = "callSchemaComponentMethod";
const UPLOAD_ACTION = "uploadSchemaComponentFile";

type Pending = {
  resolve: (result: unknown) => void;
  reject: (reason: Error) => void;
  /** The action this entry is waiting on, so a shared failure names the right one. */
  label: string;
};

type ResultDetail = {
  componentKey?: unknown;
  method?: unknown;
  fileKey?: unknown;
  result?: unknown;
};

/** Pending calls per component, keyed `${componentKey}\0${method}\0${fileKey}`, resolved FIFO. */
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
  return awaitResult(
    component,
    queueKey(componentKey, method, null),
    () => component.action(ACTION, { componentKey, method, arguments: args }),
    `${ACTION}(${componentKey}, ${method})`,
  );
}

/**
 * Upload one file to the schema component `componentKey` names, through the host's
 * `uploadSchemaComponentFile` live action, and resolve with the result that component's
 * `acceptUploadedFile()` answered.
 *
 * The file rides the live request as a multipart part named `sylament_upload_<fileKey>`: UX Live
 * reads it off a real `<input type="file">` handed to `component.files()`, and empties that
 * input's list after the response — a plain object would satisfy the same duck type but keep its
 * files forever, re-uploading the same bytes on every later request, so the input is a real
 * element even though it is never attached to the document.
 *
 * The file key is what tells concurrent uploads to one component apart: it keys the pending queue
 * here and rides back in the result payload, so a mixed outcome cannot resolve against the wrong
 * file. Within one key the queue is still FIFO, in action order.
 */
export function uploadSchemaComponentFile(
  component: Component,
  componentKey: string,
  fileKey: string,
  file: File,
): Promise<unknown> {
  const input = document.createElement("input");
  input.type = "file";

  const transfer = new DataTransfer();
  transfer.items.add(file);
  input.files = transfer.files;

  component.files(`sylament_upload_${fileKey}`, input);

  return awaitResult(
    component,
    queueKey(componentKey, UPLOAD_ACTION, fileKey),
    () => component.action(UPLOAD_ACTION, { componentKey, fileKey }),
    `${UPLOAD_ACTION}(${componentKey}, ${fileKey})`,
  );
}

function queueKey(componentKey: string, method: string, fileKey: string | null): string {
  return `${componentKey}\0${method}\0${fileKey ?? ""}`;
}

function awaitResult(
  component: Component,
  key: string,
  start: () => Promise<unknown>,
  label: string,
): Promise<unknown> {
  const byKey = queuesFor(component);
  const queue = queueFor(byKey, key);

  return new Promise<unknown>((resolve, reject) => {
    const entry: Pending = { resolve, reject, label };
    queue.push(entry);

    const settle = (reason: Error): void => {
      const index = queue.indexOf(entry);

      if (index !== -1) {
        queue.splice(index, 1);
        reject(reason);
      }

      dropQueueIfDrained(byKey, key, queue);
    };

    start().then(
      () => settle(new Error(`${label} answered without a result.`)),
      (reason: unknown) =>
        settle(reason instanceof Error ? reason : new Error(`${label} request failed.`)),
    );
  });
}

function queuesFor(component: Component): Map<string, Pending[]> {
  let byKey = queues.get(component);

  if (byKey === undefined) {
    byKey = new Map();
    queues.set(component, byKey);
    listen(component, byKey);
  }

  return byKey;
}

function queueFor(byKey: Map<string, Pending[]>, key: string): Pending[] {
  let queue = byKey.get(key);

  if (queue === undefined) {
    queue = [];
    byKey.set(key, queue);
  }

  return queue;
}

/**
 * Every upload mints a fresh file key, so its queue key is used once and never again — left in
 * place they would accumulate one empty array per uploaded file for the page's lifetime.
 */
function dropQueueIfDrained(byKey: Map<string, Pending[]>, key: string, queue: Pending[]): void {
  if (queue.length === 0 && byKey.get(key) === queue) {
    byKey.delete(key);
  }
}

function listen(component: Component, byKey: Map<string, Pending[]>): void {
  component.element.addEventListener(RESULT_EVENT, (event: Event) => {
    if (event.target !== component.element || !(event instanceof CustomEvent)) {
      return;
    }

    const detail = (event.detail ?? {}) as ResultDetail;
    const fileKey =
      detail.fileKey === undefined || detail.fileKey === null ? null : String(detail.fileKey);
    const key = queueKey(String(detail.componentKey), String(detail.method), fileKey);
    const queue = byKey.get(key);

    if (queue === undefined) {
      return;
    }

    queue.shift()?.resolve(detail.result);
    dropQueueIfDrained(byKey, key, queue);
  });

  component.on("response:error", () => {
    for (const [key, queue] of byKey) {
      for (const entry of queue.splice(0)) {
        entry.reject(new Error(`${entry.label} request failed.`));
      }

      byKey.delete(key);
    }
  });
}
