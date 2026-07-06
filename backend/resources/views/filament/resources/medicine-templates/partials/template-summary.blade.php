@php
    /** @var \App\Models\MedicineTemplate $record */
    $compact = $compact ?? false;
    $record->loadMissing(['doctor.user', 'department', 'items.medicine.type']);
    $items = $record->items;

    $scope = $record->scope_type ?? ($record->doctor_id ? \App\Models\MedicineTemplate::SCOPE_DOCTOR : \App\Models\MedicineTemplate::SCOPE_GLOBAL);
    $scopeLabel = match ($scope) {
        \App\Models\MedicineTemplate::SCOPE_DOCTOR => 'Doctor Specific',
        \App\Models\MedicineTemplate::SCOPE_DEPARTMENT => 'Department Specific',
        default => 'Global - All Doctors',
    };
    $scopeDetail = match ($scope) {
        \App\Models\MedicineTemplate::SCOPE_DOCTOR => trim(($record->doctor?->first_name ?? '') . ' ' . ($record->doctor?->last_name ?? '')) ?: ($record->doctor?->name ?? 'Selected doctor'),
        \App\Models\MedicineTemplate::SCOPE_DEPARTMENT => $record->department?->name ?? 'Selected department',
        default => 'Available for every doctor',
    };
    $scopeClass = match ($scope) {
        \App\Models\MedicineTemplate::SCOPE_DOCTOR => 'is-blue',
        \App\Models\MedicineTemplate::SCOPE_DEPARTMENT => 'is-amber',
        default => 'is-green',
    };

    $totalDosesPerDay = $items->sum(fn($item) => (int) ($item->doses_per_day ?: 1));
    $timingCount = $items->sum(fn($item) => count($item->frequency_times ?? []));
    $longestDuration = $items->max('duration_value');

    $formatTime = function (?string $time): string {
        if (! $time) {
            return '-';
        }

        try {
            return \Carbon\Carbon::parse($time)->format('h:i A');
        } catch (\Throwable) {
            return $time;
        }
    };

    $speechSettings = \App\Support\PrescriptionSpeech::settings();
@endphp

<style>
    .medicine-admin-shell{display:grid;gap:1rem}
    .medicine-page-head{display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;border:1px solid #e5e7eb;background:linear-gradient(135deg,#f8fafc,#eff6ff);border-radius:16px;padding:1.25rem}
    .medicine-kicker{margin:0 0 .35rem;color:#2563eb;font-size:.75rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase}
    .medicine-page-head h1{margin:0;color:#111827;font-size:1.55rem;font-weight:800;line-height:1.2}
    .medicine-page-head p{margin:.45rem 0 0;color:#64748b;max-width:760px}
    .medicine-head-actions,.medicine-chip-row{display:flex;flex-wrap:wrap;gap:.5rem}
    .medicine-pill,.medicine-chip{display:inline-flex;align-items:center;border-radius:999px;padding:.35rem .7rem;font-size:.75rem;font-weight:700;white-space:nowrap}
    .medicine-pill.is-green,.medicine-chip.is-green{background:#dcfce7;color:#166534}
    .medicine-pill.is-blue,.medicine-chip.is-blue{background:#dbeafe;color:#1d4ed8}
    .medicine-pill.is-amber,.medicine-chip.is-amber{background:#fef3c7;color:#92400e}
    .medicine-pill.is-gray,.medicine-chip.is-gray{background:#f1f5f9;color:#475569}
    .medicine-pill.is-rose,.medicine-chip.is-rose{background:#ffe4e6;color:#be123c}
    .medicine-stats-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.75rem}
    .medicine-stats-grid.is-compact{grid-template-columns:repeat(3,minmax(0,1fr))}
    .medicine-stat{border:1px solid #e5e7eb;background:#fff;border-radius:14px;padding:1rem}
    .medicine-stat span{display:block;color:#64748b;font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em}
    .medicine-stat strong{display:block;margin-top:.35rem;color:#111827;font-size:1.15rem;font-weight:800}
    .medicine-stat small{display:block;margin-top:.15rem;color:#64748b}
    .medicine-section,.medicine-admin-note,.medicine-edit-panel{border:1px solid #e5e7eb;background:#fff;border-radius:16px;padding:1.1rem}
    .medicine-section-head,.medicine-edit-head{display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;margin-bottom:1rem}
    .medicine-section h2,.medicine-admin-note h2,.medicine-edit-head h2{margin:0;color:#111827;font-size:1.05rem;font-weight:800}
    .medicine-section p,.medicine-admin-note p,.medicine-edit-head p{margin:.25rem 0 0;color:#64748b;font-size:.9rem}
    .medicine-template-card{display:grid;grid-template-columns:minmax(0,1fr) 280px;gap:1rem;border:1px solid #e5e7eb;background:#fff;border-radius:16px;padding:1rem}
    .medicine-card-title{display:flex;justify-content:space-between;gap:1rem;align-items:flex-start}
    .medicine-card-title h2{margin:0;color:#111827;font-size:1.15rem;font-weight:800}
    .medicine-card-title p{margin:.25rem 0 0;color:#64748b}
    .medicine-template-side{border-left:1px solid #e5e7eb;padding-left:1rem}
    .medicine-template-side dl{display:grid;gap:.75rem;margin:0}
    .medicine-template-side div{display:flex;justify-content:space-between;gap:.75rem}
    .medicine-template-side dt{color:#64748b;font-size:.8rem}
    .medicine-template-side dd{margin:0;color:#111827;font-weight:800;text-align:right}
    .medicine-medicine-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.85rem}
    .medicine-card{border:1px solid #e5e7eb;border-radius:14px;background:#fff;padding:1rem;box-shadow:0 1px 2px rgba(15,23,42,.04)}
    .medicine-card-head{display:flex;justify-content:space-between;gap:.75rem;align-items:flex-start}
    .medicine-card h3{margin:0;color:#111827;font-size:1rem;font-weight:800}
    .medicine-card .type{margin:.15rem 0 0;color:#64748b;font-size:.78rem}
    .medicine-meta-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem;margin-top:.9rem}
    .medicine-meta{border-radius:10px;background:#f8fafc;padding:.65rem}
    .medicine-meta span{display:block;color:#64748b;font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em}
    .medicine-meta strong{display:block;margin-top:.2rem;color:#111827;font-size:.86rem}
    .medicine-timing-row{display:flex;flex-wrap:wrap;gap:.4rem;margin-top:.55rem}
    .medicine-time{border-radius:999px;background:#eef2ff;color:#3730a3;padding:.25rem .55rem;font-size:.74rem;font-weight:800}
    .medicine-instructions{margin-top:.8rem;border-radius:10px;background:#eff6ff;color:#1e3a8a;padding:.75rem;font-size:.86rem}
    .medicine-form-surface{border-radius:14px;background:#f8fafc;padding:1rem}
    .medicine-empty{border:1px dashed #cbd5e1;border-radius:14px;padding:2rem;text-align:center;color:#64748b}
    @media (max-width: 1024px){.medicine-template-card{grid-template-columns:1fr}.medicine-template-side{border-left:0;border-top:1px solid #e5e7eb;padding-left:0;padding-top:1rem}.medicine-medicine-grid{grid-template-columns:1fr}.medicine-stats-grid,.medicine-stats-grid.is-compact{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media (max-width: 640px){.medicine-page-head,.medicine-card-title,.medicine-section-head{flex-direction:column}.medicine-stats-grid,.medicine-stats-grid.is-compact{grid-template-columns:1fr}.medicine-meta-grid{grid-template-columns:1fr}}
</style>

<div class="medicine-admin-shell"
    x-data="{
        speechEnabled: @js((bool) ($speechSettings['enabled'] ?? true)),
        language: @js((string) ($speechSettings['default_language'] ?? 'en')),
        speechTemplate: @js((string) ($speechSettings['template'] ?? \App\Support\PrescriptionSpeech::defaultTemplate())),
        placeholderTokens: @js($speechSettings['placeholders'] ?? []),
        translationCache: {},
        isPlayingAll: false,
        currentPlayingIndex: null,
        isSpeakingSelf: {},
        items: @js($items),
        supportedLanguages: [
            { key: 'en', label: 'English' },
            { key: 'hi', label: 'Hindi' },
            { key: 'pa', label: 'Punjabi' },
        ],

        init() {
            if (!('speechSynthesis' in window)) {
                return;
            }

            if (typeof window.speechSynthesis.getVoices === 'function') {
                window.speechSynthesis.getVoices();
            }

            if (typeof window.speechSynthesis.addEventListener === 'function') {
                window.speechSynthesis.addEventListener('voiceschanged', () => {
                    window.speechSynthesis.getVoices();
                });
            }
        },

        canSpeak() {
            return this.speechEnabled && ('speechSynthesis' in window);
        },

        normalizeText(value) {
            if (value === null || value === undefined) {
                return '';
            }

            return String(value).replace(/\s+/g, ' ').trim();
        },

        normalizeCompiledText(value) {
            return this.normalizeText(
                String(value || '')
                    .replace(/\s+([,.;:!?।])/g, '$1')
                    .replace(/([.?!।])(?=[^\s])/g, '$1 ')
            );
        },

        getLocale(lang) {
            return {
                en: 'en-IN',
                hi: 'hi-IN',
                pa: 'pa-IN',
            }[lang] || 'en-IN';
        },

        renderTemplate(template, data) {
            const raw = String(template || '').replace(/\{([a-z0-9_]+)\}/gi, (_, key) => {
                return this.normalizeText(data[key] ?? '');
            });

            return this.normalizeCompiledText(raw);
        },

        templateForLanguage() {
            return this.normalizeText(this.speechTemplate);
        },

        formatTimeForSpeech(time, lang) {
            const normalized = this.normalizeText(time);
            if (!normalized) {
                return '';
            }

            const parts = normalized.split(':');
            if (parts.length < 2) {
                return normalized;
            }

            const hour = Number.parseInt(parts[0], 10);
            const minute = Number.parseInt(parts[1], 10);

            if (Number.isNaN(hour) || Number.isNaN(minute)) {
                return normalized;
            }

            const date = new Date();
            date.setHours(hour, minute, 0, 0);

            return new Intl.DateTimeFormat(this.getLocale(lang), {
                hour: 'numeric',
                minute: '2-digit',
            }).format(date);
        },

        formatTimesForSpeech(times, lang) {
            const normalized = Array.isArray(times)
                ? times
                : String(times || '')
                    .split(',')
                    .map((time) => time.trim())
                    .filter(Boolean);

            const formatted = normalized
                .map((time) => this.formatTimeForSpeech(time, lang))
                .filter(Boolean);

            if (!formatted.length) {
                return '';
            }

            if (typeof Intl.ListFormat === 'function') {
                return new Intl.ListFormat(this.getLocale(lang), {
                    style: 'long',
                    type: 'conjunction',
                }).format(formatted);
            }

            if (formatted.length === 1) {
                return formatted[0];
            }

            const last = formatted.pop();
            return formatted.join(', ') + ' and ' + last;
        },

        mealTimingLabel(value) {
            const labels = {
                before_meal: 'before meal',
                after_meal: 'after meal',
                with_meal: 'with meal',
            };

            return labels[value] || this.normalizeText(String(value || '').replace(/_/g, ' '));
        },

        durationLabel(item) {
            const medicine = item || {};
            const value = Number.parseInt(medicine.duration_value ?? '', 10);
            if (!value) {
                return '';
            }

            const type = this.normalizeText(medicine.duration_type || 'days');
            const units = {
                days: ['day', 'days'],
                weeks: ['week', 'weeks'],
                months: ['month', 'months'],
            };
            const typeUnits = units[type];

            if (!typeUnits) {
                return value + ' ' + type;
            }

            return value + ' ' + (value === 1 ? typeUnits[0] : typeUnits[1]);
        },

        frequencyLabel(item) {
            const medicine = item || {};
            if (this.normalizeText(medicine.use_type) === 'sos') {
                return 'as needed';
            }

            const doses = Number.parseInt(medicine.doses_per_day ?? '', 10);
            if (doses === 1) {
                return 'once a day';
            }
            if (doses === 2) {
                return 'twice a day';
            }
            if (doses === 3) {
                return 'three times a day';
            }
            if (doses > 3) {
                return doses + ' times a day';
            }

            const code = this.normalizeText(medicine.frequency || '').toUpperCase();
            if (code === 'OD') {
                return 'once a day';
            }
            if (code === 'BD') {
                return 'twice a day';
            }
            if (code === 'TDS') {
                return 'three times a day';
            }

            return this.normalizeText(medicine.frequency || '');
        },

        sentence(prefix, value, suffix = '.') {
            const normalizedValue = this.normalizeText(value);
            if (!normalizedValue) {
                return '';
            }

            return this.normalizeCompiledText(prefix + normalizedValue + suffix);
        },

        buildSpeechData(item, index, lang = this.language) {
            const medicine = item || {};
            const useType = this.normalizeText(medicine.use_type || 'regular');
            const dosage = this.normalizeText(medicine.dosage);
            const instructions = Array.isArray(medicine.instructions)
                ? medicine.instructions.map((instruction) => this.normalizeText(instruction)).filter(Boolean).join(', ')
                : this.normalizeText(medicine.instructions);
            const timingList = useType === 'sos' ? '' : this.formatTimesForSpeech(medicine.frequency_times || [], lang);
            const mealTimingLabel = this.mealTimingLabel(medicine.meal_timing);
            const durationLabel = this.durationLabel(medicine);
            const takeWhen = this.normalizeText(medicine.take_when);
            const minGap = this.normalizeText(medicine.min_gap);
            const maxDosesPerDay = this.normalizeText(medicine.max_doses_per_day);
            const frequencyLabel = this.frequencyLabel(medicine);

            return {
                item_number: String(index + 1),
                medicine_name: this.normalizeText(medicine.medicine_name),
                medicine_type: this.normalizeText(medicine.medicine_type),
                dosage,
                use_type_label: useType === 'sos' ? 'as needed' : 'scheduled daily',
                frequency_label: frequencyLabel,
                timing_list: timingList,
                meal_timing_label: mealTimingLabel,
                duration_label: durationLabel,
                instructions,
                take_when: takeWhen,
                min_gap: minGap,
                max_doses_per_day: maxDosesPerDay,
                dosage_sentence: this.sentence('Dosage: ', dosage),
                schedule_sentence: useType === 'sos'
                    ? 'Take only when needed.'
                    : this.sentence('Take ', frequencyLabel),
                timing_sentence: this.sentence('Scheduled times: ', timingList),
                meal_timing_sentence: this.sentence('Take ', mealTimingLabel),
                duration_sentence: this.sentence('Continue for ', durationLabel),
                instructions_sentence: this.sentence('Instructions: ', instructions),
                reason_sentence: this.sentence('Use for ', takeWhen),
                min_gap_sentence: this.sentence('Keep at least ', minGap, ' between doses.'),
                max_doses_sentence: this.sentence('Maximum ', maxDosesPerDay, ' in 24 hours.'),
            };
        },

        generateMedicineSpeechText(item, index, lang = this.language) {
            const template = this.templateForLanguage();
            return this.renderTemplate(template, this.buildSpeechData(item, index, lang));
        },

        gurmukhiToDevanagari(text) {
            const map = {
                'ਕ': 'क', 'ਖ': 'ख', 'ਗ': 'ग', 'ਘ': 'घ', 'ਙ': 'ङ',
                'ਚ': 'च', 'ਛ': 'छ', 'ਜ': 'ज', 'ਝ': 'झ', 'ਞ': 'ञ',
                'ਟ': 'ट', 'ਠ': 'ठ', 'ਡ': 'ड', 'ਢ': 'ढ', 'ਣ': 'ण',
                'ਤ': 'त', 'ਥ': 'थ', 'ਦ': 'द', 'ਧ': 'ध', 'ਨ': 'न',
                'ਪ': 'प', 'ਫ': 'फ', 'ਬ': 'ब', 'ਭ': 'भ', 'ਮ': 'म',
                'ਯ': 'य', 'ਰ': 'र', 'ਲ': 'ल', 'ਵ': 'व', 'ੜ': 'ੜ',
                'ਸ': 'स', 'ਹ': 'ह',
                'ਸ਼': 'श', 'ਖ਼': 'ख़', 'ਗ਼': 'ग़', 'ਜ਼': 'ज़', 'ਫ਼': 'फ़', 'ਲ਼': 'ळ',
                'ਅ': 'अ', 'ਆ': 'आ', 'ਇ': 'इ', 'ਈ': 'ई', 'ਉ': 'उ', 'ਊ': 'ऊ',
                'ਏ': 'ए', 'ਐ': 'ऐ', 'ਓ': 'ओ', 'ਔ': 'औ',
                'ਾ': 'ा', 'ਿ': 'ि', 'ੀ': 'ी', 'ੁ': 'ु', 'ੂ': 'ू',
                'ੇ': 'े', 'ੈ': 'ै', 'ੋ': 'ो', 'ੌ': 'ौ',
                'ਂ': 'ं', 'ੰ': 'ं', 'ੱ': '्',
                '੍ਹ': '्ह', '੍ਰ': '्र', '੍ਵ': '्व',
            };

            let result = text;
            result = result.replace(/ਖ਼/g, 'ख़')
                           .replace(/ਗ਼/g, 'ग़')
                           .replace(/ਜ਼/g, 'ज़')
                           .replace(/ਫ਼/g, 'फ़')
                           .replace(/ਲ਼/g, 'ळ')
                           .replace(/ਸ਼/g, 'श');

            return result.split('').map(char => map[char] || char).join('');
        },

        pickVoice(lang) {
            if (!('speechSynthesis' in window)) {
                return null;
            }

            const voices = typeof window.speechSynthesis.getVoices === 'function'
                ? window.speechSynthesis.getVoices()
                : [];
            if (!voices.length) {
                return null;
            }

            const locale = this.getLocale(lang).toLowerCase();
            const languagePrefix = locale.slice(0, 2);
            const scoreVoice = (voice) => {
                const voiceLocale = String(voice.lang || '').toLowerCase();
                const voiceName = String(voice.name || '').toLowerCase();
                let score = 0;

                if (voice.localService) score += 3;
                if (voice.default) score += 2;
                if (voiceLocale === locale) score += 4;
                if (voiceLocale.startsWith(languagePrefix)) score += 2;
                if (voiceName.includes('natural')) score += 3;
                if (voiceName.includes('enhanced')) score += 2;
                if (voiceName.includes('premium')) score += 2;
                if (voiceName.includes('google')) score += 1;
                if (voiceName.includes('microsoft')) score += 1;

                return score;
            };

            let pool = voices.filter((voice) => String(voice.lang || '').toLowerCase().startsWith(languagePrefix));

            if (lang === 'pa') {
                const punjabiVoices = voices.filter((voice) => String(voice.lang || '').toLowerCase().startsWith('pa'));
                if (punjabiVoices.length) {
                    pool = punjabiVoices;
                } else {
                    pool = voices.filter((voice) => ['hi', 'en'].includes(String(voice.lang || '').toLowerCase().slice(0, 2)));
                }
            }

            if (!pool.length) {
                pool = voices;
            }

            return pool.slice().sort((a, b) => scoreVoice(b) - scoreVoice(a))[0] || null;
        },

        shouldTranslate(text, lang) {
            return lang !== 'en' && Boolean(this.normalizeText(text));
        },

        async translateText(text, lang) {
            const normalizedText = this.normalizeCompiledText(text);
            if (!normalizedText || !this.shouldTranslate(normalizedText, lang)) {
                return normalizedText;
            }

            const cacheKey = lang + '::' + normalizedText;
            if (this.translationCache[cacheKey]) {
                return this.translationCache[cacheKey];
            }

            try {
                const url = 'https://translate.googleapis.com/translate_a/single?client=gtx&sl=auto&tl=' + encodeURIComponent(lang) + '&dt=t&q=' + encodeURIComponent(normalizedText);
                const response = await fetch(url);
                const payload = await response.json();
                const translated = Array.isArray(payload && payload[0])
                    ? payload[0].map((part) => part[0] || '').join('')
                    : normalizedText;

                this.translationCache[cacheKey] = this.normalizeCompiledText(translated);
                return this.translationCache[cacheKey];
            } catch (error) {
                console.error('Prescription speech translation failed:', error);
                return normalizedText;
            }
        },

        async speakText(text, lang, onEnd) {
            if (!this.canSpeak()) {
                if (typeof onEnd === 'function') {
                    onEnd();
                }
                return;
            }

            window.speechSynthesis.cancel();

            let speechText = await this.translateText(text, lang);
            if (!speechText) {
                if (typeof onEnd === 'function') {
                    onEnd();
                }
                return;
            }

            let bcpLang = this.getLocale(lang);
            const voice = this.pickVoice(lang);

            if (lang === 'pa' && voice && String(voice.lang || '').toLowerCase().startsWith('hi')) {
                speechText = this.gurmukhiToDevanagari(speechText);
                bcpLang = 'hi-IN';
            }

            const utterance = new SpeechSynthesisUtterance(speechText);
            utterance.rate = 0.94;
            utterance.pitch = 1;
            utterance.volume = 1;
            utterance.lang = bcpLang;

            if (voice) {
                utterance.voice = voice;
                if (voice.lang) {
                    utterance.lang = voice.lang;
                }
            }

            utterance.onend = () => {
                if (typeof onEnd === 'function') {
                    onEnd();
                }
            };
            utterance.onerror = () => {
                if (typeof onEnd === 'function') {
                    onEnd();
                }
            };

            window.speechSynthesis.speak(utterance);
        },

        playAll() {
            if (this.isPlayingAll) {
                this.stopAll();
                return;
            }

            if (!this.canSpeak() || !this.items || !this.items.length) {
                return;
            }

            this.isPlayingAll = true;
            this.speakNext(0);
        },

        speakNext(index) {
            if (index >= this.items.length) {
                this.isPlayingAll = false;
                this.currentPlayingIndex = null;
                return;
            }

            this.currentPlayingIndex = index;
            const text = this.generateMedicineSpeechText(this.items[index], index);
            this.speakText(text, this.language, () => {
                if (this.isPlayingAll) {
                    this.speakNext(index + 1);
                }
            });
        },

        toggleSpeakItem(index) {
            if (!this.canSpeak()) {
                return;
            }

            const wasSpeaking = Boolean(this.isSpeakingSelf[index]);
            this.stopAll();

            if (wasSpeaking) {
                return;
            }

            this.isSpeakingSelf[index] = true;
            this.isSpeakingSelf = { ...this.isSpeakingSelf };

            const text = this.generateMedicineSpeechText(this.items[index], index);
            this.speakText(text, this.language, () => {
                this.isSpeakingSelf[index] = false;
                this.isSpeakingSelf = { ...this.isSpeakingSelf };
            });
        },

        stopAll() {
            if ('speechSynthesis' in window) {
                window.speechSynthesis.cancel();
            }
            this.isPlayingAll = false;
            this.currentPlayingIndex = null;
            this.isSpeakingSelf = {};
        }
    }"
    @unload.window="stopAll()"
    class="medicine-admin-shell">
    <div class="medicine-page-head">
        <div>
            <p class="medicine-kicker">Medicine / Prescription Template</p>
            <h1>{{ $record->name }}</h1>
            <p>{{ $record->description ?: 'Reusable prescription template with medicines, auto-generated daily timings, duration, and patient instructions.' }}</p>
        </div>

        <div class="medicine-head-actions">
            <span class="medicine-pill {{ $record->is_active ? 'is-green' : 'is-gray' }}">{{ $record->is_active ? 'Active' : 'Inactive' }}</span>
            <span class="medicine-pill {{ $scopeClass }}">{{ $scopeLabel }}</span>
            <span class="medicine-pill is-gray">{{ $items->count() }} medicines</span>
        </div>
    </div>

    <div class="medicine-stats-grid {{ $compact ? 'is-compact' : '' }}">
        <div class="medicine-stat"><span>Scope</span><strong>{{ $scopeLabel }}</strong><small>{{ $scopeDetail }}</small></div>
        <div class="medicine-stat"><span>Medicines</span><strong>{{ $items->count() }}</strong><small>template items</small></div>
        <div class="medicine-stat"><span>Total Doses</span><strong>{{ $totalDosesPerDay }}</strong><small>per day across all medicines</small></div>
        @unless($compact)
            <div class="medicine-stat"><span>Auto Timings</span><strong>{{ $timingCount }}</strong><small>saved timing slots</small></div>
        @endunless
        <div class="medicine-stat"><span>Longest Duration</span><strong>{{ $longestDuration ?: '-' }}</strong><small>{{ $longestDuration ? 'configured value' : 'no end date' }}</small></div>
    </div>

    @unless($compact)
        <div class="medicine-template-card">
            <div>
                <div class="medicine-card-title">
                    <div>
                        <h2>Template Setup</h2>
                        <p>{{ $scopeDetail }} · Last updated {{ $record->updated_at?->format('d M Y, h:i A') ?: '-' }}</p>
                    </div>
                    <span class="medicine-pill {{ $scopeClass }}">{{ $scopeLabel }}</span>
                </div>

                <div class="medicine-chip-row" style="margin-top:.85rem">
                    <span class="medicine-chip is-blue">Auto Timing Preview</span>
                    <span class="medicine-chip is-green">PDF refresh on assign</span>
                    <span class="medicine-chip is-gray">Patient view unchanged</span>
                    <span class="medicine-chip is-amber">Doctor can still add custom medicine</span>
                </div>
            </div>

            <aside class="medicine-template-side">
                <dl>
                    <div><dt>Status</dt><dd>{{ $record->is_active ? 'Active' : 'Inactive' }}</dd></div>
                    <div><dt>Scope</dt><dd>{{ $scopeLabel }}</dd></div>
                    <div><dt>Target</dt><dd>{{ $scopeDetail }}</dd></div>
                    <div><dt>Created</dt><dd>{{ $record->created_at?->format('d M Y') ?: '-' }}</dd></div>
                </dl>
            </aside>
        </div>
    @endunless

    <div class="medicine-section">
        <div class="medicine-section-head">
            <div>
                <h2>Template Medicines</h2>
                <p>Readable prescription preview including calculated daily timings.</p>
            </div>
            <span class="medicine-pill is-gray">{{ $items->count() }} item(s)</span>
        </div>

        <!-- Voice Announcement Test Panel in Admin -->
        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 0.75rem; padding: 0.65rem 0.95rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 1rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                <span style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 0.05em; display: flex; align-items: center; gap: 0.25rem;">
                    <svg style="width: 14px; height: 14px; color: #3b82f6;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5c-.347 2.287-1.567 4.52-3.238 6.412m-.346-6.412a17.982 17.982 0 00-2.22 3.864m1.96-1.83l-1.96-1.83" /></svg>
                    Voice Language
                </span>
                <div style="display: inline-flex; background: #fff; padding: 8px; border-radius: 8px; border: 1px solid #e2e8f0;gap:15px;">
                    <template x-for="lang in supportedLanguages" :key="lang.key">
                        <button
                            type="button"
                            @click="language = lang.key; stopAll();"
                            :class="language === lang.key ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'"
                            style="padding: 0.2rem 0.55rem; font-size: 0.72rem; font-weight: 700; border-radius: 6px; cursor: pointer; transition: all 0.2s; border: none; outline: none; background: transparent; color: inherit;"
                            x-text="lang.label"
                            :style="language === lang.key ? 'background-color: #3b82f6; color: white;padding:5px 15px;border-radius:6px;' : ''"
                        ></button>
                    </template>
                </div>
            </div>

            <button
                type="button"
                @click="playAll()"
                style="display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.35rem 0.85rem; font-size: 0.72rem; font-weight: 800; border-radius: 8px; cursor: pointer; transition: all 0.2s; border: none; outline: none; box-shadow: 0 1px 2px rgba(0,0,0,0.05); color: white;"
                :disabled="!canSpeak()"
                :style="!canSpeak() ? 'background-color: #94a3b8; padding:5px 15px; border-radius:6px; cursor:not-allowed;' : (isPlayingAll ? 'background-color: #ef4444;padding:5px 15px;border-radius:6px;' : 'background-color: #059669;padding:5px 15px;border-radius:6px;')"
            >
                <template x-if="isPlayingAll">
                    <span style="display: flex; align-items: center; gap: 0.25rem;color:#fff;">
                        <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                        Stop Preview
                    </span>
                </template>
                <template x-if="!isPlayingAll">
                    <span style="display: flex; align-items: center; gap: 0.25rem;color:#fff;">
                        <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" /></svg>
                        Listen to Prescription
                    </span>
                </template>
            </button>
        </div>

        <div style="margin: -0.15rem 0 1rem; padding: 0.85rem 0.95rem; border-radius: 12px; background: #fff; border: 1px dashed #cbd5e1;">
            <p style="margin: 0; font-size: 0.82rem; color: #475569;">
                Global speech template source:
                <strong>Medicine → Prescription Voice</strong>.
                Browser TTS is
                <strong x-text="speechEnabled ? 'enabled' : 'disabled'"></strong>.
            </p>
            <p style="margin: 0.45rem 0 0; font-size: 0.78rem; color: #64748b;">
                Available placeholders:
            </p>
            <div style="display:flex; flex-wrap:wrap; gap:0.45rem; margin-top:0.55rem;">
                <template x-for="token in placeholderTokens" :key="token">
                    <span style="display:inline-flex; align-items:center; border-radius:999px; background:#eff6ff; color:#1d4ed8; padding:0.22rem 0.55rem; font-size:0.72rem; font-weight:700;" x-text="token"></span>
                </template>
            </div>
        </div>

        <div class="medicine-medicine-grid">
            @forelse($items as $index => $item)
                <div class="medicine-card" :style="(isPlayingAll && currentPlayingIndex === {{ $index }}) || isSpeakingSelf[{{ $index }}] ? 'border-color: #10b981; background-color: #f0fdf4;' : ''" style="transition: all 0.2s;">
                    <div class="medicine-card-head">
                        <div style="display: flex; align-items: center; gap: 0.5rem; justify-content: space-between; width: 100%;">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <h3>{{ $loop->iteration }}. {{ $item->medicine_name }}</h3>
                                <button
                                    type="button"
                                    @click="toggleSpeakItem({{ $index }})"
                                    :disabled="!canSpeak()"
                                    :class="(isPlayingAll && currentPlayingIndex === {{ $index }}) || isSpeakingSelf[{{ $index }}] ? 'bg-blue-600 text-white scale-105' : 'bg-gray-100 text-gray-500 hover:text-gray-800 hover:bg-gray-200'"
                                    style="padding: 0.2rem; border-radius: 9999px; transition: all 0.2s; cursor: pointer; border: none; display: inline-flex; align-items: center; justify-content: center; outline: none;"
                                    :style="!canSpeak() ? 'background-color: #e2e8f0; color: #94a3b8; cursor:not-allowed;' : (((isPlayingAll && currentPlayingIndex === {{ $index }}) || isSpeakingSelf[{{ $index }}]) ? 'background-color: #3b82f6; color: white;' : 'background-color: #f1f5f9; color: #64748b;')"
                                    title="Listen to medicine voice guidance"
                                >
                                    <template x-if="(isPlayingAll && currentPlayingIndex === {{ $index }}) || isSpeakingSelf[{{ $index }}]">
                                        <svg style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                    </template>
                                    <template x-if="!((isPlayingAll && currentPlayingIndex === {{ $index }}) || isSpeakingSelf[{{ $index }}])">
                                        <svg style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" /></svg>
                                    </template>
                                </button>
                            </div>
                            <span class="medicine-pill is-blue">{{ $item->doses_per_day ?: 1 }}x / day</span>
                        </div>
                    </div>

                    <div class="medicine-meta-grid">
                        <div class="medicine-meta"><span>Dosage</span><strong>{{ $item->dosage ?: '-' }}</strong></div>
                        <div class="medicine-meta"><span>Meal</span><strong>{{ str($item->meal_timing ?: '-')->replace('_', ' ')->title() }}</strong></div>
                        <div class="medicine-meta"><span>First Dose</span><strong>{{ $formatTime($item->first_dose_time) }}</strong></div>
                        <div class="medicine-meta"><span>Gap</span><strong>{{ $item->dose_interval_hours ?: '-' }} hour(s)</strong></div>
                        <div class="medicine-meta"><span>Duration</span><strong>{{ $item->duration_value ? $item->duration_value . ' ' . $item->duration_type : 'No end date' }}</strong></div>
                        <div class="medicine-meta"><span>Frequency</span><strong>{{ $item->frequency ?: '-' }}</strong></div>
                    </div>

                    <div class="medicine-timing-row">
                        @forelse(($item->frequency_times ?? []) as $time)
                            <span class="medicine-time">{{ $formatTime($time) }}</span>
                        @empty
                            <span class="medicine-time">No timing set</span>
                        @endforelse
                    </div>

                    @if($item->instructions)
                        <div class="medicine-instructions">{{ $item->instructions }}</div>
                    @endif
                </div>
            @empty
                <div class="medicine-empty">No medicines added to this template.</div>
            @endforelse
        </div>
    </div>
</div>
