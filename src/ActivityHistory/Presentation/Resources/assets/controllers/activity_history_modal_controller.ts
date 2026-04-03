import { Controller } from "@hotwired/stimulus";

export default class extends Controller<HTMLElement> {
    static targets = ["dialog", "content"];

    static values = {
        url: String,
    };

    declare readonly dialogTarget: HTMLDialogElement;
    declare readonly contentTarget: HTMLElement;
    declare readonly urlValue: string;

    async open(): Promise<void> {
        this.dialogTarget.showModal();
        this.contentTarget.innerHTML = '<div class="p-6 text-sm text-slate-500">Lade Historie ...</div>';

        const response = await fetch(this.urlValue, {
            headers: { "X-Requested-With": "XMLHttpRequest" },
        });

        if (!response.ok) {
            this.contentTarget.innerHTML =
                '<div class="p-6 text-sm text-red-600">Historie konnte nicht geladen werden.</div>';

            return;
        }

        this.contentTarget.innerHTML = await response.text();
    }

    close(): void {
        this.dialogTarget.close();
    }

    closeOnBackdrop(event: MouseEvent): void {
        if (event.target === this.dialogTarget) {
            this.close();
        }
    }
}
