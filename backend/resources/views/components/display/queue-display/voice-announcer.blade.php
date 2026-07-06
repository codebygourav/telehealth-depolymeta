@props([
    'enabled' => false,
    'language' => 'en-US',
    'voiceName' => '',
    'template' => 'Token {token_number}, please proceed to Room {room_number}, {doctor_name}.',
])

<div
    x-data="{
        enabled: @js((bool) $enabled),
        language: @js((string) $language),
        voiceName: @js((string) $voiceName),
        template: @js((string) $template),
        pickVoice() {
            if (!('speechSynthesis' in window)) {
                return null;
            }

            const voices = window.speechSynthesis.getVoices?.() || [];
            if (!voices.length) {
                return null;
            }

            const normalizedLanguage = String(this.language || '').toLowerCase();
            const normalizedName = String(this.voiceName || '').toLowerCase();

            if (normalizedName) {
                const exactNamedVoice = voices.find((voice) => String(voice.name || '').toLowerCase() === normalizedName);
                if (exactNamedVoice) {
                    return exactNamedVoice;
                }

                const partialNamedVoice = voices.find((voice) => String(voice.name || '').toLowerCase().includes(normalizedName));
                if (partialNamedVoice) {
                    return partialNamedVoice;
                }
            }

            const sameLanguage = voices.filter((voice) => String(voice.lang || '').toLowerCase().startsWith(normalizedLanguage.slice(0, 2)));

            const scoreVoice = (voice) => {
                let score = 0;
                if (voice.localService) score += 3;
                if (voice.default) score += 2;
                if (String(voice.lang || '').toLowerCase() === normalizedLanguage) score += 4;
                if (String(voice.lang || '').toLowerCase().startsWith(normalizedLanguage.slice(0, 2))) score += 2;
                const name = String(voice.name || '').toLowerCase();
                if (name.includes('natural')) score += 3;
                if (name.includes('enhanced')) score += 2;
                if (name.includes('premium')) score += 2;
                if (name.includes('google')) score += 1;
                if (name.includes('microsoft')) score += 1;
                return score;
            };

            const pool = sameLanguage.length ? sameLanguage : voices;
            return pool.slice().sort((a, b) => scoreVoice(b) - scoreVoice(a))[0] || null;
        },
        speak(payload) {
            if (!this.enabled || !('speechSynthesis' in window) || !payload) {
                return;
            }

            const message = (this.template || 'Token {token_number}, please proceed to Room {room_number}, Dr. {doctor_name}.')
                .replaceAll('{token_number}', payload.current_token || payload.token_number || payload.token || '')
                .replaceAll('{patient_name}', payload.current_patient || payload.patient_name || '')
                .replaceAll('{doctor_name}', payload.doctor_name || '')
                .replaceAll('{room_number}', payload.room_number || payload.room || '')
                .replaceAll('{time_slot}', payload.current_time_slot || payload.time_slot || '');

            if (!message.trim()) {
                return;
            }

            window.speechSynthesis.cancel();

            const utterance = new SpeechSynthesisUtterance(message);
            utterance.lang = this.language || 'en-US';
            utterance.rate = 0.96;
            utterance.pitch = 1;
            utterance.volume = 1;

            const voice = this.pickVoice();
            if (voice) {
                utterance.voice = voice;
                if (!utterance.lang && voice.lang) {
                    utterance.lang = voice.lang;
                }
            }

            window.speechSynthesis.speak(utterance);
        },
    }"
    x-on:display-voice-announce.window="speak($event.detail)"
    x-cloak
    aria-hidden="true"
    class="hidden"
></div>
