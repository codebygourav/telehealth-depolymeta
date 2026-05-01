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
  onViewDetail?: (id: string) => void;
}

interface InfoBadgeProps {
  icon: React.ReactNode;
  value: string | number;
  label?: string;
}

function InfoBadges({ prescription }: { prescription: any }) {
  console.log(prescription);
  const items: InfoBadgeProps[] = [
    {
      icon: <User className="w-4 h-4" />,
      value: prescription?.doctor_name ?? "Unknown doctor",
    },
    {
      icon: <Calendar className="w-4 h-4" />,
      value: prescription?.timing
        ? prescription?.timing
        : "N/A",
    },
    // Add more items as needed.
  ];

  return (
    <div className="flex flex-wrap items-center gap-2 pt-1">
      {items.map((item, i) => (
        <span
          key={i}
          className="inline-flex items-center gap-1 px-4 py-2.5 text-span-12 g-text-muted font-semibold bg-light-gray global-radius"
        >
          {item.icon}
          <span className="font-semibold">{item.value}</span>
          {item.label && (
            <span className="ml-1 font-normal text-gray-400">{item.label}</span>
          )}
        </span>
      ))}
    </div>
  );
}

export const MedicineCard = ({ prescription, onViewDetail }: MedicineCardProps) => {

  return (
    <motion.div
      initial={{ opacity: 0, y: 10 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.3 }}
      className="w-full h-full"
    >
      <Card className="p-5 h-full overflow-hidden bg-white global-radius-10 group">
        <CardContent className="flex flex-col h-full justify-between gap-4 p-0 md:flex-row md:items-center">
          <div className="flex-1 space-y-3">
            {/* Header: Label */}
            <div className="flex items-center gap-1.5 font-bold uppercase tracking-widest g-text-muted">
              <AlertCircle className="w-3.5 h-3.5 text-error" />
              <span className="font-semibold g-text-muted">Health Issue</span>
            </div>

            {/* Problem Title */}
            <h2 className="font-bold g-text-dark line-clamp-1">
              {prescription.problem}
            </h2>

            {/* Info Badges */}
            <InfoBadges prescription={prescription} />
          </div>
      

          {/* Action Button */}
          <div className="flex items-center pt-2 shrink-0 md:pt-0">
            {onViewDetail ? (
              <Button
                className="h-10 btn-primary-cta mt-0"
                onClick={() => onViewDetail(prescription.appointment_id)}
              >
                View Detail
                <ChevronRight className="m-0 size-4" />
              </Button>
            ) : (
              <Button className="btn-primary-cta" asChild>
                <Link href={`/my-medicines/${prescription.appointment_id}`}>View Detail</Link>
              </Button>
            )}
          </div>
        </CardContent>
      </Card>
    </motion.div>
  );
};


