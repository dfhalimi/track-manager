import { Controller } from "@hotwired/stimulus";

type SuggestionsResponse = {
    suggestions: string[];
};

export default class extends Controller<HTMLElement> {
    static targets = ["form", "searchInput", "suggestions", "list", "page"];

    static values = {
        indexUrl: String,
        listUrl: String,
        suggestionsUrl: String,
        debounceMs: Number,
    };

    declare readonly formTarget: HTMLFormElement;
    declare readonly searchInputTarget: HTMLInputElement;
    declare readonly suggestionsTarget: HTMLElement;
    declare readonly listTarget: HTMLElement;
    declare readonly pageTarget: HTMLInputElement;

    declare readonly indexUrlValue: string;
    declare readonly listUrlValue: string;
    declare readonly suggestionsUrlValue: string;
    declare readonly debounceMsValue: number;

    private listTimeoutId: number | null = null;
    private suggestionsTimeoutId: number | null = null;
    private blurTimeoutId: number | null = null;
    private listAbortController: AbortController | null = null;
    private suggestionsAbortController: AbortController | null = null;
    private selectedSuggestionIndex = -1;

    disconnect(): void {
        this.clearTimers();
        this.listAbortController?.abort();
        this.suggestionsAbortController?.abort();
    }

    handleSearchInput(): void {
        this.resetPage();
        this.scheduleListFetch();
        this.scheduleSuggestionsFetch();
    }

    handleFilterChange(): void {
        this.resetPage();
        this.fetchList();
        this.fetchSuggestions();
    }

    submitForm(event: SubmitEvent): void {
        event.preventDefault();
        this.resetPage();
        this.fetchList();
        this.fetchSuggestions();
    }

    handleClick(event: MouseEvent): void {
        const target = event.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }

        const pageLink = target.closest<HTMLAnchorElement>("a[data-live-search-page]");
        if (pageLink !== null) {
            event.preventDefault();
            this.pageTarget.value = pageLink.dataset.liveSearchPage ?? "1";
            this.fetchList();
            return;
        }

        const suggestionButton = target.closest<HTMLButtonElement>("button[data-live-search-suggestion]");
        if (suggestionButton !== null) {
            event.preventDefault();
            this.searchInputTarget.value = suggestionButton.dataset.liveSearchSuggestion ?? "";
            this.resetPage();
            this.fetchList();
            this.hideSuggestions();
        }
    }

    handleKeydown(event: KeyboardEvent): void {
        const suggestionButtons = this.suggestionButtons();
        if (suggestionButtons.length === 0) {
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

        if (event.key === "Enter" && this.selectedSuggestionIndex >= 0) {
            event.preventDefault();
            suggestionButtons[this.selectedSuggestionIndex]?.click();
            return;
        }

        if (event.key === "Escape") {
            this.hideSuggestions();
        }
    }

    handleSearchFocus(): void {
        if (this.suggestionButtons().length > 0) {
            this.showSuggestions();
        }
    }

    handleSearchBlur(): void {
        this.blurTimeoutId = window.setTimeout(() => {
            this.hideSuggestions();
        }, 150);
    }

    private scheduleListFetch(): void {
        if (this.listTimeoutId !== null) {
            window.clearTimeout(this.listTimeoutId);
        }

        this.listTimeoutId = window.setTimeout(() => {
            this.fetchList();
        }, this.debounceMs());
    }

    private scheduleSuggestionsFetch(): void {
        if (this.suggestionsTimeoutId !== null) {
            window.clearTimeout(this.suggestionsTimeoutId);
        }

        this.suggestionsTimeoutId = window.setTimeout(() => {
            this.fetchSuggestions();
        }, this.debounceMs());
    }

    private fetchList(): void {
        this.listAbortController?.abort();
        this.listAbortController = new AbortController();

        fetch(this.buildUrl(this.listUrlValue), {
            headers: { "X-Requested-With": "XMLHttpRequest" },
            signal: this.listAbortController.signal,
        })
            .then(async (response) => {
                if (!response.ok) {
                    throw new Error("List request failed.");
                }

                return response.text();
            })
            .then((html) => {
                this.listTarget.innerHTML = html;
                window.history.replaceState({}, "", this.buildUrl(this.indexUrlValue));
            })
            .catch((error: unknown) => {
                if (error instanceof DOMException && error.name === "AbortError") {
                    return;
                }

                throw error;
            });
    }

    private fetchSuggestions(): void {
        const searchQuery = this.searchInputTarget.value.trim();
        if (searchQuery === "") {
            this.hideSuggestions();
            this.suggestionsTarget.innerHTML = "";
            return;
        }

        this.suggestionsAbortController?.abort();
        this.suggestionsAbortController = new AbortController();

        fetch(this.buildUrl(this.suggestionsUrlValue), {
            headers: { Accept: "application/json" },
            signal: this.suggestionsAbortController.signal,
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

    private buildUrl(baseUrl: string): string {
        const queryString = this.buildQueryString();

        return queryString === "" ? baseUrl : `${baseUrl}?${queryString}`;
    }

    private buildQueryString(): string {
        const formData = new FormData(this.formTarget);
        const params = new URLSearchParams();

        formData.forEach((value, key) => {
            if (typeof value !== "string") {
                return;
            }

            if (value.trim() === "") {
                return;
            }

            params.append(key, value);
        });

        return params.toString();
    }

    private renderSuggestions(suggestions: string[]): void {
        this.selectedSuggestionIndex = -1;

        if (suggestions.length === 0) {
            this.suggestionsTarget.innerHTML = "";
            this.hideSuggestions();
            return;
        }

        this.suggestionsTarget.innerHTML = suggestions
            .map(
                (suggestion) => `
                    <button
                        type="button"
                        class="block w-full overflow-hidden px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50"
                        title="${this.escapeHtmlAttribute(suggestion)}"
                        data-live-search-suggestion="${this.escapeHtmlAttribute(suggestion)}"
                    ><span class="block truncate">${this.escapeHtml(suggestion)}</span></button>
                `,
            )
            .join("");

        this.showSuggestions();
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
        return Array.from(
            this.suggestionsTarget.querySelectorAll<HTMLButtonElement>("button[data-live-search-suggestion]"),
        );
    }

    private resetPage(): void {
        this.pageTarget.value = "1";
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

    private clearTimers(): void {
        if (this.listTimeoutId !== null) {
            window.clearTimeout(this.listTimeoutId);
            this.listTimeoutId = null;
        }

        if (this.suggestionsTimeoutId !== null) {
            window.clearTimeout(this.suggestionsTimeoutId);
            this.suggestionsTimeoutId = null;
        }

        if (this.blurTimeoutId !== null) {
            window.clearTimeout(this.blurTimeoutId);
            this.blurTimeoutId = null;
        }
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
