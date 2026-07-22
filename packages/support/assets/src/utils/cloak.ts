const CLOAK_CLASS = "sy-cloak";

/**
 * Reveal server-cloaked markup (Alpine x-cloak equivalent): the element ships
 * with the `sy-cloak` class (hidden while scripting is enabled — see base.css)
 * and the controller that owns it calls this once its initial state is
 * applied, typically from connect().
 */
export function uncloak(element: Element): void {
  element.classList.remove(CLOAK_CLASS);
}
