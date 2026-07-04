import { Pill, Info, Clock, CheckCircle2, Volume2, VolumeX } from 'lucide-react';
import { MedicineDetail } from '@/types/prescriptions';
import { cn } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { SpeechLanguage, speakText, stopSpeaking, generateMedicineSpeechText } from '@/lib/medicineVoiceHelper';
import { useState, useEffect } from 'react';

interface MedicineItemProps {
  medicine: MedicineDetail;
  language?: SpeechLanguage;
  isCurrentlySpeaking?: boolean;
}

export const MedicineItem = ({ medicine, language = 'en', isCurrentlySpeaking = false }: MedicineItemProps) => {
  const isPast = medicine.status?.toLowerCase() === 'past';
  const [isSpeakingSelf, setIsSpeakingSelf] = useState(false);

  const isSpeaking = isCurrentlySpeaking || isSpeakingSelf;

  const toggleSpeak = (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();

    if (isSpeakingSelf) {
      stopSpeaking();
      setIsSpeakingSelf(false);
    } else {
      stopSpeaking();
      setIsSpeakingSelf(true);
      
      const text = generateMedicineSpeechText(medicine, language);
      speakText(
        text,
        language,
        undefined, // onStart
        () => setIsSpeakingSelf(false), // onEnd
        () => setIsSpeakingSelf(false) // onError
      );
    }
  };

  useEffect(() => {
    return () => {
      if (isSpeakingSelf) {
        stopSpeaking();
      }
    };
  }, [isSpeakingSelf]);

  return (
    <div className={cn(
      "p-4 sm:p-6 bg-light-gray global-radius-10 shadow-card-sm space-y-4 group transition-all border",
      isSpeaking ? "border-emerald-600/30 bg-emerald-50/10 shadow-md scale-[1.01]" : "border-transparent"
    )}>
      <div className="flex flex-col sm:flex-row justify-between gap-4">
        <div className="flex flex-col gap-2">
          <div className="flex flex-wrap items-center gap-2">
            <h4 className="text-lg sm:text-xl font-bold g-text-dark leading-tight">{medicine.name}</h4>
            <Badge variant="outline" className={cn(
              "rounded-full uppercase font-bold py-0 h-5 border-none text-[10px]",
              isPast ? "bg-surface-container text-red" : "bg-emerald-100 text-emerald-700"
            )}>
              {medicine.status || "Active"}
            </Badge>
            <button
              onClick={toggleSpeak}
              className={cn(
                "p-1.5 rounded-full transition-all cursor-pointer border border-outline-variant/10 shadow-sm flex items-center justify-center outline-none",
                isSpeaking 
                  ? "bg-[#013220] text-white hover:bg-emerald-800 scale-105" 
                  : "bg-white text-gray-500 hover:text-gray-800 hover:bg-gray-50"
              )}
              title={isSpeaking ? "Stop voice guidance" : "Listen to voice guidance"}
              aria-label={isSpeaking ? "Stop voice guidance" : "Listen to voice guidance"}
            >
              {isSpeaking ? (
                <VolumeX className="w-3.5 h-3.5 animate-pulse" />
              ) : (
                <Volume2 className="w-3.5 h-3.5" />
              )}
            </button>
          </div>
          
          <div className="space-y-1">
            <p className="text-sm font-medium g-text-muted flex flex-wrap gap-1 items-center">
              <span>{medicine.dosage}</span>
              <span className="opacity-50">•</span>
              <span className="capitalize">
                {medicine.use_type === 'sos'
                  ? 'SOS (As Needed)'
                  : (medicine.use_type && medicine.use_type !== 'regular'
                      ? medicine.use_type.replace('_', ' ')
                      : (medicine.frequencylabel || medicine.frequency))}
              </span>
              {medicine.meal && (
                <>
                  <span className="opacity-50">•</span>
                  <span className="capitalize">{medicine.meal.replace('_', ' ')}</span>
                </>
              )}
            </p>
            
            <div className="flex flex-wrap items-start gap-1 sm:gap-2">
              <span className="text-[10px] sm:text-xs font-semibold tracking-wider g-text-muted uppercase opacity-70 mt-0.5">Period:</span>
              <span className="text-xs font-semibold g-text-muted flex-1 min-w-[140px]">
                {medicine.start_date} - {medicine.end_date}
              </span>
            </div>
          </div>
        </div>

        <div className="flex items-center justify-between sm:flex-col sm:items-end sm:justify-start pt-3 sm:pt-0 sm:border-t-0 border-outline-variant/10">
          <div className="flex items-center sm:justify-end gap-1.5 g-text-dark font-bold">
            <Info className="w-4 h-4 text-primary opacity-70" />
            <p className="text-sm tracking-widest uppercase">
              {medicine.use_type === 'sos' ? 'SOS' : medicine.times}
            </p>
          </div>
          <p className="text-sm font-medium g-text-muted mt-0.5 sm:mt-1">
            {medicine.type}
          </p>
        </div>
      </div>

      {medicine.use_type === 'sos' && (
        <div className="pt-3 border-t border-outline-variant/10 grid grid-cols-3 gap-3">
          {medicine.take_when && (
            <div className="space-y-0.5">
              <span className="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Take When / Reason</span>
              <p className="text-xs sm:text-sm font-semibold g-text-dark capitalize">{medicine.take_when}</p>
            </div>
          )}
          {medicine.min_gap && (
            <div className="space-y-0.5">
              <span className="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Minimum Gap</span>
              <p className="text-xs sm:text-sm font-semibold g-text-dark capitalize">{medicine.min_gap}</p>
            </div>
          )}
          {medicine.max_doses_per_day && (
            <div className="space-y-0.5">
              <span className="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Max Doses Per Day</span>
              <p className="text-xs sm:text-sm font-semibold g-text-dark capitalize">{medicine.max_doses_per_day}</p>
            </div>
          )}
        </div>
      )}

      {medicine.use_type && medicine.use_type !== 'regular' && medicine.use_type !== 'sos' && (
        <div className="pt-3 border-t border-outline-variant/10">
          <span className="text-[10px] font-bold text-gray-400 uppercase tracking-wider font-semibold">Special Instructions</span>
          <p className="text-xs sm:text-sm font-semibold g-text-dark capitalize">Take as {medicine.use_type.replace('_', ' ')}</p>
        </div>
      )}

      {(medicine.instructions && medicine.instructions.length > 0) && (
        <div className="pt-3 border-t border-outline-variant/10">
          <div className="flex items-start gap-2.5">
            <div className="mt-1 flex-shrink-0">
               <Info className="w-4 h-4 text-success" />
            </div>
            <div className="text-sm font-medium leading-relaxed g-text-muted">
              <span className="mr-1 font-bold g-text-dark">Instruction:</span>
              <span className="break-words">{medicine.instructions.join(', ')}</span>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

