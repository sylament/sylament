import { Controller } from "@hotwired/stimulus";

const STORAGE_KEY = "sylament.theme";
const THEMES = ["light", "dark", "system"] as const;
const ACTIVE_CLASS = "sy-active";
const DARK_CLASS = "dark";

type Theme = (typeof THEMES)[number];

export default class extends Controller<HTMLElement> {
  static targets = ["button"];

  declare readonly buttonTargets: HTMLElement[];

  #mediaQuery: MediaQueryList | null = null;

  connect(): void {
    this.#mediaQuery = window.matchMedia("(prefers-color-scheme: dark)");
    this.#mediaQuery.addEventListener("change", this.#onSystemChange);
    this.#render(this.#storedTheme());
  }

  disconnect(): void {
    this.#mediaQuery?.removeEventListener("change", this.#onSystemChange);
    this.#mediaQuery = null;
  }

  select(event: Event): void {
    const theme = (event.currentTarget as HTMLElement).dataset.theme;

    if (!this.#isTheme(theme)) {
      return;
    }

    try {
      window.localStorage.setItem(STORAGE_KEY, theme);
    } catch {
      // Storage unavailable: the choice still applies, it just won't persist.
    }

    this.#render(theme);
  }

  #onSystemChange = (): void => {
    if (this.#storedTheme() === "system") {
      this.#render("system");
    }
  };

  /**
   * "system" is the default for an absent key, an unavailable storage, and an
   * unrecognised value alike — the same fallback the document head script applies.
   */
  #storedTheme(): Theme {
    let stored: string | null = null;

    try {
      stored = window.localStorage.getItem(STORAGE_KEY);
    } catch {
      return "system";
    }

    return this.#isTheme(stored) ? stored : "system";
  }

  #isTheme(value: string | null | undefined): value is Theme {
    return typeof value === "string" && (THEMES as readonly string[]).includes(value);
  }

  #render(theme: Theme): void {
    const dark = theme === "dark" || (theme === "system" && (this.#mediaQuery?.matches ?? false));
    document.documentElement.classList.toggle(DARK_CLASS, dark);

    for (const button of this.buttonTargets) {
      const active = button.dataset.theme === theme;
      button.classList.toggle(ACTIVE_CLASS, active);
      button.setAttribute("aria-pressed", active ? "true" : "false");
    }
  }
}
