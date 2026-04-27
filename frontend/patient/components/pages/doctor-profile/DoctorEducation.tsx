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
    <div className="bg-surface-container-lowest rounded-3xl p-8 space-y-4 shadow-sm border border-outline-variant/10">
      <h3 className="text-lg font-bold font-headline text-primary flex items-center gap-2">
        <School className="w-5 h-5 text-surface-tint" />
        Education
      </h3>
      <ul className="space-y-4">
        {education.map((edu, idx) => (
          <li key={idx} className="flex flex-col">
            <span className="font-bold text-primary">{edu.degree}</span>
            <span className="text-sm text-on-surface-variant">{edu.institution}</span>
            <span className="text-xs text-on-surface-variant/60">
              {edu.start_date} - {edu.end_date}
            </span>
          </li>
        ))}
      </ul>
    </div>
  );
};

export default DoctorEducation;