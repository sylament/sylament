const RESULT_EVENT = "sylament:schema-component-method-result";
const ACTION = "callSchemaComponentMethod";
const UPLOAD_ACTION = "uploadSchemaComponentFile";
const queues = /* @__PURE__ */ new WeakMap();
function callSchemaComponentMethod(component, componentKey, method, args = {}) {
  return awaitResult(
    component,
    queueKey(componentKey, method, null),
    () => component.action(ACTION, { componentKey, method, arguments: args }),
    `${ACTION}(${componentKey}, ${method})`
  );
}
function uploadSchemaComponentFile(component, componentKey, fileKey, file) {
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
    `${UPLOAD_ACTION}(${componentKey}, ${fileKey})`
  );
}
function queueKey(componentKey, method, fileKey) {
  return `${componentKey}\0${method}\0${fileKey ?? ""}`;
}
function awaitResult(component, key, start, label) {
  const byKey = queuesFor(component);
  const queue = queueFor(byKey, key);
  return new Promise((resolve, reject) => {
    const entry = { resolve, reject, label };
    queue.push(entry);
    const settle = (reason) => {
      const index = queue.indexOf(entry);
      if (index !== -1) {
        queue.splice(index, 1);
        reject(reason);
      }
      dropQueueIfDrained(byKey, key, queue);
    };
    start().then(
      () => settle(new Error(`${label} answered without a result.`)),
      (reason) => settle(reason instanceof Error ? reason : new Error(`${label} request failed.`))
    );
  });
}
function queuesFor(component) {
  let byKey = queues.get(component);
  if (byKey === void 0) {
    byKey = /* @__PURE__ */ new Map();
    queues.set(component, byKey);
    listen(component, byKey);
  }
  return byKey;
}
function queueFor(byKey, key) {
  let queue = byKey.get(key);
  if (queue === void 0) {
    queue = [];
    byKey.set(key, queue);
  }
  return queue;
}
function dropQueueIfDrained(byKey, key, queue) {
  if (queue.length === 0 && byKey.get(key) === queue) {
    byKey.delete(key);
  }
}
function listen(component, byKey) {
  component.element.addEventListener(RESULT_EVENT, (event) => {
    if (event.target !== component.element || !(event instanceof CustomEvent)) {
      return;
    }
    const detail = event.detail ?? {};
    const fileKey = detail.fileKey === void 0 || detail.fileKey === null ? null : String(detail.fileKey);
    const key = queueKey(String(detail.componentKey), String(detail.method), fileKey);
    const queue = byKey.get(key);
    if (queue === void 0) {
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
export {
  callSchemaComponentMethod,
  uploadSchemaComponentFile
};
//# sourceMappingURL=schema_component_method.js.map
