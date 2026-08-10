import { Controller } from "@hotwired/stimulus";
class notifications_controller_default extends Controller {
  receive(event) {
    const html = event.detail?.html;
    if (typeof html !== "string") {
      return;
    }
    this.element.insertAdjacentHTML("beforeend", html);
  }
}
export {
  notifications_controller_default as default
};
//# sourceMappingURL=notifications_controller.js.map
