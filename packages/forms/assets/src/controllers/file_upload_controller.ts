import { Controller } from "@hotwired/stimulus";
import { resolveLiveHost } from "@sylament/schemas/utils/live_host.js";
import {
  callSchemaComponentMethod,
  uploadSchemaComponentFile,
} from "@sylament/schemas/utils/schema_component_method.js";
import type { Component } from "@symfony/ux-live-component";
import * as FilePond from "filepond";
import FilePondPluginFileValidateSize from "filepond-plugin-file-validate-size";
import FilePondPluginFileValidateType from "filepond-plugin-file-validate-type";
import FilePondPluginImagePreview from "filepond-plugin-image-preview";

FilePond.registerPlugin(
  FilePondPluginFileValidateSize,
  FilePondPluginFileValidateType,
  FilePondPluginImagePreview,
);

const UPLOAD_ERROR = "The file could not be uploaded.";
const REMOVE_ERROR = "The file could not be removed.";
const LOAD_ERROR = "The file could not be loaded.";
const MAX_FILES_WARNING = "Max files";
const TEMPORARY_TOKEN_PREFIX = "sylament-file:";
const AVATAR_PANEL_LAYOUT = "compact circle";
const PREVIEWABLE_TYPE = /^(audio|image|video)\//;

/** `initfile` is a real event (filepond.esm.js:8842) the shipped types leave out of FilePondEvent. */
const INIT_FILE_EVENT = "initfile" as FilePond.FilePondEvent;

type UploadedFileEntry = {
  name: string;
  size: number;
  type: string | null;
  url?: string | null;
  openableUrl?: string | null;
  downloadableUrl?: string | null;
};

type UploadResult = { ok: true } | { ok: false; message: string };

/**
 * FilePond over the schemas host seam: every upload rides one live round trip
 * (`uploadSchemaComponentFile`), every mutation one `callSchemaComponentMethod` call, and the
 * server's re-rendered `state`/`uploadedFiles` values are the file list's only truth — the pond's
 * own subtree is `data-live-ignore`, so this controller reconciles it by hand after each morph the
 * way Filament's `$watch('state')` does.
 *
 * FilePond measures its layout at create time and renders a zero-height panel inside a hidden
 * container, so the pond is built only once the element is visible and a resize/intersection pair
 * retries — mandatory under Tabs and Wizard.
 */
export default class extends Controller<HTMLElement> {
  static targets = ["pond", "input", "error"];

  static values = {
    key: String,
    statePath: String,
    state: Object,
    uploadedFiles: Object,
    multiple: Boolean,
    disabled: Boolean,
    deletable: Boolean,
    reorderable: Boolean,
    pasteable: Boolean,
    previewable: Boolean,
    downloadable: Boolean,
    openable: Boolean,
    avatar: Boolean,
    appendFiles: Boolean,
    acceptedFileTypes: Array,
    maxFileSize: Number,
    minSize: String,
    maxFiles: Number,
    maxFilesMessage: String,
    maxParallelUploads: Number,
    mimeTypeMap: Object,
    placeholder: String,
    imagePreviewHeight: String,
    itemPanelAspectRatio: String,
    panelAspectRatio: String,
    panelLayout: String,
    loadingIndicatorPosition: String,
    removeUploadedFileButtonPosition: String,
    uploadButtonPosition: String,
    uploadProgressIndicatorPosition: String,
    downloadActionLabel: String,
    openActionLabel: String,
  };

  declare readonly hasInputTarget: boolean;
  declare readonly inputTarget: HTMLInputElement;
  declare readonly hasErrorTarget: boolean;
  declare readonly errorTarget: HTMLElement;

  declare readonly keyValue: string;
  declare readonly statePathValue: string;
  declare readonly stateValue: Record<string, unknown>;
  declare readonly uploadedFilesValue: Record<string, UploadedFileEntry | null>;
  declare readonly multipleValue: boolean;
  declare readonly disabledValue: boolean;
  declare readonly deletableValue: boolean;
  declare readonly reorderableValue: boolean;
  declare readonly pasteableValue: boolean;
  declare readonly previewableValue: boolean;
  declare readonly downloadableValue: boolean;
  declare readonly openableValue: boolean;
  declare readonly avatarValue: boolean;
  declare readonly appendFilesValue: boolean;
  declare readonly hasAcceptedFileTypesValue: boolean;
  declare readonly acceptedFileTypesValue: string[];
  declare readonly maxFileSizeValue: number;
  declare readonly hasMinSizeValue: boolean;
  declare readonly minSizeValue: string;
  declare readonly hasMaxFilesValue: boolean;
  declare readonly maxFilesValue: number;
  declare readonly hasMaxFilesMessageValue: boolean;
  declare readonly maxFilesMessageValue: string;
  declare readonly hasMaxParallelUploadsValue: boolean;
  declare readonly maxParallelUploadsValue: number;
  declare readonly mimeTypeMapValue: Record<string, string>;
  declare readonly hasPlaceholderValue: boolean;
  declare readonly placeholderValue: string;
  declare readonly hasImagePreviewHeightValue: boolean;
  declare readonly imagePreviewHeightValue: string;
  declare readonly hasItemPanelAspectRatioValue: boolean;
  declare readonly itemPanelAspectRatioValue: string;
  declare readonly hasPanelAspectRatioValue: boolean;
  declare readonly panelAspectRatioValue: string;
  declare readonly panelLayoutValue: string;
  declare readonly loadingIndicatorPositionValue: string;
  declare readonly removeUploadedFileButtonPositionValue: string;
  declare readonly uploadButtonPositionValue: string;
  declare readonly uploadProgressIndicatorPositionValue: string;
  declare readonly downloadActionLabelValue: string;
  declare readonly openActionLabelValue: string;

  #generation = 0;
  #host: Component | null = null;
  #pond: FilePond.FilePond | null = null;
  /** The `<input>` FilePond replaced — `destroy()` takes it, and the target is gone until then. */
  #input: HTMLInputElement | null = null;
  /** A pond file's source (its url, or its file key when it has none) → the state's file key. */
  #sourceIndex = new Map<string, string>();
  #activeUploads = 0;
  #lastState: string | null = null;
  #cancelled = new Set<string>();
  #resizeObserver: ResizeObserver | null = null;
  #intersectionObserver: IntersectionObserver | null = null;
  #initializing = false;

  connect(): void {
    const generation = ++this.#generation;

    this.#observeVisibility();

    void resolveLiveHost(this.element).then((host) => {
      if (generation !== this.#generation) {
        return;
      }

      this.#host = host;
      this.#init();
    });
  }

  disconnect(): void {
    this.#generation++;

    this.#resizeObserver?.disconnect();
    this.#intersectionObserver?.disconnect();
    this.#resizeObserver = null;
    this.#intersectionObserver = null;

    const input = this.#input;

    if (this.#pond !== null && input !== null) {
      FilePond.destroy(input);
    }

    this.#pond = null;
    this.#input = null;
    this.#host = null;
    this.#lastState = null;
    this.#sourceIndex.clear();
    this.#cancelled.clear();
    this.#activeUploads = 0;
  }

  stateValueChanged(): void {
    this.#reconcile();
  }

  uploadedFilesValueChanged(): void {
    this.#reconcile();
  }

  disabledValueChanged(): void {
    this.#pond?.setOptions({ disabled: this.disabledValue });
  }

  #isHidden(): boolean {
    return (
      this.element.offsetParent === null || getComputedStyle(this.element).visibility === "hidden"
    );
  }

  /**
   * Builds the pond as soon as the element has a box, and once it has one, wakes FilePond's own
   * render loop with the `visibilitychange` it listens for.
   */
  #observeVisibility(): void {
    const onVisible = (): void => {
      if (this.#isHidden()) {
        return;
      }

      if (this.#pond === null) {
        this.#init();
      } else {
        document.dispatchEvent(new Event("visibilitychange"));
      }
    };

    this.#resizeObserver = new ResizeObserver(onVisible);
    this.#resizeObserver.observe(this.element);

    this.#intersectionObserver = new IntersectionObserver(
      (entries) => {
        if (entries[0]?.isIntersecting === true) {
          onVisible();
        }
      },
      { threshold: 0 },
    );
    this.#intersectionObserver.observe(this.element);
  }

  #init(): void {
    if (this.#pond !== null || this.#initializing || !this.hasInputTarget || this.#isHidden()) {
      return;
    }

    this.#initializing = true;

    const input = this.inputTarget;
    const pond = FilePond.create(input, this.#options());

    this.#input = input;
    this.#pond = pond;
    this.#lastState = JSON.stringify(this.stateValue);
    this.#registerEvents(pond);
    this.#initializing = false;
  }

  #options(): FilePond.FilePondOptions {
    return {
      allowMultiple: this.multipleValue,
      allowPaste: this.pasteableValue,
      allowRemove: this.deletableValue,
      allowReorder: this.reorderableValue,
      allowImagePreview: this.previewableValue,
      credits: false,
      disabled: this.disabledValue,
      files: this.#buildFiles(),
      itemInsertLocation: this.appendFilesValue ? "after" : "before",
      maxFiles: this.hasMaxFilesValue ? this.maxFilesValue : null,
      // The size options are Type.INT and parse a numeric string; the shipped types say `string`.
      maxFileSize: String(this.maxFileSizeValue),
      styleButtonProcessItemPosition: this.uploadButtonPositionValue,
      styleButtonRemoveItemPosition: this.removeUploadedFileButtonPositionValue,
      styleItemPanelAspectRatio: this.hasItemPanelAspectRatioValue
        ? this.itemPanelAspectRatioValue
        : null,
      styleLoadIndicatorPosition: this.loadingIndicatorPositionValue,
      stylePanelAspectRatio: this.hasPanelAspectRatioValue ? this.panelAspectRatioValue : null,
      stylePanelLayout: this.panelLayoutValue as FilePond.FilePondOptions["stylePanelLayout"],
      styleProgressIndicatorPosition: this.uploadProgressIndicatorPositionValue,
      fileValidateTypeDetectType: (file, detectedType) => this.#detectType(file, detectedType),
      server: {
        load: (source, load, error) => this.#load(source, load, error),
        process: (_fieldName, file, _metadata, load, error, progress, abort) =>
          this.#process(file, load, error, progress, abort),
        revert: (fileKey, load, error) => this.#revert(String(fileKey), load, error),
        remove: (source, load, error) => this.#remove(source, load, error),
      },
      ...(this.hasAcceptedFileTypesValue
        ? { acceptedFileTypes: [...this.acceptedFileTypesValue] }
        : {}),
      ...(this.hasMinSizeValue ? { minFileSize: this.minSizeValue } : {}),
      ...(this.hasImagePreviewHeightValue
        ? { imagePreviewHeight: Number.parseInt(this.imagePreviewHeightValue, 10) }
        : {}),
      // FilePond's own label and parallel-upload default only survive when the key is absent.
      ...(this.hasPlaceholderValue ? { labelIdle: this.placeholderValue } : {}),
      ...(this.hasMaxParallelUploadsValue
        ? { maxParallelUploads: this.maxParallelUploadsValue }
        : {}),
    };
  }

  /** The server's `uploadedFiles` payload as FilePond's initial-file list, newest first unless appending. */
  #buildFiles(): FilePond.FilePondInitialFile[] {
    this.#sourceIndex.clear();

    const files: FilePond.FilePondInitialFile[] = [];

    for (const [fileKey, entry] of Object.entries(this.uploadedFilesValue)) {
      if (entry === null) {
        continue;
      }

      const url = entry.url ?? null;
      const source = url ?? fileKey;
      this.#sourceIndex.set(source, fileKey);

      const preview =
        url !== null &&
        this.previewableValue &&
        typeof entry.type === "string" &&
        PREVIEWABLE_TYPE.test(entry.type);

      files.push({
        source,
        options: {
          type: "local",
          metadata: {
            fileKey,
            openableUrl: entry.openableUrl,
            downloadableUrl: entry.downloadableUrl,
          },
          // A mock file skips the fetch, at the cost of the preview the plugin can only build from a real one.
          ...(preview
            ? {}
            : { file: { name: entry.name, size: entry.size, type: entry.type ?? undefined } }),
        },
      });
    }

    return this.appendFilesValue ? files : files.reverse();
  }

  /** The morph's twin of Filament's `$watch('state')`: rebuild the list only for a settled, changed, token-free state. */
  #reconcile(): void {
    const pond = this.#pond;

    if (pond === null || this.#activeUploads > 0) {
      return;
    }

    const state = this.stateValue;

    if (
      Object.values(state).some(
        (value) => typeof value === "string" && value.startsWith(TEMPORARY_TOKEN_PREFIX),
      )
    ) {
      this.#lastState = null;

      return;
    }

    const json = JSON.stringify(state);

    if (json === this.#lastState) {
      return;
    }

    this.#lastState = json;
    pond.files = this.#buildFiles();
  }

  #detectType(file: File, detectedType: string): Promise<string> {
    const extension = file.name.split(".").pop()?.toLowerCase() ?? "";
    const mimeType = this.mimeTypeMapValue[extension] ?? detectedType;

    return mimeType === ""
      ? Promise.reject(new Error(`The type of ${file.name} could not be detected.`))
      : Promise.resolve(mimeType);
  }

  /** Filament resolves a failed fetch as an empty blob; a stored file that will not load is an error here. */
  #load(source: unknown, load: (file: Blob) => void, error: (message: string) => void): void {
    void (async () => {
      try {
        const response = await fetch(String(source), { cache: "no-store" });

        if (!response.ok) {
          error(LOAD_ERROR);

          return;
        }

        load(await response.blob());
      } catch {
        // Any failure here is one FilePond can only be told about through `error`.
        error(LOAD_ERROR);
      }
    })();
  }

  /**
   * One live round trip per file, with the file key minted here so the queue, the multipart part
   * name and the state entry all agree. There is no byte progress to report over a live action, so
   * the indicator is indeterminate, and an abort can only be honoured once the request settles.
   */
  #process(
    file: FilePond.ActualFileObject,
    load: (fileKey: string) => void,
    error: (message: string) => void,
    progress: (computable: boolean, loaded: number, total: number) => void,
    abort: () => void,
  ): { abort: () => void } {
    const host = this.#host;
    const fileKey = crypto.randomUUID();

    if (host === null) {
      error(UPLOAD_ERROR);

      return { abort: () => {} };
    }

    this.#activeUploads++;
    progress(false, 0, 0);

    void uploadSchemaComponentFile(host, this.keyValue, fileKey, file as File).then(
      (result) => {
        this.#activeUploads--;

        if (this.#cancelled.delete(fileKey)) {
          void callSchemaComponentMethod(host, this.keyValue, "removeUploadedFile", { fileKey });
          abort();

          return;
        }

        const parsed = parseUploadResult(result);

        if (parsed.ok) {
          load(fileKey);
        } else {
          error(parsed.message);
        }
      },
      () => {
        this.#activeUploads--;
        error(UPLOAD_ERROR);
      },
    );

    return {
      abort: () => {
        this.#cancelled.add(fileKey);
      },
    };
  }

  #revert(fileKey: string, load: () => void, error: (message: string) => void): void {
    const host = this.#host;

    if (host === null) {
      error(REMOVE_ERROR);

      return;
    }

    void callSchemaComponentMethod(host, this.keyValue, "removeUploadedFile", { fileKey }).then(
      () => load(),
      () => error(REMOVE_ERROR),
    );
  }

  #remove(source: unknown, load: () => void, error: (message: string) => void): void {
    const host = this.#host;
    const fileKey = this.#sourceIndex.get(String(source));

    if (fileKey === undefined) {
      load();

      return;
    }

    if (host === null) {
      error(REMOVE_ERROR);

      return;
    }

    void callSchemaComponentMethod(host, this.keyValue, "deleteUploadedFile", { fileKey }).then(
      () => load(),
      () => error(REMOVE_ERROR),
    );
  }

  #registerEvents(pond: FilePond.FilePond): void {
    pond.on("reorderfiles", (files: FilePond.FilePondFile[]) => this.#onReorder(files));
    pond.on(INIT_FILE_EVENT, (file: FilePond.FilePondFile) => this.#onInitFile(file));
    pond.on("addfilestart", () => this.#clearError());
    pond.on("removefile", () => this.#clearError());

    pond.on("warning", (warning: unknown) => {
      if (isRecord(warning) && warning.body === MAX_FILES_WARNING && this.hasMaxFilesMessageValue) {
        this.#showError(this.maxFilesMessageValue);
      }
    });

    // The circle layout has no room for FilePond's inline error, so it is shown beneath the pond.
    if (this.panelLayoutValue === AVATAR_PANEL_LAYOUT) {
      pond.on("error", (error: unknown) => {
        const message = errorMessage(error);

        if (message !== "") {
          this.#showError(message);
        }
      });
    }
  }

  #onReorder(files: FilePond.FilePondFile[]): void {
    const host = this.#host;

    if (host === null) {
      return;
    }

    const fileKeys: string[] = [];

    for (const file of files) {
      // A file uploaded in this session carries its key as the server id; a stored one is indexed by source.
      const fileKey =
        file.source instanceof File
          ? file.serverId
          : (this.#sourceIndex.get(String(file.source)) ?? null);

      if (fileKey !== null && fileKey !== "") {
        fileKeys.push(fileKey);
      }
    }

    void callSchemaComponentMethod(host, this.keyValue, "reorderUploadedFiles", {
      fileKeys: this.appendFilesValue ? fileKeys : fileKeys.reverse(),
    });
  }

  #onInitFile(file: FilePond.FilePondFile): void {
    if (this.avatarValue || file.origin !== FilePond.FileOrigin.LOCAL) {
      return;
    }

    const info = document
      .getElementById(`filepond--item-${file.id}`)
      ?.querySelector(".filepond--file-info-main");

    if (info === null || info === undefined) {
      return;
    }

    if (this.downloadableValue) {
      const anchor = this.#downloadLink(file);

      if (anchor !== null) {
        info.prepend(anchor);
      }
    }

    if (this.openableValue) {
      const anchor = this.#openLink(file);

      if (anchor !== null) {
        info.prepend(anchor);
      }
    }
  }

  #downloadLink(file: FilePond.FilePondFile): HTMLAnchorElement | null {
    const url = urlFor(file, "downloadableUrl");

    if (url === null) {
      return null;
    }

    const anchor = anchorFor(url, "download", this.downloadActionLabelValue);
    anchor.download = file.file.name;

    return anchor;
  }

  #openLink(file: FilePond.FilePondFile): HTMLAnchorElement | null {
    const url = urlFor(file, "openableUrl");

    if (url === null) {
      return null;
    }

    const anchor = anchorFor(url, "open", this.openActionLabelValue);
    anchor.target = "_blank";
    anchor.rel = "noopener noreferrer";

    return anchor;
  }

  #showError(message: string): void {
    if (!this.hasErrorTarget) {
      return;
    }

    this.errorTarget.textContent = message;
    this.errorTarget.hidden = false;
  }

  #clearError(): void {
    if (!this.hasErrorTarget) {
      return;
    }

    this.errorTarget.textContent = "";
    this.errorTarget.hidden = true;
  }
}

function parseUploadResult(result: unknown): UploadResult {
  if (!isRecord(result)) {
    return { ok: false, message: UPLOAD_ERROR };
  }

  if (result.ok === true) {
    return { ok: true };
  }

  return {
    ok: false,
    message: typeof result.message === "string" ? result.message : UPLOAD_ERROR,
  };
}

/**
 * A validation failure arrives as `{main, sub}` and a transport failure as `{type, code, body}`, so
 * the parts are read defensively rather than rendered as "undefined: undefined". The replacement
 * patches FilePond's own English for a file of an unexpected type.
 */
function errorMessage(error: unknown): string {
  if (!isRecord(error)) {
    return "";
  }

  return [error.main, error.sub]
    .filter((part) => typeof part === "string" && part !== "")
    .join(": ")
    .replace("Expects  or", "Expects");
}

function urlFor(file: FilePond.FilePondFile, key: string): string | null {
  const url: unknown = file.getMetadata(key);

  if (typeof url === "string" && url !== "") {
    return url;
  }

  // An entry the storage could mint no url for is sourced by its file key, which is not a link.
  const fileKey: unknown = file.getMetadata("fileKey");

  return typeof file.source === "string" && file.source !== "" && file.source !== fileKey
    ? file.source
    : null;
}

function anchorFor(url: string, action: "download" | "open", label: string): HTMLAnchorElement {
  const anchor = document.createElement("a");
  anchor.className = `sy-fo-file-upload-${action}-action`;
  anchor.href = url;
  anchor.setAttribute("aria-label", label);
  anchor.title = label;

  const icon = document.createElement("span");
  icon.className = `sy-fo-file-upload-${action}-icon`;
  icon.setAttribute("aria-hidden", "true");
  anchor.append(icon);

  return anchor;
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === "object" && value !== null;
}
