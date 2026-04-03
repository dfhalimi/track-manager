import { Application } from "@hotwired/stimulus";
import ProjectImageUploadController from "../../src/ProjectManagement/Presentation/Resources/assets/controllers/project_image_upload_controller.ts";

function createFileList(files: File[]): FileList {
    const fileList = files.slice() as File[] & {
        item: (index: number) => File | null;
    };

    fileList.item = (index: number) => files[index] ?? null;

    return fileList as unknown as FileList;
}

class DataTransferMock {
    private readonly internalFiles: File[] = [];

    readonly items = {
        add: (file: File): void => {
            this.internalFiles.push(file);
        },
    };

    get files(): FileList {
        return createFileList(this.internalFiles);
    }
}

async function mountController(autoSubmit: boolean): Promise<{
    app: Application;
    controller: ProjectImageUploadController;
    form: HTMLFormElement;
    input: HTMLInputElement;
    dialog: HTMLDialogElement;
    currentFiles: () => File[];
}> {
    document.body.innerHTML = `
        <form
            data-controller="project-image-upload"
            data-project-image-upload-auto-submit-value="${autoSubmit ? "true" : "false"}"
            data-project-image-upload-target-size-value="3000"
        >
            <input type="file" data-project-image-upload-target="input" data-action="change->project-image-upload#selectFile">
            <dialog data-project-image-upload-target="dialog" data-action="cancel->project-image-upload#handleDialogCancel click->project-image-upload#closeOnBackdrop">
                <div
                    data-project-image-upload-target="viewport"
                    data-action="pointerdown->project-image-upload#startDrag pointermove->project-image-upload#drag pointerup->project-image-upload#endDrag pointercancel->project-image-upload#endDrag"
                >
                    <img data-project-image-upload-target="image" alt="preview">
                </div>
                <input type="range" value="1" data-project-image-upload-target="zoom" data-action="input->project-image-upload#updateZoom">
                <button type="button" data-action="project-image-upload#confirmCrop">Confirm</button>
            </dialog>
        </form>
    `;

    const form = document.querySelector<HTMLFormElement>("form");
    const input = document.querySelector<HTMLInputElement>('input[type="file"]');
    const dialog = document.querySelector<HTMLDialogElement>("dialog");
    const viewport = document.querySelector<HTMLDivElement>('[data-project-image-upload-target="viewport"]');

    if (form === null || input === null || dialog === null || viewport === null) {
        throw new Error("Project image upload markup not found.");
    }

    let files: File[] = [];
    Object.defineProperty(input, "files", {
        configurable: true,
        get: () => createFileList(files),
        set: (value: FileList) => {
            files = Array.from(value);
        },
    });

    dialog.showModal = vi.fn();
    dialog.close = vi.fn();
    form.requestSubmit = vi.fn();
    viewport.getBoundingClientRect = vi.fn(() => ({
        width: 320,
        height: 320,
        top: 0,
        left: 0,
        bottom: 320,
        right: 320,
        x: 0,
        y: 0,
        toJSON: () => ({}),
    }));

    const app = Application.start();
    app.register("project-image-upload", ProjectImageUploadController);

    await Promise.resolve();

    const controller = app.getControllerForElementAndIdentifier(form, "project-image-upload");
    if (!(controller instanceof ProjectImageUploadController)) {
        throw new Error("Project image upload controller not found.");
    }

    return {
        app,
        controller,
        form,
        input,
        dialog,
        currentFiles: () => files,
    };
}

describe("project_image_upload_controller", () => {
    beforeEach(() => {
        vi.stubGlobal("DataTransfer", DataTransferMock);
        vi.stubGlobal(
            "Image",
            class {
                onload: null | (() => void) = null;
                onerror: null | (() => void) = null;
                naturalWidth = 2400;
                naturalHeight = 1600;

                set src(_value: string) {
                    this.onload?.();
                }
            },
        );

        vi.stubGlobal("requestAnimationFrame", (callback: FrameRequestCallback): number => {
            callback(0);

            return 1;
        });

        vi.spyOn(URL, "createObjectURL").mockReturnValue("blob:project-image");
        vi.spyOn(URL, "revokeObjectURL").mockImplementation(() => {});
        vi.spyOn(window, "alert").mockImplementation(() => {});
        vi.spyOn(HTMLCanvasElement.prototype, "getContext").mockReturnValue({
            drawImage: vi.fn(),
        } as unknown as CanvasRenderingContext2D);
        vi.spyOn(HTMLCanvasElement.prototype, "toBlob").mockImplementation(function (
            callback: BlobCallback,
            type?: string,
        ): void {
            callback(new Blob(["cropped"], { type: type ?? "image/jpeg" }));
        });
    });

    it("replaces the selected file with the cropped version without auto-submit", async () => {
        const { app, controller, input, dialog, currentFiles, form } = await mountController(false);
        const originalFile = new File(["original"], "cover.png", { type: "image/png" });

        input.files = createFileList([originalFile]);
        await controller.selectFile();

        expect(dialog.showModal).toHaveBeenCalledTimes(1);

        await controller.confirmCrop();

        expect(dialog.close).toHaveBeenCalledTimes(1);
        expect(currentFiles()).toHaveLength(1);
        expect(currentFiles()[0].name).toBe("cover.png");
        expect(currentFiles()[0].type).toBe("image/png");
        expect(form.requestSubmit).not.toHaveBeenCalled();

        app.stop();
    });

    it("auto-submits the form after confirming the crop when configured", async () => {
        const { app, controller, input, form } = await mountController(true);
        const originalFile = new File(["original"], "cover.jpg", { type: "image/jpeg" });

        input.files = createFileList([originalFile]);
        await controller.selectFile();

        await controller.confirmCrop();

        expect(form.requestSubmit).toHaveBeenCalledTimes(1);

        app.stop();
    });
});
