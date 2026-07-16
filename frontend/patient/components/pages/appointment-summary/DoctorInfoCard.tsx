"use client";

import { Card, CardContent } from "@/components/ui/card";
import { Star, Stethoscope } from "lucide-react";
import type { AppointmentDoctor } from "@/types/appointment-summary";

interface DoctorInfoCardProps {
  doctor: AppointmentDoctor;
}
const DoctorInfoCard = ({ doctor }: DoctorInfoCardProps) => {
  return (
    <Card className="global-radius-10 g-border  p-4 sm:p-5 md:p-6 w-full">
      <CardContent className="p-0">
        <div className="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4">
          <div className="flex flex-col sm:flex-row gap-4 sm:gap-5 flex-1">
            <img
              src={doctor?.avatar || "https://api.dicebear.com/7.x/avataaars/svg?seed=Felix"}
              alt={doctor?.name || "Profile"}
              className="w-24 h-24 rounded-full object-cover mx-auto sm:mx-0"
            />
            <div className="text-center sm:text-left flex-1">
              <p className="text-primary flex items-center justify-center sm:justify-start text-xs font-semibold uppercase tracking-wider">
                <Stethoscope size={14} className="mr-1.5 text-primary" />
                {doctor?.department || "Department"}
              </p>
              <h2 className="text-2xl font-bold text-[#1F1E1E] mt-1">
                {doctor?.name || "Doctor Name"}
              </h2>

              {/* Experience & Review Cards */}
              <div className="flex flex-wrap justify-center sm:justify-start gap-3 sm:gap-4 mt-2">
                {/* Experience Card */}
                <div className="flex items-center gap-x-1.5 bg-light-gray global-radius-10 g-border p-1.5 text-xs">
                  <p className="g-text-muted">Experience</p>
                  <p className="g-text-muted">
                    {doctor?.years_experience ?? "N/A"}
                  </p>
                </div>
                {/* Review Card */}
                <div className="flex items-center gap-x-1.5 bg-light-gray global-radius-10 g-border p-1.5 text-xs">
                  <p className="g-text-muted flex items-center gap-x-1">
                    <Star className="w-4 h-4 border-none text-amber-500" fill="#f99c00" />
                    Rating
                  </p>
                  <div className="g-text-body font-bold">
                    {doctor?.average_rating ?? "N/A"}
                    <span className="g-text-muted"> ({doctor?.total_reviews ?? "0"})</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          {/* Optionally add badge here if needed */}
        </div>
      </CardContent>
    </Card>
  );
};

export default DoctorInfoCard;