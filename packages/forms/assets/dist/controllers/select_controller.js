/*! @floating-ui/dom 1.8.0 | MIT License | Copyright (c) 2021-present Floating UI contributors | https://floating-ui.com */
/*! sortablejs 1.15.7 | MIT License | Copyright (c) 2019 All contributors to Sortable */

// src/controllers/select_controller.ts
import { Controller } from "@hotwired/stimulus";
import { getComponent } from "@symfony/ux-live-component";

// src/utils/schema_component_method.ts
var RESULT_EVENT = "sylament:schema-component-method-result";
var ACTION = "callSchemaComponentMethod";
var queues = /* @__PURE__ */ new WeakMap();
function callSchemaComponentMethod(component, componentKey, method, args = {}) {
  const queue = queueFor(component, `${componentKey}\0${method}`);
  return new Promise((resolve, reject) => {
    const entry = { resolve, reject };
    queue.push(entry);
    const settle = (reason) => {
      const index2 = queue.indexOf(entry);
      if (index2 !== -1) {
        queue.splice(index2, 1);
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

// ../../../node_modules/.pnpm/@floating-ui+utils@0.2.12/node_modules/@floating-ui/utils/dist/floating-ui.utils.mjs
var min = Math.min;
var max = Math.max;
var round = Math.round;
var createCoords = (v) => ({
  x: v,
  y: v
});
var oppositeSideMap = {
  left: "right",
  right: "left",
  bottom: "top",
  top: "bottom"
};
function clamp(start, value, end) {
  return max(start, min(value, end));
}
function evaluate(value, param) {
  return typeof value === "function" ? value(param) : value;
}
function getSide(placement) {
  return placement.split("-")[0];
}
function getAlignment(placement) {
  return placement.split("-")[1];
}
function getOppositeAxis(axis) {
  return axis === "x" ? "y" : "x";
}
function getAxisLength(axis) {
  return axis === "y" ? "height" : "width";
}
function getSideAxis(placement) {
  const firstChar = placement[0];
  return firstChar === "t" || firstChar === "b" ? "y" : "x";
}
function getAlignmentAxis(placement) {
  return getOppositeAxis(getSideAxis(placement));
}
function getAlignmentSides(placement, rects, rtl) {
  if (rtl === void 0) {
    rtl = false;
  }
  const alignment = getAlignment(placement);
  const alignmentAxis = getAlignmentAxis(placement);
  const length = getAxisLength(alignmentAxis);
  let mainAlignmentSide = alignmentAxis === "x" ? alignment === (rtl ? "end" : "start") ? "right" : "left" : alignment === "start" ? "bottom" : "top";
  if (rects.reference[length] > rects.floating[length]) {
    mainAlignmentSide = getOppositePlacement(mainAlignmentSide);
  }
  return [mainAlignmentSide, getOppositePlacement(mainAlignmentSide)];
}
function getExpandedPlacements(placement) {
  const oppositePlacement = getOppositePlacement(placement);
  return [getOppositeAlignmentPlacement(placement), oppositePlacement, getOppositeAlignmentPlacement(oppositePlacement)];
}
function getOppositeAlignmentPlacement(placement) {
  return placement.includes("start") ? placement.replace("start", "end") : placement.replace("end", "start");
}
var lrPlacement = ["left", "right"];
var rlPlacement = ["right", "left"];
var tbPlacement = ["top", "bottom"];
var btPlacement = ["bottom", "top"];
function getSideList(side, isStart, rtl) {
  switch (side) {
    case "top":
    case "bottom":
      if (rtl) return isStart ? rlPlacement : lrPlacement;
      return isStart ? lrPlacement : rlPlacement;
    case "left":
    case "right":
      return isStart ? tbPlacement : btPlacement;
    default:
      return [];
  }
}
function getOppositeAxisPlacements(placement, flipAlignment, direction, rtl) {
  const alignment = getAlignment(placement);
  let list = getSideList(getSide(placement), direction === "start", rtl);
  if (alignment) {
    list = list.map((side) => side + "-" + alignment);
    if (flipAlignment) {
      list = list.concat(list.map(getOppositeAlignmentPlacement));
    }
  }
  return list;
}
function getOppositePlacement(placement) {
  const side = getSide(placement);
  return oppositeSideMap[side] + placement.slice(side.length);
}
function expandPaddingObject(padding) {
  var _padding$top, _padding$right, _padding$bottom, _padding$left;
  return {
    top: (_padding$top = padding.top) != null ? _padding$top : 0,
    right: (_padding$right = padding.right) != null ? _padding$right : 0,
    bottom: (_padding$bottom = padding.bottom) != null ? _padding$bottom : 0,
    left: (_padding$left = padding.left) != null ? _padding$left : 0
  };
}
function getPaddingObject(padding) {
  return typeof padding !== "number" ? expandPaddingObject(padding) : {
    top: padding,
    right: padding,
    bottom: padding,
    left: padding
  };
}
function rectToClientRect(rect) {
  const {
    x,
    y,
    width,
    height
  } = rect;
  return {
    width,
    height,
    top: y,
    left: x,
    right: x + width,
    bottom: y + height,
    x,
    y
  };
}

// ../../../node_modules/.pnpm/@floating-ui+core@1.8.0/node_modules/@floating-ui/core/dist/floating-ui.core.mjs
function computeCoordsFromPlacement(_ref, placement, rtl) {
  let {
    reference,
    floating
  } = _ref;
  const sideAxis = getSideAxis(placement);
  const alignmentAxis = getAlignmentAxis(placement);
  const alignLength = getAxisLength(alignmentAxis);
  const side = getSide(placement);
  const isVertical = sideAxis === "y";
  const commonX = reference.x + reference.width / 2 - floating.width / 2;
  const commonY = reference.y + reference.height / 2 - floating.height / 2;
  const commonAlign = reference[alignLength] / 2 - floating[alignLength] / 2;
  let coords;
  switch (side) {
    case "top":
      coords = {
        x: commonX,
        y: reference.y - floating.height
      };
      break;
    case "bottom":
      coords = {
        x: commonX,
        y: reference.y + reference.height
      };
      break;
    case "right":
      coords = {
        x: reference.x + reference.width,
        y: commonY
      };
      break;
    case "left":
      coords = {
        x: reference.x - floating.width,
        y: commonY
      };
      break;
    default:
      coords = {
        x: reference.x,
        y: reference.y
      };
  }
  const alignment = getAlignment(placement);
  if (alignment) {
    coords[alignmentAxis] += commonAlign * (alignment === "end" ? 1 : -1) * (rtl && isVertical ? -1 : 1);
  }
  return coords;
}
async function detectOverflow(state, options) {
  var _await$platform$isEle;
  if (options === void 0) {
    options = {};
  }
  const {
    x,
    y,
    platform: platform2,
    rects,
    elements,
    strategy
  } = state;
  const {
    boundary = "clippingAncestors",
    rootBoundary = "viewport",
    elementContext = "floating",
    altBoundary = false,
    padding = 0
  } = evaluate(options, state);
  const paddingObject = getPaddingObject(padding);
  const altContext = elementContext === "floating" ? "reference" : "floating";
  const element = elements[altBoundary ? altContext : elementContext];
  const clippingClientRect = rectToClientRect(await platform2.getClippingRect({
    element: ((_await$platform$isEle = await (platform2.isElement == null ? void 0 : platform2.isElement(element))) != null ? _await$platform$isEle : true) ? element : element.contextElement || await (platform2.getDocumentElement == null ? void 0 : platform2.getDocumentElement(elements.floating)),
    boundary,
    rootBoundary,
    strategy
  }));
  const rect = elementContext === "floating" ? {
    x,
    y,
    width: rects.floating.width,
    height: rects.floating.height
  } : rects.reference;
  const offsetParent = await (platform2.getOffsetParent == null ? void 0 : platform2.getOffsetParent(elements.floating));
  const offsetScale = await (platform2.isElement == null ? void 0 : platform2.isElement(offsetParent)) && await (platform2.getScale == null ? void 0 : platform2.getScale(offsetParent)) || {
    x: 1,
    y: 1
  };
  const elementClientRect = rectToClientRect(platform2.convertOffsetParentRelativeRectToViewportRelativeRect ? await platform2.convertOffsetParentRelativeRectToViewportRelativeRect({
    elements,
    rect,
    offsetParent,
    strategy
  }) : rect);
  return {
    top: (clippingClientRect.top - elementClientRect.top + paddingObject.top) / offsetScale.y,
    bottom: (elementClientRect.bottom - clippingClientRect.bottom + paddingObject.bottom) / offsetScale.y,
    left: (clippingClientRect.left - elementClientRect.left + paddingObject.left) / offsetScale.x,
    right: (elementClientRect.right - clippingClientRect.right + paddingObject.right) / offsetScale.x
  };
}
var MAX_RESET_COUNT = 50;
var computePosition = async (reference, floating, config) => {
  const {
    placement = "bottom",
    strategy = "absolute",
    middleware = [],
    platform: platform2
  } = config;
  const platformWithDetectOverflow = platform2.detectOverflow ? platform2 : {
    ...platform2,
    detectOverflow
  };
  const rtl = await (platform2.isRTL == null ? void 0 : platform2.isRTL(floating));
  let rects = await platform2.getElementRects({
    reference,
    floating,
    strategy
  });
  let {
    x,
    y
  } = computeCoordsFromPlacement(rects, placement, rtl);
  let statefulPlacement = placement;
  let resetCount = 0;
  const middlewareData = {};
  for (let i = 0; i < middleware.length; i++) {
    const currentMiddleware = middleware[i];
    if (!currentMiddleware) {
      continue;
    }
    const {
      name,
      fn
    } = currentMiddleware;
    const {
      x: nextX,
      y: nextY,
      data,
      reset
    } = await fn({
      x,
      y,
      initialPlacement: placement,
      placement: statefulPlacement,
      strategy,
      middlewareData,
      rects,
      platform: platformWithDetectOverflow,
      elements: {
        reference,
        floating
      }
    });
    x = nextX != null ? nextX : x;
    y = nextY != null ? nextY : y;
    middlewareData[name] = {
      ...middlewareData[name],
      ...data
    };
    if (reset && resetCount < MAX_RESET_COUNT) {
      resetCount++;
      if (typeof reset === "object") {
        if (reset.placement) {
          statefulPlacement = reset.placement;
        }
        if (reset.rects) {
          rects = reset.rects === true ? await platform2.getElementRects({
            reference,
            floating,
            strategy
          }) : reset.rects;
        }
        ({
          x,
          y
        } = computeCoordsFromPlacement(rects, statefulPlacement, rtl));
      }
      i = -1;
    }
  }
  return {
    x,
    y,
    placement: statefulPlacement,
    strategy,
    middlewareData
  };
};
var flip = function(options) {
  if (options === void 0) {
    options = {};
  }
  return {
    name: "flip",
    options,
    async fn(state) {
      var _middlewareData$arrow, _middlewareData$flip;
      const {
        placement,
        middlewareData,
        rects,
        initialPlacement,
        platform: platform2,
        elements
      } = state;
      const {
        mainAxis: checkMainAxis = true,
        crossAxis: checkCrossAxis = true,
        fallbackPlacements: specifiedFallbackPlacements,
        fallbackStrategy = "bestFit",
        fallbackAxisSideDirection = "none",
        flipAlignment = true,
        ...detectOverflowOptions
      } = evaluate(options, state);
      if ((_middlewareData$arrow = middlewareData.arrow) != null && _middlewareData$arrow.alignmentOffset) {
        return {};
      }
      const side = getSide(placement);
      const initialSideAxis = getSideAxis(initialPlacement);
      const isBasePlacement = getSide(initialPlacement) === initialPlacement;
      const rtl = await (platform2.isRTL == null ? void 0 : platform2.isRTL(elements.floating));
      const fallbackPlacements = specifiedFallbackPlacements || (isBasePlacement || !flipAlignment ? [getOppositePlacement(initialPlacement)] : getExpandedPlacements(initialPlacement));
      const hasFallbackAxisSideDirection = fallbackAxisSideDirection !== "none";
      if (!specifiedFallbackPlacements && hasFallbackAxisSideDirection) {
        fallbackPlacements.push(...getOppositeAxisPlacements(initialPlacement, flipAlignment, fallbackAxisSideDirection, rtl));
      }
      const placements2 = [initialPlacement, ...fallbackPlacements];
      const overflow = await platform2.detectOverflow(state, detectOverflowOptions);
      const overflows = [];
      let overflowsData = ((_middlewareData$flip = middlewareData.flip) == null ? void 0 : _middlewareData$flip.overflows) || [];
      if (checkMainAxis) {
        overflows.push(overflow[side]);
      }
      if (checkCrossAxis) {
        const sides2 = getAlignmentSides(placement, rects, rtl);
        overflows.push(overflow[sides2[0]], overflow[sides2[1]]);
      }
      overflowsData = [...overflowsData, {
        placement,
        overflows
      }];
      if (!overflows.every((side2) => side2 <= 0)) {
        var _middlewareData$flip2, _overflowsData$filter;
        const nextIndex = (((_middlewareData$flip2 = middlewareData.flip) == null ? void 0 : _middlewareData$flip2.index) || 0) + 1;
        const nextPlacement = placements2[nextIndex];
        if (nextPlacement) {
          const ignoreCrossAxisOverflow = checkCrossAxis === "alignment" ? initialSideAxis !== getSideAxis(nextPlacement) : false;
          if (!ignoreCrossAxisOverflow || // We leave the current main axis only if every placement on that axis
          // overflows the main axis.
          overflowsData.every((d) => getSideAxis(d.placement) === initialSideAxis ? d.overflows[0] > 0 : true)) {
            return {
              data: {
                index: nextIndex,
                overflows: overflowsData
              },
              reset: {
                placement: nextPlacement
              }
            };
          }
        }
        let resetPlacement = (_overflowsData$filter = overflowsData.filter((d) => d.overflows[0] <= 0).sort((a, b) => a.overflows[1] - b.overflows[1])[0]) == null ? void 0 : _overflowsData$filter.placement;
        if (!resetPlacement) {
          switch (fallbackStrategy) {
            case "bestFit": {
              var _overflowsData$filter2;
              const placement2 = (_overflowsData$filter2 = overflowsData.filter((d) => {
                if (hasFallbackAxisSideDirection) {
                  const currentSideAxis = getSideAxis(d.placement);
                  return currentSideAxis === initialSideAxis || // Create a bias to the `y` side axis due to horizontal
                  // reading directions favoring greater width.
                  currentSideAxis === "y";
                }
                return true;
              }).map((d) => [d.placement, d.overflows.filter((overflow2) => overflow2 > 0).reduce((acc, overflow2) => acc + overflow2, 0)]).sort((a, b) => a[1] - b[1])[0]) == null ? void 0 : _overflowsData$filter2[0];
              if (placement2) {
                resetPlacement = placement2;
              }
              break;
            }
            case "initialPlacement":
              resetPlacement = initialPlacement;
              break;
          }
        }
        if (placement !== resetPlacement) {
          return {
            reset: {
              placement: resetPlacement
            }
          };
        }
      }
      return {};
    }
  };
};
var originSides = /* @__PURE__ */ new Set(["left", "top"]);
async function convertValueToCoords(state, options) {
  const {
    placement,
    platform: platform2,
    elements
  } = state;
  const rtl = await (platform2.isRTL == null ? void 0 : platform2.isRTL(elements.floating));
  const side = getSide(placement);
  const alignment = getAlignment(placement);
  const isVertical = getSideAxis(placement) === "y";
  const mainAxisMulti = originSides.has(side) ? -1 : 1;
  const crossAxisMulti = rtl && isVertical ? -1 : 1;
  const rawValue = evaluate(options, state);
  let {
    mainAxis,
    crossAxis,
    alignmentAxis
  } = typeof rawValue === "number" ? {
    mainAxis: rawValue,
    crossAxis: 0,
    alignmentAxis: null
  } : {
    mainAxis: rawValue.mainAxis || 0,
    crossAxis: rawValue.crossAxis || 0,
    alignmentAxis: rawValue.alignmentAxis
  };
  if (alignment && typeof alignmentAxis === "number") {
    crossAxis = alignment === "end" ? alignmentAxis * -1 : alignmentAxis;
  }
  return isVertical ? {
    x: crossAxis * crossAxisMulti,
    y: mainAxis * mainAxisMulti
  } : {
    x: mainAxis * mainAxisMulti,
    y: crossAxis * crossAxisMulti
  };
}
var offset = function(options) {
  if (options === void 0) {
    options = 0;
  }
  return {
    name: "offset",
    options,
    async fn(state) {
      var _middlewareData$offse, _middlewareData$arrow;
      const {
        x,
        y,
        placement,
        middlewareData
      } = state;
      const diffCoords = await convertValueToCoords(state, options);
      if (placement === ((_middlewareData$offse = middlewareData.offset) == null ? void 0 : _middlewareData$offse.placement) && (_middlewareData$arrow = middlewareData.arrow) != null && _middlewareData$arrow.alignmentOffset) {
        return {};
      }
      return {
        x: x + diffCoords.x,
        y: y + diffCoords.y,
        data: {
          ...diffCoords,
          placement
        }
      };
    }
  };
};
var shift = function(options) {
  if (options === void 0) {
    options = {};
  }
  return {
    name: "shift",
    options,
    async fn(state) {
      const {
        x,
        y,
        placement,
        platform: platform2
      } = state;
      const {
        mainAxis: checkMainAxis = true,
        crossAxis: checkCrossAxis = false,
        limiter = {
          fn: (_ref) => {
            let {
              x: x2,
              y: y2
            } = _ref;
            return {
              x: x2,
              y: y2
            };
          }
        },
        ...detectOverflowOptions
      } = evaluate(options, state);
      const coords = {
        x,
        y
      };
      const overflow = await platform2.detectOverflow(state, detectOverflowOptions);
      const crossAxis = getSideAxis(placement);
      const mainAxis = getOppositeAxis(crossAxis);
      let mainAxisCoord = coords[mainAxis];
      let crossAxisCoord = coords[crossAxis];
      const clampCoord = (axis, coord) => clamp(coord + overflow[axis === "y" ? "top" : "left"], coord, coord - overflow[axis === "y" ? "bottom" : "right"]);
      if (checkMainAxis) {
        mainAxisCoord = clampCoord(mainAxis, mainAxisCoord);
      }
      if (checkCrossAxis) {
        crossAxisCoord = clampCoord(crossAxis, crossAxisCoord);
      }
      const limitedCoords = limiter.fn({
        ...state,
        [mainAxis]: mainAxisCoord,
        [crossAxis]: crossAxisCoord
      });
      return {
        ...limitedCoords,
        data: {
          x: limitedCoords.x - x,
          y: limitedCoords.y - y,
          enabled: {
            [mainAxis]: checkMainAxis,
            [crossAxis]: checkCrossAxis
          }
        }
      };
    }
  };
};

// ../../../node_modules/.pnpm/@floating-ui+utils@0.2.12/node_modules/@floating-ui/utils/dist/floating-ui.utils.dom.mjs
function hasWindow() {
  return typeof window !== "undefined";
}
function getNodeName(node) {
  if (isNode(node)) {
    return (node.nodeName || "").toLowerCase();
  }
  return "#document";
}
function getWindow(node) {
  var _node$ownerDocument;
  return (node == null || (_node$ownerDocument = node.ownerDocument) == null ? void 0 : _node$ownerDocument.defaultView) || window;
}
function getDocumentElement(node) {
  var _ref;
  return (_ref = (isNode(node) ? node.ownerDocument : node.document) || window.document) == null ? void 0 : _ref.documentElement;
}
function isNode(value) {
  if (!hasWindow()) {
    return false;
  }
  return value instanceof Node || value instanceof getWindow(value).Node;
}
function isElement(value) {
  if (!hasWindow()) {
    return false;
  }
  return value instanceof Element || value instanceof getWindow(value).Element;
}
function isHTMLElement(value) {
  if (!hasWindow()) {
    return false;
  }
  return value instanceof HTMLElement || value instanceof getWindow(value).HTMLElement;
}
function isShadowRoot(value) {
  if (!hasWindow() || typeof ShadowRoot === "undefined") {
    return false;
  }
  return value instanceof ShadowRoot || value instanceof getWindow(value).ShadowRoot;
}
function isOverflowElement(element) {
  const {
    overflow,
    overflowX,
    overflowY,
    display
  } = getComputedStyle2(element);
  return /auto|scroll|overlay|hidden|clip/.test(overflow + overflowY + overflowX) && display !== "inline" && display !== "contents";
}
function isTableElement(element) {
  return /^(table|td|th)$/.test(getNodeName(element));
}
function isTopLayer(element) {
  try {
    if (element.matches(":popover-open")) {
      return true;
    }
  } catch (_e) {
  }
  try {
    return element.matches(":modal");
  } catch (_e) {
    return false;
  }
}
var willChangeRe = /transform|translate|scale|rotate|perspective|filter/;
var containRe = /paint|layout|strict|content/;
var isNotNone = (value) => !!value && value !== "none";
var isWebKitValue;
function isContainingBlock(elementOrCss) {
  const css2 = isElement(elementOrCss) ? getComputedStyle2(elementOrCss) : elementOrCss;
  return isNotNone(css2.transform) || isNotNone(css2.translate) || isNotNone(css2.scale) || isNotNone(css2.rotate) || isNotNone(css2.perspective) || !isWebKit() && (isNotNone(css2.backdropFilter) || isNotNone(css2.filter)) || willChangeRe.test(css2.willChange || "") || containRe.test(css2.contain || "");
}
function getContainingBlock(element) {
  let currentNode = getParentNode(element);
  while (isHTMLElement(currentNode) && !isLastTraversableNode(currentNode)) {
    if (isContainingBlock(currentNode)) {
      return currentNode;
    } else if (isTopLayer(currentNode)) {
      return null;
    }
    currentNode = getParentNode(currentNode);
  }
  return null;
}
function isWebKit() {
  if (isWebKitValue == null) {
    isWebKitValue = typeof CSS !== "undefined" && CSS.supports && CSS.supports("-webkit-backdrop-filter", "none");
  }
  return isWebKitValue;
}
function isLastTraversableNode(node) {
  return /^(html|body|#document)$/.test(getNodeName(node));
}
function getComputedStyle2(element) {
  return getWindow(element).getComputedStyle(element);
}
function getNodeScroll(element) {
  if (isElement(element)) {
    return {
      scrollLeft: element.scrollLeft,
      scrollTop: element.scrollTop
    };
  }
  return {
    scrollLeft: element.scrollX,
    scrollTop: element.scrollY
  };
}
function getParentNode(node) {
  if (getNodeName(node) === "html") {
    return node;
  }
  const result = (
    // Step into the shadow DOM of the parent of a slotted node.
    node.assignedSlot || // DOM Element detected.
    node.parentNode || // ShadowRoot detected.
    isShadowRoot(node) && node.host || // Fallback.
    getDocumentElement(node)
  );
  return isShadowRoot(result) ? result.host : result;
}
function getNearestOverflowAncestor(node) {
  const parentNode = getParentNode(node);
  if (isLastTraversableNode(parentNode)) {
    return (node.ownerDocument || node).body;
  }
  if (isHTMLElement(parentNode) && isOverflowElement(parentNode)) {
    return parentNode;
  }
  return getNearestOverflowAncestor(parentNode);
}
function getOverflowAncestors(node, list, traverseIframes) {
  var _node$ownerDocument2;
  if (list === void 0) {
    list = [];
  }
  if (traverseIframes === void 0) {
    traverseIframes = true;
  }
  const scrollableAncestor = getNearestOverflowAncestor(node);
  const isBody = scrollableAncestor === ((_node$ownerDocument2 = node.ownerDocument) == null ? void 0 : _node$ownerDocument2.body);
  const win = getWindow(scrollableAncestor);
  if (isBody) {
    const frameElement = getFrameElement(win);
    return list.concat(win, win.visualViewport || [], isOverflowElement(scrollableAncestor) ? scrollableAncestor : [], frameElement && traverseIframes ? getOverflowAncestors(frameElement) : []);
  } else {
    return list.concat(scrollableAncestor, getOverflowAncestors(scrollableAncestor, [], traverseIframes));
  }
}
function getFrameElement(win) {
  return win.parent && Object.getPrototypeOf(win.parent) ? win.frameElement : null;
}

// ../../../node_modules/.pnpm/@floating-ui+dom@1.8.0/node_modules/@floating-ui/dom/dist/floating-ui.dom.mjs
function getCssDimensions(element) {
  const css2 = getComputedStyle2(element);
  let width = parseFloat(css2.width) || 0;
  let height = parseFloat(css2.height) || 0;
  const hasOffset = isHTMLElement(element);
  const offsetWidth = hasOffset ? element.offsetWidth : width;
  const offsetHeight = hasOffset ? element.offsetHeight : height;
  const shouldFallback = round(width) !== offsetWidth || round(height) !== offsetHeight;
  if (shouldFallback) {
    width = offsetWidth;
    height = offsetHeight;
  }
  return {
    width,
    height,
    $: shouldFallback
  };
}
function unwrapElement(element) {
  return !isElement(element) ? element.contextElement : element;
}
function getScale(element) {
  const domElement = unwrapElement(element);
  if (!isHTMLElement(domElement)) {
    return createCoords(1);
  }
  const rect = domElement.getBoundingClientRect();
  const {
    width,
    height,
    $
  } = getCssDimensions(domElement);
  let x = ($ ? round(rect.width) : rect.width) / width;
  let y = ($ ? round(rect.height) : rect.height) / height;
  if (!x || !Number.isFinite(x)) {
    x = 1;
  }
  if (!y || !Number.isFinite(y)) {
    y = 1;
  }
  return {
    x,
    y
  };
}
var noOffsets = /* @__PURE__ */ createCoords(0);
function getVisualOffsets(element) {
  const win = getWindow(element);
  if (!isWebKit() || !win.visualViewport) {
    return noOffsets;
  }
  return {
    x: win.visualViewport.offsetLeft,
    y: win.visualViewport.offsetTop
  };
}
function shouldAddVisualOffsets(element, isFixed, floatingOffsetParent) {
  if (isFixed === void 0) {
    isFixed = false;
  }
  return !!floatingOffsetParent && isFixed && floatingOffsetParent === getWindow(element);
}
function getBoundingClientRect(element, includeScale, isFixedStrategy, offsetParent) {
  if (includeScale === void 0) {
    includeScale = false;
  }
  if (isFixedStrategy === void 0) {
    isFixedStrategy = false;
  }
  const clientRect = element.getBoundingClientRect();
  const domElement = unwrapElement(element);
  let scale = createCoords(1);
  if (includeScale) {
    if (offsetParent) {
      if (isElement(offsetParent)) {
        scale = getScale(offsetParent);
      }
    } else {
      scale = getScale(element);
    }
  }
  const visualOffsets = shouldAddVisualOffsets(domElement, isFixedStrategy, offsetParent) ? getVisualOffsets(domElement) : createCoords(0);
  let x = (clientRect.left + visualOffsets.x) / scale.x;
  let y = (clientRect.top + visualOffsets.y) / scale.y;
  let width = clientRect.width / scale.x;
  let height = clientRect.height / scale.y;
  if (domElement && offsetParent) {
    const win = getWindow(domElement);
    const offsetWin = isElement(offsetParent) ? getWindow(offsetParent) : offsetParent;
    let currentWin = win;
    let currentIFrame = getFrameElement(currentWin);
    while (currentIFrame && offsetWin !== currentWin) {
      const iframeScale = getScale(currentIFrame);
      const iframeRect = currentIFrame.getBoundingClientRect();
      const css2 = getComputedStyle2(currentIFrame);
      const left = iframeRect.left + (currentIFrame.clientLeft + parseFloat(css2.paddingLeft)) * iframeScale.x;
      const top = iframeRect.top + (currentIFrame.clientTop + parseFloat(css2.paddingTop)) * iframeScale.y;
      x *= iframeScale.x;
      y *= iframeScale.y;
      width *= iframeScale.x;
      height *= iframeScale.y;
      x += left;
      y += top;
      currentWin = getWindow(currentIFrame);
      currentIFrame = getFrameElement(currentWin);
    }
  }
  return rectToClientRect({
    width,
    height,
    x,
    y
  });
}
function getWindowScrollBarX(element, rect) {
  const leftScroll = getNodeScroll(element).scrollLeft;
  if (!rect) {
    return getBoundingClientRect(getDocumentElement(element)).left + leftScroll;
  }
  return rect.left + leftScroll;
}
function getHTMLOffset(documentElement, scroll) {
  const htmlRect = documentElement.getBoundingClientRect();
  const x = htmlRect.left + scroll.scrollLeft - getWindowScrollBarX(documentElement, htmlRect);
  const y = htmlRect.top + scroll.scrollTop;
  return {
    x,
    y
  };
}
function convertOffsetParentRelativeRectToViewportRelativeRect(_ref) {
  let {
    elements,
    rect,
    offsetParent,
    strategy
  } = _ref;
  const isFixed = strategy === "fixed";
  const documentElement = getDocumentElement(offsetParent);
  const topLayer = elements ? isTopLayer(elements.floating) : false;
  if (offsetParent === documentElement || topLayer && isFixed) {
    return rect;
  }
  let scroll = {
    scrollLeft: 0,
    scrollTop: 0
  };
  let scale = createCoords(1);
  const offsets = createCoords(0);
  const isOffsetParentAnElement = isHTMLElement(offsetParent);
  if (isOffsetParentAnElement || !isFixed) {
    if (getNodeName(offsetParent) !== "body" || isOverflowElement(documentElement)) {
      scroll = getNodeScroll(offsetParent);
    }
    if (isOffsetParentAnElement) {
      const offsetRect = getBoundingClientRect(offsetParent);
      scale = getScale(offsetParent);
      offsets.x = offsetRect.x + offsetParent.clientLeft;
      offsets.y = offsetRect.y + offsetParent.clientTop;
    }
  }
  const htmlOffset = documentElement && !isOffsetParentAnElement && !isFixed ? getHTMLOffset(documentElement, scroll) : createCoords(0);
  return {
    width: rect.width * scale.x,
    height: rect.height * scale.y,
    x: rect.x * scale.x - scroll.scrollLeft * scale.x + offsets.x + htmlOffset.x,
    y: rect.y * scale.y - scroll.scrollTop * scale.y + offsets.y + htmlOffset.y
  };
}
function getClientRects(element) {
  return element.getClientRects ? Array.from(element.getClientRects()) : [];
}
function getDocumentRect(html) {
  const scroll = getNodeScroll(html);
  const body = html.ownerDocument.body;
  const width = max(html.scrollWidth, html.clientWidth, body.scrollWidth, body.clientWidth);
  const height = max(html.scrollHeight, html.clientHeight, body.scrollHeight, body.clientHeight);
  let x = -scroll.scrollLeft + getWindowScrollBarX(html);
  const y = -scroll.scrollTop;
  if (getComputedStyle2(body).direction === "rtl") {
    x += max(html.clientWidth, body.clientWidth) - width;
  }
  return {
    width,
    height,
    x,
    y
  };
}
var SCROLLBAR_MAX = 25;
function getViewportRect(element, strategy, rootBoundary) {
  if (rootBoundary === void 0) {
    rootBoundary = "viewport";
  }
  const isLayoutViewport = rootBoundary === "layoutViewport";
  const win = getWindow(element);
  const html = getDocumentElement(element);
  const visualViewport = win.visualViewport;
  let width = html.clientWidth;
  let height = html.clientHeight;
  let x = 0;
  let y = 0;
  if (visualViewport) {
    const layoutRelativeClientCoords = !isWebKit() || strategy === "fixed";
    if (isLayoutViewport) {
      if (!layoutRelativeClientCoords) {
        x = -visualViewport.offsetLeft;
        y = -visualViewport.offsetTop;
      }
    } else {
      width = visualViewport.width;
      height = visualViewport.height;
      if (layoutRelativeClientCoords) {
        x = visualViewport.offsetLeft;
        y = visualViewport.offsetTop;
      }
    }
  }
  const windowScrollbarX = getWindowScrollBarX(html);
  if (windowScrollbarX <= 0) {
    const doc = html.ownerDocument;
    const body = doc.body;
    const bodyStyles = getComputedStyle(body);
    const bodyMarginInline = doc.compatMode === "CSS1Compat" ? parseFloat(bodyStyles.marginLeft) + parseFloat(bodyStyles.marginRight) || 0 : 0;
    const reservedWidth = Math.abs(html.clientWidth - body.clientWidth - bodyMarginInline);
    const gutter = getComputedStyle(html).scrollbarGutter === "stable both-edges" ? reservedWidth / 2 : reservedWidth;
    if (gutter <= SCROLLBAR_MAX) {
      width -= gutter;
    }
  }
  return {
    width,
    height,
    x,
    y
  };
}
function getInnerBoundingClientRect(element, strategy) {
  const clientRect = getBoundingClientRect(element, true, strategy === "fixed");
  const top = clientRect.top + element.clientTop;
  const left = clientRect.left + element.clientLeft;
  const scale = getScale(element);
  const width = element.clientWidth * scale.x;
  const height = element.clientHeight * scale.y;
  const x = left * scale.x;
  const y = top * scale.y;
  return {
    width,
    height,
    x,
    y
  };
}
function getClientRectFromClippingAncestor(element, clippingAncestor, strategy) {
  let rect;
  if (clippingAncestor === "viewport" || clippingAncestor === "layoutViewport") {
    rect = getViewportRect(element, strategy, clippingAncestor);
  } else if (clippingAncestor === "document") {
    rect = getDocumentRect(getDocumentElement(element));
  } else if (isElement(clippingAncestor)) {
    rect = getInnerBoundingClientRect(clippingAncestor, strategy);
  } else {
    const visualOffsets = getVisualOffsets(element);
    rect = {
      x: clippingAncestor.x - visualOffsets.x,
      y: clippingAncestor.y - visualOffsets.y,
      width: clippingAncestor.width,
      height: clippingAncestor.height
    };
  }
  return rectToClientRect(rect);
}
function getClippingElementAncestors(element, cache) {
  const cachedResult = cache.get(element);
  if (cachedResult) {
    return cachedResult;
  }
  let result = getOverflowAncestors(element, [], false).filter((el) => isElement(el) && getNodeName(el) !== "body");
  let lastKeptComputedStyle = null;
  const elementIsFixed = getComputedStyle2(element).position === "fixed";
  let currentNode = elementIsFixed ? getParentNode(element) : element;
  while (isElement(currentNode) && !isLastTraversableNode(currentNode)) {
    const computedStyle = getComputedStyle2(currentNode);
    const currentNodeIsContaining = isContainingBlock(currentNode);
    const lastPosition = lastKeptComputedStyle ? lastKeptComputedStyle.position : elementIsFixed ? "fixed" : "";
    const shouldDropCurrentNode = !currentNodeIsContaining && (lastPosition === "fixed" || lastPosition === "absolute" && computedStyle.position === "static");
    if (shouldDropCurrentNode) {
      result = result.filter((ancestor) => ancestor !== currentNode);
    } else {
      lastKeptComputedStyle = computedStyle;
    }
    currentNode = getParentNode(currentNode);
  }
  cache.set(element, result);
  return result;
}
function getClippingRect(_ref) {
  let {
    element,
    boundary,
    rootBoundary,
    strategy
  } = _ref;
  const elementClippingAncestors = boundary === "clippingAncestors" ? isTopLayer(element) ? [] : getClippingElementAncestors(element, this._c) : [].concat(boundary);
  const clippingAncestors = [...elementClippingAncestors, rootBoundary];
  const firstRect = getClientRectFromClippingAncestor(element, clippingAncestors[0], strategy);
  let top = firstRect.top;
  let right = firstRect.right;
  let bottom = firstRect.bottom;
  let left = firstRect.left;
  for (let i = 1; i < clippingAncestors.length; i++) {
    const rect = getClientRectFromClippingAncestor(element, clippingAncestors[i], strategy);
    top = max(rect.top, top);
    right = min(rect.right, right);
    bottom = min(rect.bottom, bottom);
    left = max(rect.left, left);
  }
  return {
    width: right - left,
    height: bottom - top,
    x: left,
    y: top
  };
}
function getDimensions(element) {
  const {
    width,
    height
  } = getCssDimensions(element);
  return {
    width,
    height
  };
}
function getRectRelativeToOffsetParent(element, offsetParent, strategy) {
  const isOffsetParentAnElement = isHTMLElement(offsetParent);
  const documentElement = getDocumentElement(offsetParent);
  const isFixed = strategy === "fixed";
  const rect = getBoundingClientRect(element, true, isFixed, offsetParent);
  let scroll = {
    scrollLeft: 0,
    scrollTop: 0
  };
  const offsets = createCoords(0);
  if (isOffsetParentAnElement || !isFixed) {
    if (getNodeName(offsetParent) !== "body" || isOverflowElement(documentElement)) {
      scroll = getNodeScroll(offsetParent);
    }
    if (isOffsetParentAnElement) {
      const offsetRect = getBoundingClientRect(offsetParent, true, isFixed, offsetParent);
      offsets.x = offsetRect.x + offsetParent.clientLeft;
      offsets.y = offsetRect.y + offsetParent.clientTop;
    }
  }
  if (!isOffsetParentAnElement && documentElement) {
    offsets.x = getWindowScrollBarX(documentElement);
  }
  const htmlOffset = documentElement && !isOffsetParentAnElement && !isFixed ? getHTMLOffset(documentElement, scroll) : createCoords(0);
  const x = rect.left + scroll.scrollLeft - offsets.x - htmlOffset.x;
  const y = rect.top + scroll.scrollTop - offsets.y - htmlOffset.y;
  return {
    x,
    y,
    width: rect.width,
    height: rect.height
  };
}
function isStaticPositioned(element) {
  return getComputedStyle2(element).position === "static";
}
function getTrueOffsetParent(element, polyfill) {
  if (!isHTMLElement(element) || getComputedStyle2(element).position === "fixed") {
    return null;
  }
  if (polyfill) {
    return polyfill(element);
  }
  let rawOffsetParent = element.offsetParent;
  if (getDocumentElement(element) === rawOffsetParent) {
    rawOffsetParent = rawOffsetParent.ownerDocument.body;
  }
  return rawOffsetParent;
}
function getOffsetParent(element, polyfill) {
  const win = getWindow(element);
  if (isTopLayer(element)) {
    return win;
  }
  if (!isHTMLElement(element)) {
    let svgOffsetParent = getParentNode(element);
    while (svgOffsetParent && !isLastTraversableNode(svgOffsetParent)) {
      if (isElement(svgOffsetParent) && !isStaticPositioned(svgOffsetParent)) {
        return svgOffsetParent;
      }
      svgOffsetParent = getParentNode(svgOffsetParent);
    }
    return win;
  }
  let offsetParent = getTrueOffsetParent(element, polyfill);
  while (offsetParent && isTableElement(offsetParent) && isStaticPositioned(offsetParent)) {
    offsetParent = getTrueOffsetParent(offsetParent, polyfill);
  }
  if (offsetParent && isLastTraversableNode(offsetParent) && isStaticPositioned(offsetParent) && !isContainingBlock(offsetParent)) {
    return win;
  }
  return offsetParent || getContainingBlock(element) || win;
}
var getElementRects = async function(data) {
  const getOffsetParentFn = this.getOffsetParent || getOffsetParent;
  const getDimensionsFn = this.getDimensions;
  const floatingDimensions = await getDimensionsFn(data.floating);
  return {
    reference: getRectRelativeToOffsetParent(data.reference, await getOffsetParentFn(data.floating), data.strategy),
    floating: {
      x: 0,
      y: 0,
      width: floatingDimensions.width,
      height: floatingDimensions.height
    }
  };
};
function isRTL(element) {
  return getComputedStyle2(element).direction === "rtl";
}
var platform = {
  convertOffsetParentRelativeRectToViewportRelativeRect,
  getDocumentElement,
  getClippingRect,
  getOffsetParent,
  getElementRects,
  getClientRects,
  getDimensions,
  getScale,
  isElement,
  isRTL
};
var offset2 = offset;
var shift2 = shift;
var flip2 = flip;
var computePosition2 = (reference, floating, options) => {
  const cache = /* @__PURE__ */ new Map();
  const mergedOptions = options != null ? options : {};
  const platformWithCache = {
    ...platform,
    ...mergedOptions.platform,
    _c: cache
  };
  return computePosition(reference, floating, {
    ...mergedOptions,
    platform: platformWithCache
  });
};

// ../../../node_modules/.pnpm/sortablejs@1.15.7/node_modules/sortablejs/modular/sortable.esm.js
function _defineProperty(e, r, t) {
  return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, {
    value: t,
    enumerable: true,
    configurable: true,
    writable: true
  }) : e[r] = t, e;
}
function _extends() {
  return _extends = Object.assign ? Object.assign.bind() : function(n) {
    for (var e = 1; e < arguments.length; e++) {
      var t = arguments[e];
      for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]);
    }
    return n;
  }, _extends.apply(null, arguments);
}
function ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function(r2) {
      return Object.getOwnPropertyDescriptor(e, r2).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function _objectSpread2(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? ownKeys(Object(t), true).forEach(function(r2) {
      _defineProperty(e, r2, t[r2]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function(r2) {
      Object.defineProperty(e, r2, Object.getOwnPropertyDescriptor(t, r2));
    });
  }
  return e;
}
function _objectWithoutProperties(e, t) {
  if (null == e) return {};
  var o, r, i = _objectWithoutPropertiesLoose(e, t);
  if (Object.getOwnPropertySymbols) {
    var n = Object.getOwnPropertySymbols(e);
    for (r = 0; r < n.length; r++) o = n[r], -1 === t.indexOf(o) && {}.propertyIsEnumerable.call(e, o) && (i[o] = e[o]);
  }
  return i;
}
function _objectWithoutPropertiesLoose(r, e) {
  if (null == r) return {};
  var t = {};
  for (var n in r) if ({}.hasOwnProperty.call(r, n)) {
    if (-1 !== e.indexOf(n)) continue;
    t[n] = r[n];
  }
  return t;
}
function _toPrimitive(t, r) {
  if ("object" != typeof t || !t) return t;
  var e = t[Symbol.toPrimitive];
  if (void 0 !== e) {
    var i = e.call(t, r || "default");
    if ("object" != typeof i) return i;
    throw new TypeError("@@toPrimitive must return a primitive value.");
  }
  return ("string" === r ? String : Number)(t);
}
function _toPropertyKey(t) {
  var i = _toPrimitive(t, "string");
  return "symbol" == typeof i ? i : i + "";
}
function _typeof(o) {
  "@babel/helpers - typeof";
  return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function(o2) {
    return typeof o2;
  } : function(o2) {
    return o2 && "function" == typeof Symbol && o2.constructor === Symbol && o2 !== Symbol.prototype ? "symbol" : typeof o2;
  }, _typeof(o);
}
var version = "1.15.7";
function userAgent(pattern) {
  if (typeof window !== "undefined" && window.navigator) {
    return !!/* @__PURE__ */ navigator.userAgent.match(pattern);
  }
}
var IE11OrLess = userAgent(/(?:Trident.*rv[ :]?11\.|msie|iemobile|Windows Phone)/i);
var Edge = userAgent(/Edge/i);
var FireFox = userAgent(/firefox/i);
var Safari = userAgent(/safari/i) && !userAgent(/chrome/i) && !userAgent(/android/i);
var IOS = userAgent(/iP(ad|od|hone)/i);
var ChromeForAndroid = userAgent(/chrome/i) && userAgent(/android/i);
var captureMode = {
  capture: false,
  passive: false
};
function on(el, event, fn) {
  el.addEventListener(event, fn, !IE11OrLess && captureMode);
}
function off(el, event, fn) {
  el.removeEventListener(event, fn, !IE11OrLess && captureMode);
}
function matches(el, selector) {
  if (!selector) return;
  selector[0] === ">" && (selector = selector.substring(1));
  if (el) {
    try {
      if (el.matches) {
        return el.matches(selector);
      } else if (el.msMatchesSelector) {
        return el.msMatchesSelector(selector);
      } else if (el.webkitMatchesSelector) {
        return el.webkitMatchesSelector(selector);
      }
    } catch (_) {
      return false;
    }
  }
  return false;
}
function getParentOrHost(el) {
  return el.host && el !== document && el.host.nodeType && el.host !== el ? el.host : el.parentNode;
}
function closest(el, selector, ctx, includeCTX) {
  if (el) {
    ctx = ctx || document;
    do {
      if (selector != null && (selector[0] === ">" ? el.parentNode === ctx && matches(el, selector) : matches(el, selector)) || includeCTX && el === ctx) {
        return el;
      }
      if (el === ctx) break;
    } while (el = getParentOrHost(el));
  }
  return null;
}
var R_SPACE = /\s+/g;
function toggleClass(el, name, state) {
  if (el && name) {
    if (el.classList) {
      el.classList[state ? "add" : "remove"](name);
    } else {
      var className = (" " + el.className + " ").replace(R_SPACE, " ").replace(" " + name + " ", " ");
      el.className = (className + (state ? " " + name : "")).replace(R_SPACE, " ");
    }
  }
}
function css(el, prop, val) {
  var style = el && el.style;
  if (style) {
    if (val === void 0) {
      if (document.defaultView && document.defaultView.getComputedStyle) {
        val = document.defaultView.getComputedStyle(el, "");
      } else if (el.currentStyle) {
        val = el.currentStyle;
      }
      return prop === void 0 ? val : val[prop];
    } else {
      if (!(prop in style) && prop.indexOf("webkit") === -1) {
        prop = "-webkit-" + prop;
      }
      style[prop] = val + (typeof val === "string" ? "" : "px");
    }
  }
}
function matrix(el, selfOnly) {
  var appliedTransforms = "";
  if (typeof el === "string") {
    appliedTransforms = el;
  } else {
    do {
      var transform = css(el, "transform");
      if (transform && transform !== "none") {
        appliedTransforms = transform + " " + appliedTransforms;
      }
    } while (!selfOnly && (el = el.parentNode));
  }
  var matrixFn = window.DOMMatrix || window.WebKitCSSMatrix || window.CSSMatrix || window.MSCSSMatrix;
  return matrixFn && new matrixFn(appliedTransforms);
}
function find(ctx, tagName, iterator) {
  if (ctx) {
    var list = ctx.getElementsByTagName(tagName), i = 0, n = list.length;
    if (iterator) {
      for (; i < n; i++) {
        iterator(list[i], i);
      }
    }
    return list;
  }
  return [];
}
function getWindowScrollingElement() {
  var scrollingElement = document.scrollingElement;
  if (scrollingElement) {
    return scrollingElement;
  } else {
    return document.documentElement;
  }
}
function getRect(el, relativeToContainingBlock, relativeToNonStaticParent, undoScale, container) {
  if (!el.getBoundingClientRect && el !== window) return;
  var elRect, top, left, bottom, right, height, width;
  if (el !== window && el.parentNode && el !== getWindowScrollingElement()) {
    elRect = el.getBoundingClientRect();
    top = elRect.top;
    left = elRect.left;
    bottom = elRect.bottom;
    right = elRect.right;
    height = elRect.height;
    width = elRect.width;
  } else {
    top = 0;
    left = 0;
    bottom = window.innerHeight;
    right = window.innerWidth;
    height = window.innerHeight;
    width = window.innerWidth;
  }
  if ((relativeToContainingBlock || relativeToNonStaticParent) && el !== window) {
    container = container || el.parentNode;
    if (!IE11OrLess) {
      do {
        if (container && container.getBoundingClientRect && (css(container, "transform") !== "none" || relativeToNonStaticParent && css(container, "position") !== "static")) {
          var containerRect = container.getBoundingClientRect();
          top -= containerRect.top + parseInt(css(container, "border-top-width"));
          left -= containerRect.left + parseInt(css(container, "border-left-width"));
          bottom = top + elRect.height;
          right = left + elRect.width;
          break;
        }
      } while (container = container.parentNode);
    }
  }
  if (undoScale && el !== window) {
    var elMatrix = matrix(container || el), scaleX = elMatrix && elMatrix.a, scaleY = elMatrix && elMatrix.d;
    if (elMatrix) {
      top /= scaleY;
      left /= scaleX;
      width /= scaleX;
      height /= scaleY;
      bottom = top + height;
      right = left + width;
    }
  }
  return {
    top,
    left,
    bottom,
    right,
    width,
    height
  };
}
function isScrolledPast(el, elSide, parentSide) {
  var parent = getParentAutoScrollElement(el, true), elSideVal = getRect(el)[elSide];
  while (parent) {
    var parentSideVal = getRect(parent)[parentSide], visible = void 0;
    if (parentSide === "top" || parentSide === "left") {
      visible = elSideVal >= parentSideVal;
    } else {
      visible = elSideVal <= parentSideVal;
    }
    if (!visible) return parent;
    if (parent === getWindowScrollingElement()) break;
    parent = getParentAutoScrollElement(parent, false);
  }
  return false;
}
function getChild(el, childNum, options, includeDragEl) {
  var currentChild = 0, i = 0, children = el.children;
  while (i < children.length) {
    if (children[i].style.display !== "none" && children[i] !== Sortable.ghost && (includeDragEl || children[i] !== Sortable.dragged) && closest(children[i], options.draggable, el, false)) {
      if (currentChild === childNum) {
        return children[i];
      }
      currentChild++;
    }
    i++;
  }
  return null;
}
function lastChild(el, selector) {
  var last = el.lastElementChild;
  while (last && (last === Sortable.ghost || css(last, "display") === "none" || selector && !matches(last, selector))) {
    last = last.previousElementSibling;
  }
  return last || null;
}
function index(el, selector) {
  var index2 = 0;
  if (!el || !el.parentNode) {
    return -1;
  }
  while (el = el.previousElementSibling) {
    if (el.nodeName.toUpperCase() !== "TEMPLATE" && el !== Sortable.clone && (!selector || matches(el, selector))) {
      index2++;
    }
  }
  return index2;
}
function getRelativeScrollOffset(el) {
  var offsetLeft = 0, offsetTop = 0, winScroller = getWindowScrollingElement();
  if (el) {
    do {
      var elMatrix = matrix(el), scaleX = elMatrix.a, scaleY = elMatrix.d;
      offsetLeft += el.scrollLeft * scaleX;
      offsetTop += el.scrollTop * scaleY;
    } while (el !== winScroller && (el = el.parentNode));
  }
  return [offsetLeft, offsetTop];
}
function indexOfObject(arr, obj) {
  for (var i in arr) {
    if (!arr.hasOwnProperty(i)) continue;
    for (var key in obj) {
      if (obj.hasOwnProperty(key) && obj[key] === arr[i][key]) return Number(i);
    }
  }
  return -1;
}
function getParentAutoScrollElement(el, includeSelf) {
  if (!el || !el.getBoundingClientRect) return getWindowScrollingElement();
  var elem = el;
  var gotSelf = false;
  do {
    if (elem.clientWidth < elem.scrollWidth || elem.clientHeight < elem.scrollHeight) {
      var elemCSS = css(elem);
      if (elem.clientWidth < elem.scrollWidth && (elemCSS.overflowX == "auto" || elemCSS.overflowX == "scroll") || elem.clientHeight < elem.scrollHeight && (elemCSS.overflowY == "auto" || elemCSS.overflowY == "scroll")) {
        if (!elem.getBoundingClientRect || elem === document.body) return getWindowScrollingElement();
        if (gotSelf || includeSelf) return elem;
        gotSelf = true;
      }
    }
  } while (elem = elem.parentNode);
  return getWindowScrollingElement();
}
function extend(dst, src) {
  if (dst && src) {
    for (var key in src) {
      if (src.hasOwnProperty(key)) {
        dst[key] = src[key];
      }
    }
  }
  return dst;
}
function isRectEqual(rect1, rect2) {
  return Math.round(rect1.top) === Math.round(rect2.top) && Math.round(rect1.left) === Math.round(rect2.left) && Math.round(rect1.height) === Math.round(rect2.height) && Math.round(rect1.width) === Math.round(rect2.width);
}
var _throttleTimeout;
function throttle(callback, ms) {
  return function() {
    if (!_throttleTimeout) {
      var args = arguments, _this = this;
      if (args.length === 1) {
        callback.call(_this, args[0]);
      } else {
        callback.apply(_this, args);
      }
      _throttleTimeout = setTimeout(function() {
        _throttleTimeout = void 0;
      }, ms);
    }
  };
}
function cancelThrottle() {
  clearTimeout(_throttleTimeout);
  _throttleTimeout = void 0;
}
function scrollBy(el, x, y) {
  el.scrollLeft += x;
  el.scrollTop += y;
}
function clone(el) {
  var Polymer = window.Polymer;
  var $ = window.jQuery || window.Zepto;
  if (Polymer && Polymer.dom) {
    return Polymer.dom(el).cloneNode(true);
  } else if ($) {
    return $(el).clone(true)[0];
  } else {
    return el.cloneNode(true);
  }
}
function getChildContainingRectFromElement(container, options, ghostEl2) {
  var rect = {};
  Array.from(container.children).forEach(function(child) {
    var _rect$left, _rect$top, _rect$right, _rect$bottom;
    if (!closest(child, options.draggable, container, false) || child.animated || child === ghostEl2) return;
    var childRect = getRect(child);
    rect.left = Math.min((_rect$left = rect.left) !== null && _rect$left !== void 0 ? _rect$left : Infinity, childRect.left);
    rect.top = Math.min((_rect$top = rect.top) !== null && _rect$top !== void 0 ? _rect$top : Infinity, childRect.top);
    rect.right = Math.max((_rect$right = rect.right) !== null && _rect$right !== void 0 ? _rect$right : -Infinity, childRect.right);
    rect.bottom = Math.max((_rect$bottom = rect.bottom) !== null && _rect$bottom !== void 0 ? _rect$bottom : -Infinity, childRect.bottom);
  });
  rect.width = rect.right - rect.left;
  rect.height = rect.bottom - rect.top;
  rect.x = rect.left;
  rect.y = rect.top;
  return rect;
}
var expando = "Sortable" + (/* @__PURE__ */ new Date()).getTime();
function AnimationStateManager() {
  var animationStates = [], animationCallbackId;
  return {
    captureAnimationState: function captureAnimationState() {
      animationStates = [];
      if (!this.options.animation) return;
      var children = [].slice.call(this.el.children);
      children.forEach(function(child) {
        if (css(child, "display") === "none" || child === Sortable.ghost) return;
        animationStates.push({
          target: child,
          rect: getRect(child)
        });
        var fromRect = _objectSpread2({}, animationStates[animationStates.length - 1].rect);
        if (child.thisAnimationDuration) {
          var childMatrix = matrix(child, true);
          if (childMatrix) {
            fromRect.top -= childMatrix.f;
            fromRect.left -= childMatrix.e;
          }
        }
        child.fromRect = fromRect;
      });
    },
    addAnimationState: function addAnimationState(state) {
      animationStates.push(state);
    },
    removeAnimationState: function removeAnimationState(target) {
      animationStates.splice(indexOfObject(animationStates, {
        target
      }), 1);
    },
    animateAll: function animateAll(callback) {
      var _this = this;
      if (!this.options.animation) {
        clearTimeout(animationCallbackId);
        if (typeof callback === "function") callback();
        return;
      }
      var animating = false, animationTime = 0;
      animationStates.forEach(function(state) {
        var time = 0, target = state.target, fromRect = target.fromRect, toRect = getRect(target), prevFromRect = target.prevFromRect, prevToRect = target.prevToRect, animatingRect = state.rect, targetMatrix = matrix(target, true);
        if (targetMatrix) {
          toRect.top -= targetMatrix.f;
          toRect.left -= targetMatrix.e;
        }
        target.toRect = toRect;
        if (target.thisAnimationDuration) {
          if (isRectEqual(prevFromRect, toRect) && !isRectEqual(fromRect, toRect) && // Make sure animatingRect is on line between toRect & fromRect
          (animatingRect.top - toRect.top) / (animatingRect.left - toRect.left) === (fromRect.top - toRect.top) / (fromRect.left - toRect.left)) {
            time = calculateRealTime(animatingRect, prevFromRect, prevToRect, _this.options);
          }
        }
        if (!isRectEqual(toRect, fromRect)) {
          target.prevFromRect = fromRect;
          target.prevToRect = toRect;
          if (!time) {
            time = _this.options.animation;
          }
          _this.animate(target, animatingRect, toRect, time);
        }
        if (time) {
          animating = true;
          animationTime = Math.max(animationTime, time);
          clearTimeout(target.animationResetTimer);
          target.animationResetTimer = setTimeout(function() {
            target.animationTime = 0;
            target.prevFromRect = null;
            target.fromRect = null;
            target.prevToRect = null;
            target.thisAnimationDuration = null;
          }, time);
          target.thisAnimationDuration = time;
        }
      });
      clearTimeout(animationCallbackId);
      if (!animating) {
        if (typeof callback === "function") callback();
      } else {
        animationCallbackId = setTimeout(function() {
          if (typeof callback === "function") callback();
        }, animationTime);
      }
      animationStates = [];
    },
    animate: function animate(target, currentRect, toRect, duration) {
      if (duration) {
        css(target, "transition", "");
        css(target, "transform", "");
        var elMatrix = matrix(this.el), scaleX = elMatrix && elMatrix.a, scaleY = elMatrix && elMatrix.d, translateX = (currentRect.left - toRect.left) / (scaleX || 1), translateY = (currentRect.top - toRect.top) / (scaleY || 1);
        target.animatingX = !!translateX;
        target.animatingY = !!translateY;
        css(target, "transform", "translate3d(" + translateX + "px," + translateY + "px,0)");
        this.forRepaintDummy = repaint(target);
        css(target, "transition", "transform " + duration + "ms" + (this.options.easing ? " " + this.options.easing : ""));
        css(target, "transform", "translate3d(0,0,0)");
        typeof target.animated === "number" && clearTimeout(target.animated);
        target.animated = setTimeout(function() {
          css(target, "transition", "");
          css(target, "transform", "");
          target.animated = false;
          target.animatingX = false;
          target.animatingY = false;
        }, duration);
      }
    }
  };
}
function repaint(target) {
  return target.offsetWidth;
}
function calculateRealTime(animatingRect, fromRect, toRect, options) {
  return Math.sqrt(Math.pow(fromRect.top - animatingRect.top, 2) + Math.pow(fromRect.left - animatingRect.left, 2)) / Math.sqrt(Math.pow(fromRect.top - toRect.top, 2) + Math.pow(fromRect.left - toRect.left, 2)) * options.animation;
}
var plugins = [];
var defaults = {
  initializeByDefault: true
};
var PluginManager = {
  mount: function mount(plugin) {
    for (var option2 in defaults) {
      if (defaults.hasOwnProperty(option2) && !(option2 in plugin)) {
        plugin[option2] = defaults[option2];
      }
    }
    plugins.forEach(function(p) {
      if (p.pluginName === plugin.pluginName) {
        throw "Sortable: Cannot mount plugin ".concat(plugin.pluginName, " more than once");
      }
    });
    plugins.push(plugin);
  },
  pluginEvent: function pluginEvent(eventName, sortable, evt) {
    var _this = this;
    this.eventCanceled = false;
    evt.cancel = function() {
      _this.eventCanceled = true;
    };
    var eventNameGlobal = eventName + "Global";
    plugins.forEach(function(plugin) {
      if (!sortable[plugin.pluginName]) return;
      if (sortable[plugin.pluginName][eventNameGlobal]) {
        sortable[plugin.pluginName][eventNameGlobal](_objectSpread2({
          sortable
        }, evt));
      }
      if (sortable.options[plugin.pluginName] && sortable[plugin.pluginName][eventName]) {
        sortable[plugin.pluginName][eventName](_objectSpread2({
          sortable
        }, evt));
      }
    });
  },
  initializePlugins: function initializePlugins(sortable, el, defaults2, options) {
    plugins.forEach(function(plugin) {
      var pluginName = plugin.pluginName;
      if (!sortable.options[pluginName] && !plugin.initializeByDefault) return;
      var initialized = new plugin(sortable, el, sortable.options);
      initialized.sortable = sortable;
      initialized.options = sortable.options;
      sortable[pluginName] = initialized;
      _extends(defaults2, initialized.defaults);
    });
    for (var option2 in sortable.options) {
      if (!sortable.options.hasOwnProperty(option2)) continue;
      var modified = this.modifyOption(sortable, option2, sortable.options[option2]);
      if (typeof modified !== "undefined") {
        sortable.options[option2] = modified;
      }
    }
  },
  getEventProperties: function getEventProperties(name, sortable) {
    var eventProperties = {};
    plugins.forEach(function(plugin) {
      if (typeof plugin.eventProperties !== "function") return;
      _extends(eventProperties, plugin.eventProperties.call(sortable[plugin.pluginName], name));
    });
    return eventProperties;
  },
  modifyOption: function modifyOption(sortable, name, value) {
    var modifiedValue;
    plugins.forEach(function(plugin) {
      if (!sortable[plugin.pluginName]) return;
      if (plugin.optionListeners && typeof plugin.optionListeners[name] === "function") {
        modifiedValue = plugin.optionListeners[name].call(sortable[plugin.pluginName], value);
      }
    });
    return modifiedValue;
  }
};
function dispatchEvent(_ref) {
  var sortable = _ref.sortable, rootEl2 = _ref.rootEl, name = _ref.name, targetEl = _ref.targetEl, cloneEl2 = _ref.cloneEl, toEl = _ref.toEl, fromEl = _ref.fromEl, oldIndex2 = _ref.oldIndex, newIndex2 = _ref.newIndex, oldDraggableIndex2 = _ref.oldDraggableIndex, newDraggableIndex2 = _ref.newDraggableIndex, originalEvent = _ref.originalEvent, putSortable2 = _ref.putSortable, extraEventProperties = _ref.extraEventProperties;
  sortable = sortable || rootEl2 && rootEl2[expando];
  if (!sortable) return;
  var evt, options = sortable.options, onName = "on" + name.charAt(0).toUpperCase() + name.substr(1);
  if (window.CustomEvent && !IE11OrLess && !Edge) {
    evt = new CustomEvent(name, {
      bubbles: true,
      cancelable: true
    });
  } else {
    evt = document.createEvent("Event");
    evt.initEvent(name, true, true);
  }
  evt.to = toEl || rootEl2;
  evt.from = fromEl || rootEl2;
  evt.item = targetEl || rootEl2;
  evt.clone = cloneEl2;
  evt.oldIndex = oldIndex2;
  evt.newIndex = newIndex2;
  evt.oldDraggableIndex = oldDraggableIndex2;
  evt.newDraggableIndex = newDraggableIndex2;
  evt.originalEvent = originalEvent;
  evt.pullMode = putSortable2 ? putSortable2.lastPutMode : void 0;
  var allEventProperties = _objectSpread2(_objectSpread2({}, extraEventProperties), PluginManager.getEventProperties(name, sortable));
  for (var option2 in allEventProperties) {
    evt[option2] = allEventProperties[option2];
  }
  if (rootEl2) {
    rootEl2.dispatchEvent(evt);
  }
  if (options[onName]) {
    options[onName].call(sortable, evt);
  }
}
var _excluded = ["evt"];
var pluginEvent2 = function pluginEvent3(eventName, sortable) {
  var _ref = arguments.length > 2 && arguments[2] !== void 0 ? arguments[2] : {}, originalEvent = _ref.evt, data = _objectWithoutProperties(_ref, _excluded);
  PluginManager.pluginEvent.bind(Sortable)(eventName, sortable, _objectSpread2({
    dragEl,
    parentEl,
    ghostEl,
    rootEl,
    nextEl,
    lastDownEl,
    cloneEl,
    cloneHidden,
    dragStarted: moved,
    putSortable,
    activeSortable: Sortable.active,
    originalEvent,
    oldIndex,
    oldDraggableIndex,
    newIndex,
    newDraggableIndex,
    hideGhostForTarget: _hideGhostForTarget,
    unhideGhostForTarget: _unhideGhostForTarget,
    cloneNowHidden: function cloneNowHidden() {
      cloneHidden = true;
    },
    cloneNowShown: function cloneNowShown() {
      cloneHidden = false;
    },
    dispatchSortableEvent: function dispatchSortableEvent(name) {
      _dispatchEvent({
        sortable,
        name,
        originalEvent
      });
    }
  }, data));
};
function _dispatchEvent(info) {
  dispatchEvent(_objectSpread2({
    putSortable,
    cloneEl,
    targetEl: dragEl,
    rootEl,
    oldIndex,
    oldDraggableIndex,
    newIndex,
    newDraggableIndex
  }, info));
}
var dragEl;
var parentEl;
var ghostEl;
var rootEl;
var nextEl;
var lastDownEl;
var cloneEl;
var cloneHidden;
var oldIndex;
var newIndex;
var oldDraggableIndex;
var newDraggableIndex;
var activeGroup;
var putSortable;
var awaitingDragStarted = false;
var ignoreNextClick = false;
var sortables = [];
var tapEvt;
var touchEvt;
var lastDx;
var lastDy;
var tapDistanceLeft;
var tapDistanceTop;
var moved;
var lastTarget;
var lastDirection;
var pastFirstInvertThresh = false;
var isCircumstantialInvert = false;
var targetMoveDistance;
var ghostRelativeParent;
var ghostRelativeParentInitialScroll = [];
var _silent = false;
var savedInputChecked = [];
var documentExists = typeof document !== "undefined";
var PositionGhostAbsolutely = IOS;
var CSSFloatProperty = Edge || IE11OrLess ? "cssFloat" : "float";
var supportDraggable = documentExists && !ChromeForAndroid && !IOS && "draggable" in document.createElement("div");
var supportCssPointerEvents = (function() {
  if (!documentExists) return;
  if (IE11OrLess) {
    return false;
  }
  var el = document.createElement("x");
  el.style.cssText = "pointer-events:auto";
  return el.style.pointerEvents === "auto";
})();
var _detectDirection = function _detectDirection2(el, options) {
  var elCSS = css(el), elWidth = parseInt(elCSS.width) - parseInt(elCSS.paddingLeft) - parseInt(elCSS.paddingRight) - parseInt(elCSS.borderLeftWidth) - parseInt(elCSS.borderRightWidth), child1 = getChild(el, 0, options), child2 = getChild(el, 1, options), firstChildCSS = child1 && css(child1), secondChildCSS = child2 && css(child2), firstChildWidth = firstChildCSS && parseInt(firstChildCSS.marginLeft) + parseInt(firstChildCSS.marginRight) + getRect(child1).width, secondChildWidth = secondChildCSS && parseInt(secondChildCSS.marginLeft) + parseInt(secondChildCSS.marginRight) + getRect(child2).width;
  if (elCSS.display === "flex") {
    return elCSS.flexDirection === "column" || elCSS.flexDirection === "column-reverse" ? "vertical" : "horizontal";
  }
  if (elCSS.display === "grid") {
    return elCSS.gridTemplateColumns.split(" ").length <= 1 ? "vertical" : "horizontal";
  }
  if (child1 && firstChildCSS["float"] && firstChildCSS["float"] !== "none") {
    var touchingSideChild2 = firstChildCSS["float"] === "left" ? "left" : "right";
    return child2 && (secondChildCSS.clear === "both" || secondChildCSS.clear === touchingSideChild2) ? "vertical" : "horizontal";
  }
  return child1 && (firstChildCSS.display === "block" || firstChildCSS.display === "flex" || firstChildCSS.display === "table" || firstChildCSS.display === "grid" || firstChildWidth >= elWidth && elCSS[CSSFloatProperty] === "none" || child2 && elCSS[CSSFloatProperty] === "none" && firstChildWidth + secondChildWidth > elWidth) ? "vertical" : "horizontal";
};
var _dragElInRowColumn = function _dragElInRowColumn2(dragRect, targetRect, vertical) {
  var dragElS1Opp = vertical ? dragRect.left : dragRect.top, dragElS2Opp = vertical ? dragRect.right : dragRect.bottom, dragElOppLength = vertical ? dragRect.width : dragRect.height, targetS1Opp = vertical ? targetRect.left : targetRect.top, targetS2Opp = vertical ? targetRect.right : targetRect.bottom, targetOppLength = vertical ? targetRect.width : targetRect.height;
  return dragElS1Opp === targetS1Opp || dragElS2Opp === targetS2Opp || dragElS1Opp + dragElOppLength / 2 === targetS1Opp + targetOppLength / 2;
};
var _detectNearestEmptySortable = function _detectNearestEmptySortable2(x, y) {
  var ret;
  sortables.some(function(sortable) {
    var threshold = sortable[expando].options.emptyInsertThreshold;
    if (!threshold || lastChild(sortable)) return;
    var rect = getRect(sortable), insideHorizontally = x >= rect.left - threshold && x <= rect.right + threshold, insideVertically = y >= rect.top - threshold && y <= rect.bottom + threshold;
    if (insideHorizontally && insideVertically) {
      return ret = sortable;
    }
  });
  return ret;
};
var _prepareGroup = function _prepareGroup2(options) {
  function toFn(value, pull) {
    return function(to, from, dragEl2, evt) {
      var sameGroup = to.options.group.name && from.options.group.name && to.options.group.name === from.options.group.name;
      if (value == null && (pull || sameGroup)) {
        return true;
      } else if (value == null || value === false) {
        return false;
      } else if (pull && value === "clone") {
        return value;
      } else if (typeof value === "function") {
        return toFn(value(to, from, dragEl2, evt), pull)(to, from, dragEl2, evt);
      } else {
        var otherGroup = (pull ? to : from).options.group.name;
        return value === true || typeof value === "string" && value === otherGroup || value.join && value.indexOf(otherGroup) > -1;
      }
    };
  }
  var group = {};
  var originalGroup = options.group;
  if (!originalGroup || _typeof(originalGroup) != "object") {
    originalGroup = {
      name: originalGroup
    };
  }
  group.name = originalGroup.name;
  group.checkPull = toFn(originalGroup.pull, true);
  group.checkPut = toFn(originalGroup.put);
  group.revertClone = originalGroup.revertClone;
  options.group = group;
};
var _hideGhostForTarget = function _hideGhostForTarget2() {
  if (!supportCssPointerEvents && ghostEl) {
    css(ghostEl, "display", "none");
  }
};
var _unhideGhostForTarget = function _unhideGhostForTarget2() {
  if (!supportCssPointerEvents && ghostEl) {
    css(ghostEl, "display", "");
  }
};
if (documentExists && !ChromeForAndroid) {
  document.addEventListener("click", function(evt) {
    if (ignoreNextClick) {
      evt.preventDefault();
      evt.stopPropagation && evt.stopPropagation();
      evt.stopImmediatePropagation && evt.stopImmediatePropagation();
      ignoreNextClick = false;
      return false;
    }
  }, true);
}
var nearestEmptyInsertDetectEvent = function nearestEmptyInsertDetectEvent2(evt) {
  if (dragEl) {
    evt = evt.touches ? evt.touches[0] : evt;
    var nearest = _detectNearestEmptySortable(evt.clientX, evt.clientY);
    if (nearest) {
      var event = {};
      for (var i in evt) {
        if (evt.hasOwnProperty(i)) {
          event[i] = evt[i];
        }
      }
      event.target = event.rootEl = nearest;
      event.preventDefault = void 0;
      event.stopPropagation = void 0;
      nearest[expando]._onDragOver(event);
    }
  }
};
var _checkOutsideTargetEl = function _checkOutsideTargetEl2(evt) {
  if (dragEl) {
    dragEl.parentNode[expando]._isOutsideThisEl(evt.target);
  }
};
function Sortable(el, options) {
  if (!(el && el.nodeType && el.nodeType === 1)) {
    throw "Sortable: `el` must be an HTMLElement, not ".concat({}.toString.call(el));
  }
  this.el = el;
  this.options = options = _extends({}, options);
  el[expando] = this;
  var defaults2 = {
    group: null,
    sort: true,
    disabled: false,
    store: null,
    handle: null,
    draggable: /^[uo]l$/i.test(el.nodeName) ? ">li" : ">*",
    swapThreshold: 1,
    // percentage; 0 <= x <= 1
    invertSwap: false,
    // invert always
    invertedSwapThreshold: null,
    // will be set to same as swapThreshold if default
    removeCloneOnHide: true,
    direction: function direction() {
      return _detectDirection(el, this.options);
    },
    ghostClass: "sortable-ghost",
    chosenClass: "sortable-chosen",
    dragClass: "sortable-drag",
    ignore: "a, img",
    filter: null,
    preventOnFilter: true,
    animation: 0,
    easing: null,
    setData: function setData(dataTransfer, dragEl2) {
      dataTransfer.setData("Text", dragEl2.textContent);
    },
    dropBubble: false,
    dragoverBubble: false,
    dataIdAttr: "data-id",
    delay: 0,
    delayOnTouchOnly: false,
    touchStartThreshold: (Number.parseInt ? Number : window).parseInt(window.devicePixelRatio, 10) || 1,
    forceFallback: false,
    fallbackClass: "sortable-fallback",
    fallbackOnBody: false,
    fallbackTolerance: 0,
    fallbackOffset: {
      x: 0,
      y: 0
    },
    // Disabled on Safari: #1571; Enabled on Safari IOS: #2244
    supportPointer: Sortable.supportPointer !== false && "PointerEvent" in window && (!Safari || IOS),
    emptyInsertThreshold: 5
  };
  PluginManager.initializePlugins(this, el, defaults2);
  for (var name in defaults2) {
    !(name in options) && (options[name] = defaults2[name]);
  }
  _prepareGroup(options);
  for (var fn in this) {
    if (fn.charAt(0) === "_" && typeof this[fn] === "function") {
      this[fn] = this[fn].bind(this);
    }
  }
  this.nativeDraggable = options.forceFallback ? false : supportDraggable;
  if (this.nativeDraggable) {
    this.options.touchStartThreshold = 1;
  }
  if (options.supportPointer) {
    on(el, "pointerdown", this._onTapStart);
  } else {
    on(el, "mousedown", this._onTapStart);
    on(el, "touchstart", this._onTapStart);
  }
  if (this.nativeDraggable) {
    on(el, "dragover", this);
    on(el, "dragenter", this);
  }
  sortables.push(this.el);
  options.store && options.store.get && this.sort(options.store.get(this) || []);
  _extends(this, AnimationStateManager());
}
Sortable.prototype = /** @lends Sortable.prototype */
{
  constructor: Sortable,
  _isOutsideThisEl: function _isOutsideThisEl(target) {
    if (!this.el.contains(target) && target !== this.el) {
      lastTarget = null;
    }
  },
  _getDirection: function _getDirection(evt, target) {
    return typeof this.options.direction === "function" ? this.options.direction.call(this, evt, target, dragEl) : this.options.direction;
  },
  _onTapStart: function _onTapStart(evt) {
    if (!evt.cancelable) return;
    var _this = this, el = this.el, options = this.options, preventOnFilter = options.preventOnFilter, type = evt.type, touch = evt.touches && evt.touches[0] || evt.pointerType && evt.pointerType === "touch" && evt, target = (touch || evt).target, originalTarget = evt.target.shadowRoot && (evt.path && evt.path[0] || evt.composedPath && evt.composedPath()[0]) || target, filter = options.filter;
    _saveInputCheckedState(el);
    if (dragEl) {
      return;
    }
    if (/mousedown|pointerdown/.test(type) && evt.button !== 0 || options.disabled) {
      return;
    }
    if (originalTarget.isContentEditable) {
      return;
    }
    if (!this.nativeDraggable && Safari && target && target.tagName.toUpperCase() === "SELECT") {
      return;
    }
    target = closest(target, options.draggable, el, false);
    if (target && target.animated) {
      return;
    }
    if (lastDownEl === target) {
      return;
    }
    oldIndex = index(target);
    oldDraggableIndex = index(target, options.draggable);
    if (typeof filter === "function") {
      if (filter.call(this, evt, target, this)) {
        _dispatchEvent({
          sortable: _this,
          rootEl: originalTarget,
          name: "filter",
          targetEl: target,
          toEl: el,
          fromEl: el
        });
        pluginEvent2("filter", _this, {
          evt
        });
        preventOnFilter && evt.preventDefault();
        return;
      }
    } else if (filter) {
      filter = filter.split(",").some(function(criteria) {
        criteria = closest(originalTarget, criteria.trim(), el, false);
        if (criteria) {
          _dispatchEvent({
            sortable: _this,
            rootEl: criteria,
            name: "filter",
            targetEl: target,
            fromEl: el,
            toEl: el
          });
          pluginEvent2("filter", _this, {
            evt
          });
          return true;
        }
      });
      if (filter) {
        preventOnFilter && evt.preventDefault();
        return;
      }
    }
    if (options.handle && !closest(originalTarget, options.handle, el, false)) {
      return;
    }
    this._prepareDragStart(evt, touch, target);
  },
  _prepareDragStart: function _prepareDragStart(evt, touch, target) {
    var _this = this, el = _this.el, options = _this.options, ownerDocument = el.ownerDocument, dragStartFn;
    if (target && !dragEl && target.parentNode === el) {
      var dragRect = getRect(target);
      rootEl = el;
      dragEl = target;
      parentEl = dragEl.parentNode;
      nextEl = dragEl.nextSibling;
      lastDownEl = target;
      activeGroup = options.group;
      Sortable.dragged = dragEl;
      tapEvt = {
        target: dragEl,
        clientX: (touch || evt).clientX,
        clientY: (touch || evt).clientY
      };
      tapDistanceLeft = tapEvt.clientX - dragRect.left;
      tapDistanceTop = tapEvt.clientY - dragRect.top;
      this._lastX = (touch || evt).clientX;
      this._lastY = (touch || evt).clientY;
      dragEl.style["will-change"] = "all";
      dragStartFn = function dragStartFn2() {
        pluginEvent2("delayEnded", _this, {
          evt
        });
        if (Sortable.eventCanceled) {
          _this._onDrop();
          return;
        }
        _this._disableDelayedDragEvents();
        if (!FireFox && _this.nativeDraggable) {
          dragEl.draggable = true;
        }
        _this._triggerDragStart(evt, touch);
        _dispatchEvent({
          sortable: _this,
          name: "choose",
          originalEvent: evt
        });
        toggleClass(dragEl, options.chosenClass, true);
      };
      options.ignore.split(",").forEach(function(criteria) {
        find(dragEl, criteria.trim(), _disableDraggable);
      });
      on(ownerDocument, "dragover", nearestEmptyInsertDetectEvent);
      on(ownerDocument, "mousemove", nearestEmptyInsertDetectEvent);
      on(ownerDocument, "touchmove", nearestEmptyInsertDetectEvent);
      if (options.supportPointer) {
        on(ownerDocument, "pointerup", _this._onDrop);
        !this.nativeDraggable && on(ownerDocument, "pointercancel", _this._onDrop);
      } else {
        on(ownerDocument, "mouseup", _this._onDrop);
        on(ownerDocument, "touchend", _this._onDrop);
        on(ownerDocument, "touchcancel", _this._onDrop);
      }
      if (FireFox && this.nativeDraggable) {
        this.options.touchStartThreshold = 4;
        dragEl.draggable = true;
      }
      pluginEvent2("delayStart", this, {
        evt
      });
      if (options.delay && (!options.delayOnTouchOnly || touch) && (!this.nativeDraggable || !(Edge || IE11OrLess))) {
        if (Sortable.eventCanceled) {
          this._onDrop();
          return;
        }
        if (options.supportPointer) {
          on(ownerDocument, "pointerup", _this._disableDelayedDrag);
          on(ownerDocument, "pointercancel", _this._disableDelayedDrag);
        } else {
          on(ownerDocument, "mouseup", _this._disableDelayedDrag);
          on(ownerDocument, "touchend", _this._disableDelayedDrag);
          on(ownerDocument, "touchcancel", _this._disableDelayedDrag);
        }
        on(ownerDocument, "mousemove", _this._delayedDragTouchMoveHandler);
        on(ownerDocument, "touchmove", _this._delayedDragTouchMoveHandler);
        options.supportPointer && on(ownerDocument, "pointermove", _this._delayedDragTouchMoveHandler);
        _this._dragStartTimer = setTimeout(dragStartFn, options.delay);
      } else {
        dragStartFn();
      }
    }
  },
  _delayedDragTouchMoveHandler: function _delayedDragTouchMoveHandler(e) {
    var touch = e.touches ? e.touches[0] : e;
    if (Math.max(Math.abs(touch.clientX - this._lastX), Math.abs(touch.clientY - this._lastY)) >= Math.floor(this.options.touchStartThreshold / (this.nativeDraggable && window.devicePixelRatio || 1))) {
      this._disableDelayedDrag();
    }
  },
  _disableDelayedDrag: function _disableDelayedDrag() {
    dragEl && _disableDraggable(dragEl);
    clearTimeout(this._dragStartTimer);
    this._disableDelayedDragEvents();
  },
  _disableDelayedDragEvents: function _disableDelayedDragEvents() {
    var ownerDocument = this.el.ownerDocument;
    off(ownerDocument, "mouseup", this._disableDelayedDrag);
    off(ownerDocument, "touchend", this._disableDelayedDrag);
    off(ownerDocument, "touchcancel", this._disableDelayedDrag);
    off(ownerDocument, "pointerup", this._disableDelayedDrag);
    off(ownerDocument, "pointercancel", this._disableDelayedDrag);
    off(ownerDocument, "mousemove", this._delayedDragTouchMoveHandler);
    off(ownerDocument, "touchmove", this._delayedDragTouchMoveHandler);
    off(ownerDocument, "pointermove", this._delayedDragTouchMoveHandler);
  },
  _triggerDragStart: function _triggerDragStart(evt, touch) {
    touch = touch || evt.pointerType == "touch" && evt;
    if (!this.nativeDraggable || touch) {
      if (this.options.supportPointer) {
        on(document, "pointermove", this._onTouchMove);
      } else if (touch) {
        on(document, "touchmove", this._onTouchMove);
      } else {
        on(document, "mousemove", this._onTouchMove);
      }
    } else {
      on(dragEl, "dragend", this);
      on(rootEl, "dragstart", this._onDragStart);
    }
    try {
      if (document.selection) {
        _nextTick(function() {
          document.selection.empty();
        });
      } else {
        window.getSelection().removeAllRanges();
      }
    } catch (err) {
    }
  },
  _dragStarted: function _dragStarted(fallback, evt) {
    awaitingDragStarted = false;
    if (rootEl && dragEl) {
      pluginEvent2("dragStarted", this, {
        evt
      });
      if (this.nativeDraggable) {
        on(document, "dragover", _checkOutsideTargetEl);
      }
      var options = this.options;
      !fallback && toggleClass(dragEl, options.dragClass, false);
      toggleClass(dragEl, options.ghostClass, true);
      Sortable.active = this;
      fallback && this._appendGhost();
      _dispatchEvent({
        sortable: this,
        name: "start",
        originalEvent: evt
      });
    } else {
      this._nulling();
    }
  },
  _emulateDragOver: function _emulateDragOver() {
    if (touchEvt) {
      this._lastX = touchEvt.clientX;
      this._lastY = touchEvt.clientY;
      _hideGhostForTarget();
      var target = document.elementFromPoint(touchEvt.clientX, touchEvt.clientY);
      var parent = target;
      while (target && target.shadowRoot) {
        target = target.shadowRoot.elementFromPoint(touchEvt.clientX, touchEvt.clientY);
        if (target === parent) break;
        parent = target;
      }
      dragEl.parentNode[expando]._isOutsideThisEl(target);
      if (parent) {
        do {
          if (parent[expando]) {
            var inserted = void 0;
            inserted = parent[expando]._onDragOver({
              clientX: touchEvt.clientX,
              clientY: touchEvt.clientY,
              target,
              rootEl: parent
            });
            if (inserted && !this.options.dragoverBubble) {
              break;
            }
          }
          target = parent;
        } while (parent = getParentOrHost(parent));
      }
      _unhideGhostForTarget();
    }
  },
  _onTouchMove: function _onTouchMove(evt) {
    if (tapEvt) {
      var options = this.options, fallbackTolerance = options.fallbackTolerance, fallbackOffset = options.fallbackOffset, touch = evt.touches ? evt.touches[0] : evt, ghostMatrix = ghostEl && matrix(ghostEl, true), scaleX = ghostEl && ghostMatrix && ghostMatrix.a, scaleY = ghostEl && ghostMatrix && ghostMatrix.d, relativeScrollOffset = PositionGhostAbsolutely && ghostRelativeParent && getRelativeScrollOffset(ghostRelativeParent), dx = (touch.clientX - tapEvt.clientX + fallbackOffset.x) / (scaleX || 1) + (relativeScrollOffset ? relativeScrollOffset[0] - ghostRelativeParentInitialScroll[0] : 0) / (scaleX || 1), dy = (touch.clientY - tapEvt.clientY + fallbackOffset.y) / (scaleY || 1) + (relativeScrollOffset ? relativeScrollOffset[1] - ghostRelativeParentInitialScroll[1] : 0) / (scaleY || 1);
      if (!Sortable.active && !awaitingDragStarted) {
        if (fallbackTolerance && Math.max(Math.abs(touch.clientX - this._lastX), Math.abs(touch.clientY - this._lastY)) < fallbackTolerance) {
          return;
        }
        this._onDragStart(evt, true);
      }
      if (ghostEl) {
        if (ghostMatrix) {
          ghostMatrix.e += dx - (lastDx || 0);
          ghostMatrix.f += dy - (lastDy || 0);
        } else {
          ghostMatrix = {
            a: 1,
            b: 0,
            c: 0,
            d: 1,
            e: dx,
            f: dy
          };
        }
        var cssMatrix = "matrix(".concat(ghostMatrix.a, ",").concat(ghostMatrix.b, ",").concat(ghostMatrix.c, ",").concat(ghostMatrix.d, ",").concat(ghostMatrix.e, ",").concat(ghostMatrix.f, ")");
        css(ghostEl, "webkitTransform", cssMatrix);
        css(ghostEl, "mozTransform", cssMatrix);
        css(ghostEl, "msTransform", cssMatrix);
        css(ghostEl, "transform", cssMatrix);
        lastDx = dx;
        lastDy = dy;
        touchEvt = touch;
      }
      evt.cancelable && evt.preventDefault();
    }
  },
  _appendGhost: function _appendGhost() {
    if (!ghostEl) {
      var container = this.options.fallbackOnBody ? document.body : rootEl, rect = getRect(dragEl, true, PositionGhostAbsolutely, true, container), options = this.options;
      if (PositionGhostAbsolutely) {
        ghostRelativeParent = container;
        while (css(ghostRelativeParent, "position") === "static" && css(ghostRelativeParent, "transform") === "none" && ghostRelativeParent !== document) {
          ghostRelativeParent = ghostRelativeParent.parentNode;
        }
        if (ghostRelativeParent !== document.body && ghostRelativeParent !== document.documentElement) {
          if (ghostRelativeParent === document) ghostRelativeParent = getWindowScrollingElement();
          rect.top += ghostRelativeParent.scrollTop;
          rect.left += ghostRelativeParent.scrollLeft;
        } else {
          ghostRelativeParent = getWindowScrollingElement();
        }
        ghostRelativeParentInitialScroll = getRelativeScrollOffset(ghostRelativeParent);
      }
      ghostEl = dragEl.cloneNode(true);
      toggleClass(ghostEl, options.ghostClass, false);
      toggleClass(ghostEl, options.fallbackClass, true);
      toggleClass(ghostEl, options.dragClass, true);
      css(ghostEl, "transition", "");
      css(ghostEl, "transform", "");
      css(ghostEl, "box-sizing", "border-box");
      css(ghostEl, "margin", 0);
      css(ghostEl, "top", rect.top);
      css(ghostEl, "left", rect.left);
      css(ghostEl, "width", rect.width);
      css(ghostEl, "height", rect.height);
      css(ghostEl, "opacity", "0.8");
      css(ghostEl, "position", PositionGhostAbsolutely ? "absolute" : "fixed");
      css(ghostEl, "zIndex", "100000");
      css(ghostEl, "pointerEvents", "none");
      Sortable.ghost = ghostEl;
      container.appendChild(ghostEl);
      css(ghostEl, "transform-origin", tapDistanceLeft / parseInt(ghostEl.style.width) * 100 + "% " + tapDistanceTop / parseInt(ghostEl.style.height) * 100 + "%");
    }
  },
  _onDragStart: function _onDragStart(evt, fallback) {
    var _this = this;
    var dataTransfer = evt.dataTransfer;
    var options = _this.options;
    pluginEvent2("dragStart", this, {
      evt
    });
    if (Sortable.eventCanceled) {
      this._onDrop();
      return;
    }
    pluginEvent2("setupClone", this);
    if (!Sortable.eventCanceled) {
      cloneEl = clone(dragEl);
      cloneEl.removeAttribute("id");
      cloneEl.draggable = false;
      cloneEl.style["will-change"] = "";
      this._hideClone();
      toggleClass(cloneEl, this.options.chosenClass, false);
      Sortable.clone = cloneEl;
    }
    _this.cloneId = _nextTick(function() {
      pluginEvent2("clone", _this);
      if (Sortable.eventCanceled) return;
      if (!_this.options.removeCloneOnHide) {
        rootEl.insertBefore(cloneEl, dragEl);
      }
      _this._hideClone();
      _dispatchEvent({
        sortable: _this,
        name: "clone"
      });
    });
    !fallback && toggleClass(dragEl, options.dragClass, true);
    if (fallback) {
      ignoreNextClick = true;
      _this._loopId = setInterval(_this._emulateDragOver, 50);
    } else {
      off(document, "mouseup", _this._onDrop);
      off(document, "touchend", _this._onDrop);
      off(document, "touchcancel", _this._onDrop);
      if (dataTransfer) {
        dataTransfer.effectAllowed = "move";
        options.setData && options.setData.call(_this, dataTransfer, dragEl);
      }
      on(document, "drop", _this);
      css(dragEl, "transform", "translateZ(0)");
    }
    awaitingDragStarted = true;
    _this._dragStartId = _nextTick(_this._dragStarted.bind(_this, fallback, evt));
    on(document, "selectstart", _this);
    moved = true;
    window.getSelection().removeAllRanges();
    if (Safari) {
      css(document.body, "user-select", "none");
    }
  },
  // Returns true - if no further action is needed (either inserted or another condition)
  _onDragOver: function _onDragOver(evt) {
    var el = this.el, target = evt.target, dragRect, targetRect, revert, options = this.options, group = options.group, activeSortable = Sortable.active, isOwner = activeGroup === group, canSort = options.sort, fromSortable = putSortable || activeSortable, vertical, _this = this, completedFired = false;
    if (_silent) return;
    function dragOverEvent(name, extra) {
      pluginEvent2(name, _this, _objectSpread2({
        evt,
        isOwner,
        axis: vertical ? "vertical" : "horizontal",
        revert,
        dragRect,
        targetRect,
        canSort,
        fromSortable,
        target,
        completed,
        onMove: function onMove(target2, after2) {
          return _onMove(rootEl, el, dragEl, dragRect, target2, getRect(target2), evt, after2);
        },
        changed
      }, extra));
    }
    function capture() {
      dragOverEvent("dragOverAnimationCapture");
      _this.captureAnimationState();
      if (_this !== fromSortable) {
        fromSortable.captureAnimationState();
      }
    }
    function completed(insertion) {
      dragOverEvent("dragOverCompleted", {
        insertion
      });
      if (insertion) {
        if (isOwner) {
          activeSortable._hideClone();
        } else {
          activeSortable._showClone(_this);
        }
        if (_this !== fromSortable) {
          toggleClass(dragEl, putSortable ? putSortable.options.ghostClass : activeSortable.options.ghostClass, false);
          toggleClass(dragEl, options.ghostClass, true);
        }
        if (putSortable !== _this && _this !== Sortable.active) {
          putSortable = _this;
        } else if (_this === Sortable.active && putSortable) {
          putSortable = null;
        }
        if (fromSortable === _this) {
          _this._ignoreWhileAnimating = target;
        }
        _this.animateAll(function() {
          dragOverEvent("dragOverAnimationComplete");
          _this._ignoreWhileAnimating = null;
        });
        if (_this !== fromSortable) {
          fromSortable.animateAll();
          fromSortable._ignoreWhileAnimating = null;
        }
      }
      if (target === dragEl && !dragEl.animated || target === el && !target.animated) {
        lastTarget = null;
      }
      if (!options.dragoverBubble && !evt.rootEl && target !== document) {
        dragEl.parentNode[expando]._isOutsideThisEl(evt.target);
        !insertion && nearestEmptyInsertDetectEvent(evt);
      }
      !options.dragoverBubble && evt.stopPropagation && evt.stopPropagation();
      return completedFired = true;
    }
    function changed() {
      newIndex = index(dragEl);
      newDraggableIndex = index(dragEl, options.draggable);
      _dispatchEvent({
        sortable: _this,
        name: "change",
        toEl: el,
        newIndex,
        newDraggableIndex,
        originalEvent: evt
      });
    }
    if (evt.preventDefault !== void 0) {
      evt.cancelable && evt.preventDefault();
    }
    target = closest(target, options.draggable, el, true);
    dragOverEvent("dragOver");
    if (Sortable.eventCanceled) return completedFired;
    if (dragEl.contains(evt.target) || target.animated && target.animatingX && target.animatingY || _this._ignoreWhileAnimating === target) {
      return completed(false);
    }
    ignoreNextClick = false;
    if (activeSortable && !options.disabled && (isOwner ? canSort || (revert = parentEl !== rootEl) : putSortable === this || (this.lastPutMode = activeGroup.checkPull(this, activeSortable, dragEl, evt)) && group.checkPut(this, activeSortable, dragEl, evt))) {
      vertical = this._getDirection(evt, target) === "vertical";
      dragRect = getRect(dragEl);
      dragOverEvent("dragOverValid");
      if (Sortable.eventCanceled) return completedFired;
      if (revert) {
        parentEl = rootEl;
        capture();
        this._hideClone();
        dragOverEvent("revert");
        if (!Sortable.eventCanceled) {
          if (nextEl) {
            rootEl.insertBefore(dragEl, nextEl);
          } else {
            rootEl.appendChild(dragEl);
          }
        }
        return completed(true);
      }
      var elLastChild = lastChild(el, options.draggable);
      if (!elLastChild || _ghostIsLast(evt, vertical, this) && !elLastChild.animated) {
        if (elLastChild === dragEl) {
          return completed(false);
        }
        if (elLastChild && el === evt.target) {
          target = elLastChild;
        }
        if (target) {
          targetRect = getRect(target);
        }
        if (_onMove(rootEl, el, dragEl, dragRect, target, targetRect, evt, !!target) !== false) {
          capture();
          if (elLastChild && elLastChild.nextSibling) {
            el.insertBefore(dragEl, elLastChild.nextSibling);
          } else {
            el.appendChild(dragEl);
          }
          parentEl = el;
          changed();
          return completed(true);
        }
      } else if (elLastChild && _ghostIsFirst(evt, vertical, this)) {
        var firstChild = getChild(el, 0, options, true);
        if (firstChild === dragEl) {
          return completed(false);
        }
        target = firstChild;
        targetRect = getRect(target);
        if (_onMove(rootEl, el, dragEl, dragRect, target, targetRect, evt, false) !== false) {
          capture();
          el.insertBefore(dragEl, firstChild);
          parentEl = el;
          changed();
          return completed(true);
        }
      } else if (target.parentNode === el) {
        targetRect = getRect(target);
        var direction = 0, targetBeforeFirstSwap, differentLevel = dragEl.parentNode !== el, differentRowCol = !_dragElInRowColumn(dragEl.animated && dragEl.toRect || dragRect, target.animated && target.toRect || targetRect, vertical), side1 = vertical ? "top" : "left", scrolledPastTop = isScrolledPast(target, "top", "top") || isScrolledPast(dragEl, "top", "top"), scrollBefore = scrolledPastTop ? scrolledPastTop.scrollTop : void 0;
        if (lastTarget !== target) {
          targetBeforeFirstSwap = targetRect[side1];
          pastFirstInvertThresh = false;
          isCircumstantialInvert = !differentRowCol && options.invertSwap || differentLevel;
        }
        direction = _getSwapDirection(evt, target, targetRect, vertical, differentRowCol ? 1 : options.swapThreshold, options.invertedSwapThreshold == null ? options.swapThreshold : options.invertedSwapThreshold, isCircumstantialInvert, lastTarget === target);
        var sibling;
        if (direction !== 0) {
          var dragIndex = index(dragEl);
          do {
            dragIndex -= direction;
            sibling = parentEl.children[dragIndex];
          } while (sibling && (css(sibling, "display") === "none" || sibling === ghostEl));
        }
        if (direction === 0 || sibling === target) {
          return completed(false);
        }
        lastTarget = target;
        lastDirection = direction;
        var nextSibling = target.nextElementSibling, after = false;
        after = direction === 1;
        var moveVector = _onMove(rootEl, el, dragEl, dragRect, target, targetRect, evt, after);
        if (moveVector !== false) {
          if (moveVector === 1 || moveVector === -1) {
            after = moveVector === 1;
          }
          _silent = true;
          setTimeout(_unsilent, 30);
          capture();
          if (after && !nextSibling) {
            el.appendChild(dragEl);
          } else {
            target.parentNode.insertBefore(dragEl, after ? nextSibling : target);
          }
          if (scrolledPastTop) {
            scrollBy(scrolledPastTop, 0, scrollBefore - scrolledPastTop.scrollTop);
          }
          parentEl = dragEl.parentNode;
          if (targetBeforeFirstSwap !== void 0 && !isCircumstantialInvert) {
            targetMoveDistance = Math.abs(targetBeforeFirstSwap - getRect(target)[side1]);
          }
          changed();
          return completed(true);
        }
      }
      if (el.contains(dragEl)) {
        return completed(false);
      }
    }
    return false;
  },
  _ignoreWhileAnimating: null,
  _offMoveEvents: function _offMoveEvents() {
    off(document, "mousemove", this._onTouchMove);
    off(document, "touchmove", this._onTouchMove);
    off(document, "pointermove", this._onTouchMove);
    off(document, "dragover", nearestEmptyInsertDetectEvent);
    off(document, "mousemove", nearestEmptyInsertDetectEvent);
    off(document, "touchmove", nearestEmptyInsertDetectEvent);
  },
  _offUpEvents: function _offUpEvents() {
    var ownerDocument = this.el.ownerDocument;
    off(ownerDocument, "mouseup", this._onDrop);
    off(ownerDocument, "touchend", this._onDrop);
    off(ownerDocument, "pointerup", this._onDrop);
    off(ownerDocument, "pointercancel", this._onDrop);
    off(ownerDocument, "touchcancel", this._onDrop);
    off(document, "selectstart", this);
  },
  _onDrop: function _onDrop(evt) {
    var el = this.el, options = this.options;
    newIndex = index(dragEl);
    newDraggableIndex = index(dragEl, options.draggable);
    pluginEvent2("drop", this, {
      evt
    });
    parentEl = dragEl && dragEl.parentNode;
    newIndex = index(dragEl);
    newDraggableIndex = index(dragEl, options.draggable);
    if (Sortable.eventCanceled) {
      this._nulling();
      return;
    }
    awaitingDragStarted = false;
    isCircumstantialInvert = false;
    pastFirstInvertThresh = false;
    clearInterval(this._loopId);
    clearTimeout(this._dragStartTimer);
    _cancelNextTick(this.cloneId);
    _cancelNextTick(this._dragStartId);
    if (this.nativeDraggable) {
      off(document, "drop", this);
      off(el, "dragstart", this._onDragStart);
    }
    this._offMoveEvents();
    this._offUpEvents();
    if (Safari) {
      css(document.body, "user-select", "");
    }
    css(dragEl, "transform", "");
    if (evt) {
      if (moved) {
        evt.cancelable && evt.preventDefault();
        !options.dropBubble && evt.stopPropagation();
      }
      ghostEl && ghostEl.parentNode && ghostEl.parentNode.removeChild(ghostEl);
      if (rootEl === parentEl || putSortable && putSortable.lastPutMode !== "clone") {
        cloneEl && cloneEl.parentNode && cloneEl.parentNode.removeChild(cloneEl);
      }
      if (dragEl) {
        if (this.nativeDraggable) {
          off(dragEl, "dragend", this);
        }
        _disableDraggable(dragEl);
        dragEl.style["will-change"] = "";
        if (moved && !awaitingDragStarted) {
          toggleClass(dragEl, putSortable ? putSortable.options.ghostClass : this.options.ghostClass, false);
        }
        toggleClass(dragEl, this.options.chosenClass, false);
        _dispatchEvent({
          sortable: this,
          name: "unchoose",
          toEl: parentEl,
          newIndex: null,
          newDraggableIndex: null,
          originalEvent: evt
        });
        if (rootEl !== parentEl) {
          if (newIndex >= 0) {
            _dispatchEvent({
              rootEl: parentEl,
              name: "add",
              toEl: parentEl,
              fromEl: rootEl,
              originalEvent: evt
            });
            _dispatchEvent({
              sortable: this,
              name: "remove",
              toEl: parentEl,
              originalEvent: evt
            });
            _dispatchEvent({
              rootEl: parentEl,
              name: "sort",
              toEl: parentEl,
              fromEl: rootEl,
              originalEvent: evt
            });
            _dispatchEvent({
              sortable: this,
              name: "sort",
              toEl: parentEl,
              originalEvent: evt
            });
          }
          putSortable && putSortable.save();
        } else {
          if (newIndex !== oldIndex) {
            if (newIndex >= 0) {
              _dispatchEvent({
                sortable: this,
                name: "update",
                toEl: parentEl,
                originalEvent: evt
              });
              _dispatchEvent({
                sortable: this,
                name: "sort",
                toEl: parentEl,
                originalEvent: evt
              });
            }
          }
        }
        if (Sortable.active) {
          if (newIndex == null || newIndex === -1) {
            newIndex = oldIndex;
            newDraggableIndex = oldDraggableIndex;
          }
          _dispatchEvent({
            sortable: this,
            name: "end",
            toEl: parentEl,
            originalEvent: evt
          });
          this.save();
        }
      }
    }
    this._nulling();
  },
  _nulling: function _nulling() {
    pluginEvent2("nulling", this);
    rootEl = dragEl = parentEl = ghostEl = nextEl = cloneEl = lastDownEl = cloneHidden = tapEvt = touchEvt = moved = newIndex = newDraggableIndex = oldIndex = oldDraggableIndex = lastTarget = lastDirection = putSortable = activeGroup = Sortable.dragged = Sortable.ghost = Sortable.clone = Sortable.active = null;
    var el = this.el;
    savedInputChecked.forEach(function(checkEl) {
      if (el.contains(checkEl)) {
        checkEl.checked = true;
      }
    });
    savedInputChecked.length = lastDx = lastDy = 0;
  },
  handleEvent: function handleEvent(evt) {
    switch (evt.type) {
      case "drop":
      case "dragend":
        this._onDrop(evt);
        break;
      case "dragenter":
      case "dragover":
        if (dragEl) {
          this._onDragOver(evt);
          _globalDragOver(evt);
        }
        break;
      case "selectstart":
        evt.preventDefault();
        break;
    }
  },
  /**
   * Serializes the item into an array of string.
   * @returns {String[]}
   */
  toArray: function toArray() {
    var order = [], el, children = this.el.children, i = 0, n = children.length, options = this.options;
    for (; i < n; i++) {
      el = children[i];
      if (closest(el, options.draggable, this.el, false)) {
        order.push(el.getAttribute(options.dataIdAttr) || _generateId(el));
      }
    }
    return order;
  },
  /**
   * Sorts the elements according to the array.
   * @param  {String[]}  order  order of the items
   */
  sort: function sort(order, useAnimation) {
    var items = {}, rootEl2 = this.el;
    this.toArray().forEach(function(id, i) {
      var el = rootEl2.children[i];
      if (closest(el, this.options.draggable, rootEl2, false)) {
        items[id] = el;
      }
    }, this);
    useAnimation && this.captureAnimationState();
    order.forEach(function(id) {
      if (items[id]) {
        rootEl2.removeChild(items[id]);
        rootEl2.appendChild(items[id]);
      }
    });
    useAnimation && this.animateAll();
  },
  /**
   * Save the current sorting
   */
  save: function save() {
    var store = this.options.store;
    store && store.set && store.set(this);
  },
  /**
   * For each element in the set, get the first element that matches the selector by testing the element itself and traversing up through its ancestors in the DOM tree.
   * @param   {HTMLElement}  el
   * @param   {String}       [selector]  default: `options.draggable`
   * @returns {HTMLElement|null}
   */
  closest: function closest$1(el, selector) {
    return closest(el, selector || this.options.draggable, this.el, false);
  },
  /**
   * Set/get option
   * @param   {string} name
   * @param   {*}      [value]
   * @returns {*}
   */
  option: function option(name, value) {
    var options = this.options;
    if (value === void 0) {
      return options[name];
    } else {
      var modifiedValue = PluginManager.modifyOption(this, name, value);
      if (typeof modifiedValue !== "undefined") {
        options[name] = modifiedValue;
      } else {
        options[name] = value;
      }
      if (name === "group") {
        _prepareGroup(options);
      }
    }
  },
  /**
   * Destroy
   */
  destroy: function destroy() {
    pluginEvent2("destroy", this);
    var el = this.el;
    el[expando] = null;
    off(el, "mousedown", this._onTapStart);
    off(el, "touchstart", this._onTapStart);
    off(el, "pointerdown", this._onTapStart);
    if (this.nativeDraggable) {
      off(el, "dragover", this);
      off(el, "dragenter", this);
    }
    Array.prototype.forEach.call(el.querySelectorAll("[draggable]"), function(el2) {
      el2.removeAttribute("draggable");
    });
    this._onDrop();
    this._disableDelayedDragEvents();
    sortables.splice(sortables.indexOf(this.el), 1);
    this.el = el = null;
  },
  _hideClone: function _hideClone() {
    if (!cloneHidden) {
      pluginEvent2("hideClone", this);
      if (Sortable.eventCanceled) return;
      css(cloneEl, "display", "none");
      if (this.options.removeCloneOnHide && cloneEl.parentNode) {
        cloneEl.parentNode.removeChild(cloneEl);
      }
      cloneHidden = true;
    }
  },
  _showClone: function _showClone(putSortable2) {
    if (putSortable2.lastPutMode !== "clone") {
      this._hideClone();
      return;
    }
    if (cloneHidden) {
      pluginEvent2("showClone", this);
      if (Sortable.eventCanceled) return;
      if (dragEl.parentNode == rootEl && !this.options.group.revertClone) {
        rootEl.insertBefore(cloneEl, dragEl);
      } else if (nextEl) {
        rootEl.insertBefore(cloneEl, nextEl);
      } else {
        rootEl.appendChild(cloneEl);
      }
      if (this.options.group.revertClone) {
        this.animate(dragEl, cloneEl);
      }
      css(cloneEl, "display", "");
      cloneHidden = false;
    }
  }
};
function _globalDragOver(evt) {
  if (evt.dataTransfer) {
    evt.dataTransfer.dropEffect = "move";
  }
  evt.cancelable && evt.preventDefault();
}
function _onMove(fromEl, toEl, dragEl2, dragRect, targetEl, targetRect, originalEvent, willInsertAfter) {
  var evt, sortable = fromEl[expando], onMoveFn = sortable.options.onMove, retVal;
  if (window.CustomEvent && !IE11OrLess && !Edge) {
    evt = new CustomEvent("move", {
      bubbles: true,
      cancelable: true
    });
  } else {
    evt = document.createEvent("Event");
    evt.initEvent("move", true, true);
  }
  evt.to = toEl;
  evt.from = fromEl;
  evt.dragged = dragEl2;
  evt.draggedRect = dragRect;
  evt.related = targetEl || toEl;
  evt.relatedRect = targetRect || getRect(toEl);
  evt.willInsertAfter = willInsertAfter;
  evt.originalEvent = originalEvent;
  fromEl.dispatchEvent(evt);
  if (onMoveFn) {
    retVal = onMoveFn.call(sortable, evt, originalEvent);
  }
  return retVal;
}
function _disableDraggable(el) {
  el.draggable = false;
}
function _unsilent() {
  _silent = false;
}
function _ghostIsFirst(evt, vertical, sortable) {
  var firstElRect = getRect(getChild(sortable.el, 0, sortable.options, true));
  var childContainingRect = getChildContainingRectFromElement(sortable.el, sortable.options, ghostEl);
  var spacer = 10;
  return vertical ? evt.clientX < childContainingRect.left - spacer || evt.clientY < firstElRect.top && evt.clientX < firstElRect.right : evt.clientY < childContainingRect.top - spacer || evt.clientY < firstElRect.bottom && evt.clientX < firstElRect.left;
}
function _ghostIsLast(evt, vertical, sortable) {
  var lastElRect = getRect(lastChild(sortable.el, sortable.options.draggable));
  var childContainingRect = getChildContainingRectFromElement(sortable.el, sortable.options, ghostEl);
  var spacer = 10;
  return vertical ? evt.clientX > childContainingRect.right + spacer || evt.clientY > lastElRect.bottom && evt.clientX > lastElRect.left : evt.clientY > childContainingRect.bottom + spacer || evt.clientX > lastElRect.right && evt.clientY > lastElRect.top;
}
function _getSwapDirection(evt, target, targetRect, vertical, swapThreshold, invertedSwapThreshold, invertSwap, isLastTarget) {
  var mouseOnAxis = vertical ? evt.clientY : evt.clientX, targetLength = vertical ? targetRect.height : targetRect.width, targetS1 = vertical ? targetRect.top : targetRect.left, targetS2 = vertical ? targetRect.bottom : targetRect.right, invert = false;
  if (!invertSwap) {
    if (isLastTarget && targetMoveDistance < targetLength * swapThreshold) {
      if (!pastFirstInvertThresh && (lastDirection === 1 ? mouseOnAxis > targetS1 + targetLength * invertedSwapThreshold / 2 : mouseOnAxis < targetS2 - targetLength * invertedSwapThreshold / 2)) {
        pastFirstInvertThresh = true;
      }
      if (!pastFirstInvertThresh) {
        if (lastDirection === 1 ? mouseOnAxis < targetS1 + targetMoveDistance : mouseOnAxis > targetS2 - targetMoveDistance) {
          return -lastDirection;
        }
      } else {
        invert = true;
      }
    } else {
      if (mouseOnAxis > targetS1 + targetLength * (1 - swapThreshold) / 2 && mouseOnAxis < targetS2 - targetLength * (1 - swapThreshold) / 2) {
        return _getInsertDirection(target);
      }
    }
  }
  invert = invert || invertSwap;
  if (invert) {
    if (mouseOnAxis < targetS1 + targetLength * invertedSwapThreshold / 2 || mouseOnAxis > targetS2 - targetLength * invertedSwapThreshold / 2) {
      return mouseOnAxis > targetS1 + targetLength / 2 ? 1 : -1;
    }
  }
  return 0;
}
function _getInsertDirection(target) {
  if (index(dragEl) < index(target)) {
    return 1;
  } else {
    return -1;
  }
}
function _generateId(el) {
  var str = el.tagName + el.className + el.src + el.href + el.textContent, i = str.length, sum = 0;
  while (i--) {
    sum += str.charCodeAt(i);
  }
  return sum.toString(36);
}
function _saveInputCheckedState(root) {
  savedInputChecked.length = 0;
  var inputs = root.getElementsByTagName("input");
  var idx = inputs.length;
  while (idx--) {
    var el = inputs[idx];
    el.checked && savedInputChecked.push(el);
  }
}
function _nextTick(fn) {
  return setTimeout(fn, 0);
}
function _cancelNextTick(id) {
  return clearTimeout(id);
}
if (documentExists) {
  on(document, "touchmove", function(evt) {
    if ((Sortable.active || awaitingDragStarted) && evt.cancelable) {
      evt.preventDefault();
    }
  });
}
Sortable.utils = {
  on,
  off,
  css,
  find,
  is: function is(el, selector) {
    return !!closest(el, selector, el, false);
  },
  extend,
  throttle,
  closest,
  toggleClass,
  clone,
  index,
  nextTick: _nextTick,
  cancelNextTick: _cancelNextTick,
  detectDirection: _detectDirection,
  getChild,
  expando
};
Sortable.get = function(element) {
  return element[expando];
};
Sortable.mount = function() {
  for (var _len = arguments.length, plugins2 = new Array(_len), _key = 0; _key < _len; _key++) {
    plugins2[_key] = arguments[_key];
  }
  if (plugins2[0].constructor === Array) plugins2 = plugins2[0];
  plugins2.forEach(function(plugin) {
    if (!plugin.prototype || !plugin.prototype.constructor) {
      throw "Sortable: Mounted plugin must be a constructor function, not ".concat({}.toString.call(plugin));
    }
    if (plugin.utils) Sortable.utils = _objectSpread2(_objectSpread2({}, Sortable.utils), plugin.utils);
    PluginManager.mount(plugin);
  });
};
Sortable.create = function(el, options) {
  return new Sortable(el, options);
};
Sortable.version = version;
var autoScrolls = [];
var scrollEl;
var scrollRootEl;
var scrolling = false;
var lastAutoScrollX;
var lastAutoScrollY;
var touchEvt$1;
var pointerElemChangedInterval;
function AutoScrollPlugin() {
  function AutoScroll() {
    this.defaults = {
      scroll: true,
      forceAutoScrollFallback: false,
      scrollSensitivity: 30,
      scrollSpeed: 10,
      bubbleScroll: true
    };
    for (var fn in this) {
      if (fn.charAt(0) === "_" && typeof this[fn] === "function") {
        this[fn] = this[fn].bind(this);
      }
    }
  }
  AutoScroll.prototype = {
    dragStarted: function dragStarted(_ref) {
      var originalEvent = _ref.originalEvent;
      if (this.sortable.nativeDraggable) {
        on(document, "dragover", this._handleAutoScroll);
      } else {
        if (this.options.supportPointer) {
          on(document, "pointermove", this._handleFallbackAutoScroll);
        } else if (originalEvent.touches) {
          on(document, "touchmove", this._handleFallbackAutoScroll);
        } else {
          on(document, "mousemove", this._handleFallbackAutoScroll);
        }
      }
    },
    dragOverCompleted: function dragOverCompleted(_ref2) {
      var originalEvent = _ref2.originalEvent;
      if (!this.options.dragOverBubble && !originalEvent.rootEl) {
        this._handleAutoScroll(originalEvent);
      }
    },
    drop: function drop3() {
      if (this.sortable.nativeDraggable) {
        off(document, "dragover", this._handleAutoScroll);
      } else {
        off(document, "pointermove", this._handleFallbackAutoScroll);
        off(document, "touchmove", this._handleFallbackAutoScroll);
        off(document, "mousemove", this._handleFallbackAutoScroll);
      }
      clearPointerElemChangedInterval();
      clearAutoScrolls();
      cancelThrottle();
    },
    nulling: function nulling() {
      touchEvt$1 = scrollRootEl = scrollEl = scrolling = pointerElemChangedInterval = lastAutoScrollX = lastAutoScrollY = null;
      autoScrolls.length = 0;
    },
    _handleFallbackAutoScroll: function _handleFallbackAutoScroll(evt) {
      this._handleAutoScroll(evt, true);
    },
    _handleAutoScroll: function _handleAutoScroll(evt, fallback) {
      var _this = this;
      var x = (evt.touches ? evt.touches[0] : evt).clientX, y = (evt.touches ? evt.touches[0] : evt).clientY, elem = document.elementFromPoint(x, y);
      touchEvt$1 = evt;
      if (fallback || this.options.forceAutoScrollFallback || Edge || IE11OrLess || Safari) {
        autoScroll(evt, this.options, elem, fallback);
        var ogElemScroller = getParentAutoScrollElement(elem, true);
        if (scrolling && (!pointerElemChangedInterval || x !== lastAutoScrollX || y !== lastAutoScrollY)) {
          pointerElemChangedInterval && clearPointerElemChangedInterval();
          pointerElemChangedInterval = setInterval(function() {
            var newElem = getParentAutoScrollElement(document.elementFromPoint(x, y), true);
            if (newElem !== ogElemScroller) {
              ogElemScroller = newElem;
              clearAutoScrolls();
            }
            autoScroll(evt, _this.options, newElem, fallback);
          }, 10);
          lastAutoScrollX = x;
          lastAutoScrollY = y;
        }
      } else {
        if (!this.options.bubbleScroll || getParentAutoScrollElement(elem, true) === getWindowScrollingElement()) {
          clearAutoScrolls();
          return;
        }
        autoScroll(evt, this.options, getParentAutoScrollElement(elem, false), false);
      }
    }
  };
  return _extends(AutoScroll, {
    pluginName: "scroll",
    initializeByDefault: true
  });
}
function clearAutoScrolls() {
  autoScrolls.forEach(function(autoScroll2) {
    clearInterval(autoScroll2.pid);
  });
  autoScrolls = [];
}
function clearPointerElemChangedInterval() {
  clearInterval(pointerElemChangedInterval);
}
var autoScroll = throttle(function(evt, options, rootEl2, isFallback) {
  if (!options.scroll) return;
  var x = (evt.touches ? evt.touches[0] : evt).clientX, y = (evt.touches ? evt.touches[0] : evt).clientY, sens = options.scrollSensitivity, speed = options.scrollSpeed, winScroller = getWindowScrollingElement();
  var scrollThisInstance = false, scrollCustomFn;
  if (scrollRootEl !== rootEl2) {
    scrollRootEl = rootEl2;
    clearAutoScrolls();
    scrollEl = options.scroll;
    scrollCustomFn = options.scrollFn;
    if (scrollEl === true) {
      scrollEl = getParentAutoScrollElement(rootEl2, true);
    }
  }
  var layersOut = 0;
  var currentParent = scrollEl;
  do {
    var el = currentParent, rect = getRect(el), top = rect.top, bottom = rect.bottom, left = rect.left, right = rect.right, width = rect.width, height = rect.height, canScrollX = void 0, canScrollY = void 0, scrollWidth = el.scrollWidth, scrollHeight = el.scrollHeight, elCSS = css(el), scrollPosX = el.scrollLeft, scrollPosY = el.scrollTop;
    if (el === winScroller) {
      canScrollX = width < scrollWidth && (elCSS.overflowX === "auto" || elCSS.overflowX === "scroll" || elCSS.overflowX === "visible");
      canScrollY = height < scrollHeight && (elCSS.overflowY === "auto" || elCSS.overflowY === "scroll" || elCSS.overflowY === "visible");
    } else {
      canScrollX = width < scrollWidth && (elCSS.overflowX === "auto" || elCSS.overflowX === "scroll");
      canScrollY = height < scrollHeight && (elCSS.overflowY === "auto" || elCSS.overflowY === "scroll");
    }
    var vx = canScrollX && (Math.abs(right - x) <= sens && scrollPosX + width < scrollWidth) - (Math.abs(left - x) <= sens && !!scrollPosX);
    var vy = canScrollY && (Math.abs(bottom - y) <= sens && scrollPosY + height < scrollHeight) - (Math.abs(top - y) <= sens && !!scrollPosY);
    if (!autoScrolls[layersOut]) {
      for (var i = 0; i <= layersOut; i++) {
        if (!autoScrolls[i]) {
          autoScrolls[i] = {};
        }
      }
    }
    if (autoScrolls[layersOut].vx != vx || autoScrolls[layersOut].vy != vy || autoScrolls[layersOut].el !== el) {
      autoScrolls[layersOut].el = el;
      autoScrolls[layersOut].vx = vx;
      autoScrolls[layersOut].vy = vy;
      clearInterval(autoScrolls[layersOut].pid);
      if (vx != 0 || vy != 0) {
        scrollThisInstance = true;
        autoScrolls[layersOut].pid = setInterval(function() {
          if (isFallback && this.layer === 0) {
            Sortable.active._onTouchMove(touchEvt$1);
          }
          var scrollOffsetY = autoScrolls[this.layer].vy ? autoScrolls[this.layer].vy * speed : 0;
          var scrollOffsetX = autoScrolls[this.layer].vx ? autoScrolls[this.layer].vx * speed : 0;
          if (typeof scrollCustomFn === "function") {
            if (scrollCustomFn.call(Sortable.dragged.parentNode[expando], scrollOffsetX, scrollOffsetY, evt, touchEvt$1, autoScrolls[this.layer].el) !== "continue") {
              return;
            }
          }
          scrollBy(autoScrolls[this.layer].el, scrollOffsetX, scrollOffsetY);
        }.bind({
          layer: layersOut
        }), 24);
      }
    }
    layersOut++;
  } while (options.bubbleScroll && currentParent !== winScroller && (currentParent = getParentAutoScrollElement(currentParent, false)));
  scrolling = scrollThisInstance;
}, 30);
var drop = function drop2(_ref) {
  var originalEvent = _ref.originalEvent, putSortable2 = _ref.putSortable, dragEl2 = _ref.dragEl, activeSortable = _ref.activeSortable, dispatchSortableEvent = _ref.dispatchSortableEvent, hideGhostForTarget = _ref.hideGhostForTarget, unhideGhostForTarget = _ref.unhideGhostForTarget;
  if (!originalEvent) return;
  var toSortable = putSortable2 || activeSortable;
  hideGhostForTarget();
  var touch = originalEvent.changedTouches && originalEvent.changedTouches.length ? originalEvent.changedTouches[0] : originalEvent;
  var target = document.elementFromPoint(touch.clientX, touch.clientY);
  unhideGhostForTarget();
  if (toSortable && !toSortable.el.contains(target)) {
    dispatchSortableEvent("spill");
    this.onSpill({
      dragEl: dragEl2,
      putSortable: putSortable2
    });
  }
};
function Revert() {
}
Revert.prototype = {
  startIndex: null,
  dragStart: function dragStart(_ref2) {
    var oldDraggableIndex2 = _ref2.oldDraggableIndex;
    this.startIndex = oldDraggableIndex2;
  },
  onSpill: function onSpill(_ref3) {
    var dragEl2 = _ref3.dragEl, putSortable2 = _ref3.putSortable;
    this.sortable.captureAnimationState();
    if (putSortable2) {
      putSortable2.captureAnimationState();
    }
    var nextSibling = getChild(this.sortable.el, this.startIndex, this.options);
    if (nextSibling) {
      this.sortable.el.insertBefore(dragEl2, nextSibling);
    } else {
      this.sortable.el.appendChild(dragEl2);
    }
    this.sortable.animateAll();
    if (putSortable2) {
      putSortable2.animateAll();
    }
  },
  drop
};
_extends(Revert, {
  pluginName: "revertOnSpill"
});
function Remove() {
}
Remove.prototype = {
  onSpill: function onSpill2(_ref4) {
    var dragEl2 = _ref4.dragEl, putSortable2 = _ref4.putSortable;
    var parentSortable = putSortable2 || this.sortable;
    parentSortable.captureAnimationState();
    dragEl2.parentNode && dragEl2.parentNode.removeChild(dragEl2);
    parentSortable.animateAll();
  },
  drop
};
_extends(Remove, {
  pluginName: "removeOnSpill"
});
Sortable.mount(new AutoScrollPlugin());
Sortable.mount(Remove, Revert);
var sortable_esm_default = Sortable;

// src/utils/select.ts
var X_MARK_16 = '<svg class="sy sy-icon sy-size-xs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M5.28 4.22a.75.75 0 0 0-1.06 1.06L6.94 8l-2.72 2.72a.75.75 0 1 0 1.06 1.06L8 9.06l2.72 2.72a.75.75 0 1 0 1.06-1.06L9.06 8l2.72-2.72a.75.75 0 0 0-1.06-1.06L8 6.94 5.28 4.22Z"/></svg>';
var X_MARK_20 = '<svg class="sy sy-icon sy-size-sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>';
function blank(value) {
  return value === null || value === void 0 || value === "" || typeof value === "string" && value.trim() === "";
}
function filled(value) {
  return !blank(value);
}
function isGroup(entry) {
  return Array.isArray(entry.options);
}
function normalizeOptions(results) {
  if (Array.isArray(results)) {
    return results;
  }
  if (typeof results === "object" && results !== null && Array.isArray(results.options)) {
    return results.options;
  }
  return [];
}
function normalizeLabels(results) {
  if (!Array.isArray(results)) {
    return [];
  }
  const labels = [];
  for (const entry of results) {
    if (typeof entry !== "object" || entry === null) {
      continue;
    }
    const { value, label } = entry;
    if (value !== void 0 && label !== void 0) {
      labels.push({ value: String(value), label: String(label) });
    }
  }
  return labels;
}
function clone2(options) {
  return JSON.parse(JSON.stringify(options));
}
function randomSuffix() {
  return Math.random().toString(36).substring(2, 11);
}
var Select = class {
  ariaLabel;
  badgeClass;
  canOptionLabelsWrap;
  canSelectPlaceholder;
  element;
  getOptionLabelUsing;
  getOptionLabelsUsing;
  getOptionsUsing;
  getSearchResultsUsing;
  hasDynamicOptions;
  hasDynamicSearchResults;
  hasInitialNoOptionsMessage;
  id;
  initialOptionLabel;
  initialOptionLabels;
  initialState;
  isAutofocused;
  isDisabled;
  isHtmlAllowed;
  isMultiple;
  isReorderable;
  isSearchable;
  loadingMessage;
  maxItems;
  maxItemsMessage;
  noOptionsMessage;
  noSearchResultsMessage;
  onStateChange;
  options;
  optionsLimit;
  originalOptions;
  placeholder;
  position;
  searchableOptionFields;
  searchDebounce;
  searchingMessage;
  searchPrompt;
  state;
  statePath;
  activeSearchId = 0;
  labelRepository = {};
  isOpen = false;
  selectedIndex = -1;
  searchQuery = "";
  searchTimeout = null;
  isSearching = false;
  maxItemsMessageElement = null;
  selectedDisplayVersion = 0;
  container = document.createElement("div");
  selectButton = document.createElement("button");
  selectedDisplay = document.createElement("div");
  dropdown = document.createElement("div");
  optionsList = document.createElement("ul");
  statusRegion = document.createElement("div");
  dropdownId = "";
  searchContainer = null;
  searchInput = null;
  sortable = null;
  buttonClickListener = null;
  documentClickListener = null;
  buttonKeydownListener = null;
  dropdownKeydownListener = null;
  resizeListener = null;
  scrollListener = null;
  positioningRequestAnimationFrame = null;
  constructor({
    ariaLabel = null,
    badgeClass,
    canOptionLabelsWrap = true,
    canSelectPlaceholder = true,
    element,
    getOptionLabelUsing = null,
    getOptionLabelsUsing = null,
    getOptionsUsing = null,
    getSearchResultsUsing = null,
    hasDynamicOptions = false,
    hasDynamicSearchResults = true,
    hasInitialNoOptionsMessage = false,
    id = null,
    initialOptionLabel = null,
    initialOptionLabels = null,
    initialState = null,
    isAutofocused = false,
    isDisabled = false,
    isHtmlAllowed = false,
    isMultiple = false,
    isReorderable = false,
    isSearchable = false,
    loadingMessage = "Loading...",
    maxItems = null,
    maxItemsMessage = "Maximum number of items selected",
    noOptionsMessage = "No options available",
    noSearchResultsMessage = "No results found",
    onStateChange = () => {
    },
    options,
    optionsLimit = null,
    placeholder = "",
    position = null,
    searchableOptionFields = ["label"],
    searchDebounce = 1e3,
    searchingMessage = "Searching...",
    searchPrompt = "Search...",
    state,
    statePath = null
  }) {
    this.ariaLabel = ariaLabel;
    this.badgeClass = badgeClass;
    this.canOptionLabelsWrap = canOptionLabelsWrap;
    this.canSelectPlaceholder = canSelectPlaceholder;
    this.element = element;
    this.getOptionLabelUsing = getOptionLabelUsing;
    this.getOptionLabelsUsing = getOptionLabelsUsing;
    this.getOptionsUsing = getOptionsUsing;
    this.getSearchResultsUsing = getSearchResultsUsing;
    this.hasDynamicOptions = hasDynamicOptions;
    this.hasDynamicSearchResults = hasDynamicSearchResults;
    this.hasInitialNoOptionsMessage = hasInitialNoOptionsMessage;
    this.id = id;
    this.initialOptionLabel = initialOptionLabel;
    this.initialOptionLabels = initialOptionLabels;
    this.initialState = initialState;
    this.isAutofocused = isAutofocused;
    this.isDisabled = isDisabled;
    this.isHtmlAllowed = isHtmlAllowed;
    this.isMultiple = isMultiple;
    this.isReorderable = isReorderable;
    this.isSearchable = isSearchable;
    this.loadingMessage = loadingMessage;
    this.maxItems = maxItems;
    this.maxItemsMessage = maxItemsMessage;
    this.noOptionsMessage = noOptionsMessage;
    this.noSearchResultsMessage = noSearchResultsMessage;
    this.onStateChange = onStateChange;
    this.options = options;
    this.optionsLimit = optionsLimit;
    this.originalOptions = clone2(options);
    this.placeholder = placeholder;
    this.position = position;
    this.searchableOptionFields = Array.isArray(searchableOptionFields) ? searchableOptionFields : ["label"];
    this.searchDebounce = searchDebounce;
    this.searchingMessage = searchingMessage;
    this.searchPrompt = searchPrompt;
    this.state = state;
    this.statePath = statePath;
    this.render();
    this.setUpEventListeners();
    if (this.isAutofocused) {
      this.selectButton.focus();
    }
  }
  populateLabelRepositoryFromOptions(options) {
    if (!Array.isArray(options)) {
      return;
    }
    for (const option2 of options) {
      if (isGroup(option2)) {
        this.populateLabelRepositoryFromOptions(option2.options);
      } else {
        this.labelRepository[option2.value] = option2.label;
      }
    }
  }
  render() {
    this.populateLabelRepositoryFromOptions(this.options);
    this.container.className = "sy-fo-select-ctn";
    if (!this.canOptionLabelsWrap) {
      this.container.classList.add("sy-fo-select-ctn-labels-not-wrapped");
    }
    this.selectButton.className = "sy-fo-select-btn";
    this.selectButton.type = "button";
    this.selectButton.setAttribute("role", "combobox");
    this.selectButton.setAttribute("aria-haspopup", "listbox");
    this.selectButton.setAttribute("aria-expanded", "false");
    if (filled(this.id)) {
      this.selectButton.id = this.id;
    }
    if (filled(this.ariaLabel)) {
      this.selectButton.setAttribute("aria-label", this.ariaLabel);
    }
    this.selectedDisplay.className = "sy-fo-select-value-ctn";
    void this.updateSelectedDisplay();
    this.selectButton.appendChild(this.selectedDisplay);
    this.dropdown.className = "sy-dropdown-panel sy-fo-select-dropdown";
    this.dropdown.setAttribute("role", "listbox");
    this.dropdown.setAttribute("tabindex", "-1");
    this.dropdown.style.display = "none";
    this.dropdownId = `sy-fo-select-dropdown-${randomSuffix()}`;
    this.dropdown.id = this.dropdownId;
    this.selectButton.setAttribute("aria-controls", this.dropdownId);
    if (this.isMultiple) {
      this.dropdown.setAttribute("aria-multiselectable", "true");
    }
    if (this.isSearchable) {
      const searchContainer = document.createElement("div");
      searchContainer.className = "sy-fo-select-search-ctn";
      const searchInput = document.createElement("input");
      searchInput.className = "sy-fo-input";
      searchInput.type = "text";
      searchInput.placeholder = this.searchPrompt;
      searchInput.setAttribute("aria-label", "Search");
      searchContainer.appendChild(searchInput);
      this.dropdown.appendChild(searchContainer);
      this.searchContainer = searchContainer;
      this.searchInput = searchInput;
      searchInput.addEventListener("input", () => {
        if (this.isDisabled) {
          return;
        }
        this.handleSearch(searchInput.value);
      });
      searchInput.addEventListener("keydown", (event) => {
        if (this.isDisabled) {
          return;
        }
        if (event.key === "Tab") {
          event.preventDefault();
          const options = this.getVisibleOptions();
          if (options.length === 0) return;
          if (event.shiftKey) {
            this.selectedIndex = options.length - 1;
          } else {
            this.selectedIndex = 0;
          }
          for (const option2 of options) {
            option2.classList.remove("sy-selected");
          }
          options[this.selectedIndex].classList.add("sy-selected");
          options[this.selectedIndex].focus();
        } else if (event.key === "ArrowDown") {
          event.preventDefault();
          event.stopPropagation();
          const options = this.getVisibleOptions();
          if (options.length === 0) return;
          this.selectedIndex = -1;
          searchInput.blur();
          this.focusNextOption();
        } else if (event.key === "ArrowUp") {
          event.preventDefault();
          event.stopPropagation();
          const options = this.getVisibleOptions();
          if (options.length === 0) return;
          this.selectedIndex = options.length - 1;
          searchInput.blur();
          options[this.selectedIndex].classList.add("sy-selected");
          options[this.selectedIndex].focus();
          if (options[this.selectedIndex].id !== "") {
            this.dropdown.setAttribute("aria-activedescendant", options[this.selectedIndex].id);
          }
          this.scrollOptionIntoView(options[this.selectedIndex]);
        } else if (event.key === "Enter") {
          event.preventDefault();
          event.stopPropagation();
          if (this.isSearching) {
            return;
          }
          const options = this.getVisibleOptions();
          if (options.length === 0) {
            return;
          }
          const firstEnabled = options.find((option2) => {
            const ariaDisabled = option2.getAttribute("aria-disabled") === "true";
            const hasDisabledClass = option2.classList.contains("sy-disabled");
            const isHidden = option2.offsetParent === null;
            return !(ariaDisabled || hasDisabledClass || isHidden);
          });
          if (firstEnabled === void 0) {
            return;
          }
          const value = firstEnabled.getAttribute("data-value");
          if (value === null) {
            return;
          }
          this.selectOption(value);
        }
      });
    }
    this.statusRegion.className = "sy-fo-sr-only";
    this.statusRegion.setAttribute("role", "status");
    this.statusRegion.setAttribute("aria-live", "polite");
    this.statusRegion.setAttribute("aria-atomic", "true");
    this.renderOptions();
    this.container.appendChild(this.selectButton);
    this.container.appendChild(this.dropdown);
    this.container.appendChild(this.statusRegion);
    this.element.insertAdjacentElement("afterend", this.container);
    this.applyDisabledState();
  }
  renderOptions() {
    this.optionsList.innerHTML = "";
    let totalRenderedCount = 0;
    const optionsToRender = this.options;
    let optionsCount = 0;
    let hasGroupedOptions = false;
    for (const option2 of this.options) {
      if (isGroup(option2)) {
        optionsCount += option2.options.length;
        hasGroupedOptions = true;
      } else {
        optionsCount++;
      }
    }
    if (hasGroupedOptions) {
      this.optionsList.className = "sy-fo-select-options-ctn";
    } else if (optionsCount > 0) {
      this.optionsList.className = "sy-dropdown-list";
    }
    let ungroupedList = hasGroupedOptions ? null : this.optionsList;
    let renderedCount = 0;
    for (const option2 of optionsToRender) {
      if (this.optionsLimit !== null && this.optionsLimit > 0 && renderedCount >= this.optionsLimit) {
        break;
      }
      if (isGroup(option2)) {
        let groupOptions = option2.options;
        const state = this.state;
        if (this.isMultiple && Array.isArray(state) && state.length > 0) {
          groupOptions = option2.options.filter((groupOption) => !state.includes(groupOption.value));
        }
        if (groupOptions.length > 0) {
          if (this.optionsLimit !== null && this.optionsLimit > 0) {
            const remainingSlots = this.optionsLimit - renderedCount;
            if (remainingSlots < groupOptions.length) {
              groupOptions = groupOptions.slice(0, remainingSlots);
            }
          }
          this.renderOptionGroup(option2.label, groupOptions);
          renderedCount += groupOptions.length;
          totalRenderedCount += groupOptions.length;
        }
      } else {
        if (this.isMultiple && Array.isArray(this.state) && this.state.includes(option2.value)) {
          continue;
        }
        if (ungroupedList === null) {
          ungroupedList = document.createElement("ul");
          ungroupedList.className = "sy-dropdown-list";
          this.optionsList.appendChild(ungroupedList);
        }
        const optionElement = this.createOptionElement(option2.value, option2);
        ungroupedList.appendChild(optionElement);
        renderedCount++;
        totalRenderedCount++;
      }
    }
    if (totalRenderedCount === 0) {
      if (this.searchQuery !== "") {
        this.showNoResultsMessage();
      } else if (this.hasInitialNoOptionsMessage || this.hasDynamicOptions) {
        this.showNoOptionsMessage();
      } else if (this.isMultiple && this.isOpen && !this.isSearchable) {
        this.closeDropdown();
      }
      if (this.optionsList.parentNode === this.dropdown) {
        this.dropdown.removeChild(this.optionsList);
      }
    } else {
      this.hideLoadingState();
      if (this.optionsList.parentNode !== this.dropdown) {
        this.dropdown.appendChild(this.optionsList);
      }
    }
  }
  renderOptionGroup(label, options) {
    if (options.length === 0) {
      return;
    }
    const optionGroup = document.createElement("li");
    optionGroup.className = "sy-fo-select-option-group";
    const optionGroupLabel = document.createElement("div");
    optionGroupLabel.className = "sy-fo-select-option-group-label";
    optionGroupLabel.textContent = label;
    const groupOptionsList = document.createElement("ul");
    groupOptionsList.className = "sy-dropdown-list";
    for (const option2 of options) {
      groupOptionsList.appendChild(this.createOptionElement(option2.value, option2));
    }
    optionGroup.appendChild(optionGroupLabel);
    optionGroup.appendChild(groupOptionsList);
    this.optionsList.appendChild(optionGroup);
  }
  createOptionElement(value, option2) {
    const optionValue = value;
    const optionLabel = option2.label;
    const isDisabled = option2.isDisabled ?? false;
    const element = document.createElement("li");
    element.className = "sy-dropdown-item sy-fo-select-option";
    if (isDisabled) {
      element.classList.add("sy-disabled");
    }
    element.id = `sy-fo-select-option-${randomSuffix()}`;
    element.setAttribute("role", "option");
    element.setAttribute("data-value", optionValue);
    element.setAttribute("tabindex", "0");
    if (isDisabled) {
      element.setAttribute("aria-disabled", "true");
    }
    if (this.isHtmlAllowed) {
      const tempDiv = document.createElement("div");
      tempDiv.innerHTML = optionLabel;
      element.setAttribute("aria-label", tempDiv.textContent || tempDiv.innerText || optionLabel);
    }
    const isSelected = this.isMultiple ? Array.isArray(this.state) && this.state.includes(optionValue) : this.state === optionValue;
    element.setAttribute("aria-selected", isSelected ? "true" : "false");
    if (isSelected) {
      element.classList.add("sy-selected");
    }
    const labelSpan = document.createElement("span");
    labelSpan.className = "sy-fo-select-option-label";
    if (this.isHtmlAllowed) {
      labelSpan.innerHTML = optionLabel;
    } else {
      labelSpan.textContent = optionLabel;
    }
    element.appendChild(labelSpan);
    if (!isDisabled) {
      element.addEventListener("click", (event) => {
        event.preventDefault();
        event.stopPropagation();
        this.selectOption(optionValue);
        if (this.isMultiple) {
          const searchInput = this.searchInput;
          if (this.isSearchable && searchInput !== null) {
            setTimeout(() => {
              searchInput.focus();
            }, 0);
          } else {
            setTimeout(() => {
              element.focus();
            }, 0);
          }
        }
      });
    }
    return element;
  }
  async updateSelectedDisplay() {
    this.selectedDisplayVersion = this.selectedDisplayVersion + 1;
    const renderVersion = this.selectedDisplayVersion;
    const fragment = document.createDocumentFragment();
    if (this.isMultiple) {
      if (!Array.isArray(this.state) || this.state.length === 0) {
        this.sortable?.destroy();
        this.sortable = null;
        const placeholderSpan = document.createElement("span");
        placeholderSpan.textContent = this.placeholder;
        placeholderSpan.classList.add("sy-fo-select-placeholder");
        fragment.appendChild(placeholderSpan);
      } else {
        const selectedLabels = await this.getLabelsForMultipleSelection();
        if (renderVersion !== this.selectedDisplayVersion) return;
        this.addBadgesForSelectedOptions(selectedLabels, fragment);
      }
      if (renderVersion === this.selectedDisplayVersion) {
        this.selectedDisplay.replaceChildren(fragment);
        if (this.isOpen) {
          this.deferPositionDropdown();
        }
      }
      return;
    }
    if (this.state === null || this.state === "") {
      const placeholderSpan = document.createElement("span");
      placeholderSpan.textContent = this.placeholder;
      placeholderSpan.classList.add("sy-fo-select-placeholder");
      fragment.appendChild(placeholderSpan);
      if (renderVersion === this.selectedDisplayVersion) {
        this.selectedDisplay.replaceChildren(fragment);
        const existingRemoveButton = this.container.querySelector(".sy-fo-select-value-remove-btn");
        if (existingRemoveButton !== null) {
          existingRemoveButton.remove();
        }
        this.container.classList.remove("sy-fo-select-ctn-clearable");
      }
      return;
    }
    const selectedLabel = await this.getLabelForSingleSelection();
    if (renderVersion !== this.selectedDisplayVersion) return;
    this.addSingleSelectionDisplay(selectedLabel, fragment);
    if (renderVersion === this.selectedDisplayVersion) {
      this.selectedDisplay.replaceChildren(fragment);
    }
  }
  async getLabelsForMultipleSelection() {
    const selectedLabels = this.getSelectedOptionLabels();
    const missingValues = [];
    if (Array.isArray(this.state)) {
      for (const value of this.state) {
        if (filled(this.labelRepository[value])) {
          continue;
        }
        if (filled(selectedLabels[value])) {
          this.labelRepository[value] = selectedLabels[value];
          continue;
        }
        missingValues.push(value);
      }
    }
    if (missingValues.length > 0 && filled(this.initialOptionLabels) && JSON.stringify(this.state) === JSON.stringify(this.initialState)) {
      for (const initialOption of this.initialOptionLabels) {
        if (filled(initialOption) && missingValues.includes(initialOption.value)) {
          this.labelRepository[initialOption.value] = initialOption.label;
        }
      }
    } else if (missingValues.length > 0 && this.getOptionLabelsUsing !== null) {
      try {
        const fetchedOptions = normalizeLabels(await this.getOptionLabelsUsing());
        for (const option2 of fetchedOptions) {
          this.labelRepository[option2.value] = option2.label;
        }
      } catch (error) {
        console.error("Error fetching option labels:", error);
      }
    }
    const result = [];
    if (Array.isArray(this.state)) {
      for (const value of this.state) {
        if (filled(this.labelRepository[value])) {
          result.push(this.labelRepository[value]);
        } else if (filled(selectedLabels[value])) {
          result.push(selectedLabels[value]);
        } else {
          result.push(value);
        }
      }
    }
    return result;
  }
  createBadgeElement(value, label) {
    const badge = document.createElement("span");
    badge.className = this.badgeClass;
    if (filled(value)) {
      badge.setAttribute("data-value", value);
    }
    const labelContainer = document.createElement("span");
    labelContainer.className = "sy-badge-label-ctn";
    const labelElement = document.createElement("span");
    labelElement.className = "sy-badge-label";
    if (this.canOptionLabelsWrap) {
      labelElement.classList.add("sy-wrapped");
    }
    if (this.isHtmlAllowed) {
      labelElement.innerHTML = label;
    } else {
      labelElement.textContent = label;
    }
    labelContainer.appendChild(labelElement);
    badge.appendChild(labelContainer);
    badge.appendChild(this.createRemoveButton(value, label));
    return badge;
  }
  createRemoveButton(value, label) {
    const removeButton = document.createElement("button");
    removeButton.type = "button";
    removeButton.className = "sy-badge-delete-btn";
    removeButton.innerHTML = X_MARK_16;
    removeButton.setAttribute(
      "aria-label",
      `Remove ${this.isHtmlAllowed ? label.replace(/<[^>]*>/g, "") : label}`
    );
    if (this.isDisabled) {
      removeButton.setAttribute("disabled", "disabled");
      removeButton.classList.add("sy-disabled");
    }
    removeButton.addEventListener("click", (event) => {
      event.stopPropagation();
      if (filled(value)) {
        this.selectOption(value);
      }
    });
    removeButton.addEventListener("keydown", (event) => {
      if (event.key === " " || event.key === "Enter") {
        event.preventDefault();
        event.stopPropagation();
        if (filled(value)) {
          this.selectOption(value);
        }
      }
    });
    return removeButton;
  }
  addBadgesForSelectedOptions(selectedLabels, target = this.selectedDisplay) {
    const badgesContainer = document.createElement("div");
    badgesContainer.className = "sy-fo-select-value-badges-ctn";
    selectedLabels.forEach((label, index2) => {
      const value = Array.isArray(this.state) ? this.state[index2] : null;
      badgesContainer.appendChild(this.createBadgeElement(value, label));
    });
    target.appendChild(badgesContainer);
    this.sortable?.destroy();
    this.sortable = null;
    if (this.isReorderable) {
      badgesContainer.addEventListener("click", (event) => {
        event.stopPropagation();
      });
      badgesContainer.addEventListener("mousedown", (event) => {
        event.stopPropagation();
      });
      this.sortable = new sortable_esm_default(badgesContainer, {
        animation: 150,
        onEnd: () => {
          const newState = [];
          for (const badge of badgesContainer.querySelectorAll("[data-value]")) {
            newState.push(badge.getAttribute("data-value") ?? "");
          }
          this.state = newState;
          this.onStateChange(this.state);
        }
      });
    }
  }
  async getLabelForSingleSelection() {
    const state = typeof this.state === "string" ? this.state : "";
    let selectedLabel = this.labelRepository[state];
    if (blank(selectedLabel)) {
      selectedLabel = this.getSelectedOptionLabel(state);
    }
    if (blank(selectedLabel) && filled(this.initialOptionLabel) && this.state === this.initialState) {
      selectedLabel = this.initialOptionLabel;
      if (filled(state)) {
        this.labelRepository[state] = selectedLabel;
      }
    } else if (blank(selectedLabel) && this.getOptionLabelUsing !== null) {
      try {
        const fetched = await this.getOptionLabelUsing();
        selectedLabel = fetched === null || fetched === void 0 ? "" : String(fetched);
        if (filled(selectedLabel) && filled(state)) {
          this.labelRepository[state] = selectedLabel;
        }
      } catch (error) {
        console.error("Error fetching option label:", error);
        selectedLabel = state;
      }
    } else if (blank(selectedLabel)) {
      selectedLabel = state;
    }
    return selectedLabel;
  }
  addSingleSelectionDisplay(selectedLabel, target = this.selectedDisplay) {
    const labelContainer = document.createElement("span");
    labelContainer.className = "sy-fo-select-value-label";
    if (this.isHtmlAllowed) {
      labelContainer.innerHTML = selectedLabel;
    } else {
      labelContainer.textContent = selectedLabel;
    }
    target.appendChild(labelContainer);
    if (!this.canSelectPlaceholder) {
      return;
    }
    if (this.container.querySelector(".sy-fo-select-value-remove-btn") !== null) {
      return;
    }
    const removeButton = document.createElement("button");
    removeButton.type = "button";
    removeButton.className = "sy-fo-select-value-remove-btn";
    removeButton.innerHTML = X_MARK_20;
    removeButton.setAttribute("aria-label", "Clear selection");
    if (this.isDisabled) {
      removeButton.setAttribute("disabled", "disabled");
      removeButton.classList.add("sy-disabled");
    }
    removeButton.addEventListener("click", (event) => {
      event.stopPropagation();
      this.selectOption("");
    });
    removeButton.addEventListener("keydown", (event) => {
      if (event.key === " " || event.key === "Enter") {
        event.preventDefault();
        event.stopPropagation();
        this.selectOption("");
      }
    });
    this.container.appendChild(removeButton);
    this.container.classList.add("sy-fo-select-ctn-clearable");
  }
  getSelectedOptionLabel(value) {
    if (filled(this.labelRepository[value])) {
      return this.labelRepository[value];
    }
    let selectedLabel = "";
    for (const option2 of this.options) {
      if (isGroup(option2)) {
        for (const groupOption of option2.options) {
          if (groupOption.value === value) {
            selectedLabel = groupOption.label;
            this.labelRepository[value] = selectedLabel;
            break;
          }
        }
      } else if (option2.value === value) {
        selectedLabel = option2.label;
        this.labelRepository[value] = selectedLabel;
        break;
      }
    }
    return selectedLabel;
  }
  setUpEventListeners() {
    this.buttonClickListener = () => {
      this.toggleDropdown();
    };
    this.documentClickListener = (event) => {
      const target = event.target;
      if (!this.container.contains(target instanceof Node ? target : null) && this.isOpen) {
        this.closeDropdown();
      }
    };
    this.buttonKeydownListener = (event) => {
      if (this.isDisabled) {
        return;
      }
      this.handleSelectButtonKeydown(event);
    };
    this.dropdownKeydownListener = (event) => {
      if (this.isDisabled) {
        return;
      }
      if (this.isSearchable && document.activeElement === this.searchInput && !["Tab", "Escape"].includes(event.key)) {
        return;
      }
      this.handleDropdownKeydown(event);
    };
    this.selectButton.addEventListener("click", this.buttonClickListener);
    document.addEventListener("click", this.documentClickListener);
    this.selectButton.addEventListener("keydown", this.buttonKeydownListener);
    this.dropdown.addEventListener("keydown", this.dropdownKeydownListener);
  }
  updateOptionLabelInList(value, newLabel) {
    this.labelRepository[value] = newLabel;
    const options = this.getVisibleOptions();
    for (const option2 of options) {
      if (option2.getAttribute("data-value") === value) {
        option2.innerHTML = "";
        const labelSpan = document.createElement("span");
        labelSpan.className = "sy-fo-select-option-label";
        if (this.isHtmlAllowed) {
          labelSpan.innerHTML = newLabel;
        } else {
          labelSpan.textContent = newLabel;
        }
        option2.appendChild(labelSpan);
        break;
      }
    }
    for (const option2 of this.options) {
      if (isGroup(option2)) {
        for (const groupOption of option2.options) {
          if (groupOption.value === value) {
            groupOption.label = newLabel;
            break;
          }
        }
      } else if (option2.value === value) {
        option2.label = newLabel;
        break;
      }
    }
    for (const option2 of this.originalOptions) {
      if (isGroup(option2)) {
        for (const groupOption of option2.options) {
          if (groupOption.value === value) {
            groupOption.label = newLabel;
            break;
          }
        }
      } else if (option2.value === value) {
        option2.label = newLabel;
        break;
      }
    }
  }
  handleSelectButtonKeydown(event) {
    switch (event.key) {
      case "ArrowDown":
        event.preventDefault();
        event.stopPropagation();
        if (!this.isOpen) {
          void this.openDropdown();
        } else {
          this.focusNextOption();
        }
        break;
      case "ArrowUp":
        event.preventDefault();
        event.stopPropagation();
        if (!this.isOpen) {
          void this.openDropdown();
        } else {
          this.focusPreviousOption();
        }
        break;
      case " ": {
        event.preventDefault();
        if (this.isOpen) {
          if (this.selectedIndex >= 0) {
            const focusedOption = this.getVisibleOptions()[this.selectedIndex];
            if (focusedOption) {
              focusedOption.click();
            }
          }
        } else {
          void this.openDropdown();
        }
        break;
      }
      case "Enter":
        break;
      case "Escape":
        if (this.isOpen) {
          event.preventDefault();
          this.closeDropdown();
        }
        break;
      case "Tab":
        if (this.isOpen) {
          this.closeDropdown();
        }
        break;
      default: {
        if (this.isSearchable && !event.ctrlKey && !event.metaKey && !event.altKey && typeof event.key === "string" && event.key.length === 1) {
          event.preventDefault();
          const char = event.key;
          if (!this.isOpen) {
            void this.openDropdown();
          }
          const searchInput = this.searchInput;
          if (searchInput !== null) {
            searchInput.focus();
            searchInput.value = searchInput.value + char;
            searchInput.dispatchEvent(new Event("input", { bubbles: true }));
          }
        }
        break;
      }
    }
  }
  handleDropdownKeydown(event) {
    switch (event.key) {
      case "ArrowDown":
        event.preventDefault();
        event.stopPropagation();
        this.focusNextOption();
        break;
      case "ArrowUp":
        event.preventDefault();
        event.stopPropagation();
        this.focusPreviousOption();
        break;
      case " ": {
        event.preventDefault();
        if (this.selectedIndex >= 0) {
          const focusedOption = this.getVisibleOptions()[this.selectedIndex];
          if (focusedOption) {
            focusedOption.click();
          }
        }
        break;
      }
      case "Enter": {
        event.preventDefault();
        if (this.selectedIndex >= 0) {
          const focusedOption = this.getVisibleOptions()[this.selectedIndex];
          if (focusedOption) {
            focusedOption.click();
          }
        } else {
          const form = this.element.closest("form");
          if (form) {
            form.requestSubmit();
          }
        }
        break;
      }
      case "Escape":
        event.preventDefault();
        this.closeDropdown();
        this.selectButton.focus();
        break;
      case "Tab":
        this.closeDropdown();
        break;
      default: {
        if (this.isSearchable && !event.ctrlKey && !event.metaKey && !event.altKey && typeof event.key === "string" && event.key.length === 1) {
          event.preventDefault();
          const char = event.key;
          const searchInput = this.searchInput;
          if (searchInput !== null) {
            searchInput.focus();
            searchInput.value = searchInput.value + char;
            searchInput.dispatchEvent(new Event("input", { bubbles: true }));
          }
        }
        break;
      }
    }
  }
  toggleDropdown() {
    if (this.isDisabled) {
      return;
    }
    if (this.isOpen) {
      this.closeDropdown();
      return;
    }
    if (this.isMultiple && !this.isSearchable && !this.hasAvailableOptions()) {
      return;
    }
    void this.openDropdown();
  }
  hasAvailableOptions() {
    for (const option2 of this.options) {
      if (isGroup(option2)) {
        for (const groupOption of option2.options) {
          if (!Array.isArray(this.state) || !this.state.includes(groupOption.value)) {
            return true;
          }
        }
      } else if (!Array.isArray(this.state) || !this.state.includes(option2.value)) {
        return true;
      }
    }
    return false;
  }
  async openDropdown() {
    this.dropdown.style.display = "block";
    this.dropdown.style.opacity = "0";
    const useFixedPositioning = this.selectButton.closest(".sy-fixed-positioning-context") !== null && this.selectButton.closest(".sy-absolute-positioning-context") === null;
    this.dropdown.style.position = useFixedPositioning ? "fixed" : "absolute";
    this.dropdown.style.width = `${this.selectButton.offsetWidth}px`;
    this.selectButton.setAttribute("aria-expanded", "true");
    this.isOpen = true;
    this.positionDropdown();
    if (this.resizeListener === null) {
      this.resizeListener = () => {
        this.dropdown.style.width = `${this.selectButton.offsetWidth}px`;
        this.positionDropdown();
      };
      window.addEventListener("resize", this.resizeListener);
    }
    if (this.scrollListener === null) {
      this.scrollListener = () => this.positionDropdown();
      window.addEventListener("scroll", this.scrollListener, true);
    }
    this.dropdown.style.opacity = "1";
    const searchInput = this.searchInput;
    if (this.isSearchable && searchInput !== null) {
      searchInput.value = "";
      this.searchQuery = "";
      if (!this.hasDynamicOptions) {
        this.options = clone2(this.originalOptions);
        this.renderOptions();
      }
    }
    if (this.hasDynamicOptions && this.getOptionsUsing !== null) {
      this.showLoadingState(false);
      try {
        const normalizedFetched = normalizeOptions(await this.getOptionsUsing());
        this.options = normalizedFetched;
        this.originalOptions = clone2(normalizedFetched);
        this.populateLabelRepositoryFromOptions(normalizedFetched);
        if (this.isSearchable && searchInput !== null && (searchInput.value.trim() !== "" || this.searchQuery.trim() !== "")) {
          const query = (searchInput.value !== "" ? searchInput.value : this.searchQuery).trim().toLowerCase();
          this.hideLoadingState();
          this.filterOptions(query);
        } else {
          this.renderOptions();
        }
      } catch (error) {
        console.error("Error fetching options:", error);
        this.hideLoadingState();
      }
    } else if (!this.hasInitialNoOptionsMessage || this.searchQuery !== "") {
      this.hideLoadingState();
    }
    if (this.isSearchable && searchInput !== null) {
      searchInput.focus();
    } else {
      this.selectedIndex = -1;
      const options = this.getVisibleOptions();
      if (this.isMultiple) {
        if (Array.isArray(this.state) && this.state.length > 0) {
          for (let i = 0; i < options.length; i++) {
            const value = options[i].getAttribute("data-value");
            if (value !== null && this.state.includes(value)) {
              this.selectedIndex = i;
              break;
            }
          }
        }
      } else {
        for (let i = 0; i < options.length; i++) {
          if (options[i].getAttribute("data-value") === this.state) {
            this.selectedIndex = i;
            break;
          }
        }
      }
      if (this.selectedIndex === -1 && options.length > 0) {
        this.selectedIndex = 0;
      }
      if (this.selectedIndex >= 0) {
        options[this.selectedIndex].classList.add("sy-selected");
        options[this.selectedIndex].focus();
      }
    }
  }
  positionDropdown() {
    const placement = this.position === "top" ? "top-start" : "bottom-start";
    const middleware = [offset2(4), shift2({ padding: 5 })];
    if (this.position !== "top" && this.position !== "bottom") {
      middleware.push(flip2());
    }
    const useFixedPositioning = this.selectButton.closest(".sy-fixed-positioning-context") !== null && this.selectButton.closest(".sy-absolute-positioning-context") === null;
    void computePosition2(this.selectButton, this.dropdown, {
      placement,
      middleware,
      strategy: useFixedPositioning ? "fixed" : "absolute"
    }).then(({ x, y }) => {
      Object.assign(this.dropdown.style, {
        left: `${x}px`,
        top: `${y}px`
      });
    });
  }
  deferPositionDropdown() {
    if (!this.isOpen) return;
    if (this.positioningRequestAnimationFrame !== null) {
      cancelAnimationFrame(this.positioningRequestAnimationFrame);
      this.positioningRequestAnimationFrame = null;
    }
    this.positioningRequestAnimationFrame = requestAnimationFrame(() => {
      this.positionDropdown();
      this.positioningRequestAnimationFrame = null;
    });
  }
  closeDropdown() {
    this.dropdown.style.display = "none";
    this.selectButton.setAttribute("aria-expanded", "false");
    this.isOpen = false;
    if (this.searchTimeout !== null) {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = null;
    }
    this.activeSearchId++;
    this.isSearching = false;
    this.hideLoadingState();
    this.hideMaxItemsMessage();
    if (this.resizeListener !== null) {
      window.removeEventListener("resize", this.resizeListener);
      this.resizeListener = null;
    }
    if (this.scrollListener !== null) {
      window.removeEventListener("scroll", this.scrollListener, true);
      this.scrollListener = null;
    }
    for (const option2 of this.getVisibleOptions()) {
      option2.classList.remove("sy-selected");
    }
    this.dropdown.removeAttribute("aria-activedescendant");
  }
  focusNextOption() {
    const options = this.getVisibleOptions();
    if (options.length === 0) return;
    if (this.selectedIndex >= 0 && this.selectedIndex < options.length) {
      options[this.selectedIndex].classList.remove("sy-selected");
    }
    const searchInput = this.searchInput;
    if (this.selectedIndex === options.length - 1 && this.isSearchable && searchInput !== null) {
      this.selectedIndex = -1;
      searchInput.focus();
      this.dropdown.removeAttribute("aria-activedescendant");
      return;
    }
    this.selectedIndex = (this.selectedIndex + 1) % options.length;
    options[this.selectedIndex].classList.add("sy-selected");
    options[this.selectedIndex].focus();
    if (options[this.selectedIndex].id !== "") {
      this.dropdown.setAttribute("aria-activedescendant", options[this.selectedIndex].id);
    }
    this.scrollOptionIntoView(options[this.selectedIndex]);
  }
  focusPreviousOption() {
    const options = this.getVisibleOptions();
    if (options.length === 0) return;
    if (this.selectedIndex >= 0 && this.selectedIndex < options.length) {
      options[this.selectedIndex].classList.remove("sy-selected");
    }
    const searchInput = this.searchInput;
    if ((this.selectedIndex === 0 || this.selectedIndex === -1) && this.isSearchable && searchInput !== null) {
      this.selectedIndex = -1;
      searchInput.focus();
      this.dropdown.removeAttribute("aria-activedescendant");
      return;
    }
    this.selectedIndex = (this.selectedIndex - 1 + options.length) % options.length;
    options[this.selectedIndex].classList.add("sy-selected");
    options[this.selectedIndex].focus();
    if (options[this.selectedIndex].id !== "") {
      this.dropdown.setAttribute("aria-activedescendant", options[this.selectedIndex].id);
    }
    this.scrollOptionIntoView(options[this.selectedIndex]);
  }
  scrollOptionIntoView(option2) {
    if (!option2) return;
    const dropdownRect = this.dropdown.getBoundingClientRect();
    const optionRect = option2.getBoundingClientRect();
    if (optionRect.bottom > dropdownRect.bottom) {
      this.dropdown.scrollTop += optionRect.bottom - dropdownRect.bottom;
    } else if (optionRect.top < dropdownRect.top) {
      this.dropdown.scrollTop -= dropdownRect.top - optionRect.top;
    }
  }
  getVisibleOptions() {
    let ungroupedOptions = [];
    if (this.optionsList.classList.contains("sy-dropdown-list")) {
      ungroupedOptions = Array.from(
        this.optionsList.querySelectorAll(':scope > li[role="option"]')
      );
    } else {
      ungroupedOptions = Array.from(
        this.optionsList.querySelectorAll(
          ':scope > ul.sy-dropdown-list > li[role="option"]'
        )
      );
    }
    const groupOptions = Array.from(
      this.optionsList.querySelectorAll(
        'li.sy-fo-select-option-group > ul > li[role="option"]'
      )
    );
    return [...ungroupedOptions, ...groupOptions];
  }
  getSelectedOptionLabels() {
    if (!Array.isArray(this.state) || this.state.length === 0) {
      return {};
    }
    const labels = {};
    for (const value of this.state) {
      let found = false;
      for (const option2 of this.options) {
        if (isGroup(option2)) {
          for (const groupOption of option2.options) {
            if (groupOption.value === value) {
              labels[value] = groupOption.label;
              found = true;
              break;
            }
          }
          if (found) break;
        } else if (option2.value === value) {
          labels[value] = option2.label;
          break;
        }
      }
    }
    return labels;
  }
  handleSearch(rawQuery) {
    const query = rawQuery.trim();
    this.searchQuery = query;
    if (this.searchTimeout !== null) {
      clearTimeout(this.searchTimeout);
    }
    if (query === "") {
      this.activeSearchId++;
      this.isSearching = false;
      this.options = clone2(this.originalOptions);
      this.renderOptions();
      return;
    }
    const getSearchResultsUsing = this.getSearchResultsUsing;
    if (getSearchResultsUsing === null || !this.hasDynamicSearchResults) {
      this.filterOptions(query);
      return;
    }
    this.searchTimeout = setTimeout(async () => {
      this.searchTimeout = null;
      const searchId = ++this.activeSearchId;
      this.isSearching = true;
      try {
        this.showLoadingState(true);
        const results = await getSearchResultsUsing(query);
        if (searchId !== this.activeSearchId || !this.isOpen) {
          return;
        }
        this.options = normalizeOptions(results);
        this.populateLabelRepositoryFromOptions(this.options);
        this.hideLoadingState();
        this.renderOptions();
        if (this.isOpen) {
          this.deferPositionDropdown();
        }
        if (this.options.length === 0) {
          this.showNoResultsMessage();
        }
      } catch (error) {
        if (searchId === this.activeSearchId) {
          console.error("Error fetching search results:", error);
          this.hideLoadingState();
          this.options = clone2(this.originalOptions);
          this.renderOptions();
        }
      } finally {
        if (searchId === this.activeSearchId) {
          this.isSearching = false;
        }
      }
    }, this.searchDebounce);
  }
  showLoadingState(isSearching = false) {
    if (this.optionsList.parentNode === this.dropdown) {
      this.dropdown.removeChild(this.optionsList);
    }
    this.hideLoadingState();
    const loadingItem = document.createElement("div");
    loadingItem.className = "sy-fo-select-message";
    loadingItem.textContent = isSearching ? this.searchingMessage : this.loadingMessage;
    this.dropdown.appendChild(loadingItem);
    if (this.isOpen) {
      this.deferPositionDropdown();
      this.statusRegion.textContent = loadingItem.textContent;
    }
  }
  hideLoadingState() {
    const loadingItem = this.dropdown.querySelector(".sy-fo-select-message");
    if (loadingItem !== null) {
      loadingItem.remove();
      this.statusRegion.textContent = "";
    }
  }
  showNoOptionsMessage() {
    if (this.optionsList.parentNode === this.dropdown) {
      this.dropdown.removeChild(this.optionsList);
    }
    this.hideLoadingState();
    const noOptionsItem = document.createElement("div");
    noOptionsItem.className = "sy-fo-select-message";
    noOptionsItem.textContent = this.noOptionsMessage;
    this.dropdown.appendChild(noOptionsItem);
    if (this.isOpen) {
      this.deferPositionDropdown();
      this.statusRegion.textContent = this.noOptionsMessage;
    }
  }
  showNoResultsMessage() {
    if (this.optionsList.parentNode === this.dropdown) {
      this.dropdown.removeChild(this.optionsList);
    }
    this.hideLoadingState();
    const noResultsItem = document.createElement("div");
    noResultsItem.className = "sy-fo-select-message";
    noResultsItem.textContent = this.noSearchResultsMessage;
    this.dropdown.appendChild(noResultsItem);
    if (this.isOpen) {
      this.deferPositionDropdown();
      this.statusRegion.textContent = this.noSearchResultsMessage;
    }
  }
  showMaxItemsMessage() {
    this.hideMaxItemsMessage();
    const maxItemsMessageElement = document.createElement("div");
    maxItemsMessageElement.className = "sy-fo-select-max-items-message";
    maxItemsMessageElement.textContent = this.maxItemsMessage;
    this.maxItemsMessageElement = maxItemsMessageElement;
    if (this.optionsList.parentNode === this.dropdown) {
      this.dropdown.insertBefore(maxItemsMessageElement, this.optionsList);
    } else {
      this.dropdown.appendChild(maxItemsMessageElement);
    }
    this.deferPositionDropdown();
    this.statusRegion.textContent = this.maxItemsMessage;
  }
  hideMaxItemsMessage() {
    if (this.maxItemsMessageElement === null) {
      return;
    }
    this.maxItemsMessageElement.remove();
    this.maxItemsMessageElement = null;
    if (this.statusRegion.textContent === this.maxItemsMessage) {
      this.statusRegion.textContent = "";
    }
  }
  filterOptions(query) {
    const searchInLabel = this.searchableOptionFields.includes("label");
    const searchInValue = this.searchableOptionFields.includes("value");
    const search = query.toLowerCase();
    const filteredOptions = [];
    for (const option2 of this.originalOptions) {
      if (isGroup(option2)) {
        const filteredGroupOptions = option2.options.filter(
          (groupOption) => searchInLabel && groupOption.label.toLowerCase().includes(search) || searchInValue && groupOption.value.toLowerCase().includes(search)
        );
        if (filteredGroupOptions.length > 0) {
          filteredOptions.push({
            label: option2.label,
            options: filteredGroupOptions
          });
        }
      } else if (searchInLabel && option2.label.toLowerCase().includes(search) || searchInValue && option2.value.toLowerCase().includes(search)) {
        filteredOptions.push(option2);
      }
    }
    this.options = filteredOptions;
    this.renderOptions();
    if (this.options.length === 0) {
      this.showNoResultsMessage();
    }
    if (this.isOpen) {
      this.positionDropdown();
    }
  }
  selectOption(value) {
    if (this.isDisabled) {
      return;
    }
    if (!this.isMultiple) {
      this.state = value;
      void this.updateSelectedDisplay();
      this.renderOptions();
      this.closeDropdown();
      this.selectButton.focus();
      this.onStateChange(this.state);
      return;
    }
    let newState = Array.isArray(this.state) ? [...this.state] : [];
    if (newState.includes(value)) {
      const badgeToRemove = this.selectedDisplay.querySelector(
        `[data-value="${CSS.escape(value)}"]`
      );
      if (filled(badgeToRemove)) {
        const badgesContainer = badgeToRemove.parentElement;
        if (filled(badgesContainer) && badgesContainer.children.length === 1) {
          newState = newState.filter((entry) => entry !== value);
          this.state = newState;
          void this.updateSelectedDisplay();
        } else {
          badgeToRemove.remove();
          newState = newState.filter((entry) => entry !== value);
          this.state = newState;
        }
      } else {
        newState = newState.filter((entry) => entry !== value);
        this.state = newState;
        void this.updateSelectedDisplay();
      }
      this.hideMaxItemsMessage();
      this.renderOptions();
      if (this.isOpen) {
        this.deferPositionDropdown();
      }
      this.maintainFocusInMultipleMode();
      this.onStateChange(this.state);
      return;
    }
    if (this.maxItems !== null && newState.length >= this.maxItems) {
      if (this.maxItemsMessage !== "") {
        this.showMaxItemsMessage();
      }
      return;
    }
    this.hideMaxItemsMessage();
    newState.push(value);
    this.state = newState;
    const existingBadgesContainer = this.selectedDisplay.querySelector(
      ".sy-fo-select-value-badges-ctn"
    );
    if (!filled(existingBadgesContainer)) {
      void this.updateSelectedDisplay();
    } else {
      void this.addSingleBadge(value, existingBadgesContainer);
    }
    this.renderOptions();
    if (this.isOpen) {
      this.deferPositionDropdown();
    }
    this.maintainFocusInMultipleMode();
    this.onStateChange(this.state);
  }
  async addSingleBadge(value, badgesContainer) {
    let label = this.labelRepository[value];
    if (blank(label)) {
      label = this.getSelectedOptionLabel(value);
      if (filled(label)) {
        this.labelRepository[value] = label;
      }
    }
    if (blank(label) && this.getOptionLabelsUsing !== null) {
      try {
        const fetchedOptions = normalizeLabels(await this.getOptionLabelsUsing());
        for (const option2 of fetchedOptions) {
          if (option2.value === value) {
            label = option2.label;
            this.labelRepository[value] = label;
            break;
          }
        }
      } catch (error) {
        console.error("Error fetching option label:", error);
      }
    }
    if (blank(label)) {
      label = value;
    }
    badgesContainer.appendChild(this.createBadgeElement(value, label));
  }
  maintainFocusInMultipleMode() {
    const searchInput = this.searchInput;
    if (this.isSearchable && searchInput !== null) {
      searchInput.focus();
      return;
    }
    const options = this.getVisibleOptions();
    if (options.length === 0) {
      return;
    }
    this.selectedIndex = -1;
    if (Array.isArray(this.state) && this.state.length > 0) {
      for (let i = 0; i < options.length; i++) {
        const value = options[i].getAttribute("data-value");
        if (value !== null && this.state.includes(value)) {
          this.selectedIndex = i;
          break;
        }
      }
    }
    if (this.selectedIndex === -1) {
      this.selectedIndex = 0;
    }
    options[this.selectedIndex].classList.add("sy-selected");
    options[this.selectedIndex].focus();
  }
  disable() {
    if (this.isDisabled) return;
    this.isDisabled = true;
    this.applyDisabledState();
    if (this.isOpen) {
      this.closeDropdown();
    }
  }
  enable() {
    if (!this.isDisabled) return;
    this.isDisabled = false;
    this.applyDisabledState();
  }
  applyDisabledState() {
    if (this.isDisabled) {
      this.selectButton.setAttribute("disabled", "disabled");
      this.selectButton.setAttribute("aria-disabled", "true");
      this.selectButton.classList.add("sy-disabled");
      if (this.isMultiple) {
        for (const button of this.container.querySelectorAll(".sy-badge-delete-btn")) {
          button.setAttribute("disabled", "disabled");
          button.classList.add("sy-disabled");
        }
      }
      if (!this.isMultiple && this.canSelectPlaceholder) {
        const removeButton = this.container.querySelector(".sy-fo-select-value-remove-btn");
        if (removeButton) {
          removeButton.setAttribute("disabled", "disabled");
          removeButton.classList.add("sy-disabled");
        }
      }
      if (this.isSearchable && this.searchInput !== null) {
        this.searchInput.setAttribute("disabled", "disabled");
        this.searchInput.classList.add("sy-disabled");
      }
    } else {
      this.selectButton.removeAttribute("disabled");
      this.selectButton.removeAttribute("aria-disabled");
      this.selectButton.classList.remove("sy-disabled");
      if (this.isMultiple) {
        for (const button of this.container.querySelectorAll(".sy-badge-delete-btn")) {
          button.removeAttribute("disabled");
          button.classList.remove("sy-disabled");
        }
      }
      if (!this.isMultiple && this.canSelectPlaceholder) {
        const removeButton = this.container.querySelector(".sy-fo-select-value-remove-btn");
        if (removeButton) {
          removeButton.removeAttribute("disabled");
          removeButton.classList.remove("sy-disabled");
        }
      }
      if (this.isSearchable && this.searchInput !== null) {
        this.searchInput.removeAttribute("disabled");
        this.searchInput.classList.remove("sy-disabled");
      }
    }
  }
  destroy() {
    if (this.buttonClickListener !== null) {
      this.selectButton.removeEventListener("click", this.buttonClickListener);
    }
    if (this.documentClickListener !== null) {
      document.removeEventListener("click", this.documentClickListener);
    }
    if (this.buttonKeydownListener !== null) {
      this.selectButton.removeEventListener("keydown", this.buttonKeydownListener);
    }
    if (this.dropdownKeydownListener !== null) {
      this.dropdown.removeEventListener("keydown", this.dropdownKeydownListener);
    }
    if (this.resizeListener !== null) {
      window.removeEventListener("resize", this.resizeListener);
      this.resizeListener = null;
    }
    if (this.scrollListener !== null) {
      window.removeEventListener("scroll", this.scrollListener, true);
      this.scrollListener = null;
    }
    if (this.isOpen) {
      this.closeDropdown();
    }
    if (this.searchTimeout !== null) {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = null;
    }
    if (this.positioningRequestAnimationFrame !== null) {
      cancelAnimationFrame(this.positioningRequestAnimationFrame);
      this.positioningRequestAnimationFrame = null;
    }
    this.sortable?.destroy();
    this.sortable = null;
    this.container.remove();
  }
  /**
   * Replace the server-rendered pool. While a search query is typed, only the fallback pool is
   * replaced — the results on screen stay — and the fresh pool takes over on the next clear; idle,
   * the list re-renders only when the pool actually changed, so an unrelated host render does not
   * drop keyboard focus from an open list.
   */
  replaceOptions(options) {
    const next = clone2(options);
    const changed = JSON.stringify(next) !== JSON.stringify(this.originalOptions);
    this.originalOptions = next;
    this.populateLabelRepositoryFromOptions(next);
    if (this.searchQuery !== "" || !changed) {
      return;
    }
    this.options = clone2(next);
    this.renderOptions();
  }
  rememberLabels(entries) {
    for (const entry of entries) {
      this.labelRepository[entry.value] = entry.label;
    }
  }
  /** Repaint the selection and its option row from the repository, which the host's render has just refilled. */
  refreshLabels() {
    void this.updateSelectedDisplay();
    if (!this.isMultiple && typeof this.state === "string" && filled(this.state)) {
      const label = this.labelRepository[this.state];
      if (label !== void 0) {
        this.updateOptionLabelInList(this.state, label);
      }
    }
  }
};

// src/controllers/select_controller.ts
var REFRESH_EVENT = "sylament:select-refresh-selected-option-label";
var SEARCH_METHOD = "getSearchResultsForJs";
var INLINE_PREFIX_CLASS = "sy-fo-select-has-inline-prefix";
var CONTAINER_INLINE_PREFIX_CLASS = "sy-fo-select-ctn-has-inline-prefix";
var select_controller_default = class extends Controller {
  static values = {
    key: String,
    statePath: String,
    modelBinding: String,
    options: Array,
    selectedOptions: Array,
    canSelectPlaceholder: Boolean,
    searchable: Boolean,
    dynamicSearchResults: Boolean,
    initialNoOptionsMessage: Boolean,
    searchDebounce: Number,
    optionsLimit: Number,
    maxItems: Number,
    maxItemsMessage: String,
    htmlAllowed: Boolean,
    labelsWrap: Boolean,
    position: String,
    reorderable: Boolean,
    searchFields: Array,
    placeholder: String,
    searchPrompt: String,
    searchingMessage: String,
    noSearchResultsMessage: String,
    noOptionsMessage: String,
    loadingMessage: String,
    badgeClass: String
  };
  #generation = 0;
  #component = null;
  #select = null;
  #binding = { render: true, debounce: 0 };
  #id = "";
  connect() {
    void this.#connect(++this.#generation);
  }
  disconnect() {
    this.#generation++;
    const component = this.#component;
    const select = this.#select;
    this.#component = null;
    this.#select = null;
    if (component !== null) {
      component.off("render:finished", this.#onRender);
      component.element.removeEventListener(REFRESH_EVENT, this.#onRefreshLabel);
    }
    select?.destroy();
    if (this.#id !== "") {
      this.element.id = this.#id;
      this.#id = "";
    }
    this.element.style.removeProperty("display");
  }
  reorderableValueChanged() {
    const select = this.#select;
    if (select === null) {
      return;
    }
    select.isReorderable = this.reorderableValue;
    void select.updateSelectedDisplay();
  }
  async #connect(generation) {
    const root = this.element.closest('[data-controller~="live"]');
    if (!(root instanceof HTMLElement)) {
      throw new Error("The sylament--forms--select controller needs a live component host.");
    }
    const component = await resolveHost(root);
    if (generation !== this.#generation) {
      return;
    }
    this.#component = component;
    this.#binding = parseBinding(this.modelBindingValue);
    const multiple = this.element.multiple;
    const state = this.#state(component);
    const selectedOptions = labelsOf(this.selectedOptionsValue);
    this.#id = this.element.id;
    this.element.removeAttribute("id");
    this.element.style.display = "none";
    const select = new Select({
      element: this.element,
      id: this.#id === "" ? null : this.#id,
      badgeClass: this.badgeClassValue,
      canOptionLabelsWrap: this.labelsWrapValue,
      canSelectPlaceholder: this.canSelectPlaceholderValue,
      getOptionLabelUsing: null,
      getOptionLabelsUsing: null,
      getOptionsUsing: null,
      getSearchResultsUsing: this.dynamicSearchResultsValue ? (query) => callSchemaComponentMethod(component, this.keyValue, SEARCH_METHOD, {
        search: query
      }).then((results) => optionsOf(Array.isArray(results) ? results : [])) : null,
      hasDynamicOptions: false,
      hasDynamicSearchResults: this.dynamicSearchResultsValue,
      hasInitialNoOptionsMessage: this.initialNoOptionsMessageValue,
      initialOptionLabel: !multiple && typeof state === "string" && state !== "" ? selectedOptions.find((entry) => entry.value === state)?.label ?? null : null,
      initialOptionLabels: multiple ? selectedOptions : [],
      initialState: state,
      isAutofocused: this.element.autofocus,
      isDisabled: this.element.disabled,
      isHtmlAllowed: this.htmlAllowedValue,
      isMultiple: multiple,
      isReorderable: this.reorderableValue,
      isSearchable: this.searchableValue,
      loadingMessage: this.loadingMessageValue,
      maxItems: this.hasMaxItemsValue ? this.maxItemsValue : null,
      maxItemsMessage: this.maxItemsMessageValue,
      noOptionsMessage: this.noOptionsMessageValue,
      noSearchResultsMessage: this.noSearchResultsMessageValue,
      onStateChange: (value) => this.#onStateChange(value),
      options: optionsOf(this.optionsValue),
      optionsLimit: this.optionsLimitValue,
      placeholder: this.placeholderValue,
      position: this.hasPositionValue ? this.positionValue : null,
      searchableOptionFields: [...this.searchFieldsValue],
      searchDebounce: this.searchDebounceValue,
      searchingMessage: this.searchingMessageValue,
      searchPrompt: this.searchPromptValue,
      state,
      statePath: this.statePathValue
    });
    select.rememberLabels(selectedOptions);
    if (this.element.classList.contains(INLINE_PREFIX_CLASS)) {
      select.container.classList.add(CONTAINER_INLINE_PREFIX_CLASS);
    }
    this.#mirrorAria(select);
    this.#select = select;
    component.on("render:finished", this.#onRender);
    component.element.addEventListener(REFRESH_EVENT, this.#onRefreshLabel);
  }
  #onStateChange(value) {
    const component = this.#component;
    if (component === null) {
      return;
    }
    component.set(
      this.statePathValue,
      Array.isArray(value) ? [...value] : value ?? "",
      this.#binding.render,
      this.#binding.debounce
    );
  }
  #onRender = () => {
    const select = this.#select;
    const component = this.#component;
    if (select === null || component === null) {
      return;
    }
    select.replaceOptions(optionsOf(this.optionsValue));
    select.rememberLabels(labelsOf(this.selectedOptionsValue));
    const state = this.#state(component);
    if (!sameState(state, select.state, this.element.multiple, this.reorderableValue)) {
      select.state = state;
      void select.updateSelectedDisplay();
      select.renderOptions();
    }
    if (this.element.disabled) {
      select.disable();
    } else {
      select.enable();
    }
    this.#mirrorAria(select);
  };
  /** The hidden `<select>` is what the server marks invalid or required; the combobox button is what the user operates. */
  #mirrorAria(select) {
    for (const name of ["aria-invalid", "aria-required"]) {
      const value = this.element.getAttribute(name);
      if (value === null) {
        select.selectButton.removeAttribute(name);
      } else {
        select.selectButton.setAttribute(name, value);
      }
    }
  }
  #onRefreshLabel = (event) => {
    const component = this.#component;
    if (component === null || event.target !== component.element || !(event instanceof CustomEvent)) {
      return;
    }
    const detail = event.detail;
    if (isRecord(detail) && detail.statePath === this.statePathValue) {
      this.#onRender();
      this.#select?.refreshLabels();
    }
  };
  /** The value store's entry for the state path, as the strings the engine compares. */
  #state(component) {
    const raw = component.valueStore.get(this.statePathValue);
    if (this.element.multiple) {
      if (Array.isArray(raw)) {
        return raw.filter((value) => !isBlank(value)).map(String);
      }
      return isBlank(raw) ? [] : [String(raw)];
    }
    return isBlank(raw) ? "" : String(raw);
  }
};
async function resolveHost(root) {
  try {
    return await getComponent(root);
  } catch {
    await new Promise((resolve) => {
      const onConnect = (event) => {
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
function parseBinding(directive) {
  const binding = { render: true, debounce: 0 };
  const modifiers = directive.split("|");
  modifiers.pop();
  for (const modifier of modifiers) {
    const match = /^(\w+)(?:\((.*)\))?$/.exec(modifier.trim());
    if (match === null) {
      continue;
    }
    const [, name, argument = ""] = match;
    if (name === "norender") {
      binding.render = false;
    } else if (name === "debounce") {
      binding.debounce = Number.parseInt(argument, 10) || 0;
    }
  }
  return binding;
}
function optionsOf(entries) {
  const list = [];
  for (const entry of entries) {
    if (!isRecord(entry)) {
      continue;
    }
    if (Array.isArray(entry.options)) {
      const group = { label: String(entry.label ?? ""), options: [] };
      for (const option2 of entry.options) {
        if (isRecord(option2)) {
          group.options.push(optionOf(option2));
        }
      }
      list.push(group);
    } else {
      list.push(optionOf(entry));
    }
  }
  return list;
}
function optionOf(entry) {
  return {
    label: String(entry.label ?? ""),
    value: String(entry.value ?? ""),
    isDisabled: Boolean(entry.isDisabled)
  };
}
function labelsOf(entries) {
  const labels = [];
  for (const entry of entries) {
    if (isRecord(entry)) {
      labels.push({ value: String(entry.value ?? ""), label: String(entry.label ?? "") });
    }
  }
  return labels;
}
function sameState(a, b, multiple, ordered) {
  if (!multiple) {
    return a === b;
  }
  const left = Array.isArray(a) ? ordered ? a : [...a].sort() : [];
  const right = Array.isArray(b) ? ordered ? b : [...b].sort() : [];
  return left.length === right.length && left.every((value, index2) => value === right[index2]);
}
function isRecord(value) {
  return typeof value === "object" && value !== null;
}
function isBlank(value) {
  return value === null || value === void 0 || value === "";
}
export {
  select_controller_default as default
};
/*! Bundled license information:

sortablejs/modular/sortable.esm.js:
  (**!
   * Sortable 1.15.7
   * @author	RubaXa   <trash@rubaxa.org>
   * @author	owenm    <owen23355@gmail.com>
   * @license MIT
   *)
*/
//# sourceMappingURL=select_controller.js.map
