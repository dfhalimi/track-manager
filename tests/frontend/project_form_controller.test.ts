import { Application } from "@hotwired/stimulus";
import ProjectFormController from "../../src/ProjectManagement/Presentation/Resources/assets/controllers/project_form_controller.ts";

async function mountController(): Promise<{
    app: Application;
    addButton: HTMLButtonElement;
    removeButton: HTMLButtonElement;
    artistList: HTMLElement;
}> {
    document.body.innerHTML = `
        <form data-controller="project-form">
            <div data-project-form-target="artistList">
                <input type="text" name="artists[]" value="Artist One">
            </div>
            <template data-project-form-target="artistTemplate">
                <input type="text" name="artists[]" value="">
            </template>
            <button type="button" data-action="project-form#addArtist">Add</button>
            <button type="button" data-project-form-target="removeArtistButton" data-action="project-form#removeLastArtist">Remove</button>
        </form>
    `;

    const addButton = document.querySelector<HTMLButtonElement>('[data-action="project-form#addArtist"]');
    const removeButton = document.querySelector<HTMLButtonElement>('[data-action="project-form#removeLastArtist"]');
    const artistList = document.querySelector<HTMLElement>('[data-project-form-target="artistList"]');

    if (addButton === null || removeButton === null || artistList === null) {
        throw new Error("Project form markup not found.");
    }

    const app = Application.start();
    app.register("project-form", ProjectFormController);

    await Promise.resolve();

    return { app, addButton, removeButton, artistList };
}

describe("project_form_controller", () => {
    it("adds and removes artist inputs", async () => {
        const { app, addButton, removeButton, artistList } = await mountController();

        expect(artistList.querySelectorAll('input[name="artists[]"]')).toHaveLength(1);
        expect(removeButton.disabled).toBe(true);

        addButton.click();

        expect(artistList.querySelectorAll('input[name="artists[]"]')).toHaveLength(2);
        expect(removeButton.disabled).toBe(false);

        removeButton.click();

        expect(artistList.querySelectorAll('input[name="artists[]"]')).toHaveLength(1);
        expect(removeButton.disabled).toBe(true);

        app.stop();
    });
});
