import { StatusBadge, StatusBadgeStatus } from '@/components/custom/StatusBadge';
import { Card, CardContent } from '@/components/ui/card';
import { AppointmentSchedule } from '@/types/appointment-summary';
import { Building2, Calendar, Clock, User, Video } from 'lucide-react';

interface ScheduleDetailsProps {
  schedule: AppointmentSchedule;
}
function InfoBadges({ schedule }: { schedule: AppointmentSchedule }) {
  return (
    <div className="p-6 pt-4">
      <div className="flex flex-col items-start justify-between gap-2 mb-3 md:flex-row ">
        <div className="flex items-center gap-2">
          <Calendar className="w-5 h-5 text-primary" />
          <span className="text-lg font-semibold text-on-surface">Schedule Detail</span>
        </div>
        <StatusBadge status={schedule?.consultation_type as StatusBadgeStatus} label={schedule?.consultation_type_label || "N/A"} />
      </div>
      <div className="grid grid-cols-[140px_1fr] gap-y-3 text-sm leading-snug">
        <div className="text-on-surface-variant">Date</div>
        <div className="text-right">
          {schedule?.date_formatted || "N/A"}
        </div>

        <div className="text-on-surface-variant">Time</div>
        <div className="text-right">{schedule?.time_formatted || "N/A"}</div>

        <div className="text-on-surface-variant">Booking Type</div>
        <div className="text-right capitalize">{schedule?.booking_type || "N/A"}</div>
      </div>

    </div>
  );
}
const ScheduleDetails = ({ schedule, status, statusLabel }: ScheduleDetailsProps) => {
  console.log("scheduleadsad dataasdasdasd", schedule);

  return (
    <Card className="w-full p-0 g-border global-radius-10">
      <CardContent className="p-0">
        <InfoBadges schedule={schedule} status={status} statusLabel={statusLabel} />
      </CardContent>
    </Card>
  );
};

export default ScheduleDetails;