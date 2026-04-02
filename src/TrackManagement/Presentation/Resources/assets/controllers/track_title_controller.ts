import { Controller } from "@hotwired/stimulus";

export default class extends Controller<HTMLFormElement> {
    static targets = [
        "beatName",
        "title",
        "replaceFlag",
        "suggestionLabel",
        "bpmList",
        "bpmTemplate",
        "removeBpmButton",
        "musicalKeyList",
        "musicalKeyTemplate",
        "removeMusicalKeyButton",
        "modal",
        "modalCurrentTitle",
        "modalSuggestedTitle",
    ];

    static values = {
        trackNumber: Number,
        editMode: Boolean,
        originalBeatName: String,
        originalBpms: String,
        originalMusicalKeys: String,
    };

    declare readonly beatNameTarget: HTMLInputElement;
    declare readonly titleTarget: HTMLInputElement;
    declare readonly replaceFlagTarget: HTMLInputElement;
    declare readonly suggestionLabelTarget: HTMLElement;
    declare readonly bpmListTarget: HTMLElement;
    declare readonly bpmTemplateTarget: HTMLTemplateElement;
    declare readonly removeBpmButtonTarget: HTMLButtonElement;
    declare readonly musicalKeyListTarget: HTMLElement;
    declare readonly musicalKeyTemplateTarget: HTMLTemplateElement;
    declare readonly removeMusicalKeyButtonTarget: HTMLButtonElement;
    declare readonly modalTarget: HTMLDialogElement;
    declare readonly modalCurrentTitleTarget: HTMLElement;
    declare readonly modalSuggestedTitleTarget: HTMLElement;
    declare readonly trackNumberValue: number;
    declare readonly editModeValue: boolean;
    declare readonly originalBeatNameValue: string;
    declare readonly originalBpmsValue: string;
    declare readonly originalMusicalKeysValue: string;

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

    addMusicalKey(): void {
        const fragment = this.musicalKeyTemplateTarget.content.cloneNode(true);
        this.musicalKeyListTarget.appendChild(fragment);
        this.updateRemoveButtonState();
        this.updateSuggestion();

        const musicalKeyInputs = this.musicalKeyInputs();
        musicalKeyInputs[musicalKeyInputs.length - 1]?.focus();
    }

    removeLastMusicalKey(): void {
        const musicalKeyInputs = this.musicalKeyInputs();
        if (musicalKeyInputs.length <= 1) {
            return;
        }

        musicalKeyInputs[musicalKeyInputs.length - 1].remove();
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
        return (
            this.beatNameTarget.value !== this.originalBeatNameValue ||
            !this.areBpmsEqual(this.currentBpms(), this.originalBpms()) ||
            !this.areStringsEqual(this.currentMusicalKeys(), this.originalMusicalKeys())
        );
    }

    /**
     * @returns number[]
     */
    private currentBpms(): number[] {
        return this.bpmInputs()
            .map((input) => Number(input.value))
            .filter((value) => Number.isFinite(value) && value > 0);
    }

    /**
     * @returns string[]
     */
    private currentMusicalKeys(): string[] {
        return this.musicalKeyInputs()
            .map((input) => this.normalizeMusicalKey(input.value))
            .filter((value) => value !== "");
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

            return parsed.filter(
                (value): value is number => typeof value === "number" && Number.isFinite(value) && value > 0,
            );
        } catch {
            return [];
        }
    }

    /**
     * @returns string[]
     */
    private originalMusicalKeys(): string[] {
        try {
            const parsed = JSON.parse(this.originalMusicalKeysValue) as unknown;
            if (!Array.isArray(parsed)) {
                return [];
            }

            return parsed
                .filter((value): value is string => typeof value === "string")
                .map((value) => this.normalizeMusicalKey(value))
                .filter((value) => value !== "");
        } catch {
            return [];
        }
    }

    private updateRemoveButtonState(): void {
        this.removeBpmButtonTarget.disabled = this.bpmInputs().length <= 1;
        this.removeBpmButtonTarget.classList.toggle("opacity-50", this.removeBpmButtonTarget.disabled);

        this.removeMusicalKeyButtonTarget.disabled = this.musicalKeyInputs().length <= 1;
        this.removeMusicalKeyButtonTarget.classList.toggle("opacity-50", this.removeMusicalKeyButtonTarget.disabled);
    }

    /**
     * @returns HTMLInputElement[]
     */
    private bpmInputs(): HTMLInputElement[] {
        return Array.from(this.bpmListTarget.querySelectorAll<HTMLInputElement>('input[name="bpms[]"]'));
    }

    /**
     * @returns HTMLInputElement[]
     */
    private musicalKeyInputs(): HTMLInputElement[] {
        return Array.from(this.musicalKeyListTarget.querySelectorAll<HTMLInputElement>('input[name="musical_keys[]"]'));
    }

    private buildTitle(): string {
        const beatName = this.normalizeBeatName(this.beatNameTarget.value);
        const bpmSegment = this.normalizeBpms(this.currentBpms());
        const musicalKeysSegment = this.normalizeMusicalKeys(this.currentMusicalKeys());

        return `${this.trackNumberValue}_${beatName}_${bpmSegment}_${musicalKeysSegment}`;
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

        return bpms.map((bpm) => `${this.formatBpmForTitle(bpm)}BPM`).join("__");
    }

    /**
     * @param string[] musicalKeys
     */
    private normalizeMusicalKeys(musicalKeys: string[]): string {
        if (musicalKeys.length === 0) {
            return "UnknownKey";
        }

        return musicalKeys.join("_");
    }

    private normalizeMusicalKey(value: string): string {
        const normalized = value.trim().replace(/\s+/g, "");
        if (normalized === "") {
            return "";
        }

        const optionMatch = this.findMatchingOption(normalized);
        if (optionMatch !== null) {
            return optionMatch;
        }

        return normalized.replace(/[^A-Za-z0-9#b]+/g, "");
    }

    private findMatchingOption(value: string): string | null {
        const input = this.musicalKeyInputs()[0];
        const options = Array.from(input?.list?.options ?? []);

        for (const option of options) {
            if (option.value.toLowerCase() === value.toLowerCase()) {
                return option.value;
            }
        }

        return null;
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

    private formatBpmForTitle(bpm: number): string {
        return this.formatBpm(bpm).replace(".", "_");
    }

    private formatBpm(bpm: number): string {
        return bpm.toFixed(3).replace(/\.?0+$/, "");
    }

    /**
     * @param string[] left
     * @param string[] right
     */
    private areStringsEqual(left: string[], right: string[]): boolean {
        if (left.length !== right.length) {
            return false;
        }

        return left.every((value, index) => value === right[index]);
    }
}
