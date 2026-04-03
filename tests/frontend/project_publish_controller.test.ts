import { Application } from "@hotwired/stimulus";
import ProjectPublishController from "../../src/ProjectManagement/Presentation/Resources/assets/controllers/project_publish_controller.ts";

async function mountController(requiresConfirmation: boolean): Promise<{
    app: Application;
    openButton: HTMLButtonElement;
    warningDialog: HTMLDialogElement;
    publishDialog: HTMLDialogElement;
    continueButton: HTMLButtonElement;
    forceInput: HTMLInputElement;
}> {
    document.body.innerHTML = `
        <div
            data-controller="project-publish"
            data-project-publish-requires-confirmation-value="${requiresConfirmation ? "true" : "false"}"
        >
            <button type="button" data-action="project-publish#open">Open</button>
            <dialog data-project-publish-target="warningDialog" data-action="click->project-publish#closeOnBackdrop">
                <button type="button" data-action="project-publish#continueToPublish">Continue</button>
            </dialog>
            <dialog data-project-publish-target="publishDialog" data-action="click->project-publish#closeOnBackdrop"></dialog>
            <input type="hidden" value="0" data-project-publish-target="forceInput">
        </div>
    `;

    const openButton = document.querySelector<HTMLButtonElement>('[data-action="project-publish#open"]');
    const warningDialog = document.querySelectorAll<HTMLDialogElement>("dialog")[0];
    const publishDialog = document.querySelectorAll<HTMLDialogElement>("dialog")[1];
    const continueButton = document.querySelector<HTMLButtonElement>(
        '[data-action="project-publish#continueToPublish"]',
    );
    const forceInput = document.querySelector<HTMLInputElement>('[data-project-publish-target="forceInput"]');

    if (
        openButton === null ||
        warningDialog === undefined ||
        publishDialog === undefined ||
        continueButton === null ||
        forceInput === null
    ) {
        throw new Error("Project publish markup not found.");
    }

    warningDialog.showModal = vi.fn();
    warningDialog.close = vi.fn();
    publishDialog.showModal = vi.fn();
    publishDialog.close = vi.fn();

    const app = Application.start();
    app.register("project-publish", ProjectPublishController);

    await Promise.resolve();

    return {
        app,
        openButton,
        warningDialog,
        publishDialog,
        continueButton,
        forceInput,
    };
}

describe("project_publish_controller", () => {
    it("opens the publish dialog directly when confirmation is not required", async () => {
        const { app, openButton, warningDialog, publishDialog, forceInput } = await mountController(false);

        forceInput.value = "1";
        openButton.click();

        expect(forceInput.value).toBe("0");
        expect(publishDialog.showModal).toHaveBeenCalledTimes(1);
        expect(warningDialog.showModal).not.toHaveBeenCalled();

        app.stop();
    });

    it("opens the warning dialog first and sets force publish on continue", async () => {
        const { app, openButton, warningDialog, publishDialog, continueButton, forceInput } =
            await mountController(true);

        openButton.click();
        expect(warningDialog.showModal).toHaveBeenCalledTimes(1);
        expect(publishDialog.showModal).not.toHaveBeenCalled();
        expect(forceInput.value).toBe("0");

        continueButton.click();
        expect(forceInput.value).toBe("1");
        expect(warningDialog.close).toHaveBeenCalledTimes(1);
        expect(publishDialog.showModal).toHaveBeenCalledTimes(1);

        app.stop();
    });
});
