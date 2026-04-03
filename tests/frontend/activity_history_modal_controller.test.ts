import { Application } from "@hotwired/stimulus";
import ActivityHistoryModalController from "../../src/ActivityHistory/Presentation/Resources/assets/controllers/activity_history_modal_controller.ts";

async function mountController(): Promise<{
    app: Application;
    trigger: HTMLButtonElement;
    dialog: HTMLDialogElement;
    content: HTMLElement;
}> {
    document.body.innerHTML = `
        <div
            data-controller="activity-history-modal"
            data-activity-history-modal-url-value="/tracks/track-1/history"
        >
            <button type="button" data-action="activity-history-modal#open">Open</button>
            <dialog data-activity-history-modal-target="dialog" data-action="click->activity-history-modal#closeOnBackdrop">
                <div data-activity-history-modal-target="content"></div>
            </dialog>
        </div>
    `;

    const trigger = document.querySelector<HTMLButtonElement>('button[data-action="activity-history-modal#open"]');
    const dialog = document.querySelector<HTMLDialogElement>("dialog");
    const content = document.querySelector<HTMLElement>('[data-activity-history-modal-target="content"]');

    if (trigger === null || dialog === null || content === null) {
        throw new Error("Activity history modal markup not found.");
    }

    dialog.showModal = vi.fn();
    dialog.close = vi.fn();

    const app = Application.start();
    app.register("activity-history-modal", ActivityHistoryModalController);

    await Promise.resolve();

    return { app, trigger, dialog, content };
}

describe("activity_history_modal_controller", () => {
    it("opens the dialog and loads modal content on demand", async () => {
        vi.stubGlobal(
            "fetch",
            vi.fn().mockResolvedValue({
                ok: true,
                text: async () => "<div>Historie geladen</div>",
            }),
        );

        const { app, trigger, dialog, content } = await mountController();

        trigger.click();
        await Promise.resolve();
        await Promise.resolve();

        expect(dialog.showModal).toHaveBeenCalled();
        expect(fetch).toHaveBeenCalledWith(
            "/tracks/track-1/history",
            expect.objectContaining({
                headers: { "X-Requested-With": "XMLHttpRequest" },
            }),
        );
        expect(content.innerHTML).toContain("Historie geladen");

        app.stop();
    });
});
