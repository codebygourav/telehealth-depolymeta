import { z } from "zod";

export const PrescriptionSchema = z.object({
  medicine_id: z.string().optional(),
  medicine_name: z.string().min(2, "Medication required"),
  medication_type: z.string().min(1, "Medication type required"),
  dosage: z.string().min(1, "Dosage required"),
  frequency: z.string().min(1, "Frequency required"),
  timing_morning: z.boolean().optional(),
  timing_afternoon: z.boolean().optional(),
  timing_evening: z.boolean().optional(),
  timing_night: z.boolean().optional(),
  meal: z.enum(["before_meal", "after_meal", "with_meal"], {
    message: "Please select a meal option",
  }),
  instructions: z.string().optional(),
  stamp_preference: z.string().min(1, "Stamp preference required"),
});

export type PrescriptionForm = z.infer<typeof PrescriptionSchema>;

export type MedicineItem = {
  id: string;
  name: string;
  type?: string | null;
  source?: "inventory" | "doctor_added";
};

export type MedicineSource = "inventory" | "doctor_added" | "custom" | null;

export type AddedMedicine = {
  medicine_id?: string | null;
  medicine_name: string;
  medication_type: string;
  dosage: string;
  frequency: string;
  timing_morning: boolean;
  timing_afternoon: boolean;
  timing_evening: boolean;
  timing_night: boolean;
  meal: PrescriptionForm["meal"];
  instructions?: string;
  start_date?: string | null;
  end_date?: string | null;
};

export type EntryMode = "voice" | "manual" | null;
export type VoiceLocale = string;

export type MedicineStatus = {
  tone: "green" | "amber" | "blue";
  title: string;
  description: string;
};
