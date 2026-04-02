import { Controller } from "@hotwired/stimulus";

export default class extends Controller<HTMLElement> {
    static targets = ["artistList", "artistTemplate", "removeArtistButton"];

    declare readonly artistListTarget: HTMLElement;
    declare readonly artistTemplateTarget: HTMLTemplateElement;
    declare readonly removeArtistButtonTarget: HTMLButtonElement;

    connect(): void {
        this.updateRemoveButtonState();
    }

    addArtist(): void {
        const fragment = this.artistTemplateTarget.content.cloneNode(true);
        this.artistListTarget.appendChild(fragment);
        this.updateRemoveButtonState();

        const artistInputs = this.artistInputs();
        artistInputs[artistInputs.length - 1]?.focus();
    }

    removeLastArtist(): void {
        const artistInputs = this.artistInputs();
        if (artistInputs.length <= 1) {
            return;
        }

        artistInputs[artistInputs.length - 1].remove();
        this.updateRemoveButtonState();
    }

    private updateRemoveButtonState(): void {
        this.removeArtistButtonTarget.disabled = this.artistInputs().length <= 1;
        this.removeArtistButtonTarget.classList.toggle("opacity-50", this.removeArtistButtonTarget.disabled);
    }

    /**
     * @returns HTMLInputElement[]
     */
    private artistInputs(): HTMLInputElement[] {
        return Array.from(this.artistListTarget.querySelectorAll<HTMLInputElement>('input[name="artists[]"]'));
    }
}
