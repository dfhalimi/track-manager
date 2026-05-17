import { Controller } from "@hotwired/stimulus";

export default class extends Controller<HTMLElement> {
    static targets = ["dialog"];

    static values = {
        openOnConnect: Boolean,
    };

    declare readonly dialogTarget: HTMLDialogElement;
    declare readonly openOnConnectValue: boolean;

    connect(): void {
        if (this.openOnConnectValue && this.element.getClientRects().length > 0) {
            this.open();
        }
    }

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
