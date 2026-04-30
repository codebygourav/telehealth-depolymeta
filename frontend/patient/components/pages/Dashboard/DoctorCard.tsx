  "use client";

  import { Button, Card, CardContent, Separator } from "@/components/ui";
  import { cn } from "@/lib/utils";
  import type { DoctorCardProps } from "@/types/dashboard-doctors";
  import { ArrowRight, Clock, Languages, Star, Stethoscope, UserRound, Video } from "lucide-react";
  import { CustomAvatar } from "@/components/custom/custom-avatar";
  import { useRouter } from "next/navigation";
  import type { ReactNode } from "react";
import { ChevronRight } from "lucide-react";

  function VerticalSeparator({ className }: { className?: string }) {
    return <div aria-hidden="true" className={cn("h-full w-px self-stretch", className)} />;
  }

  function consultationDetailField(
    title: string,
    value: ReactNode,
    classNameValue?: string
  ) {
    return (
      <div className={cn("gap-2 space-y-2", classNameValue)}>
        <p className="text-lg font-semibold text-foreground">{title}</p>
        <p className="text-base font-semibold text-foreground/85">{value}</p>
      </div>
    );
  }

  export function DoctorCard({ doctor, onBookNow }: DoctorCardProps) {
    const router = useRouter();

    const specialty =
      Array.isArray(doctor.speciality) && doctor.speciality.length > 0
        ? doctor.speciality[0]
        : "Cardiologist";

    const languages =
      Array.isArray(doctor.languages_known) && doctor.languages_known.length > 0
        ? doctor.languages_known.join(", ")
        : "English, Hindi, Punjabi";
    const consultationType = doctor.consultation_type || "Video";
    const consultationTypeLabel = String(consultationType);
    const isVideoConsultation = consultationTypeLabel.toLowerCase().includes("video");
    const ConsultationTypeIcon = isVideoConsultation ? Video : UserRound;
    const consultationTypeValue = (
      <span className="inline-flex items-center gap-2">
        <ConsultationTypeIcon className="text-green-500 size-4" />
        <span>{consultationTypeLabel}</span>
      </span>
    );

    return (
      <Card className="h-full py-0 custom-card-design">
        <CardContent className="flex flex-col justify-between h-full gap-5 p-5">
          <div className="flex items-start gap-4">
            <div className="bg-white rounded-full">
            <CustomAvatar
              src={doctor.avatar}
              name={doctor.name}
              radius="full"
              size="default"
              className="rounded-full bg-light-gray size-20"
            />
            </div>  
            <div className="flex-1 min-w-0">
              <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                  <h4 className="truncate font-xs text-foreground">
                    {doctor.name}
                  </h4>
                  <span className="mt-1 font-xs text-foreground/85">
                    {specialty}
                  </span>
                </div>

                {doctor.rating > 0 && (
                  <div className="flex items-center gap-1 px-2 py-1 rounded-md shrink-0 bg-primary/8 text-primary">
                    <Star className="size-3.5 fill-current text-primary" />
                    <span className="text-sm font-semibold">{doctor.rating}</span>
                  </div>
                )}
              </div>

              <div className="flex flex-wrap items-center mt-3 text-sm gap-x-2 gap-y-1 text-foreground/80">
                <span className="flex items-center gap-1.5 text-span-12">
                  <Clock className="size-3.5" />
                  Exp: {doctor.years_experience || 14} yrs
                </span>
                <span className="text-foreground/100">•</span>
                <span className="flex items-center gap-1.5 text-span-12">
                  <Languages className="size-3.5 " />
                  Lang: {languages}
                </span>
              </div>
            </div>
          </div>

          <div className="p-4 bg-light-gray global-radius-10">
            <div className="grid grid-cols-[1fr_1px_1fr] items-stretch gap-x-4">
              {consultationDetailField("Consultation Type", consultationTypeValue)}
              {typeof doctor.consultation_fee === "number" && doctor.consultation_fee > 0 && (
                <>
                  <VerticalSeparator className="bg-foreground/8" />
                  {consultationDetailField(
                    "Consultation Fee",
                    `₹${doctor.consultation_fee ?? 0}`,
                    "ml-8"
                  )}
                </>
              )}
         
         
            </div>


            <Button
              onClick={() => {
                onBookNow?.(doctor.id);
                router.push(`/find-doctors/${doctor.id}`);
              }}
              className="btn-primary-cta"
            >
              Book Your Appointment
              <ChevronRight />
            </Button>
       
          </div>
        </CardContent>
      </Card>
    );
  }
