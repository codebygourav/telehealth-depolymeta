import { School } from 'lucide-react';
import type { DoctorEducationItem } from '@/types/doctor-details';

interface DoctorEducationProps {
    education: DoctorEducationItem[];
}

const DoctorEducation = ({ education }: DoctorEducationProps) => {
    if (!education || education.length === 0) {
        return null;
    }

    return (
        <div className="rounded-lg p-5 border border-[#E7E8EB] shadow-[0px_2px_4px_0px_#0000001A]">
            <h3 className="text-[#1F1E1E] text-lg font-semibold mb-3">
                Education Information
            </h3>
            <ul className="space-y-4">
                {education.map((edu, idx) => (
                    <li key={idx} className="flex flex-col">
                        <span className="font-medium text-[#4D4D4D]">{edu.institution} - {edu.degree}</span>
                        {/* <span className="text-sm text-on-surface-variant">{edu.institution}</span> */}
                        <span className="text-[#4D4D4D] text-sm mt-1">
                            {edu.start_date} - {edu.end_date}
                        </span>
                    </li>
                ))}
            </ul>
        </div>
    );
};

export default DoctorEducation;