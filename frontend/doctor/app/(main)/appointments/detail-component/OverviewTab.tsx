"use client";

import {
  Calendar,
  CheckCircle,
  Clock,
  Copy,
  CreditCard,
  Droplet,
  FileText,
  IndianRupee,
  List,
  Mail,
  MapPin,
  Phone,
  UserCircle,
  Video,
  XCircle,
} from "lucide-react";

import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Separator } from "@/components/ui/separator";
import { getStatusColor } from "@/src/utils/getStatusColor";
import { Activity } from "lucide-react";

export default function OverviewTab({ appointment }: { appointment: any }) {
  const patient = appointment?.patient || {};
  const doctor = appointment?.doctor || {};
  const schedule = appointment?.schedule || {};
  const payment = appointment?.payment || {};

  const getStatusConfig = (status: string) => {
    switch (status) {
      case "completed":
        return {
          icon: <CheckCircle className="h-4 w-4 text-primary" />,
        };

      case "confirmed":
        return {
          icon: <Calendar className="h-4 w-4 text-primary" />,
        };

      case "cancelled":
      case "failed":
        return {
          icon: <XCircle className="h-4 w-4 text-primary" />,
        };

      case "pending":
        return {
          icon: <Clock className="h-4 w-4 text-primary" />,
        };

      default:
        return {
          icon: <Clock className="h-4 w-4 text-primary" />,
        };
    }
  };

  const statusConfig = getStatusConfig(appointment?.status);

  return (
    <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
      {/* LEFT SIDE */}
      <div className="lg:col-span-2 space-y-4 sm:space-y-6">
        {/* Patient Problem */}
        <Card className="overflow-hidden">
          <CardHeader className="bg-muted/30">
            <CardTitle className="flex items-center gap-2">
              <FileText className="h-4 w-4 sm:h-5 sm:w-5 text-primary" />
              <span className="text-sm sm:text-base font-semibold">
                {/* Patient Problem / Chief Complaint */}
                Patient Intake Notes
              </span>
            </CardTitle>
          </CardHeader>

          <CardContent>
            {patient?.problem || patient?.chief_complaint ? (
              <div className="space-y-3 sm:space-y-4">
                {/* Main Complaint */}
                <div className="flex gap-2 sm:gap-3">
                  <div className="flex-1">
                    <p className="text-sm sm:text-base font-medium mt-1 text-foreground">
                      {patient?.problem || patient?.chief_complaint}
                    </p>
                  </div>
                </div>

                {/* Additional Details - If available */}
                {(patient?.duration || patient?.severity) && (
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 pt-3 border-t">
                    {patient?.duration && (
                      <div className="flex items-center gap-2 sm:gap-3">
                        <div className="p-1.5 sm:p-2 rounded-lg bg-blue-100">
                          <Clock className="h-3 w-3 sm:h-4 sm:w-4 text-blue-600" />
                        </div>
                        <div>
                          <p className="text-[10px] sm:text-xs text-muted-foreground">
                            Duration
                          </p>
                          <p className="text-xs sm:text-sm font-medium mt-0.5">
                            {patient.duration}
                          </p>
                        </div>
                      </div>
                    )}

                    {patient?.severity && (
                      <div className="flex items-center gap-2 sm:gap-3">
                        <div className="p-1.5 sm:p-2 rounded-lg bg-red-100">
                          <Activity className="h-3 w-3 sm:h-4 sm:w-4 text-red-600" />
                        </div>
                        <div>
                          <p className="text-[10px] sm:text-xs text-muted-foreground">
                            Severity
                          </p>
                          <p className="text-xs sm:text-sm font-medium mt-0.5">
                            {patient.severity}
                          </p>
                        </div>
                      </div>
                    )}
                  </div>
                )}

                {/* Symptoms List - If available */}
                {patient?.symptoms && patient.symptoms.length > 0 && (
                  <div className="pt-3 border-t">
                    <div className="flex items-center gap-2 mb-2 sm:mb-3">
                      <div className="p-1.5 sm:p-2 rounded-lg bg-purple-100">
                        <List className="h-3 w-3 sm:h-4 sm:w-4 text-purple-600" />
                      </div>
                      <p className="text-[10px] sm:text-xs text-muted-foreground uppercase tracking-wide">
                        Symptoms
                      </p>
                    </div>
                    <div className="flex flex-wrap gap-1.5 sm:gap-2 pl-0 sm:pl-11">
                      {patient.symptoms.map(
                        (symptom: string, index: number) => (
                          <Badge
                            key={index}
                            variant="secondary"
                            className="bg-purple-50 text-purple-700 text-[10px] sm:text-xs px-1.5 sm:px-2"
                          >
                            {symptom}
                          </Badge>
                        ),
                      )}
                    </div>
                  </div>
                )}
              </div>
            ) : (
              <div className="flex items-center gap-3 rounded-lg bg-muted/30 p-3 sm:p-4">
                <p className="text-xs sm:text-sm text-muted-foreground">
                  No problem/complaint recorded
                </p>
              </div>
            )}
          </CardContent>
        </Card>

        {/* Contact Information */}
        <Card className="overflow-hidden">
          <CardHeader className="bg-muted/30 ">
            <CardTitle className="flex items-center gap-2">
              <UserCircle className="h-4 w-4 sm:h-5 sm:w-5 text-primary" />
              <span className="text-sm sm:text-base font-semibold">
                Patient Contact Information
              </span>
            </CardTitle>
          </CardHeader>

          <CardContent className="px-3 sm:p-4">
            <div>
              {/* Email */}
              <div className="flex items-center gap-2 sm:gap-3 p-2 sm:p-3 rounded-lg hover:bg-muted/30 transition-colors group">
                <div className="p-1.5 sm:p-2 rounded-lg bg-gray-200 shrink-0">
                  <Mail className="h-3 w-3 sm:h-4 sm:w-4 text-primary" />
                </div>

                <div className="flex-1 min-w-0">
                  <p className="text-[10px] sm:text-xs text-muted-foreground uppercase tracking-wide">
                    Email Address
                  </p>

                  <div className="flex items-center gap-2 mt-0.5">
                    <p className="text-xs sm:text-sm font-medium text-foreground truncate">
                      {patient?.email || "Not provided"}
                    </p>

                    {patient?.email && (
                      <button
                        className="opacity-0 group-hover:opacity-100 transition-opacity shrink-0"
                        onClick={() =>
                          navigator.clipboard.writeText(patient.email)
                        }
                      >
                        <Copy className="h-3 w-3 sm:h-3.5 sm:w-3.5 text-muted-foreground hover:text-primary" />
                      </button>
                    )}
                  </div>
                </div>
              </div>

              {/* Phone */}
              <div className="flex items-center gap-2 sm:gap-3 p-2 sm:p-3 rounded-lg hover:bg-muted/30 transition-colors group">
                <div className="p-1.5 sm:p-2 rounded-lg bg-gray-200 shrink-0">
                  <Phone className="h-3 w-3 sm:h-4 sm:w-4 text-primary" />
                </div>

                <div className="flex-1 min-w-0">
                  <p className="text-[10px] sm:text-xs text-muted-foreground uppercase tracking-wide">
                    Phone Number
                  </p>

                  <div className="flex items-center gap-2 mt-0.5">
                    <p className="text-xs sm:text-sm font-medium text-foreground truncate">
                      {patient?.phone || "Not provided"}
                    </p>

                    {patient?.phone && (
                      <button
                        className="opacity-0 group-hover:opacity-100 transition-opacity shrink-0"
                        onClick={() =>
                          navigator.clipboard.writeText(patient.phone)
                        }
                      >
                        <Copy className="h-3 w-3 sm:h-3.5 sm:w-3.5 text-muted-foreground hover:text-primary" />
                      </button>
                    )}
                  </div>
                </div>
              </div>

              {/* Blood Group & Gender - Two Column Layout */}
              <div className="grid grid-cols-1 pt-2">
                {/* Blood Group */}
                <div className="flex items-center gap-2 sm:gap-3 p-2 sm:p-3 rounded-lg bg-muted/20">
                  <div className="p-1.5 sm:p-2 rounded-lg bg-gray-200 shrink-0">
                    <Droplet className="h-3 w-3 sm:h-4 sm:w-4 text-primary" />
                  </div>
                  <div>
                    <p className="text-[10px] sm:text-xs text-muted-foreground uppercase tracking-wide">
                      Blood Group
                    </p>
                    <p className="text-xs sm:text-sm font-semibold mt-0.5">
                      {patient?.blood_group || "N/A"}
                    </p>
                  </div>
                </div>

                {/* Gender */}
                <div className="flex items-center gap-2 sm:gap-3 p-2 sm:p-3 rounded-lg bg-muted/20">
                  <div className="p-1.5 sm:p-2 rounded-lg bg-gray-200 shrink-0">
                    <UserCircle className="h-3 w-3 sm:h-4 sm:w-4 text-primary" />
                  </div>
                  <div>
                    <p className="text-[10px] sm:text-xs text-muted-foreground uppercase tracking-wide">
                      Gender
                    </p>
                    <p className="text-xs sm:text-sm font-semibold mt-0.5">
                      {patient?.gender_formatted || "N/A"}
                    </p>
                  </div>
                </div>
              </div>

              {/* Age & DOB - If available */}
              {(patient?.age_formatted || patient?.dob) && (
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 pt-2">
                  {patient?.age_formatted && (
                    <div className="flex items-center gap-2 sm:gap-3 p-2 sm:p-3 rounded-lg bg-muted/20">
                      <div className="p-1.5 sm:p-2 rounded-lg bg-gray-200 shrink-0">
                        <Calendar className="h-3 w-3 sm:h-4 sm:w-4 text-primary" />
                      </div>
                      <div>
                        <p className="text-[10px] sm:text-xs text-muted-foreground uppercase tracking-wide">
                          Age
                        </p>
                        <p className="text-xs sm:text-sm font-semibold mt-0.5">
                          {patient?.age_formatted}
                        </p>
                      </div>
                    </div>
                  )}
                  {patient?.dob && (
                    <div className="flex items-center gap-2 sm:gap-3 p-2 sm:p-3 rounded-lg bg-muted/20">
                      <div className="p-1.5 sm:p-2 rounded-lg bg-gray-200 shrink-0">
                        <Calendar className="h-3 w-3 sm:h-4 sm:w-4 text-primary" />
                      </div>
                      <div>
                        <p className="text-[10px] sm:text-xs text-muted-foreground uppercase tracking-wide">
                          Date of Birth
                        </p>
                        <p className="text-xs sm:text-sm font-semibold mt-0.5">
                          {patient?.dob}
                        </p>
                      </div>
                    </div>
                  )}
                </div>
              )}
            </div>
          </CardContent>
        </Card>

        {/* Doctor Info */}
        {/* <Card>
          <CardContent>
            <div className="flex items-center gap-3 sm:gap-4">
              <Avatar className="h-10 w-10 sm:h-12 sm:w-14 md:h-14 md:w-14">
                <AvatarImage src={doctor?.avatar} />
                <AvatarFallback>{doctor?.name?.slice(0, 2) || "DR"}</AvatarFallback>
              </Avatar>

              <div className="flex-1 min-w-0">
                <h3 className="text-sm sm:text-base font-semibold truncate">
                  {doctor?.name || "Doctor not assigned"}
                </h3>
                <p className="text-xs sm:text-sm text-muted-foreground truncate">
                  {doctor?.department || "Department not specified"}
                </p>
                {doctor?.years_experience && (
                  <p className="text-xs text-muted-foreground mt-0.5">
                    {doctor?.years_experience} exp
                  </p>
                )}
              </div>
            </div>
          </CardContent>
        </Card> */}
      </div>

      {/* RIGHT SIDE */}
      <div className="space-y-4 sm:space-y-6">
        {/* Payment Info */}
        <Card>
          <CardHeader >
            <CardTitle className="flex items-center gap-2 text-sm sm:text-base">
              <IndianRupee className="h-3.5 w-3.5 sm:h-4 sm:w-4" />
              Payment Information
            </CardTitle>
          </CardHeader>

          <CardContent className=" space-y-2 sm:space-y-3 text-xs sm:text-sm">
            <div className="flex justify-between">
              <span className="text-muted-foreground">Consultation Fee</span>
              <span>{payment?.consultation_fee_formatted || "N/A"}</span>
            </div>

            <div className="flex justify-between">
              <span className="text-muted-foreground">Admin Fee</span>
              <span>{payment?.admin_fee_formatted || "N/A"}</span>
            </div>

            <div className="flex justify-between">
              <span className="text-muted-foreground">Discount</span>
              <span>{payment?.discount_formatted || "N/A"}</span>
            </div>

            <Separator />

            <div className="flex justify-between font-semibold">
              <span>Total</span>
              <span>{payment?.total_formatted || "N/A"}</span>
            </div>

            <div className="flex justify-between items-center">
              <span className="text-muted-foreground">Payment Status</span>
              <Badge className={`${getStatusColor("payment", payment?.status)} text-[10px] sm:text-xs px-1.5 sm:px-2`}>
                {payment?.status_label || "N/A"}
              </Badge>
            </div>

            <div className="flex justify-between">
              <span className="text-muted-foreground">Payment Method</span>
              <span className="uppercase text-xs sm:text-sm">{payment?.payment_method || "N/A"}</span>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}