import { Video, Building2, CheckCircle2 } from 'lucide-react';

interface ConsultationModeCardProps {
  type: 'video' | 'in_person';
}

const ConsultationModeCard = ({ type }: ConsultationModeCardProps) => {
  const isVideo = type === 'video';
  
  return (
    <div className="space-y-4">
      <h3 className="text-lg font-bold font-headline text-primary px-2">
        Consultation Mode
      </h3>
      <div className="grid grid-cols-1 gap-4">
        <div className={`p-6 border-2 rounded-3xl flex items-center gap-4 ${
          isVideo 
            ? 'bg-emerald-50 border-emerald-200' 
            : 'bg-surface-container-low border-outline-variant/10'
        }`}>
          <div className={`w-12 h-12 rounded-2xl flex items-center justify-center shadow-sm ${
            isVideo ? 'bg-white text-emerald-600' : 'bg-surface-container text-on-surface-variant'
          }`}>
            {isVideo ? <Video className="w-6 h-6" /> : <Building2 className="w-6 h-6" />}
          </div>
          <div>
            <p className={`font-bold ${isVideo ? 'text-emerald-900' : 'text-on-surface'}`}>
              {isVideo ? 'Video Call' : 'In-Clinic Visit'}
            </p>
            <p className={`text-xs font-medium ${isVideo ? 'text-emerald-700' : 'text-on-surface-variant'}`}>
              {isVideo ? 'Join from anywhere' : 'Visit at clinic location'}
            </p>
          </div>
          <CheckCircle2 className={`w-6 h-6 ml-auto ${isVideo ? 'text-emerald-600' : 'text-on-surface-variant/30'}`} />
        </div>
      </div>
    </div>
  );
};

export default ConsultationModeCard;