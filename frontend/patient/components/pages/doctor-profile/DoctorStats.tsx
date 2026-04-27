import { Group, History } from 'lucide-react';

interface DoctorStatsProps {
  patientsHelped: number;
  experience: number;
}

const DoctorStats = ({ patientsHelped, experience }: DoctorStatsProps) => {
  return (
    <>
      <div className="bg-surface-container-lowest rounded-3xl p-6 flex items-center gap-6 shadow-sm border border-outline-variant/10">
        <div className="h-14 w-14 rounded-xl bg-emerald-50 flex items-center justify-center text-surface-tint">
          <Group className="w-8 h-8" />
        </div>
        <div>
          <div className="text-2xl font-bold font-headline text-primary">
            {patientsHelped.toLocaleString()}+
          </div>
          <div className="text-sm text-on-surface-variant font-label uppercase tracking-widest">
            Patients Helped
          </div>
        </div>
      </div>
      
      <div className="bg-surface-container-lowest rounded-3xl p-6 flex items-center gap-6 shadow-sm border border-outline-variant/10">
        <div className="h-14 w-14 rounded-xl bg-emerald-50 flex items-center justify-center text-surface-tint">
          <History className="w-8 h-8" />
        </div>
        <div>
          <div className="text-2xl font-bold font-headline text-primary">
            {experience}+ Years
          </div>
          <div className="text-sm text-on-surface-variant font-label uppercase tracking-widest">
            Experience
          </div>
        </div>
      </div>
    </>
  );
};

export default DoctorStats;