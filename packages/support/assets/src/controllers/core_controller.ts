import { Controller } from "@hotwired/stimulus";

// Intentionally empty: registering it keeps @sylament/support in AssetMapper's
// eager import graph so the package's controllers autoload.
export default class extends Controller {}
