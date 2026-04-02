import { Controller } from "@hotwired/stimulus";

export default class extends Controller<HTMLFormElement> {
    static targets = [
        "beatName",
        "title",
        "musicalKey",
        "replaceFlag",
        "suggestionLabel",
        "bpmList",
        "bpmTemplate",
        "removeBpmButton",
        "modal",
        "modalCurrentTitle",
        "modalSuggestedTitle",
    ];

    static values = {
        trackNumber: Number,
        editMode: Boolean,
        originalBeatName: String,
        originalBpms: String,
        originalMusicalKey: String,
    };

    declare readonly beatNameTarget: HTMLInputElement;
    declare readonly titleTarget: HTMLInputElement;
    declare readonly musicalKeyTarget: HTMLInputElement;
    declare readonly replaceFlagTarget: HTMLInputElement;
    declare readonly suggestionLabelTarget: HTMLElement;
    declare readonly bpmListTarget: HTMLElement;
    declare readonly bpmTemplateTarget: HTMLTemplateElement;
    declare readonly removeBpmButtonTarget: HTMLButtonElement;
    declare readonly modalTarget: HTMLDialogElement;
    declare readonly modalCurrentTitleTarget: HTMLElement;
    declare readonly modalSuggestedTitleTarget: HTMLElement;
    declare readonly trackNumberValue: number;
    declare readonly editModeValue: boolean;
    declare readonly originalBeatNameValue: string;
    declare readonly originalBpmsValue: string;
    declare readonly originalMusicalKeyValue: string;

    declare manuallyEditedTitle: boolean;

    private submitConfirmed = false;

    connect(): void {
        this.manuallyEditedTitle = false;
        this.updateSuggestion();
        this.updateRemoveButtonState();
    }

    updateSuggestion(): void {
        const suggestion = this.buildTitle();
        this.suggestionLabelTarget.textContent = suggestion;

        if (!this.editModeValue && !this.manuallyEditedTitle) {
            this.titleTarget.value = suggestion;
        }
    }

    addBpm(): void {
        const fragment = this.bpmTemplateTarget.content.cloneNode(true);
        this.bpmListTarget.appendChild(fragment);
        this.updateRemoveButtonState();
        this.updateSuggestion();

        const bpmInputs = this.bpmInputs();
        bpmInputs[bpmInputs.length - 1]?.focus();
    }

    removeLastBpm(): void {
        const bpmInputs = this.bpmInputs();
        if (bpmInputs.length <= 1) {
            return;
        }

        bpmInputs[bpmInputs.length - 1].remove();
        this.updateRemoveButtonState();
        this.updateSuggestion();
    }

    markManuallyEdited(): void {
        this.manuallyEditedTitle = true;
    }

    prepareSubmit(event: SubmitEvent): void {
        if (this.submitConfirmed) {
            this.submitConfirmed = false;
            return;
        }

        if (!this.editModeValue) {
            this.replaceFlagTarget.value = "0";
            return;
        }

        if (!this.haveSourceValuesChanged()) {
            this.replaceFlagTarget.value = "0";
            return;
        }

        event.preventDefault();

        const suggestion = this.buildTitle();
        this.modalCurrentTitleTarget.textContent = this.titleTarget.value.trim() || "Kein Title gesetzt.";
        this.modalSuggestedTitleTarget.textContent = suggestion;
        this.modalTarget.showModal();
    }

    submitWithSuggestedTitle(): void {
        this.titleTarget.value = this.buildTitle();
        this.replaceFlagTarget.value = "1";
        this.submitAfterDecision();
    }

    submitKeepingCurrentTitle(): void {
        this.replaceFlagTarget.value = "0";
        this.submitAfterDecision();
    }

    cancelTitleDecision(): void {
        this.modalTarget.close();
    }

    private submitAfterDecision(): void {
        this.submitConfirmed = true;
        this.modalTarget.close();
        this.element.requestSubmit();
    }

    private haveSourceValuesChanged(): boolean {
        return this.beatNameTarget.value !== this.originalBeatNameValue ||
            !this.areBpmsEqual(this.currentBpms(), this.originalBpms()) ||
            this.normalizeMusicalKey(this.musicalKeyTarget.value) !== this.normalizeMusicalKey(this.originalMusicalKeyValue);
    }

    /**
     * @returns number[]
     */
    private currentBpms(): number[] {
        return this.bpmInputs()
            .map((input) => Number(input.value))
            .filter((value) => Number.isInteger(value) && value > 0);
    }

    /**
     * @returns number[]
     */
    private originalBpms(): number[] {
        try {
            const parsed = JSON.parse(this.originalBpmsValue) as unknown;
            if (!Array.isArray(parsed)) {
                return [];
            }

            return parsed.filter((value): value is number => Number.isInteger(value) && value > 0);
        } catch {
            return [];
        }
    }

    private updateRemoveButtonState(): void {
        this.removeBpmButtonTarget.disabled = this.bpmInputs().length <= 1;
        this.removeBpmButtonTarget.classList.toggle("opacity-50", this.removeBpmButtonTarget.disabled);
    }

    /**
     * @returns HTMLInputElement[]
     */
    private bpmInputs(): HTMLInputElement[] {
        return Array.from(this.bpmListTarget.querySelectorAll<HTMLInputElement>('input[name="bpms[]"]'));
    }

    private buildTitle(): string {
        const beatName = this.normalizeBeatName(this.beatNameTarget.value);
        const bpmSegment = this.normalizeBpms(this.currentBpms());
        const musicalKey = this.normalizeMusicalKey(this.musicalKeyTarget.value);

        return `${this.trackNumberValue}_${beatName}_${bpmSegment}_${musicalKey}`;
    }

    private normalizeBeatName(value: string): string {
        const normalized = value
            .trim()
            .replace(/[^A-Za-z0-9]+/g, "_")
            .replace(/^_+|_+$/g, "");

        return normalized === "" ? "UntitledBeat" : normalized;
    }

    /**
     * @param number[] bpms
     */
    private normalizeBpms(bpms: number[]): string {
        if (bpms.length === 0) {
            return "UnknownBpm";
        }

        return bpms.map((bpm) => `${bpm}BPM`).join("_");
    }

    private normalizeMusicalKey(value: string): string {
        const normalized = value.trim().replace(/\s+/g, "");
        if (normalized === "") {
            return "UnknownKey";
        }

        const options = Array.from(this.musicalKeyTarget.list?.options ?? []);
        for (const option of options) {
            if (option.value.toLowerCase() === normalized.toLowerCase()) {
                return option.value;
            }
        }

        return normalized.replace(/[^A-Za-z0-9#b]+/g, "");
    }

    /**
     * @param number[] left
     * @param number[] right
     */
    private areBpmsEqual(left: number[], right: number[]): boolean {
        if (left.length !== right.length) {
            return false;
        }

        return left.every((value, index) => value === right[index]);
    }
}
