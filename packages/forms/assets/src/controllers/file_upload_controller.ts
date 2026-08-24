import { Controller } from "@hotwired/stimulus";
import { resolveLiveHost } from "@sylament/schemas/utils/live_host.js";
import {
  callSchemaComponentMethod,
  uploadSchemaComponentFile,
} from "@sylament/schemas/utils/schema_component_method.js";
import type { Component } from "@symfony/ux-live-component";
import Cropper from "cropperjs";
import * as FilePond from "filepond";
import FilePondPluginFileValidateSize from "filepond-plugin-file-validate-size";
import FilePondPluginFileValidateType from "filepond-plugin-file-validate-type";
import FilePondPluginImageCrop from "filepond-plugin-image-crop";
import FilePondPluginImageEdit from "filepond-plugin-image-edit";
import FilePondPluginImageExifOrientation from "filepond-plugin-image-exif-orientation";
import FilePondPluginImagePreview from "filepond-plugin-image-preview";
import FilePondPluginImageResize from "filepond-plugin-image-resize";
import FilePondPluginImageTransform from "filepond-plugin-image-transform";

FilePond.registerPlugin(
  FilePondPluginFileValidateSize,
  FilePondPluginFileValidateType,
  FilePondPluginImageCrop,
  FilePondPluginImageEdit,
  FilePondPluginImageExifOrientation,
  FilePondPluginImagePreview,
  FilePondPluginImageResize,
  FilePondPluginImageTransform,
);

const UPLOAD_ERROR = "The file could not be uploaded.";
const REMOVE_ERROR = "The file could not be removed.";
const LOAD_ERROR = "The file could not be loaded.";
const REORDER_ERROR = "The files could not be reordered.";
const MAX_FILES_WARNING = "Max files";
const TEMPORARY_TOKEN_PREFIX = "sylament-file:";
const AVATAR_PANEL_LAYOUT = "compact circle";
const PREVIEWABLE_TYPE = /^(audio|image|video)\//;

/** The three types a canvas can encode, and the extension each is written with. */
const ENCODED_EXTENSIONS: Record<string, string | undefined> = {
  "image/jpeg": "jpg",
  "image/png": "png",
  "image/webp": "webp",
};

const EXTENSION_TYPES: Record<string, string | undefined> = {
  jpeg: "image/jpeg",
  jpg: "image/jpeg",
  png: "image/png",
  webp: "image/webp",
};

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

/** The resize plugin types the mode as a literal union; the Stimulus String needs the cast. */
type ImageResizeMode = FilePond.FilePondOptions["imageResizeMode"];

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
  static targets = [
    "pond",
    "input",
    "error",
    "editor",
    "editorImage",
    "editorField",
    "editorRatio",
  ];

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
    imageCropAspectRatio: String,
    imageResizeMode: String,
    imageResizeTargetWidth: String,
    imageResizeTargetHeight: String,
    imageResizeUpscale: { type: Boolean, default: true },
    orientImagesFromExif: { type: Boolean, default: true },
    transformImages: Boolean,
    placeholder: String,
    imagePreviewHeight: String,
    itemPanelAspectRatio: String,
    panelAspectRatio: String,
    panelLayout: String,
    loadingIndicatorPosition: String,
    removeUploadedFileButtonPosition: String,
    uploadButtonPosition: String,
    uploadProgressIndicatorPosition: String,
    imageEditor: Boolean,
    imageEditorExplicitlyEnabled: Boolean,
    circleCropper: Boolean,
    imageEditorMode: { type: Number, default: 1 },
    imageEditorEmptyFillColor: String,
    imageEditorViewportWidth: Number,
    imageEditorViewportHeight: Number,
    automaticallyOpenImageEditorForAspectRatio: Number,
    editableSvgs: { type: Boolean, default: true },
    confirmSvgEditing: Boolean,
    downloadActionLabel: String,
    openActionLabel: String,
    confirmSvgEditingMessage: String,
    disabledSvgEditingMessage: String,
  };

  declare readonly hasInputTarget: boolean;
  declare readonly inputTarget: HTMLInputElement;
  declare readonly hasErrorTarget: boolean;
  declare readonly errorTarget: HTMLElement;
  declare readonly hasEditorTarget: boolean;
  declare readonly editorTarget: HTMLDialogElement;
  declare readonly editorImageTarget: HTMLImageElement;
  declare readonly editorFieldTargets: HTMLInputElement[];
  declare readonly editorRatioTargets: HTMLElement[];

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
  declare readonly hasMaxFileSizeValue: boolean;
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
  declare readonly hasImageCropAspectRatioValue: boolean;
  declare readonly imageCropAspectRatioValue: string;
  declare readonly hasImageResizeModeValue: boolean;
  declare readonly imageResizeModeValue: string;
  declare readonly hasImageResizeTargetWidthValue: boolean;
  declare readonly imageResizeTargetWidthValue: string;
  declare readonly hasImageResizeTargetHeightValue: boolean;
  declare readonly imageResizeTargetHeightValue: string;
  declare readonly imageResizeUpscaleValue: boolean;
  declare readonly orientImagesFromExifValue: boolean;
  declare readonly transformImagesValue: boolean;
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
  declare readonly imageEditorValue: boolean;
  declare readonly imageEditorExplicitlyEnabledValue: boolean;
  declare readonly circleCropperValue: boolean;
  declare readonly imageEditorModeValue: number;
  declare readonly hasImageEditorEmptyFillColorValue: boolean;
  declare readonly imageEditorEmptyFillColorValue: string;
  declare readonly hasImageEditorViewportWidthValue: boolean;
  declare readonly imageEditorViewportWidthValue: number;
  declare readonly hasImageEditorViewportHeightValue: boolean;
  declare readonly imageEditorViewportHeightValue: number;
  declare readonly hasAutomaticallyOpenImageEditorForAspectRatioValue: boolean;
  declare readonly automaticallyOpenImageEditorForAspectRatioValue: number;
  declare readonly editableSvgsValue: boolean;
  declare readonly confirmSvgEditingValue: boolean;
  declare readonly downloadActionLabelValue: string;
  declare readonly openActionLabelValue: string;
  declare readonly confirmSvgEditingMessageValue: string;
  declare readonly disabledSvgEditingMessageValue: string;

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
  #editor: Cropper | null = null;
  #editingFile: File | null = null;
  #editorOpenedForAspectRatio = false;
  /** The name of the file the editor is re-adding — non-null only until that item starts processing. */
  #editorSwapName: string | null = null;

  connect(): void {
    const generation = ++this.#generation;

    this.#observeVisibility();

    resolveLiveHost(this.element).then(
      (host) => {
        if (generation !== this.#generation) {
          return;
        }

        this.#host = host;
        this.#init();
      },
      () => this.#showError(UPLOAD_ERROR),
    );
  }

  disconnect(): void {
    this.#generation++;

    this.#resizeObserver?.disconnect();
    this.#intersectionObserver?.disconnect();
    this.#resizeObserver = null;
    this.#intersectionObserver = null;

    const input = this.#input;

    this.#destroyEditor();

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
    this.#editorSwapName = null;
    this.#editingFile = null;
    this.#editorOpenedForAspectRatio = false;
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

  editorSetDragMode({ params }: { params: { mode: Cropper.DragMode } }): void {
    this.#editor?.setDragMode(params.mode);
  }

  editorZoom({ params }: { params: { ratio: number } }): void {
    this.#editor?.zoom(params.ratio);
  }

  editorZoomTo({ params }: { params: { ratio: number } }): void {
    this.#editor?.zoomTo(params.ratio);
  }

  editorMove({ params }: { params: { x: number; y: number } }): void {
    this.#editor?.move(params.x, params.y);
  }

  editorRotate({ params }: { params: { degrees: number } }): void {
    this.#editor?.rotate(params.degrees);
  }

  editorFlip({ params }: { params: { axis: "x" | "y" } }): void {
    const editor = this.#editor;

    if (editor === null) {
      return;
    }

    const data = editor.getData();

    if (params.axis === "x") {
      editor.scaleX(-data.scaleX || -1);
    } else {
      editor.scaleY(-data.scaleY || -1);
    }
  }

  editorReset(): void {
    this.#editor?.reset();
  }

  editorSetAspectRatio({
    params,
  }: {
    params: { ratio: number | string; label: number | string };
  }): void {
    this.#editor?.setAspectRatio(Number(params.ratio));

    for (const button of this.editorRatioTargets) {
      button.classList.toggle(
        "sy-active",
        button.getAttribute("data-sylament--forms--file-upload-label-param") ===
          String(params.label),
      );
    }
  }

  editorApplyField(
    event: Event & { params: { field: "x" | "y" | "width" | "height" | "rotation" } },
  ): void {
    const editor = this.#editor;

    if (event instanceof KeyboardEvent) {
      if (event.key !== "Enter") {
        return;
      }

      event.preventDefault();
    }

    if (editor === null || !(event.currentTarget instanceof HTMLInputElement)) {
      return;
    }

    const value = Number(event.currentTarget.value);

    if (event.params.field === "rotation") {
      editor.rotateTo(value);
    } else {
      editor.setData({ ...editor.getData(true), [event.params.field]: value });
    }
  }

  editorSave(): void {
    this.#saveEditor();
  }

  editorCancel(event: Event): void {
    if (event.type === "cancel") {
      event.preventDefault();
    }

    this.#closeEditor();
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

    try {
      this.#initializing = true;

      const input = this.inputTarget;
      const pond = FilePond.create(input, this.#options());

      this.#input = input;
      this.#pond = pond;
      this.#lastState = JSON.stringify(this.stateValue);
      this.#registerEvents(pond);
    } finally {
      this.#initializing = false;
    }
  }

  #options(): FilePond.FilePondOptions {
    return {
      allowMultiple: this.multipleValue,
      allowPaste: this.pasteableValue,
      allowRemove: this.deletableValue,
      allowReorder: this.reorderableValue,
      allowImagePreview: this.previewableValue,
      allowImageExifOrientation: this.orientImagesFromExifValue,
      allowImageTransform: this.transformImagesValue,
      imageTransformOutputStripImageHead: false,
      allowImageEdit: this.imageEditorExplicitlyEnabledValue,
      imageEditEditor: {
        open: (file: File) => this.#loadEditor(file),
        onconfirm: () => {},
        oncancel: () => this.#closeEditor(),
        onclose: () => this.#closeEditor(),
      },
      beforeAddFile: (item) => this.#beforeAddFile(item),
      ...(this.hasImageCropAspectRatioValue
        ? { imageCropAspectRatio: this.imageCropAspectRatioValue }
        : {}),
      ...(this.hasImageResizeModeValue
        ? { imageResizeMode: this.imageResizeModeValue as ImageResizeMode }
        : {}),
      // The resize targets are Type.INT options parsing a numeric string — the maxFileSize precedent.
      ...(this.hasImageResizeTargetWidthValue
        ? { imageResizeTargetWidth: Number.parseInt(this.imageResizeTargetWidthValue, 10) }
        : {}),
      ...(this.hasImageResizeTargetHeightValue
        ? { imageResizeTargetHeight: Number.parseInt(this.imageResizeTargetHeightValue, 10) }
        : {}),
      imageResizeUpscale: this.imageResizeUpscaleValue,
      credits: false,
      disabled: this.disabledValue,
      files: this.#buildFiles(),
      itemInsertLocation: this.appendFilesValue ? "after" : "before",
      maxFiles: this.hasMaxFilesValue ? this.maxFilesValue : null,
      // The size options are Type.INT and parse a numeric string; the shipped types say `string`.
      maxFileSize: this.hasMaxFileSizeValue ? String(this.maxFileSizeValue) : null,
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

    if (pond === null || this.#activeUploads > 0 || this.#editorSwapName !== null) {
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
    const mimeType = this.mimeTypeMapValue[extension] || detectedType;

    return mimeType === ""
      ? Promise.reject(new Error(`The type of ${file.name} could not be detected.`))
      : Promise.resolve(mimeType);
  }

  /** An image off the configured ratio never enters the pond — it opens the editor instead. */
  async #beforeAddFile(item: FilePond.FilePondFile): Promise<boolean> {
    if (!this.hasAutomaticallyOpenImageEditorForAspectRatioValue) {
      return true;
    }

    const file = item.file;

    if (!(file instanceof File) || !file.type.startsWith("image/")) {
      return true;
    }

    if (await this.#checkImageAspectRatio(file)) {
      return true;
    }

    this.#editorOpenedForAspectRatio = true;
    this.#loadEditor(file);

    return false;
  }

  /** A file that will not decode resolves `true`, so it is added unedited rather than lost. */
  #checkImageAspectRatio(file: File): Promise<boolean> {
    return new Promise((resolve) => {
      const image = new Image();
      const objectUrl = URL.createObjectURL(file);

      image.onload = () => {
        URL.revokeObjectURL(objectUrl);
        resolve(
          Math.abs(
            image.width / image.height - this.automaticallyOpenImageEditorForAspectRatioValue,
          ) <= 0.01,
        );
      };

      image.onerror = () => {
        URL.revokeObjectURL(objectUrl);
        resolve(true);
      };

      image.src = objectUrl;
    });
  }

  #loadEditor(file: File): void {
    if (this.disabledValue || !this.imageEditorValue || !this.hasEditorTarget) {
      return;
    }

    const isSvg = file.type === "image/svg+xml";

    if (isSvg && !this.editableSvgsValue) {
      window.alert(this.disabledSvgEditingMessageValue);

      return;
    }

    if (
      isSvg &&
      this.confirmSvgEditingValue &&
      !window.confirm(this.confirmSvgEditingMessageValue)
    ) {
      return;
    }

    this.#fixImageDimensions(file, (editingFile) => {
      this.#editingFile = editingFile;
      this.#initEditor();

      const reader = new FileReader();
      reader.onload = () => {
        const result = reader.result;

        if (typeof result === "string") {
          requestAnimationFrame(() => this.#editor?.replace(result));
        }
      };
      reader.readAsDataURL(editingFile);
    });
  }

  /** An SVG sized only by its viewBox has no intrinsic size for Cropper to measure, so one is written on. */
  #fixImageDimensions(file: File, callback: (file: File) => void): void {
    if (file.type !== "image/svg+xml") {
      callback(file);

      return;
    }

    const reader = new FileReader();

    reader.onload = () => {
      const source = typeof reader.result === "string" ? reader.result : "";
      const svg = new DOMParser().parseFromString(source, "image/svg+xml").querySelector("svg");
      const viewBoxAttribute = ["viewBox", "ViewBox", "viewbox"].find((name) =>
        svg?.hasAttribute(name),
      );

      if (svg === null || viewBoxAttribute === undefined) {
        callback(file);

        return;
      }

      const viewBox = (svg.getAttribute(viewBoxAttribute) ?? "").split(" ");

      if (viewBox.length !== 4) {
        callback(file);

        return;
      }

      svg.setAttribute("width", `${Number.parseFloat(viewBox[2] ?? "0")}pt`);
      svg.setAttribute("height", `${Number.parseFloat(viewBox[3] ?? "0")}pt`);

      callback(
        new File(
          [new Blob([new XMLSerializer().serializeToString(svg)], { type: "image/svg+xml" })],
          file.name,
          {
            type: "image/svg+xml",
          },
        ),
      );
    };

    reader.readAsText(file);
  }

  #initEditor(): void {
    const dialog = this.editorTarget;

    if (!dialog.open) {
      dialog.showModal();
      dialog.addEventListener("close", () => this.#closeEditor(), { once: true });
    }

    this.#destroyEditor();

    const options: Cropper.Options<HTMLImageElement> = {
      aspectRatio: this.hasAutomaticallyOpenImageEditorForAspectRatioValue
        ? this.automaticallyOpenImageEditorForAspectRatioValue
        : this.#viewportRatio(),
      autoCropArea: 1,
      center: true,
      cropBoxResizable: true,
      guides: true,
      highlight: true,
      responsive: true,
      toggleDragModeOnDblclick: true,
      viewMode: this.imageEditorModeValue as Cropper.ViewMode,
      wheelZoomRatio: 0.02,
    };

    if (this.imageEditorExplicitlyEnabledValue) {
      options.crop = (event) => {
        this.#fieldInput("x").value = String(Math.round(event.detail.x));
        this.#fieldInput("y").value = String(Math.round(event.detail.y));
        this.#fieldInput("width").value = String(Math.round(event.detail.width));
        this.#fieldInput("height").value = String(Math.round(event.detail.height));
        this.#fieldInput("rotation").value = String(event.detail.rotate);
      };
    }

    this.#editor = new Cropper(this.editorImageTarget, options);
  }

  /** Filament's fallback ratio is the viewport quotient; null over null is NaN, which Cropper reads as free. */
  #viewportRatio(): number {
    return this.hasImageEditorViewportWidthValue && this.hasImageEditorViewportHeightValue
      ? this.imageEditorViewportWidthValue / this.imageEditorViewportHeightValue
      : Number.NaN;
  }

  #fieldInput(field: string): HTMLInputElement {
    const input = this.editorFieldTargets.find(
      (candidate) =>
        candidate.getAttribute("data-sylament--forms--file-upload-field-param") === field,
    );

    if (input === undefined) {
      throw new Error(`The editor renders no ${field} input.`);
    }

    return input;
  }

  #closeEditor(): void {
    if (this.#editorOpenedForAspectRatio) {
      const item = this.#editingItem();

      if (item !== undefined) {
        this.#pond?.removeFile(item.id, { revert: true });
      }

      this.#editorOpenedForAspectRatio = false;
    }

    this.#editingFile = null;
    this.#destroyEditor();

    if (this.hasEditorTarget && this.editorTarget.open) {
      this.editorTarget.close();
    }

    for (const button of this.editorRatioTargets) {
      button.classList.remove("sy-active");
    }
  }

  #destroyEditor(): void {
    this.#editor?.destroy();
    this.#editor = null;
  }

  #editingItem(): FilePond.FilePondFile | undefined {
    const name = this.#editingFile?.name;

    return name === undefined
      ? undefined
      : this.#pond?.getFiles().find((file) => file.filename === name);
  }

  #getRoundedCanvas(source: HTMLCanvasElement): HTMLCanvasElement {
    const canvas = document.createElement("canvas");
    canvas.width = source.width;
    canvas.height = source.height;

    const context = canvas.getContext("2d");

    if (context === null) {
      return source;
    }

    context.imageSmoothingEnabled = true;
    context.drawImage(source, 0, 0, source.width, source.height);
    context.globalCompositeOperation = "destination-in";
    context.beginPath();
    context.ellipse(
      source.width / 2,
      source.height / 2,
      source.width / 2,
      source.height / 2,
      0,
      0,
      2 * Math.PI,
    );
    context.fill();

    return canvas;
  }

  #saveEditor(): void {
    const editor = this.#editor;
    const editingFile = this.#editingFile;

    if (this.disabledValue || !this.imageEditorValue || editor === null || editingFile === null) {
      return;
    }

    this.#editorOpenedForAspectRatio = false;

    let canvas = editor.getCroppedCanvas({
      fillColor: this.hasImageEditorEmptyFillColorValue
        ? this.imageEditorEmptyFillColorValue
        : "transparent",
      height: this.hasImageResizeTargetHeightValue
        ? Number.parseInt(this.imageResizeTargetHeightValue, 10)
        : undefined,
      imageSmoothingEnabled: true,
      imageSmoothingQuality: "high",
      width: this.hasImageResizeTargetWidthValue
        ? Number.parseInt(this.imageResizeTargetWidthValue, 10)
        : undefined,
    });

    if (this.circleCropperValue) {
      canvas = this.#getRoundedCanvas(canvas);
    }

    const outputType =
      this.circleCropperValue || editingFile.type === "image/svg+xml"
        ? "image/png"
        : editingFile.type;

    canvas.toBlob((blob) => {
      if (blob === null) {
        this.#closeEditor();

        return;
      }

      const pond = this.#pond;

      if (pond === null) {
        this.#closeEditor();

        return;
      }

      const type = blob.type || outputType;
      const item = this.#editingItem();
      const name = versionedName(editingFile.name, type);

      this.#editorSwapName = name;

      if (item !== undefined) {
        pond.removeFile(item.id, { revert: true });
      }

      pond
        .addFile(new File([blob], name, { type, lastModified: Date.now() }))
        .then(() => this.#closeEditor())
        .catch(() => {
          this.#editorSwapName = null;
          this.#closeEditor();
        });
    }, outputType);
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
   * The transform plugin hands back a named `Blob` rather than a `File`, so it is wrapped before
   * the seam, which attaches the upload through a `DataTransfer` and takes nothing else.
   */
  #process(
    file: FilePond.ActualFileObject,
    load: (fileKey: string) => void,
    error: (message: string) => void,
    progress: (computable: boolean, loaded: number, total: number) => void,
    abort: () => void,
  ): { abort: () => void } {
    this.#editorSwapName = null;

    const host = this.#host;
    const fileKey = crypto.randomUUID();

    if (host === null) {
      error(UPLOAD_ERROR);

      return { abort: () => {} };
    }

    const upload =
      file instanceof File
        ? file
        : new File([file], file.name, { type: file.type, lastModified: file.lastModified });

    this.#activeUploads++;
    progress(false, 0, 0);

    void uploadSchemaComponentFile(host, this.keyValue, fileKey, upload).then(
      (result) => {
        this.#activeUploads--;

        if (this.#cancelled.delete(fileKey)) {
          callSchemaComponentMethod(host, this.keyValue, "removeUploadedFile", {
            fileKey,
          }).catch(() => this.#showError(REMOVE_ERROR));
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

        if (this.#cancelled.delete(fileKey)) {
          abort();

          return;
        }

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
    pond.on("removefile", (_error: unknown, file: FilePond.FilePondFile) => {
      this.#clearError();

      // The re-added item was removed before it ever processed; the original's revert carries the old name.
      if (this.#activeUploads === 0 && file.filename === this.#editorSwapName) {
        this.#editorSwapName = null;
      }
    });

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

    callSchemaComponentMethod(host, this.keyValue, "reorderUploadedFiles", {
      fileKeys: this.appendFilesValue ? fileKeys : fileKeys.reverse(),
    }).catch(() => {
      this.#lastState = null;
      this.#showError(REORDER_ERROR);
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

/**
 * Filament's `-vN` bump over the base name, with the port's one delta: the extension follows the
 * type of the blob the canvas produced, so a circle-cropped `photo.jpg` comes back as `photo-v1.png`
 * rather than a `.jpg` carrying PNG bytes (the temporary store keeps the client extension).
 */
function versionedName(name: string, type: string): string {
  const dot = name.lastIndexOf(".");
  let base = dot === -1 ? name : name.slice(0, dot);
  const original = dot === -1 ? "" : name.slice(dot + 1);
  const extension =
    EXTENSION_TYPES[original.toLowerCase()] === type
      ? original
      : (ENCODED_EXTENSIONS[type] ?? original);
  const version = /-v(\d+)/;

  base = version.test(base)
    ? base.replace(version, (_match, number: string) => `-v${Number(number) + 1}`)
    : `${base}-v1`;

  return extension === "" ? base : `${base}.${extension}`;
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
