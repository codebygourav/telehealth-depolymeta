import type { DoctorAboutInfo } from '@/types/doctor-details';

interface DoctorAboutProps {
    about: DoctorAboutInfo;
    className?: string;
}

const DoctorAbout = ({ about, className = '' }: DoctorAboutProps) => {
    return (
        <div className={`rounded-lg p-5 border border-[#E7E8EB] shadow-[0px_2px_4px_0px_#0000001A] ${className}`}>
            <h3 className="text-[#1F1E1E] text-lg font-[#1F1E1E] font-semibold">Professional Summary</h3>
            <p className="text-[#4D4D4D] text-base pt-1">
                {about?.bio || about?.description || 'No description available.'}
            </p>
        </div>
    );
};

export default DoctorAbout;