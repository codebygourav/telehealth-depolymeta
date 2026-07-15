export type SpeechLanguage = 'en' | 'hi' | 'pa';

export interface SpeechMedicineInput {
  name?: string | null;
  medicine_name?: string | null;
  dosage?: string | null;
  frequency?: string | null;
  frequencylabel?: string | null;
  times?: string | null;
  frequency_times?: string[] | string | null;
  meal?: string | null;
  meal_timing?: string | null;
  instructions?: string[] | string | null;
  use_type?: string | null;
  take_when?: string | null;
  min_gap?: string | null;
  max_doses_per_day?: string | null;
}

// Convert times string like "07:00, 12:00, 18:00" to spoken language in English
function formatTimesForSpeech(timesStr: string): string {
  if (!timesStr) return '';
  const times = timesStr.split(',').map(t => t.trim());
  
  const formatted = times.map(time => {
    const parts = time.split(':');
    if (parts.length < 2) return time;
    const hour = parseInt(parts[0], 10);
    const minute = parseInt(parts[1], 10);
    if (isNaN(hour)) return time;
    
    let displayHour = hour;
    if (hour > 12) {
      displayHour = hour - 12;
    } else if (hour === 0) {
      displayHour = 12;
    }
    
    const ampm = hour >= 12 ? 'PM' : 'AM';
    return `${displayHour}${minute > 0 ? ':' + (minute < 10 ? '0' + minute : minute) : ''} ${ampm}`;
  });
  
  if (formatted.length <= 1) return formatted[0] || '';
  if (formatted.length === 2) {
    return `${formatted[0]} and ${formatted[1]}`;
  }
  
  const last = formatted.pop();
  return `${formatted.join(', ')}, and ${last}`;
}

// Generate full spoken text for a single medicine in English (which will be auto-translated)
export function generateMedicineSpeechText(input: SpeechMedicineInput, lang?: SpeechLanguage): string {
  const name = input.name || input.medicine_name || 'Medicine';
  const dosage = input.dosage || '';
  const use_type = input.use_type || 'regular';
  const meal = input.meal || input.meal_timing || '';

  let times = '';
  if (input.times) {
    times = input.times;
  } else if (input.frequency_times) {
    if (Array.isArray(input.frequency_times)) {
      times = input.frequency_times.join(', ');
    } else {
      times = input.frequency_times;
    }
  }

  let insts: string[] = [];
  if (input.instructions) {
    if (Array.isArray(input.instructions)) {
      insts = input.instructions;
    } else if (typeof input.instructions === 'string') {
      insts = [input.instructions];
    }
  }

  let text = `Please take ${name}.`;
  if (dosage) {
    text += ` Dosage is ${dosage}.`;
  }
  if (use_type === 'sos') {
    text += ` Take as needed.`;
    if (input.take_when) {
      text += ` Take when ${input.take_when}.`;
    }
    if (input.min_gap) {
      text += ` Minimum gap of ${input.min_gap}.`;
    }
  } else {
    const freq = input.frequencylabel || input.frequency || '';
    if (freq) {
      text += ` Take it ${freq}.`;
    }
    if (times) {
      text += ` Scheduled times are ${formatTimesForSpeech(times)}.`;
    }
  }
  if (meal) {
    text += ` Take it ${meal.replace('_', ' ')}.`;
  }
  if (insts.length > 0) {
    text += ` Instruction: ${insts.join(', ')}.`;
  }
  return text;
}

// Generate intro text for full prescription
export function generatePrescriptionIntroText(doctorName: string | undefined, prescribedAt: string | undefined, lang: SpeechLanguage): string {
  const dr = doctorName || '';
  return dr ? `Here is your prescription by Doctor ${dr}.` : 'Here are your prescribed medicines.';
}

export function gurmukhiToDevanagari(text: string): string {
  const map: Record<string, string> = {
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
}

// Speech Synthesis execution engine with auto-translation
export function speakText(
  text: string, // English source text
  lang: SpeechLanguage,
  onStart?: () => void,
  onEnd?: () => void,
  onError?: (err: any) => void,
  voiceSettings?: {
    voice_name?: string | null;
    speech_rate?: number;
    speech_pitch?: number;
    speech_locale?: string | null;
  }
): SpeechSynthesisUtterance | null {
  if (typeof window === 'undefined' || !('speechSynthesis' in window)) {
    console.warn('Speech synthesis not supported in this browser.');
    return null;
  }

  // Cancel any active speech first
  window.speechSynthesis.cancel();

  // Create temporary utterance so we can set callbacks immediately
  const utterance = new SpeechSynthesisUtterance('');
  if (onStart) utterance.onstart = onStart;
  if (onEnd) utterance.onend = onEnd;
  if (onError) utterance.onerror = onError;

  // Perform async translation
  (async () => {
    let speechText = text;
    let bcpLang = 'en-IN';
    if (lang === 'hi') bcpLang = 'hi-IN';
    if (lang === 'pa') bcpLang = 'pa-IN';

    if (lang !== 'en') {
      try {
        const url = `https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl=${lang}&dt=t&q=${encodeURIComponent(text)}`;
        const res = await fetch(url);
        const json = await res.json();
        if (json && json[0]) {
          speechText = json[0].map((part: any) => part[0] || '').join('');
        }
      } catch (e) {
        console.error('Translation error, falling back to original English text:', e);
      }
    }

    const voices = window.speechSynthesis.getVoices();
    
    // Find voice using doctor preferences or fallback
    let voice;
    if (voiceSettings?.voice_name) {
      voice = voices.find(v => v.name === voiceSettings.voice_name);
    }
    
    if (!voice) {
      const searchLang = bcpLang.toLowerCase();
      voice = voices.find(v => v.lang.toLowerCase() === searchLang);
      if (!voice) {
        voice = voices.find(v => v.lang.toLowerCase().startsWith(searchLang.substring(0, 2)));
      }
    }

    if (!voice && lang === 'pa') {
      voice = voices.find(v => v.lang.toLowerCase() === 'hi-in') || 
              voices.find(v => v.lang.toLowerCase() === 'en-in');
      
      if (voice && voice.lang.toLowerCase().startsWith('hi')) {
        speechText = gurmukhiToDevanagari(speechText);
        bcpLang = 'hi-IN';
      }
    }

    // Set voice properties
    utterance.text = speechText;
    utterance.rate = voiceSettings?.speech_rate ?? 0.92;
    utterance.pitch = voiceSettings?.speech_pitch ?? 1.0;
    utterance.volume = 1.0;
    utterance.lang = bcpLang;

    if (voice) {
      utterance.voice = voice;
      utterance.lang = voice.lang;
    }

    window.speechSynthesis.speak(utterance);
  })().catch(err => {
    console.error('Async speakText error:', err);
    if (onError) onError(err);
  });

  return utterance;
}

// Global voice stop helper
export function stopSpeaking() {
  if (typeof window !== 'undefined' && 'speechSynthesis' in window) {
    window.speechSynthesis.cancel();
  }
}
