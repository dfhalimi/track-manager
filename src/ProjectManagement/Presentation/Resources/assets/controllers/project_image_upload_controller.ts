import { Controller } from "@hotwired/stimulus";

type CropPosition = {
    left: number;
    top: number;
    width: number;
    height: number;
};

export default class extends Controller<HTMLFormElement> {
    static targets = ["input", "dialog", "viewport", "image", "zoom"];
    static values = {
        autoSubmit: Boolean,
        targetSize: Number,
    };

    declare readonly inputTarget: HTMLInputElement;
    declare readonly dialogTarget: HTMLDialogElement;
    declare readonly viewportTarget: HTMLDivElement;
    declare readonly imageTarget: HTMLImageElement;
    declare readonly zoomTarget: HTMLInputElement;
    declare readonly autoSubmitValue: boolean;
    declare readonly targetSizeValue: number;

    private originalFile: File | null = null;
    private imageObjectUrl: string | null = null;
    private loadedImage: HTMLImageElement | null = null;
    private baseScale = 1;
    private zoom = 1;
    private offsetX = 0;
    private offsetY = 0;
    private dragPointerId: number | null = null;
    private dragStartX = 0;
    private dragStartY = 0;
    private dragOriginOffsetX = 0;
    private dragOriginOffsetY = 0;

    disconnect(): void {
        this.revokeObjectUrl();
    }

    openPicker(): void {
        this.inputTarget.click();
    }

    async selectFile(): Promise<void> {
        const file = this.inputTarget.files?.[0];
        if (!(file instanceof File)) {
            return;
        }

        try {
            await this.prepareCropper(file);
        } catch {
            window.alert("Das Bild konnte nicht geladen werden.");
            this.resetSelection();
        }
    }

    updateZoom(): void {
        this.zoom = Number(this.zoomTarget.value || "1");
        this.applyImagePosition();
    }

    startDrag(event: PointerEvent): void {
        if (this.loadedImage === null) {
            return;
        }

        this.dragPointerId = event.pointerId;
        this.dragStartX = event.clientX;
        this.dragStartY = event.clientY;
        this.dragOriginOffsetX = this.offsetX;
        this.dragOriginOffsetY = this.offsetY;

        this.viewportTarget.setPointerCapture(event.pointerId);
        this.viewportTarget.classList.add("cursor-grabbing");
        event.preventDefault();
    }

    drag(event: PointerEvent): void {
        if (this.dragPointerId !== event.pointerId) {
            return;
        }

        this.offsetX = this.dragOriginOffsetX + (event.clientX - this.dragStartX);
        this.offsetY = this.dragOriginOffsetY + (event.clientY - this.dragStartY);
        this.applyImagePosition();
    }

    endDrag(event: PointerEvent): void {
        if (this.dragPointerId !== event.pointerId) {
            return;
        }

        if (this.viewportTarget.hasPointerCapture(event.pointerId)) {
            this.viewportTarget.releasePointerCapture(event.pointerId);
        }

        this.dragPointerId = null;
        this.viewportTarget.classList.remove("cursor-grabbing");
    }

    async confirmCrop(): Promise<void> {
        if (this.loadedImage === null || this.originalFile === null) {
            return;
        }

        try {
            const croppedFile = await this.buildCroppedFile();
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(croppedFile);
            this.inputTarget.files = dataTransfer.files;

            this.dialogTarget.close();

            if (this.autoSubmitValue) {
                this.element.requestSubmit();
            }
        } catch {
            window.alert("Das Bild konnte nicht zugeschnitten werden.");
            this.resetSelection();
        }
    }

    cancelCrop(): void {
        this.dialogTarget.close();
        this.resetSelection();
    }

    closeOnBackdrop(event: MouseEvent): void {
        if (event.target === this.dialogTarget) {
            this.cancelCrop();
        }
    }

    handleDialogCancel(event: Event): void {
        event.preventDefault();
        this.cancelCrop();
    }

    private async prepareCropper(file: File): Promise<void> {
        this.revokeObjectUrl();

        this.originalFile = file;
        this.imageObjectUrl = URL.createObjectURL(file);
        this.loadedImage = await this.loadImage(this.imageObjectUrl);
        this.zoom = 1;
        this.offsetX = 0;
        this.offsetY = 0;
        this.zoomTarget.value = "1";
        this.imageTarget.src = this.imageObjectUrl;
        this.dialogTarget.showModal();

        requestAnimationFrame(() => {
            this.initializeCrop();
        });
    }

    private initializeCrop(): void {
        if (this.loadedImage === null) {
            return;
        }

        const viewportSize = this.viewportSize();
        this.baseScale = Math.max(
            viewportSize / this.loadedImage.naturalWidth,
            viewportSize / this.loadedImage.naturalHeight,
        );

        this.applyImagePosition();
    }

    private applyImagePosition(): void {
        if (this.loadedImage === null) {
            return;
        }

        const cropPosition = this.calculateCropPosition();

        this.imageTarget.style.width = `${cropPosition.width}px`;
        this.imageTarget.style.height = `${cropPosition.height}px`;
        this.imageTarget.style.left = `${cropPosition.left}px`;
        this.imageTarget.style.top = `${cropPosition.top}px`;
    }

    private calculateCropPosition(): CropPosition {
        if (this.loadedImage === null) {
            throw new Error("Crop image is not loaded.");
        }

        const viewportSize = this.viewportSize();
        const displayScale = this.baseScale * this.zoom;
        const displayWidth = this.loadedImage.naturalWidth * displayScale;
        const displayHeight = this.loadedImage.naturalHeight * displayScale;
        const maxOffsetX = Math.max(0, (displayWidth - viewportSize) / 2);
        const maxOffsetY = Math.max(0, (displayHeight - viewportSize) / 2);

        this.offsetX = this.clamp(this.offsetX, -maxOffsetX, maxOffsetX);
        this.offsetY = this.clamp(this.offsetY, -maxOffsetY, maxOffsetY);

        return {
            left: (viewportSize - displayWidth) / 2 + this.offsetX,
            top: (viewportSize - displayHeight) / 2 + this.offsetY,
            width: displayWidth,
            height: displayHeight,
        };
    }

    private async buildCroppedFile(): Promise<File> {
        if (this.loadedImage === null || this.originalFile === null) {
            throw new Error("Missing crop state.");
        }

        const viewportSize = this.viewportSize();
        const displayScale = this.baseScale * this.zoom;
        const cropPosition = this.calculateCropPosition();
        const sourceX = Math.max(0, -cropPosition.left / displayScale);
        const sourceY = Math.max(0, -cropPosition.top / displayScale);
        const sourceSize = Math.min(
            this.loadedImage.naturalWidth - sourceX,
            this.loadedImage.naturalHeight - sourceY,
            viewportSize / displayScale,
        );
        const outputMimeType = this.resolveOutputMimeType(this.originalFile);
        const outputSize = Math.max(1, Math.min(this.targetSizeValue, Math.round(sourceSize)));
        const canvas = document.createElement("canvas");
        canvas.width = outputSize;
        canvas.height = outputSize;

        const context = canvas.getContext("2d");
        if (context === null) {
            throw new Error("Canvas context is not available.");
        }

        context.drawImage(this.loadedImage, sourceX, sourceY, sourceSize, sourceSize, 0, 0, outputSize, outputSize);

        const blob = await this.canvasToBlob(canvas, outputMimeType);

        return new File([blob], this.buildOutputFilename(this.originalFile, outputMimeType), {
            type: outputMimeType,
            lastModified: Date.now(),
        });
    }

    private canvasToBlob(canvas: HTMLCanvasElement, mimeType: string): Promise<Blob> {
        return new Promise((resolve, reject) => {
            canvas.toBlob(
                (blob) => {
                    if (!(blob instanceof Blob)) {
                        reject(new Error("Canvas blob could not be created."));

                        return;
                    }

                    resolve(blob);
                },
                mimeType,
                mimeType === "image/jpeg" ? 0.92 : undefined,
            );
        });
    }

    private resolveOutputMimeType(file: File): string {
        if (file.type === "image/png" || file.name.toLowerCase().endsWith(".png")) {
            return "image/png";
        }

        return "image/jpeg";
    }

    private buildOutputFilename(file: File, mimeType: string): string {
        const lastDotIndex = file.name.lastIndexOf(".");
        const basename = lastDotIndex > 0 ? file.name.slice(0, lastDotIndex) : file.name;
        const extension = mimeType === "image/png" ? "png" : "jpg";

        return `${basename}.${extension}`;
    }

    private viewportSize(): number {
        return this.viewportTarget.getBoundingClientRect().width;
    }

    private clamp(value: number, min: number, max: number): number {
        return Math.min(Math.max(value, min), max);
    }

    private async loadImage(objectUrl: string): Promise<HTMLImageElement> {
        return await new Promise((resolve, reject) => {
            const image = new Image();
            image.onload = () => resolve(image);
            image.onerror = () => reject(new Error("Image could not be loaded."));
            image.src = objectUrl;
        });
    }

    private resetSelection(): void {
        this.originalFile = null;
        this.loadedImage = null;
        this.inputTarget.value = "";
        this.imageTarget.removeAttribute("src");
        this.revokeObjectUrl();
    }

    private revokeObjectUrl(): void {
        if (this.imageObjectUrl !== null) {
            URL.revokeObjectURL(this.imageObjectUrl);
            this.imageObjectUrl = null;
        }
    }
}
