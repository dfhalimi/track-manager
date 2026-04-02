import { Controller } from "@hotwired/stimulus";

export default class extends Controller<HTMLElement> {
    static targets = ["form", "input", "item", "status"];

    declare readonly formTarget: HTMLFormElement;
    declare readonly inputTarget: HTMLInputElement;
    declare readonly itemTargets: HTMLElement[];
    declare readonly statusTarget: HTMLElement;
    declare readonly hasStatusTarget: boolean;

    private draggedItem: HTMLElement | null = null;
    private isSubmitting = false;

    startDrag(event: DragEvent): void {
        const item = event.currentTarget;
        if (!(item instanceof HTMLElement)) {
            return;
        }

        this.draggedItem = item;
        item.classList.add("opacity-60", "shadow-sm");

        const handle = item.querySelector("[data-drag-handle]");
        if (handle instanceof HTMLElement) {
            handle.classList.remove("cursor-pointer");
            handle.classList.add("cursor-grabbing");
        }

        if (event.dataTransfer !== null) {
            event.dataTransfer.effectAllowed = "move";
        }
    }

    dragOver(event: DragEvent): void {
        event.preventDefault();

        if (!(event.target instanceof HTMLElement) || this.draggedItem === null) {
            return;
        }

        const hoveredItem = event.target.closest("[data-checklist-reorder-target='item']");
        if (!(hoveredItem instanceof HTMLElement) || hoveredItem === this.draggedItem) {
            return;
        }

        const hoveredBounds = hoveredItem.getBoundingClientRect();
        const shouldInsertBefore = event.clientY < hoveredBounds.top + (hoveredBounds.height / 2);

        if (shouldInsertBefore) {
            hoveredItem.before(this.draggedItem);

            return;
        }

        hoveredItem.after(this.draggedItem);
    }

    drop(event: DragEvent): void {
        event.preventDefault();
        void this.submitOrder();
    }

    endDrag(): void {
        if (this.draggedItem !== null) {
            this.draggedItem.classList.remove("opacity-60", "shadow-sm");

            const handle = this.draggedItem.querySelector("[data-drag-handle]");
            if (handle instanceof HTMLElement) {
                handle.classList.remove("cursor-grabbing");
                handle.classList.add("cursor-pointer");
            }
        }

        this.draggedItem = null;
    }

    private async submitOrder(): Promise<void> {
        if (this.isSubmitting) {
            return;
        }

        const orderedItemUuids = this.itemTargets
            .map((item) => item.dataset.itemUuid)
            .filter((itemUuid): itemUuid is string => itemUuid !== undefined && itemUuid !== "");

        this.inputTarget.value = JSON.stringify(orderedItemUuids);
        this.isSubmitting = true;
        this.setStatus("Speichere Reihenfolge...", false);

        try {
            const response = await fetch(this.formTarget.action, {
                method: this.formTarget.method,
                body: new FormData(this.formTarget),
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    Accept: "application/json",
                },
            });

            const payload = await response.json() as { message?: string };

            if (!response.ok) {
                throw new Error(payload.message ?? "Die neue Reihenfolge konnte nicht gespeichert werden.");
            }

            this.setStatus(payload.message ?? "Checklisten-Reihenfolge wurde gespeichert.", false);
            window.setTimeout((): void => this.clearStatus(), 2000);
        } catch (error) {
            const message = error instanceof Error
                ? error.message
                : "Die neue Reihenfolge konnte nicht gespeichert werden.";

            this.setStatus(message, true);
        } finally {
            this.isSubmitting = false;
        }
    }

    private setStatus(message: string, isError: boolean): void {
        if (!this.hasStatusTarget) {
            return;
        }

        this.statusTarget.textContent = message;
        this.statusTarget.className = isError
            ? "mt-3 text-sm text-red-700"
            : "mt-3 text-sm text-emerald-700";
    }

    private clearStatus(): void {
        if (!this.hasStatusTarget) {
            return;
        }

        this.statusTarget.textContent = "";
        this.statusTarget.className = "hidden";
    }
}
