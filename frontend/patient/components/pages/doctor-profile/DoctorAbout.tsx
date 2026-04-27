import type { DoctorAboutInfo } from '@/types/doctor-details';

interface DoctorAboutProps {
  about: DoctorAboutInfo;
  className?: string;
}

const DoctorAbout = ({ about, className = '' }: DoctorAboutProps) => {
  return (
    <div className={`bg-surface-container-lowest rounded-3xl p-8 space-y-4 ${className}`}>
      <h3 className="text-xl font-bold font-headline text-primary">Professional Summary</h3>
      <p className="text-on-surface-variant leading-relaxed text-lg">
        {about?.bio || about?.description || 'No description available.'}
      </p>
    </div>
  );
};

export default DoctorAbout;