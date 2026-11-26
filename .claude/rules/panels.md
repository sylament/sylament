---
paths:
  - "packages/panels/src/**/*.php"
description: "How a shipped panel page or relation manager declares itself a live component."
---
# Panels rules

- **Live panel pages.** Give every concrete panel page and relation manager a bare `#[AsLiveComponent]`; UX finds a live parent by reflecting for it, and the page passes refuse a class without it.

Background: `docs/progress/panels/page.md`, `docs/progress/panels/relation-manager.md`.
