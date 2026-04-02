import { Application } from "@hotwired/stimulus";
import AudioPlayerController from "../../src/TrackManagement/Presentation/Resources/assets/controllers/audio_player_controller.ts";

function buildPlayerMarkup(): string {
    return `
        <div data-controller="audio-player">
            <audio data-audio-player-target="audio"></audio>
            <button type="button" data-action="audio-player#togglePlayback">
                <span data-audio-player-target="playButtonLabel">Play</span>
                <span data-audio-player-target="playIcon"></span>
                <span class="hidden" data-audio-player-target="pauseIcon"></span>
            </button>
            <input
                type="range"
                min="0"
                max="100"
                value="0"
                data-audio-player-target="progress"
                data-action="input->audio-player#seek change->audio-player#seek"
            >
            <span data-audio-player-target="currentTime">0:00</span>
            <span data-audio-player-target="duration">0:00</span>
            <div
                data-action="mouseenter->audio-player#showVolumeSlider mouseleave->audio-player#hideVolumeSlider focusin->audio-player#showVolumeSlider focusout->audio-player#hideVolumeSliderIfFocusLeaves"
            >
                <button
                    type="button"
                    data-audio-player-target="volumeButton"
                    data-action="audio-player#toggleMute"
                ></button>
                <div class="hidden" data-audio-player-target="volumePanel">
                    <input
                        type="range"
                        min="0"
                        max="100"
                        value="100"
                        data-audio-player-target="volumeSlider"
                        data-action="input->audio-player#changeVolume change->audio-player#changeVolume"
                    >
                </div>
            </div>
        </div>
    `;
}

async function mountController(): Promise<{
    app: Application;
    element: HTMLElement;
    audio: HTMLAudioElement;
}> {
    document.body.innerHTML = buildPlayerMarkup();

    const element = document.querySelector<HTMLElement>('[data-controller="audio-player"]');
    if (element === null) {
        throw new Error("Audio player element not found.");
    }

    const audio = element.querySelector("audio");
    if (audio === null) {
        throw new Error("Audio element not found.");
    }

    let paused = true;
    Object.defineProperty(audio, "paused", {
        configurable: true,
        get: () => paused,
    });
    Object.defineProperty(audio, "duration", {
        configurable: true,
        value: 120,
        writable: true,
    });
    Object.defineProperty(audio, "currentTime", {
        configurable: true,
        value: 0,
        writable: true,
    });

    audio.play = vi.fn().mockImplementation(async () => {
        paused = false;
        audio.dispatchEvent(new Event("play"));
    });
    audio.pause = vi.fn().mockImplementation(() => {
        paused = true;
        audio.dispatchEvent(new Event("pause"));
    });

    const app = Application.start();
    app.register("audio-player", AudioPlayerController);

    await Promise.resolve();

    return { app, element, audio };
}

describe("audio_player_controller", () => {
    it("shows the volume slider on hover and updates volume", async () => {
        const { app, element, audio } = await mountController();

        const volumeWrapper = element.querySelector<HTMLElement>(
            '[data-audio-player-target="volumePanel"]',
        )?.parentElement;
        const volumePanel = element.querySelector<HTMLElement>('[data-audio-player-target="volumePanel"]');
        const volumeSlider = element.querySelector<HTMLInputElement>('[data-audio-player-target="volumeSlider"]');

        if (volumeWrapper == null || volumePanel === null || volumeSlider === null) {
            throw new Error("Volume controls not found.");
        }

        volumeWrapper.dispatchEvent(new Event("mouseenter", { bubbles: true }));
        expect(volumePanel.classList.contains("hidden")).toBe(false);

        volumeSlider.value = "35";
        volumeSlider.dispatchEvent(new Event("input", { bubbles: true }));

        expect(audio.volume).toBeCloseTo(0.35);
        expect(audio.muted).toBe(false);

        app.stop();
    });

    it("toggles playback state and button label", async () => {
        const { app, element, audio } = await mountController();

        const playButton = element.querySelector<HTMLButtonElement>('[data-action="audio-player#togglePlayback"]');
        const playButtonLabel = element.querySelector<HTMLElement>('[data-audio-player-target="playButtonLabel"]');

        if (playButton === null || playButtonLabel === null) {
            throw new Error("Playback controls not found.");
        }

        playButton.click();
        expect(audio.play).toHaveBeenCalled();
        expect(playButtonLabel.textContent).toBe("Pause");

        playButton.click();
        expect(audio.pause).toHaveBeenCalled();
        expect(playButtonLabel.textContent).toBe("Play");

        app.stop();
    });
});
