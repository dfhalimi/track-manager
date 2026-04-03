import { Controller } from "@hotwired/stimulus";

export default class extends Controller<HTMLElement> {
    static targets = ["warningDialog", "publishDialog", "forceInput"];
    static values = {
        requiresConfirmation: Boolean,
    };

    declare readonly warningDialogTarget: HTMLDialogElement;
    declare readonly publishDialogTarget: HTMLDialogElement;
    declare readonly forceInputTarget: HTMLInputElement;
    declare readonly requiresConfirmationValue: boolean;

    open(): void {
        this.forceInputTarget.value = "0";

        if (this.requiresConfirmationValue) {
            this.warningDialogTarget.showModal();

            return;
        }

        this.publishDialogTarget.showModal();
    }

    continueToPublish(): void {
        this.forceInputTarget.value = "1";
        this.warningDialogTarget.close();
        this.publishDialogTarget.showModal();
    }

    closeWarning(): void {
        this.warningDialogTarget.close();
    }

    closePublish(): void {
        this.publishDialogTarget.close();
    }

    closeOnBackdrop(event: MouseEvent): void {
        if (event.target instanceof HTMLDialogElement) {
            event.target.close();
        }
    }
}
