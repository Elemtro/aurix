import { Controller } from '@hotwired/stimulus';

/*
 * Handles microphone recording on the home page.
 *
 * The recorded audio stays in the browser for now; sending it to the
 * verification engine will be added together with the API endpoints.
 */
export default class extends Controller {
    static targets = ['button', 'status', 'dot', 'playback'];

    connect() {
        this.recorder = null;
        this.stream = null;
        this.chunks = [];
    }

    async toggle() {
        if (this.recorder && this.recorder.state === 'recording') {
            this.recorder.stop();
            return;
        }

        try {
            this.stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        } catch (error) {
            this.statusTarget.textContent = 'Brak dostępu do mikrofonu';
            return;
        }

        this.chunks = [];
        this.recorder = new MediaRecorder(this.stream);

        this.recorder.ondataavailable = (event) => {
            if (event.data.size > 0) {
                this.chunks.push(event.data);
            }
        };

        this.recorder.onstop = () => {
            const blob = new Blob(this.chunks, { type: this.recorder.mimeType });

            this.playbackTarget.src = URL.createObjectURL(blob);
            this.playbackTarget.hidden = false;

            this.stream.getTracks().forEach((track) => track.stop());

            this.buttonTarget.textContent = 'Nagraj ponownie';
            this.dotTarget.classList.remove('bg-red-500', 'animate-pulse');
            this.dotTarget.classList.add('bg-emerald-400');
            this.statusTarget.textContent = 'Nagranie gotowe (lokalnie)';
        };

        this.recorder.start();
        this.buttonTarget.textContent = 'Zatrzymaj nagrywanie';
        this.dotTarget.classList.remove('bg-neutral-600', 'bg-emerald-400');
        this.dotTarget.classList.add('bg-red-500', 'animate-pulse');
        this.statusTarget.textContent = 'Nagrywanie…';
    }

    disconnect() {
        if (this.stream) {
            this.stream.getTracks().forEach((track) => track.stop());
        }
    }
}
