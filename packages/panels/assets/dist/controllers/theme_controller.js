import { Controller } from "@hotwired/stimulus";
const STORAGE_KEY = "sylament.theme";
const THEMES = ["light", "dark", "system"];
const ACTIVE_CLASS = "sy-active";
const DARK_CLASS = "dark";
class theme_controller_default extends Controller {
  static targets = ["button"];
  #mediaQuery = null;
  connect() {
    this.#mediaQuery = window.matchMedia("(prefers-color-scheme: dark)");
    this.#mediaQuery.addEventListener("change", this.#onSystemChange);
    this.#render(this.#storedTheme());
  }
  disconnect() {
    this.#mediaQuery?.removeEventListener("change", this.#onSystemChange);
    this.#mediaQuery = null;
  }
  select(event) {
    const theme = event.currentTarget.dataset.theme;
    if (!this.#isTheme(theme)) {
      return;
    }
    try {
      window.localStorage.setItem(STORAGE_KEY, theme);
    } catch {
    }
    this.#render(theme);
  }
  #onSystemChange = () => {
    if (this.#storedTheme() === "system") {
      this.#render("system");
    }
  };
  /**
   * "system" is the default for an absent key, an unavailable storage, and an
   * unrecognised value alike — the same fallback the document head script applies.
   */
  #storedTheme() {
    let stored = null;
    try {
      stored = window.localStorage.getItem(STORAGE_KEY);
    } catch {
      return "system";
    }
    return this.#isTheme(stored) ? stored : "system";
  }
  #isTheme(value) {
    return typeof value === "string" && THEMES.includes(value);
  }
  #render(theme) {
    const dark = theme === "dark" || theme === "system" && (this.#mediaQuery?.matches ?? false);
    document.documentElement.classList.toggle(DARK_CLASS, dark);
    for (const button of this.buttonTargets) {
      const active = button.dataset.theme === theme;
      button.classList.toggle(ACTIVE_CLASS, active);
      button.setAttribute("aria-pressed", active ? "true" : "false");
    }
  }
}
export {
  theme_controller_default as default
};
//# sourceMappingURL=theme_controller.js.map
