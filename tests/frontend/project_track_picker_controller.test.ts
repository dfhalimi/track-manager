import { Application } from "@hotwired/stimulus";
import ProjectTrackPickerController from "../../src/ProjectManagement/Presentation/Resources/assets/controllers/project_track_picker_controller.ts";

async function mountController(): Promise<{
    app: Application;
    searchInput: HTMLInputElement;
    hiddenInput: HTMLInputElement;
    submitButton: HTMLButtonElement;
    suggestions: HTMLElement;
    form: HTMLFormElement;
}> {
    document.body.innerHTML = `
        <form
            data-controller="project-track-picker"
            data-project-track-picker-suggestions-url-value="/projects/project-1/tracks/suggestions"
            data-project-track-picker-debounce-ms-value="1"
            data-action="submit->project-track-picker#submitForm"
        >
            <input type="hidden" name="track_uuid" value="" data-project-track-picker-target="hiddenInput">
            <input
                type="text"
                value=""
                data-project-track-picker-target="searchInput"
                data-action="input->project-track-picker#handleInput focus->project-track-picker#handleFocus blur->project-track-picker#handleBlur keydown->project-track-picker#handleKeydown"
            >
            <div
                class="hidden"
                data-project-track-picker-target="suggestions"
                data-action="mousedown->project-track-picker#handleMouseDown click->project-track-picker#handleClick"
            ></div>
            <button type="submit" data-project-track-picker-target="submitButton" disabled>Track hinzufügen</button>
        </form>
    `;

    const form = document.querySelector("form");
    const searchInput = document.querySelector<HTMLInputElement>('input[type="text"]');
    const hiddenInput = document.querySelector<HTMLInputElement>('input[type="hidden"]');
    const submitButton = document.querySelector<HTMLButtonElement>('button[type="submit"]');
    const suggestions = document.querySelector<HTMLElement>('[data-project-track-picker-target="suggestions"]');

    if (
        form === null ||
        searchInput === null ||
        hiddenInput === null ||
        submitButton === null ||
        suggestions === null
    ) {
        throw new Error("Project track picker markup not found.");
    }

    searchInput.reportValidity = vi.fn(() => true);

    const app = Application.start();
    app.register("project-track-picker", ProjectTrackPickerController);

    await Promise.resolve();

    return { app, searchInput, hiddenInput, submitButton, suggestions, form };
}

describe("project_track_picker_controller", () => {
    it("renders suggestions and selects a track", async () => {
        vi.useFakeTimers();
        vi.stubGlobal(
            "fetch",
            vi.fn().mockResolvedValue({
                ok: true,
                json: async () => ({
                    suggestions: [
                        { trackUuid: "track-1", label: "#1 - Alpha / Alternate (Beat)" },
                        { trackUuid: "track-2", label: "#2 - Beta (Beat)" },
                    ],
                }),
            }),
        );

        const { app, searchInput, hiddenInput, submitButton, suggestions } = await mountController();

        searchInput.value = "alp";
        searchInput.dispatchEvent(new Event("input", { bubbles: true }));

        await vi.runAllTimersAsync();
        await Promise.resolve();

        expect(fetch).toHaveBeenCalledWith(
            "/projects/project-1/tracks/suggestions?q=alp",
            expect.objectContaining({
                headers: { Accept: "application/json" },
            }),
        );

        const firstSuggestion = suggestions.querySelector<HTMLButtonElement>('button[data-track-uuid="track-1"]');
        if (firstSuggestion === null) {
            throw new Error("First suggestion not rendered.");
        }

        firstSuggestion.click();

        expect(hiddenInput.value).toBe("track-1");
        expect(searchInput.value).toBe("#1 - Alpha / Alternate (Beat)");
        expect(submitButton.disabled).toBe(false);

        app.stop();
        vi.useRealTimers();
    });

    it("requires selecting a suggestion before submitting", async () => {
        vi.useFakeTimers();
        vi.stubGlobal(
            "fetch",
            vi.fn().mockResolvedValue({
                ok: true,
                json: async () => ({
                    suggestions: [{ trackUuid: "track-1", label: "#1 - Alpha (Beat)" }],
                }),
            }),
        );

        const { app, searchInput, form } = await mountController();
        const submitEvent = new SubmitEvent("submit", { bubbles: true, cancelable: true });

        searchInput.value = "alp";
        searchInput.dispatchEvent(new Event("input", { bubbles: true }));

        await vi.runAllTimersAsync();
        await Promise.resolve();

        form.dispatchEvent(submitEvent);

        expect(submitEvent.defaultPrevented).toBe(true);
        expect(searchInput.reportValidity).toHaveBeenCalled();
        expect(searchInput.validationMessage).toBe("Bitte wähle einen Track aus den Vorschlägen aus.");

        app.stop();
        vi.useRealTimers();
    });
});
