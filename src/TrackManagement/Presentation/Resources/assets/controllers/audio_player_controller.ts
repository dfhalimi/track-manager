import { Controller } from "@hotwired/stimulus";

export default class extends Controller<HTMLElement> {
    static targets = [
        "audio",
        "playButtonLabel",
        "playIcon",
        "pauseIcon",
        "progress",
        "currentTime",
        "duration",
        "volumeButton",
        "volumePanel",
        "volumeSlider",
    ];

    declare readonly audioTarget: HTMLAudioElement;
    declare readonly playButtonLabelTarget: HTMLElement;
    declare readonly playIconTarget: HTMLElement;
    declare readonly pauseIconTarget: HTMLElement;
    declare readonly progressTarget: HTMLInputElement;
    declare readonly currentTimeTarget: HTMLElement;
    declare readonly durationTarget: HTMLElement;
    declare readonly volumeButtonTarget: HTMLButtonElement;
    declare readonly volumePanelTarget: HTMLElement;
    declare readonly volumeSliderTarget: HTMLInputElement;

    private previousVolume = 1;

    private readonly boundLoadedMetadata = (): void => {
        this.syncProgressUi();
    };

    private readonly boundDurationChange = (): void => {
        this.syncProgressUi();
    };

    private readonly boundTimeUpdate = (): void => {
        this.syncProgressUi();
    };

    private readonly boundPlayStateChange = (): void => {
        this.syncPlaybackUi();
    };

    private readonly boundVolumeChange = (): void => {
        this.syncVolumeUi();
    };

    connect(): void {
        this.audioTarget.addEventListener("loadedmetadata", this.boundLoadedMetadata);
        this.audioTarget.addEventListener("durationchange", this.boundDurationChange);
        this.audioTarget.addEventListener("timeupdate", this.boundTimeUpdate);
        this.audioTarget.addEventListener("play", this.boundPlayStateChange);
        this.audioTarget.addEventListener("pause", this.boundPlayStateChange);
        this.audioTarget.addEventListener("ended", this.boundPlayStateChange);
        this.audioTarget.addEventListener("volumechange", this.boundVolumeChange);

        this.previousVolume = this.audioTarget.volume > 0 ? this.audioTarget.volume : 1;
        this.syncPlaybackUi();
        this.syncProgressUi();
        this.syncVolumeUi();
        this.hideVolumeSlider();
        this.audioTarget.load();
    }

    disconnect(): void {
        this.audioTarget.removeEventListener("loadedmetadata", this.boundLoadedMetadata);
        this.audioTarget.removeEventListener("durationchange", this.boundDurationChange);
        this.audioTarget.removeEventListener("timeupdate", this.boundTimeUpdate);
        this.audioTarget.removeEventListener("play", this.boundPlayStateChange);
        this.audioTarget.removeEventListener("pause", this.boundPlayStateChange);
        this.audioTarget.removeEventListener("ended", this.boundPlayStateChange);
        this.audioTarget.removeEventListener("volumechange", this.boundVolumeChange);
    }

    togglePlayback(): void {
        if (this.audioTarget.paused) {
            if (this.audioTarget.readyState === 0) {
                this.audioTarget.load();
            }

            void this.audioTarget.play();

            return;
        }

        this.audioTarget.pause();
    }

    seek(): void {
        if (!Number.isFinite(this.audioTarget.duration) || this.audioTarget.duration <= 0) {
            return;
        }

        this.audioTarget.currentTime = (Number(this.progressTarget.value) / 100) * this.audioTarget.duration;
        this.syncProgressUi();
    }

    toggleMute(): void {
        if (this.audioTarget.muted || this.audioTarget.volume === 0) {
            this.audioTarget.muted = false;
            this.audioTarget.volume = this.previousVolume > 0 ? this.previousVolume : 1;

            return;
        }

        this.previousVolume = this.audioTarget.volume > 0 ? this.audioTarget.volume : this.previousVolume;
        this.audioTarget.muted = true;
    }

    changeVolume(): void {
        const volume = Math.min(1, Math.max(0, Number(this.volumeSliderTarget.value) / 100));

        this.audioTarget.volume = volume;
        this.audioTarget.muted = volume === 0;

        if (volume > 0) {
            this.previousVolume = volume;
        }
    }

    showVolumeSlider(): void {
        this.volumePanelTarget.classList.remove("hidden");
    }

    hideVolumeSlider(): void {
        this.volumePanelTarget.classList.add("hidden");
    }

    hideVolumeSliderIfFocusLeaves(event: FocusEvent): void {
        if (event.relatedTarget instanceof Node && this.element.contains(event.relatedTarget)) {
            return;
        }

        this.hideVolumeSlider();
    }

    private syncPlaybackUi(): void {
        const isPaused = this.audioTarget.paused;

        this.playButtonLabelTarget.textContent = isPaused ? "Play" : "Pause";
        this.playButtonLabelTarget.parentElement?.setAttribute(
            "aria-label",
            isPaused ? "Wiedergabe starten" : "Wiedergabe pausieren",
        );
        this.playIconTarget.classList.toggle("hidden", !isPaused);
        this.pauseIconTarget.classList.toggle("hidden", isPaused);
    }

    private syncProgressUi(): void {
        const duration = this.audioTarget.duration;
        const currentTime = this.audioTarget.currentTime;

        this.currentTimeTarget.textContent = this.formatTime(currentTime);
        this.durationTarget.textContent = this.formatTime(duration);

        if (!Number.isFinite(duration) || duration <= 0) {
            this.progressTarget.value = "0";

            return;
        }

        this.progressTarget.value = String((currentTime / duration) * 100);
    }

    private syncVolumeUi(): void {
        const volume = this.audioTarget.muted ? 0 : this.audioTarget.volume;

        this.volumeSliderTarget.value = String(Math.round(volume * 100));
        this.volumeButtonTarget.classList.toggle("text-slate-400", volume === 0);
        this.volumeButtonTarget.classList.toggle("text-slate-700", volume > 0);
    }

    private formatTime(value: number): string {
        if (!Number.isFinite(value) || value < 0) {
            return "0:00";
        }

        const totalSeconds = Math.floor(value);
        const minutes = Math.floor(totalSeconds / 60);
        const seconds = totalSeconds % 60;

        return `${minutes}:${seconds.toString().padStart(2, "0")}`;
    }
}
