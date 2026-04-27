"use client";

import { useEffect, useMemo, useState } from "react";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { zodResolver } from "@hookform/resolvers/zod";
import { X } from "lucide-react";
import { useParams } from "next/navigation";

import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Checkbox } from "@/components/ui/checkbox";
import { RadioField } from "@/components/custom/RadioField";

import { useAuth } from "@/context/userContext";
import { useAddPrescription } from "@/queries/useAddPrescription";
import { useMedicines } from "@/queries/useMedicines";

const PrescriptionSchema = z.object({
  medicine_id: z.string().min(1, "Medicine selection required"),
  medicine_name: z.string().min(2, "Medication required"),
  medication_type: z.string().min(1, "Medication type required"),
  dosage: z.string().min(1, "Dosage required"),
  frequency: z.string().min(1, "Frequency required"),
  timing_morning: z.boolean().optional(),
  timing_afternoon: z.boolean().optional(),
  timing_evening: z.boolean().optional(),
  timing_night: z.boolean().optional(),
  meal: z.enum(["before_meal", "after_meal"], {
    message: "Please select a meal option",
  }),
  instructions: z.string().optional(),
  stamp_preference: z.string().min(1, "Stamp preference required"),
});

export type PrescriptionForm = z.infer<typeof PrescriptionSchema>;

type MedicineItem = {
  id: string;
  name: string;
  type?: string | null;
};

interface AddPrescriptionDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

export default function AddPrescriptionDialog({
  open,
  onOpenChange,
}: AddPrescriptionDialogProps) {
  const { token } = useAuth();
  const params = useParams();
  const appointment_id = params?.id as string;

  const addPrescription = useAddPrescription(appointment_id || "", token!);

  const [selectedType, setSelectedType] = useState<string | null>(null);
  const [searchQuery, setSearchQuery] = useState("");
  const [debouncedSearch, setDebouncedSearch] = useState("");
  const [startDate, setStartDate] = useState<string>(getTodayDate());
  const [endDate, setEndDate] = useState<string>("");
  const [showSuccess, setShowSuccess] = useState(false);

  useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedSearch(searchQuery);
    }, 500);

    return () => clearTimeout(timer);
  }, [searchQuery]);

  const medicinesQuery = useMedicines({
    page: 1,
    per_page: 20,
    search: debouncedSearch,
  });

  const {
    handleSubmit,
    reset,
    setValue,
    watch,
    formState: { errors },
  } = useForm<PrescriptionForm>({
    resolver: zodResolver(PrescriptionSchema),
    defaultValues: {
      medicine_id: "",
      medicine_name: "",
      medication_type: "tablet",
      dosage: "",
      frequency: "",
      timing_morning: false,
      timing_afternoon: false,
      timing_evening: false,
      timing_night: false,
      meal: undefined,
      instructions: "",
      stamp_preference: "only_global",
    },
  });

  const medicationType = watch("medication_type");
  const meal = watch("meal");
  const selectedMedicineName = watch("medicine_name");

  useEffect(() => {
    setValue("dosage", "");
  }, [medicationType, setValue]);

  const dosageOptions = useMemo(() => {
    return getDosageOptions(medicationType);
  }, [medicationType]);

  const frequencyOptions = [
    { label: "Once a day", value: "OD" },
    { label: "Twice a day", value: "BD" },
    { label: "Three times a day", value: "TDS" },
    { label: "SOS", value: "SOS" },
  ];

  const stampOptions = [
    {
      label: "Global Stamp (Default Stamp with Signature)",
      value: "only_global",
    },
    {
      label: "Department Stamp (With Signature)",
      value: "only_department",
    },
    {
      label: "Both (Global & Department Stamp with Signature)",
      value: "both",
    },
  ];

  const mealOptions = [
    { label: "Before Meal", value: "before_meal" },
    { label: "After Meal", value: "after_meal" },
  ];

  const handleSelectMedicine = (medicine: MedicineItem) => {
    setValue("medicine_id", medicine.id, { shouldValidate: true });
    setValue("medicine_name", medicine.name, { shouldValidate: true });
    setValue("medication_type", medicine.type || "tablet", {
      shouldValidate: true,
    });
    setSelectedType(medicine.type || "tablet");
    setSearchQuery("");
  };

  const clearSelectedMedicine = () => {
    setValue("medicine_id", "");
    setValue("medicine_name", "");
    setValue("medication_type", "tablet");
    setSelectedType(null);
  };

  const onSubmit = (data: PrescriptionForm) => {
    const timings: string[] = [];

    if (data.timing_morning) timings.push("morning");
    if (data.timing_afternoon) timings.push("afternoon");
    if (data.timing_evening) timings.push("evening");
    if (data.timing_night) timings.push("night");

    const payload = {
      stamp_preference: data.stamp_preference,
      medicines: [
        {
          medicine_id: data.medicine_id,
          medicine_name: data.medicine_name,
          dosage: data.dosage,
          frequency: data.frequency,
          timings,
          meal: data.meal,
          start_date: startDate || null,
          end_date: endDate || null,
          instructions: data.instructions || "",
        },
      ],
    };

    addPrescription.mutate(payload, {
      onSuccess: () => {
        setShowSuccess(true);
      },
      onError: (error: any) => {
        alert(
          error?.response?.data?.errors?.message ||
          error?.message ||
          "Failed to add prescription. Please try again."
        );
      },
    });
  };

  const handleSuccessClose = () => {
    setShowSuccess(false);
    reset();
    setStartDate(getTodayDate());
    setEndDate("");
    setSelectedType(null);
    onOpenChange(false);
  };

  const medicineList = medicinesQuery.data?.data || [];

  return (
    <>
      <Dialog open={open} onOpenChange={onOpenChange}>
        <DialogContent className="w-[95vw] max-w-2xl! p-0 overflow-hidden rounded-xl sm:rounded-2xl">
          <DialogHeader className="border-b px-4 sm:px-6 py-3 sm:py-4">
            <div className="flex items-center justify-between">
              <DialogTitle className="text-base sm:text-lg md:text-xl">
                Add Prescription
              </DialogTitle>
            </div>
          </DialogHeader>

          <div className="max-h-[80vh] overflow-y-auto px-4 sm:px-6 py-4 sm:py-5">
            <form onSubmit={handleSubmit(onSubmit)} className="space-y-4 sm:space-y-5">
              {/* Medicine search / selected medicine */}
              <div className="space-y-1.5 sm:space-y-2">
                <Label className="text-xs sm:text-sm">Medicine</Label>

                {selectedMedicineName ? (
                  <div className="flex items-center justify-between rounded-lg border bg-muted/40 px-2 sm:px-3 py-1.5 sm:py-2">
                    <div className="flex items-center gap-2">
                      <span className="font-medium text-xs sm:text-sm">
                        {selectedMedicineName}
                      </span>
                    </div>
                    <button
                      type="button"
                      onClick={clearSelectedMedicine}
                      className="rounded-md p-1 text-muted-foreground hover:bg-muted"
                    >
                      <X className="h-3 w-3 sm:h-4 sm:w-4" />
                    </button>
                  </div>
                ) : (
                  <div className="space-y-2">
                    <Input
                      placeholder="Search medicine..."
                      value={searchQuery}
                      onChange={(e) => setSearchQuery(e.target.value)}
                      className="h-9 sm:h-10 text-xs sm:text-sm"
                    />

                    {!!searchQuery && (
                      <div className="max-h-48 sm:max-h-56 overflow-y-auto rounded-md border bg-background">
                        {medicinesQuery.isLoading ? (
                          <div className="p-2 sm:p-3 text-xs sm:text-sm text-muted-foreground">
                            Searching...
                          </div>
                        ) : medicineList.length > 0 ? (
                          medicineList.map((medicine: MedicineItem) => (
                            <button
                              key={medicine.id}
                              type="button"
                              onClick={() => handleSelectMedicine(medicine)}
                              className="flex w-full items-center justify-between px-2 sm:px-3 py-1.5 sm:py-2 text-left hover:bg-muted"
                            >
                              <span className="text-xs sm:text-sm">{medicine.name}</span>
                              <span className="text-[9px] sm:text-xs text-muted-foreground">
                                {medicine.type || "tablet"}
                              </span>
                            </button>
                          ))
                        ) : (
                          <div className="p-2 sm:p-3 text-xs sm:text-sm text-muted-foreground">
                            No medicines found
                          </div>
                        )}
                      </div>
                    )}
                  </div>
                )}

                {errors.medicine_name && (
                  <p className="text-[11px] sm:text-sm text-red-500">
                    {errors.medicine_name.message}
                  </p>
                )}
              </div>

              {/* Dosage */}
              <div className="space-y-1.5 sm:space-y-2">
                <Label className="text-xs sm:text-sm">
                  Dosage{medicationType ? ` (${medicationType})` : ""} *
                </Label>
                <Select
                  value={watch("dosage")}
                  onValueChange={(value) =>
                    setValue("dosage", value, { shouldValidate: true })
                  }
                >
                  <SelectTrigger className="h-9 sm:h-10 text-xs sm:text-sm">
                    <SelectValue placeholder="Select dosage" />
                  </SelectTrigger>
                  <SelectContent>
                    {dosageOptions.map((item) => (
                      <SelectItem key={item.value} value={item.value} className="text-xs sm:text-sm">
                        {item.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {errors.dosage && (
                  <p className="text-[11px] sm:text-sm text-red-500">{errors.dosage.message}</p>
                )}
              </div>

              {/* Frequency */}
              <div className="space-y-1.5 sm:space-y-2">
                <Label className="text-xs sm:text-sm">Frequency *</Label>
                <Select
                  value={watch("frequency")}
                  onValueChange={(value) =>
                    setValue("frequency", value, { shouldValidate: true })
                  }
                >
                  <SelectTrigger className="h-9 sm:h-10 text-xs sm:text-sm">
                    <SelectValue placeholder="Select frequency" />
                  </SelectTrigger>
                  <SelectContent>
                    {frequencyOptions.map((item) => (
                      <SelectItem key={item.value} value={item.value} className="text-xs sm:text-sm">
                        {item.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {errors.frequency && (
                  <p className="text-[11px] sm:text-sm text-red-500">
                    {errors.frequency.message}
                  </p>
                )}
              </div>

              {/* Timings */}
              <div className="space-y-2 sm:space-y-3">
                <Label className="text-xs sm:text-sm">Timings</Label>
                <div className="grid grid-cols-2 gap-2 sm:gap-3">
                  <CheckboxField
                    label="Morning"
                    checked={!!watch("timing_morning")}
                    onCheckedChange={(checked) =>
                      setValue("timing_morning", !!checked)
                    }
                  />
                  <CheckboxField
                    label="Afternoon"
                    checked={!!watch("timing_afternoon")}
                    onCheckedChange={(checked) =>
                      setValue("timing_afternoon", !!checked)
                    }
                  />
                  <CheckboxField
                    label="Evening"
                    checked={!!watch("timing_evening")}
                    onCheckedChange={(checked) =>
                      setValue("timing_evening", !!checked)
                    }
                  />
                  <CheckboxField
                    label="Night"
                    checked={!!watch("timing_night")}
                    onCheckedChange={(checked) =>
                      setValue("timing_night", !!checked)
                    }
                  />
                </div>
              </div>

              {/* Meal */}
              <RadioField
                label="Meal *"
                value={meal || ""}
                onChange={(value) =>
                  setValue("meal", value as "before_meal" | "after_meal", {
                    shouldValidate: true,
                  })
                }
                options={mealOptions}
                direction="row"
                error={errors.meal?.message}
              />

              {/* Dates */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                <div className="space-y-1.5 sm:space-y-2">
                  <Label className="text-xs sm:text-sm">Start Date</Label>
                  <Input
                    type="date"
                    min={getTodayDate()}
                    value={startDate}
                    onChange={(e) => setStartDate(e.target.value)}
                    className="h-9 sm:h-10 text-xs sm:text-sm"
                  />
                </div>

                <div className="space-y-1.5 sm:space-y-2">
                  <Label className="text-xs sm:text-sm">End Date</Label>
                  <Input
                    type="date"
                    min={startDate || getTodayDate()}
                    value={endDate}
                    onChange={(e) => setEndDate(e.target.value)}
                    className="h-9 sm:h-10 text-xs sm:text-sm"
                  />
                </div>
              </div>

              {/* Stamp preference */}
              <div className="space-y-1.5 sm:space-y-2">
                <Label className="text-xs sm:text-sm">Stamp Preference *</Label>
                <Select
                  value={watch("stamp_preference")}
                  onValueChange={(value) =>
                    setValue("stamp_preference", value, {
                      shouldValidate: true,
                    })
                  }
                >
                  <SelectTrigger className="h-9 sm:h-10 text-xs sm:text-sm">
                    <SelectValue placeholder="Select stamp preference" />
                  </SelectTrigger>
                  <SelectContent>
                    {stampOptions.map((item) => (
                      <SelectItem key={item.value} value={item.value} className="text-xs sm:text-sm">
                        {item.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {errors.stamp_preference && (
                  <p className="text-[11px] sm:text-sm text-red-500">
                    {errors.stamp_preference.message}
                  </p>
                )}
              </div>

              {/* Notes */}
              <div className="space-y-1.5 sm:space-y-2">
                <Label className="text-xs sm:text-sm">Notes</Label>
                <Textarea
                  placeholder="Write instructions..."
                  value={watch("instructions") || ""}
                  onChange={(e) =>
                    setValue("instructions", e.target.value, {
                      shouldValidate: true,
                    })
                  }
                  rows={4}
                  className="text-xs sm:text-sm"
                />
              </div>

              <Button
                type="submit"
                className="w-full h-9 sm:h-10 text-xs sm:text-sm"
                disabled={addPrescription.isPending}
              >
                {addPrescription.isPending ? "Saving..." : "Add Prescription"}
              </Button>
            </form>
          </div>
        </DialogContent>
      </Dialog>

      <SuccessDialog open={showSuccess} onClose={handleSuccessClose} />
    </>
  );
}

function CheckboxField({
  label,
  checked,
  onCheckedChange,
}: {
  label: string;
  checked: boolean;
  onCheckedChange: (checked: boolean) => void;
}) {
  return (
    <div className="flex items-center space-x-2 rounded-md border p-2 sm:p-3">
      <Checkbox checked={checked} onCheckedChange={onCheckedChange} className="h-3 w-3 sm:h-4 sm:w-4" />
      <Label className="text-[10px] sm:text-sm">{label}</Label>
    </div>
  );
}

function SuccessDialog({
  open,
  onClose,
}: {
  open: boolean;
  onClose: () => void;
}) {
  return (
    <Dialog open={open} onOpenChange={onClose}>
      <DialogContent className="w-[90vw] max-w-sm rounded-xl">
        <DialogHeader>
          <DialogTitle className="text-base sm:text-lg">Prescription Added</DialogTitle>
        </DialogHeader>
        <p className="text-xs sm:text-sm text-muted-foreground">
          The prescription has been added successfully.
        </p>
        <Button onClick={onClose} className="mt-4 w-full h-9 sm:h-10 text-xs sm:text-sm">
          OK
        </Button>
      </DialogContent>
    </Dialog>
  );
}

function getTodayDate() {
  return new Date().toISOString().split("T")[0];
}

function getDosageOptions(type: string) {
  const t = (type || "").toLowerCase();

  if (t.includes("tablet") || t.includes("capsule")) {
    return [
      { label: "½ Tablet", value: "0.5 tablet" },
      { label: "1 Tablet", value: "1 tablet" },
      { label: "1½ Tablets", value: "1.5 tablets" },
      { label: "2 Tablets", value: "2 tablets" },
      { label: "3 Tablets", value: "3 tablets" },
    ];
  }

  if (
    t.includes("liquid") ||
    t.includes("syrup") ||
    t.includes("suspension") ||
    t.includes("solution")
  ) {
    return [
      { label: "2.5 ml (½ spoon)", value: "2.5 ml" },
      { label: "5 ml (1 spoon)", value: "5 ml" },
      { label: "10 ml (2 spoons)", value: "10 ml" },
      { label: "15 ml (3 spoons)", value: "15 ml" },
      { label: "20 ml (4 spoons)", value: "20 ml" },
    ];
  }

  if (t.includes("drop")) {
    return [
      { label: "1 Drop", value: "1 drop" },
      { label: "2 Drops", value: "2 drops" },
      { label: "3 Drops", value: "3 drops" },
      { label: "4 Drops", value: "4 drops" },
    ];
  }

  if (t.includes("cream") || t.includes("ointment") || t.includes("gel")) {
    return [
      { label: "Thin layer", value: "thin layer" },
      { label: "Pea-sized amount", value: "pea-sized amount" },
      { label: "As prescribed", value: "as prescribed" },
    ];
  }

  return [
    { label: "1 Unit", value: "1 unit" },
    { label: "2 Units", value: "2 units" },
    { label: "As prescribed", value: "as prescribed" },
  ];
}