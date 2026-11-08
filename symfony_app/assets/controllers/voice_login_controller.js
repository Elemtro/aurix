import { Controller } from '@hotwired/stimulus';

/*
 * Second login step: record the displayed phrase and verify it.
 */
export default class extends Controller {
    static targets = ['dot', 'status', 'button', 'error', 'attempts'];

    static values = {
        verifyUrl: String,
        csrfToken: String,
        homeUrl: String,
        loginUrl: String,
    };

    connect() {
        this.recorder = null;
        this.stream = null;
        this.chunks = [];
    }

    disconnect() {
        this.stopStream();
    }

    async toggle() {
        if (this.recorder && this.recorder.state === 'recording') {
            this.recorder.stop();
            return;
        }

        try {
            this.stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        } catch (error) {
            this.showError('Brak dostępu do mikrofonu.');
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
            this.stopStream();
            this.upload(blob);
        };

        this.recorder.start();
        this.buttonTarget.textContent = 'Zatrzymaj i sprawdź';
        this.dotTarget.className = 'h-2 w-2 rounded-full bg-red-500 animate-pulse';
        this.statusTarget.textContent = 'Nagrywanie…';
    }

    stopStream() {
        if (this.stream) {
            this.stream.getTracks().forEach((track) => track.stop());
            this.stream = null;
        }
    }

    async upload(blob) {
        this.buttonTarget.disabled = true;
        this.statusTarget.textContent = 'Weryfikacja…';
        this.hideError();

        const form = new FormData();
        form.append('audio', blob, this.filename(blob));

        try {
            const response = await fetch(this.verifyUrlValue, {
                method: 'POST',
                headers: { 'X-CSRF-Token': this.csrfTokenValue },
                body: form,
            });
            const data = await response.json();

            if (response.ok && data.accepted) {
                window.location.assign(this.homeUrlValue);
                return;
            }

            if (response.status === 429) {
                window.location.assign(this.loginUrlValue);
                return;
            }

            if (data.attemptsLeft !== undefined) {
                this.attemptsTarget.textContent = String(data.attemptsLeft);
            }

            this.showError(data.message || 'Nie udało się zweryfikować głosu.');
            this.statusTarget.textContent = 'Spróbuj ponownie';
            this.dotTarget.className = 'h-2 w-2 rounded-full bg-neutral-600';
            this.buttonTarget.disabled = false;
            this.buttonTarget.textContent = 'Nowa próba';
            this.buttonTarget.dataset.action = 'voice-login#retry';
        } catch (error) {
            this.showError('Błąd połączenia. Spróbuj ponownie.');
            this.statusTarget.textContent = 'Błąd';
            this.buttonTarget.disabled = false;
            this.buttonTarget.textContent = 'Nowa próba';
            this.buttonTarget.dataset.action = 'voice-login#retry';
        }
    }

    retry() {
        window.location.reload();
    }

    filename(blob) {
        const type = (blob.type || '').split(';')[0];

        if (type.includes('mp4')) {
            return 'sample.m4a';
        }

        if (type.includes('ogg')) {
            return 'sample.ogg';
        }

        if (type.includes('wav')) {
            return 'sample.wav';
        }

        return 'sample.webm';
    }

    showError(message) {
        this.errorTarget.textContent = message;
        this.errorTarget.classList.remove('hidden');
    }

    hideError() {
        this.errorTarget.textContent = '';
        this.errorTarget.classList.add('hidden');
    }
}
