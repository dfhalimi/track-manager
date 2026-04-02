import { Controller } from "@hotwired/stimulus";

export default class extends Controller<HTMLFormElement> {
    static targets = ["input"];

    declare readonly inputTarget: HTMLInputElement;

    openPicker(): void {
        this.inputTarget.click();
    }

    submitOnChange(): void {
        if ((this.inputTarget.files?.length ?? 0) === 0) {
            return;
        }

        this.element.requestSubmit();
    }
}
