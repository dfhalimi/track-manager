import { Application } from "@hotwired/stimulus";
import DialogController from "../../src/TrackManagement/Presentation/Resources/assets/controllers/dialog_controller.ts";

async function mountController(): Promise<{
    app: Application;
    trigger: HTMLButtonElement;
    dialog: HTMLDialogElement;
    closeButton: HTMLButtonElement;
}> {
    document.body.innerHTML = `
        <div data-controller="dialog">
            <button type="button" data-action="dialog#open">Open</button>
            <dialog data-dialog-target="dialog" data-action="click->dialog#closeOnBackdrop">
                <button type="button" data-action="dialog#close">Close</button>
            </dialog>
        </div>
    `;

    const trigger = document.querySelector<HTMLButtonElement>('[data-action="dialog#open"]');
    const dialog = document.querySelector<HTMLDialogElement>("dialog");
    const closeButton = document.querySelector<HTMLButtonElement>('[data-action="dialog#close"]');

    if (trigger === null || dialog === null || closeButton === null) {
        throw new Error("Dialog markup not found.");
    }

    dialog.showModal = vi.fn();
    dialog.close = vi.fn();

    const app = Application.start();
    app.register("dialog", DialogController);

    await Promise.resolve();

    return { app, trigger, dialog, closeButton };
}

async function mountAutoOpenController(): Promise<{
    app: Application;
    dialog: HTMLDialogElement;
}> {
    document.body.innerHTML = `
        <div data-controller="dialog" data-dialog-open-on-connect-value="true">
            <dialog data-dialog-target="dialog"></dialog>
        </div>
    `;

    const element = document.querySelector<HTMLElement>('[data-controller="dialog"]');
    const dialog = document.querySelector<HTMLDialogElement>("dialog");

    if (element === null || dialog === null) {
        throw new Error("Dialog markup not found.");
    }

    vi.spyOn(element, "getClientRects").mockReturnValue({ length: 1 } as DOMRectList);
    dialog.showModal = vi.fn();

    const app = Application.start();
    app.register("dialog", DialogController);

    await Promise.resolve();

    return { app, dialog };
}

async function mountHiddenAutoOpenController(): Promise<{
    app: Application;
    dialog: HTMLDialogElement;
}> {
    document.body.innerHTML = `
        <div data-controller="dialog" data-dialog-open-on-connect-value="true">
            <dialog data-dialog-target="dialog"></dialog>
        </div>
    `;

    const element = document.querySelector<HTMLElement>('[data-controller="dialog"]');
    const dialog = document.querySelector<HTMLDialogElement>("dialog");

    if (element === null || dialog === null) {
        throw new Error("Dialog markup not found.");
    }

    vi.spyOn(element, "getClientRects").mockReturnValue({ length: 0 } as DOMRectList);
    dialog.showModal = vi.fn();

    const app = Application.start();
    app.register("dialog", DialogController);

    await Promise.resolve();

    return { app, dialog };
}

describe("dialog_controller", () => {
    it("opens and closes the dialog target", async () => {
        const { app, trigger, dialog, closeButton } = await mountController();

        trigger.click();
        expect(dialog.showModal).toHaveBeenCalled();

        closeButton.click();
        expect(dialog.close).toHaveBeenCalledTimes(1);

        dialog.dispatchEvent(new MouseEvent("click", { bubbles: true }));
        expect(dialog.close).toHaveBeenCalledTimes(2);

        app.stop();
    });

    it("opens the dialog when configured to open on connect", async () => {
        const { app, dialog } = await mountAutoOpenController();

        expect(dialog.showModal).toHaveBeenCalledTimes(1);

        app.stop();
    });

    it("does not auto-open hidden duplicate dialogs", async () => {
        const { app, dialog } = await mountHiddenAutoOpenController();

        expect(dialog.showModal).not.toHaveBeenCalled();

        app.stop();
    });
});
