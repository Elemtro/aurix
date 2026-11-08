import { Controller } from '@hotwired/stimulus';

/*
 * Three-step enrollment: record each phrase, send it to the API, advance.
 */
export default class extends Controller {
    static targets = ['phrase', 'stepNumber', 'stepLabel', 'progress', 'dot', 'status', 'button', 'error'];

    static values = {
        enrollUrl: String,
        homeUrl: String,
        csrfToken: String,
        phrases: Object,
        existing: Object,
        nextIndex: Number,
    };

    connect() {
        this.recorder = null;
        this.stream = null;
        this.chunks = [];
        this.currentIndex = this.nextIndexValue || 1;
        this.completed = Math.max(0, ...Object.keys(this.existingValue || {}).map(Number));

        this.render();
    }

    disconnect() {
        this.stopStream();
    }

    get phrase() {
        return this.phrasesValue[this.currentIndex] || '';
    }

    render() {
        if (this.currentIndex > 3) {
            window.location.assign(this.homeUrlValue);
            return;
        }

        this.phraseTarget.textContent = this.phrase;
        this.stepNumberTarget.textContent = String(this.currentIndex);
        this.stepLabelTarget.textContent = ['Fraza referencyjna', 'Druga fraza', 'Trzecia fraza'][this.currentIndex - 1] || 'Fraza';

        this.progressTargets.forEach((element, index) => {
            const step = index + 1;
            const done = step <= this.completed;
            const active = step === this.currentIndex;

            element.className = 'h-1 rounded-full transition ' + (
                done ? 'bg-[#4fb3a6]' : active ? 'bg-[#2f6f66]' : 'bg-[#2a2828]'
            );
        });

        this.statusTarget.textContent = 'Gotowy do nagrania';
        this.dotTarget.className = 'h-2 w-2 rounded-full bg-neutral-600';
        this.buttonTarget.textContent = 'Nagraj frazę';
        this.buttonTarget.disabled = false;
        this.hideError();
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
        this.buttonTarget.textContent = 'Zatrzymaj nagrywanie';
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
        this.statusTarget.textContent = 'Sprawdzanie frazy…';
        this.hideError();

        const form = new FormData();
        form.append('phraseIndex', String(this.currentIndex));
        form.append('audio', blob, this.filename(blob));

        try {
            const response = await fetch(this.enrollUrlValue, {
                method: 'POST',
                headers: { 'X-CSRF-Token': this.csrfTokenValue },
                body: form,
            });
            const data = await response.json();

            if (!response.ok) {
                this.showError(data.message || 'Nie udało się zapisać frazy.');
                this.statusTarget.textContent = 'Spróbuj ponownie';
                this.dotTarget.className = 'h-2 w-2 rounded-full bg-neutral-600';
                this.buttonTarget.disabled = false;
                this.buttonTarget.textContent = 'Nagraj ponownie';
                return;
            }

            this.completed = Math.max(this.completed, data.phraseIndex || this.currentIndex);

            if (data.completed) {
                window.location.assign(this.homeUrlValue);
                return;
            }

            this.currentIndex = data.nextIndex || (this.currentIndex + 1);
            this.render();
        } catch (error) {
            this.showError('Błąd połączenia. Spróbuj ponownie.');
            this.statusTarget.textContent = 'Błąd';
            this.buttonTarget.disabled = false;
            this.buttonTarget.textContent = 'Nagraj ponownie';
        }
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
