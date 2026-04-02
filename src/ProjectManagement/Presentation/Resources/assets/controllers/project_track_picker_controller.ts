import { Controller } from "@hotwired/stimulus";

type TrackSuggestion = {
    trackUuid: string;
    label: string;
};

type SuggestionsResponse = {
    suggestions: TrackSuggestion[];
};

export default class extends Controller<HTMLElement> {
    static targets = ["searchInput", "hiddenInput", "suggestions", "submitButton"];

    static values = {
        suggestionsUrl: String,
        debounceMs: Number,
    };

    declare readonly searchInputTarget: HTMLInputElement;
    declare readonly hiddenInputTarget: HTMLInputElement;
    declare readonly suggestionsTarget: HTMLElement;
    declare readonly submitButtonTarget: HTMLButtonElement;

    declare readonly suggestionsUrlValue: string;
    declare readonly debounceMsValue: number;

    private selectedSuggestionIndex = -1;
    private fetchTimeoutId: number | null = null;
    private blurTimeoutId: number | null = null;
    private abortController: AbortController | null = null;
    private selectedLabel = "";

    connect(): void {
        this.updateSubmitButton();
    }

    disconnect(): void {
        if (this.fetchTimeoutId !== null) {
            window.clearTimeout(this.fetchTimeoutId);
        }

        if (this.blurTimeoutId !== null) {
            window.clearTimeout(this.blurTimeoutId);
        }

        this.abortController?.abort();
    }

    handleInput(): void {
        if (this.searchInputTarget.value !== this.selectedLabel) {
            this.hiddenInputTarget.value = "";
        }

        this.searchInputTarget.setCustomValidity("");
        this.updateSubmitButton();
        this.scheduleSuggestionsFetch();
    }

    handleFocus(): void {
        if (this.suggestionButtons().length > 0) {
            this.showSuggestions();
        }
    }

    handleBlur(): void {
        this.blurTimeoutId = window.setTimeout(() => {
            this.hideSuggestions();
        }, 150);
    }

    handleMouseDown(event: MouseEvent): void {
        event.preventDefault();
    }

    handleClick(event: MouseEvent): void {
        const target = event.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }

        const suggestionButton = target.closest<HTMLButtonElement>("button[data-track-uuid]");
        if (suggestionButton === null) {
            return;
        }

        this.selectSuggestion(suggestionButton.dataset.trackUuid ?? "", suggestionButton.dataset.trackLabel ?? "");
    }

    handleKeydown(event: KeyboardEvent): void {
        const suggestionButtons = this.suggestionButtons();
        if (suggestionButtons.length === 0) {
            if (event.key === "Enter" && this.hiddenInputTarget.value === "") {
                event.preventDefault();
                this.reportSelectionRequired();
            }

            return;
        }

        if (event.key === "ArrowDown") {
            event.preventDefault();
            this.selectedSuggestionIndex = Math.min(this.selectedSuggestionIndex + 1, suggestionButtons.length - 1);
            this.updateSelectedSuggestion();

            return;
        }

        if (event.key === "ArrowUp") {
            event.preventDefault();
            this.selectedSuggestionIndex = Math.max(this.selectedSuggestionIndex - 1, 0);
            this.updateSelectedSuggestion();

            return;
        }

        if (event.key === "Enter") {
            event.preventDefault();

            if (this.selectedSuggestionIndex >= 0) {
                suggestionButtons[this.selectedSuggestionIndex]?.click();

                return;
            }

            if (suggestionButtons.length === 1) {
                suggestionButtons[0]?.click();

                return;
            }

            this.reportSelectionRequired();

            return;
        }

        if (event.key === "Escape") {
            this.hideSuggestions();
        }
    }

    submitForm(event: SubmitEvent): void {
        if (this.hiddenInputTarget.value !== "") {
            return;
        }

        event.preventDefault();
        this.reportSelectionRequired();
    }

    private scheduleSuggestionsFetch(): void {
        if (this.fetchTimeoutId !== null) {
            window.clearTimeout(this.fetchTimeoutId);
        }

        this.fetchTimeoutId = window.setTimeout(() => {
            this.fetchSuggestions();
        }, this.debounceMs());
    }

    private fetchSuggestions(): void {
        const query = this.searchInputTarget.value.trim();
        if (query === "") {
            this.suggestionsTarget.innerHTML = "";
            this.hideSuggestions();

            return;
        }

        this.abortController?.abort();
        this.abortController = new AbortController();

        fetch(`${this.suggestionsUrlValue}?${new URLSearchParams({ q: query }).toString()}`, {
            headers: { Accept: "application/json" },
            signal: this.abortController.signal,
        })
            .then(async (response) => {
                if (!response.ok) {
                    throw new Error("Suggestions request failed.");
                }

                return response.json() as Promise<SuggestionsResponse>;
            })
            .then((payload) => {
                this.renderSuggestions(payload.suggestions);
            })
            .catch((error: unknown) => {
                if (error instanceof DOMException && error.name === "AbortError") {
                    return;
                }

                throw error;
            });
    }

    private renderSuggestions(suggestions: TrackSuggestion[]): void {
        this.selectedSuggestionIndex = -1;

        if (suggestions.length === 0) {
            this.suggestionsTarget.innerHTML = `
                <div class="px-3 py-2 text-sm text-slate-500">Keine passenden Tracks gefunden.</div>
            `;
            this.showSuggestions();

            return;
        }

        this.suggestionsTarget.innerHTML = suggestions
            .map(
                (suggestion) => `
                    <button
                        type="button"
                        class="block w-full overflow-hidden px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50"
                        title="${this.escapeHtmlAttribute(suggestion.label)}"
                        data-track-uuid="${this.escapeHtmlAttribute(suggestion.trackUuid)}"
                        data-track-label="${this.escapeHtmlAttribute(suggestion.label)}"
                    ><span class="block truncate">${this.escapeHtml(suggestion.label)}</span></button>
                `,
            )
            .join("");

        this.showSuggestions();
    }

    private selectSuggestion(trackUuid: string, label: string): void {
        this.hiddenInputTarget.value = trackUuid;
        this.searchInputTarget.value = label;
        this.selectedLabel = label;
        this.searchInputTarget.setCustomValidity("");
        this.updateSubmitButton();
        this.hideSuggestions();
    }

    private updateSelectedSuggestion(): void {
        this.suggestionButtons().forEach((button, index) => {
            button.classList.toggle("bg-slate-100", index === this.selectedSuggestionIndex);
        });
    }

    /**
     * @returns HTMLButtonElement[]
     */
    private suggestionButtons(): HTMLButtonElement[] {
        return Array.from(this.suggestionsTarget.querySelectorAll<HTMLButtonElement>("button[data-track-uuid]"));
    }

    private reportSelectionRequired(): void {
        this.searchInputTarget.setCustomValidity("Bitte wähle einen Track aus den Vorschlägen aus.");
        this.searchInputTarget.reportValidity();
    }

    private updateSubmitButton(): void {
        const isDisabled = this.hiddenInputTarget.value === "";

        this.submitButtonTarget.disabled = isDisabled;
        this.submitButtonTarget.classList.toggle("cursor-not-allowed", isDisabled);
        this.submitButtonTarget.classList.toggle("bg-slate-300", isDisabled);
        this.submitButtonTarget.classList.toggle("text-slate-500", isDisabled);
        this.submitButtonTarget.classList.toggle("bg-slate-900", !isDisabled);
        this.submitButtonTarget.classList.toggle("text-white", !isDisabled);
    }

    private showSuggestions(): void {
        if (this.blurTimeoutId !== null) {
            window.clearTimeout(this.blurTimeoutId);
            this.blurTimeoutId = null;
        }

        this.suggestionsTarget.classList.remove("hidden");
    }

    private hideSuggestions(): void {
        this.selectedSuggestionIndex = -1;
        this.suggestionsTarget.classList.add("hidden");
    }

    private debounceMs(): number {
        return this.debounceMsValue > 0 ? this.debounceMsValue : 200;
    }

    private escapeHtml(value: string): string {
        return value
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#39;");
    }

    private escapeHtmlAttribute(value: string): string {
        return this.escapeHtml(value);
    }
}
