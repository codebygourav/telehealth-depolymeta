import { Building2, Calendar, Clock, Video } from 'lucide-react';

interface ScheduleDetailsProps {
  date: string;
  timeSlot: string;
  consultationType: 'video' | 'in_person';
}

const ScheduleDetails = ({ date, timeSlot, consultationType }: ScheduleDetailsProps) => {
  // Format date for display
  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
    });
  };

  return (
    <div className="p-8 pb-10 border-b border-dashed border-outline-variant/20">
      <div className="flex items-center justify-between mb-8">
        <div className="text-xs font-bold text-on-surface-variant uppercase tracking-widest">
          Schedule
        </div>
        <div className="px-3 py-1 bg-primary/5 text-primary rounded-lg text-[10px] font-bold uppercase tracking-widest">
          Confirmed
        </div>
      </div>

      <div className="space-y-6">
        <div className="flex items-center gap-4">
          <div className="w-12 h-12 bg-surface-container-low rounded-2xl flex items-center justify-center text-primary">
            <Calendar className="w-6 h-6" />
          </div>
          <div>
            <p className="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest">
              Date
            </p>
            <p className="text-lg font-bold text-primary">
              {date}
            </p>
          </div>
        </div>

        <div className="flex items-center gap-4">
          <div className="w-12 h-12 bg-surface-container-low rounded-2xl flex items-center justify-center text-primary">
            <Clock className="w-6 h-6" />
          </div>
          <div>
            <p className="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest">
              Time Slot
            </p>
            <p className="text-lg font-bold text-primary">
              {timeSlot}
            </p>
          </div>
        </div>

        <div className="flex items-center gap-4">
          <div className="w-12 h-12 bg-surface-container-low rounded-2xl flex items-center justify-center text-primary">
            {consultationType === 'video' ? <Video className="w-6 h-6" /> : <Building2 className="w-6 h-6" />}
          </div>
          <div>
            <p className="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest">
              Consultation Type
            </p>
            <p className="text-lg font-bold text-primary capitalize">
              {consultationType === 'video' ? 'Video Call' : 'In-Clinic Visit'}
            </p>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ScheduleDetails;