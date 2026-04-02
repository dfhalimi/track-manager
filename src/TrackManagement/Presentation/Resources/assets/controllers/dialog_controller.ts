import { Controller } from "@hotwired/stimulus";

export default class extends Controller<HTMLElement> {
    static targets = ["dialog"];

    declare readonly dialogTarget: HTMLDialogElement;

    open(): void {
        this.dialogTarget.showModal();
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
