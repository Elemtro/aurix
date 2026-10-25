import { Controller } from '@hotwired/stimulus';

/*
 * Drives the verification demo:
 * fetch a server-generated phrase, record the user, upload the sample
 * and render the result returned by the back end.
 */
export default class extends Controller {
    static targets = [
        'phrase',
        'timer',
        'dot',
        'status',
        'button',
        'playback',
        'result',
        'verdict',
        'similarity',
        'pad',
        'phraseMatch',
        'compared',
        'reason',
    ];

    static values = {
        challengeUrl: String,
        verifyUrl: String,
        csrfToken: String,
    };

    connect() {
        this.recorder = null;
        this.stream = null;
        this.chunks = [];
        this.challengeId = null;
        this.currentPhrase = '';
        this.countdown = null;

        this.loadChallenge();
    }

    disconnect() {
        this.stopStream();

        if (this.countdown) {
            clearInterval(this.countdown);
        }
    }

    async refresh() {
        this.stopStream();
        await this.loadChallenge();
    }

    async loadChallenge(keepResult = false) {
        if (!keepResult) {
            this.resetResult();
        }

        this.buttonTarget.disabled = true;
        this.buttonTarget.textContent = 'Nagraj próbkę';
        this.phraseTarget.textContent = '\u2014';
        this.dotTarget.className = 'h-2 w-2 rounded-full bg-neutral-600';
        this.statusTarget.textContent = 'Pobieranie frazy\u2026';

        try {
            const response = await fetch(this.challengeUrlValue, {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': this.csrfTokenValue,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Nie udało się pobrać frazy');
            }

            this.challengeId = data.id;
            this.currentPhrase = data.phrase;
            this.phraseTarget.textContent = data.phrase;
            this.startCountdown(new Date(data.expiresAt));

            this.buttonTarget.disabled = false;
            this.statusTarget.textContent = 'Gotowy do nagrania';
        } catch (error) {
            this.statusTarget.textContent = error.message || 'Nie udało się pobrać frazy';
        }
    }

    startCountdown(expiresAt) {
        if (this.countdown) {
            clearInterval(this.countdown);
        }

        const tick = () => {
            const left = Math.max(0, Math.ceil((expiresAt.getTime() - Date.now()) / 1000));
            this.timerTarget.textContent = String(left);

            if (left === 0) {
                clearInterval(this.countdown);
                this.countdown = null;
                this.buttonTarget.disabled = true;
                this.statusTarget.textContent = 'Fraza wygasła — pobierz nową';
            }
        };

        tick();
        this.countdown = setInterval(tick, 1000);
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
        this.statusTarget.textContent = 'Analiza…';
        this.buttonTarget.disabled = true;

        const form = new FormData();
        form.append('audio', blob, 'sample.webm');
        form.append('challengeId', this.challengeId);

        try {
            const response = await fetch(this.verifyUrlValue, {
                method: 'POST',
                headers: { 'X-CSRF-Token': this.csrfTokenValue },
                body: form,
            });
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Błąd weryfikacji');
            }

            this.render(data);
            await this.loadChallenge(true);
            this.statusTarget.textContent = 'Gotowe — możesz spróbować ponownie';
        } catch (error) {
            this.statusTarget.textContent = error.message || 'Błąd weryfikacji';
            this.buttonTarget.disabled = false;
        }
    }

    render(data) {
        this.resultTarget.classList.remove('hidden');

        this.verdictTarget.textContent = data.accepted ? 'Weryfikacja udana' : 'Weryfikacja odrzucona';
        this.verdictTarget.className = 'text-sm font-semibold ' + (data.accepted ? 'text-emerald-400' : 'text-red-400');

        this.similarityTarget.textContent = Number(data.similarity).toFixed(2);
        this.padTarget.textContent = Number(data.padScore).toFixed(2);
        this.phraseMatchTarget.textContent = Number(data.phraseMatch).toFixed(2);
        this.comparedTarget.textContent = this.currentPhrase || '\u2014';

        this.reasonTarget.textContent = data.reason
            || (data.stub ? 'Wynik ze stubu — silnik Python nie jest jeszcze podłączony.' : '');
    }

    resetResult() {
        this.resultTarget.classList.add('hidden');
        this.verdictTarget.textContent = '';
        this.verdictTarget.className = 'text-sm font-semibold';
        this.similarityTarget.textContent = '\u2014';
        this.padTarget.textContent = '\u2014';
        this.phraseMatchTarget.textContent = '\u2014';
        this.comparedTarget.textContent = '\u2014';
        this.reasonTarget.textContent = '';
    }
}
