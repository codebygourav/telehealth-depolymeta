import { Building2, Monitor, Video } from 'lucide-react';

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
            <div className="space-y-5">
                <h3 className="text-[#1F1E1E] text-lg font-semibold">Appointment type</h3>
                <div className="p-8 rounded-2xl border border-dashed border-outline-variant/30 bg-surface-container-low/30 text-center">
                    <p className="text-on-surface-variant text-sm">No appointment types available</p>
                </div>
            </div>
        );
    }

    return (
        <div className="space-y-5">
            <h3 className="text-[#1F1E1E] text-lg font-semibold">Appointment type</h3>
            <div className="grid grid-cols-2 gap-4">

                <button
                    onClick={() => inPersonAvailable && onChange('in_person')}
                    disabled={!inPersonAvailable}
                    className={`p-6 rounded-lg border border-[#E7E8EB] transition-all flex flex-col items-center text-center gap-3 ${value === 'in_person'
                        ? 'border-primary bg-[#055BD9]'
                        : 'border-[#E7E8EB] hover:border-primary/50'
                        } ${!inPersonAvailable && 'opacity-50 cursor-not-allowed'}`}
                >
                    <span className={`w-10 h-10 flex justify-center items-center rounded-full ${value === 'in_person' ? 'bg-white' : 'bg-[#F5F6F8]'}`}>
                        <Building2 color='#1F1E1E' size={16} />
                    </span>
                    <span className={`text-sm font-medium leading-tight ${value === 'in_person' ? 'text-white' : 'text-[#1F1E1E]'}`}>
                        Book In-Clinic Appointment
                    </span>
                </button>

                <button
                    onClick={() => videoAvailable && onChange('video')}
                    disabled={!videoAvailable}
                    className={`p-6 rounded-lg border border-[#E7E8EB] transition-all flex flex-col items-center text-center gap-3 ${value === 'video'
                        ? 'border-primary bg-[#055BD9]'
                        : 'border-[#E7E8EB] hover:border-primary/50'
                        } ${!videoAvailable && 'opacity-50 cursor-not-allowed'}`}
                >
                    <span className={`w-10 h-10 flex justify-center items-center rounded-full ${value === 'video' ? 'bg-white' : 'bg-[#F5F6F8]'}`}>
                        <Video color='#1F1E1E' size={16} />
                    </span>
                    <span className={`text-sm font-medium leading-tight ${value === 'video' ? 'text-white' : 'text-[#1F1E1E]'}`}>
                        Online Video Appointment
                    </span>
                </button>
            </div>
        </div>
    );
};

export default AppointmentTypeSelector;