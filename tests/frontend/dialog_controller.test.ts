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
});
