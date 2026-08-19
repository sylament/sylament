const RESULT_EVENT = "sylament:schema-component-method-result";
const ACTION = "callSchemaComponentMethod";
const queues = /* @__PURE__ */ new WeakMap();
function callSchemaComponentMethod(component, componentKey, method, args = {}) {
  const queue = queueFor(component, `${componentKey}\0${method}`);
  return new Promise((resolve, reject) => {
    const entry = { resolve, reject };
    queue.push(entry);
    const settle = (reason) => {
      const index = queue.indexOf(entry);
      if (index !== -1) {
        queue.splice(index, 1);
        reject(reason);
      }
    };
    component.action(ACTION, { componentKey, method, arguments: args }).then(
      () => settle(new Error(`${ACTION}(${componentKey}, ${method}) answered without a result.`)),
      (reason) => settle(reason instanceof Error ? reason : new Error(`${ACTION} request failed.`))
    );
  });
}
function queueFor(component, key) {
  let byKey = queues.get(component);
  if (byKey === void 0) {
    byKey = /* @__PURE__ */ new Map();
    queues.set(component, byKey);
    listen(component, byKey);
  }
  let queue = byKey.get(key);
  if (queue === void 0) {
    queue = [];
    byKey.set(key, queue);
  }
  return queue;
}
function listen(component, byKey) {
  component.element.addEventListener(RESULT_EVENT, (event) => {
    if (event.target !== component.element || !(event instanceof CustomEvent)) {
      return;
    }
    const detail = event.detail ?? {};
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
export {
  callSchemaComponentMethod
};
//# sourceMappingURL=schema_component_method.js.map
