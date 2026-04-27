"use client";

import Link from "next/link";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { cn } from "@/lib/utils";
import { AlertCircle, Calendar, ChevronRight, User } from "lucide-react";
import { motion } from "motion/react";

interface MedicineCardProps {
  prescription: any; // Using any for now to match the user's detailed JSON
  status: "current" | "past";
  onViewDetail?: (id: string) => void;
}

export const MedicineCard = ({ prescription, status, onViewDetail }: MedicineCardProps) => {
  const isCurrent = status === "current" || prescription.status === "Active";

  return (
    <motion.div
      initial={{ opacity: 0, y: 10 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.3 }}
      className="w-full"
    >
      <Card className="rounded-3xl p-5 shadow-sm border border-outline-variant/5 bg-white hover:shadow-md transition-all group overflow-hidden">
        <CardContent className="p-0 flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div className="flex-1 space-y-3">
            {/* Header: Label */}
            <div className="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-muted-foreground/70">
              <AlertCircle className="w-3.5 h-3.5" />
              <span>Health Issue</span>
            </div>

            {/* Problem Title */}
            <h3 className="text-xl md:text-2xl font-bold text-[#052116] font-headline line-clamp-1">
              {prescription.problem}
            </h3>

            {/* Info Badges */}
            <div className="flex flex-wrap items-center gap-2 pt-1">
              <Badge
                variant="secondary"
                className="p-3 md:p-4 bg-surface-container-low rounded-xl font-semibold text-sm border-none transition-colors"
              >
                <User className="w-5 h-5 md:w-6 md:h-6 mr-1 text-muted-foreground/80" />
                {prescription.doctor_name}
              </Badge>

              {/* Optional Medication Count Badge */}
              <Badge
                variant="secondary"
                className={cn(
                  "p-3 md:p-4 bg-surface-container-low rounded-xl font-semibold text-sm border-none transition-colors",
                )}
              >
                <Calendar className="w-5 h-5 md:w-6 md:h-6 mr-1 text-muted-foreground/80" />
                {prescription.timing || "Prescribed"}
              </Badge>
            </div>
          </div>

          {/* Action Button */}
          <div className="shrink-0 flex items-center pt-2 md:pt-0">
            {onViewDetail ? (
              <Button
                className="bg-[#052116] text-white hover:bg-[#052116]/90 font-bold rounded-full h-12 px-8 flex items-center gap-2 shadow-sm group/btn"
                onClick={() => onViewDetail(prescription.appointment_id)}
              >
                View Detail
                <ChevronRight className="w-4 h-4 transition-transform group-hover/btn:translate-x-1" />
              </Button>
            ) : (
              <Button
                className="bg-[#052116] text-white hover:bg-[#052116]/90 font-bold rounded-full h-12 px-8 flex items-center gap-2 shadow-sm group/btn"
                asChild
              >
                <Link href={`/my-medicines/${prescription.appointment_id}`}>
                  View Detail
                  <ChevronRight className="w-4 h-4 transition-transform group-hover/btn:translate-x-1" />
                </Link>
              </Button>
            )}
          </div>
        </CardContent>
      </Card>
    </motion.div>
  );
};


