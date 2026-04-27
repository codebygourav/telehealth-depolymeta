import { Building2, Monitor } from 'lucide-react';

interface AppointmentTypeSelectorProps {
    value: 'in_person' | 'video' | null;
    onChange: (type: 'in_person' | 'video') => void;
    inPersonAvailable?: boolean;
    videoAvailable?: boolean;
}

const AppointmentTypeSelector = ({
    value,
    onChange,
    inPersonAvailable = true,
    videoAvailable = true
}: AppointmentTypeSelectorProps) => {

    const hasNoAppointments = !inPersonAvailable && !videoAvailable;

    if (hasNoAppointments) {
        return (
            <div className="space-y-6">
                <h3 className="text-xl font-bold font-headline text-primary">Appointment type</h3>
                <div className="p-8 rounded-2xl border border-dashed border-outline-variant/30 bg-surface-container-low/30 text-center">
                    <p className="text-on-surface-variant text-sm">No appointment types available</p>
                </div>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <h3 className="text-xl font-bold font-headline text-primary">Appointment type</h3>
            <div className="grid grid-cols-2 gap-4">

                <button
                    onClick={() => inPersonAvailable && onChange('in_person')}
                    disabled={!inPersonAvailable}
                    className={`p-6 rounded-2xl border-2 transition-all flex flex-col items-center text-center gap-3 ${value === 'in_person'
                        ? 'border-primary bg-emerald-50/30'
                        : 'border-outline-variant/20 hover:border-primary/50'
                        } ${!inPersonAvailable && 'opacity-50 cursor-not-allowed'}`}
                >
                    <Building2 className={`w-8 h-8 ${value === 'in_person' ? 'text-primary' : 'text-on-surface-variant'}`} />
                    <span className="text-xs font-bold text-primary leading-tight">
                        Book In-Clinic Appointment
                    </span>
                </button>

                <button
                    onClick={() => videoAvailable && onChange('video')}
                    disabled={!videoAvailable}
                    className={`p-6 rounded-2xl border-2 transition-all flex flex-col items-center text-center gap-3 ${value === 'video'
                        ? 'border-primary bg-emerald-50/30'
                        : 'border-outline-variant/20 hover:border-primary/50'
                        } ${!videoAvailable && 'opacity-50 cursor-not-allowed'}`}
                >
                    <Monitor className={`w-8 h-8 ${value === 'video' ? 'text-primary' : 'text-on-surface-variant'}`} />
                    <span className="text-xs font-bold text-primary leading-tight">
                        Online Video Appointment
                    </span>
                </button>
            </div>
        </div>
    );
};

export default AppointmentTypeSelector;