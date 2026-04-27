// "use client";

// import { useState } from "react";
// import { useForm, FormProvider } from "react-hook-form";
// import { z } from "zod";
// import { zodResolver } from "@hookform/resolvers/zod";
// import { X, Calendar } from "lucide-react";

// import {
//   Dialog,
//   DialogContent,
//   DialogHeader,
//   DialogTitle,
// } from "@/components/ui/dialog";
// import { Button } from "@/components/ui/button";
// import { Input } from "@/components/ui/input";
// import { Label } from "@/components/ui/label";
// import { Textarea } from "@/components/ui/textarea";
// import SelectField from "@/components/custom/SelectField";

// const ConclusionSchema = z.object({
//   conclusion_notes: z.string().min(1, "Conclusion notes are required"),
//   next_visit_date: z.string().min(1, "Next visit date is required"),
//   report_type: z.string().optional(),
// });

// export type ConclusionForm = z.infer<typeof ConclusionSchema>;

// interface AddConclusionDialogProps {
//   open: boolean;
//   onOpenChange: (open: boolean) => void;
// }

// export default function AddConclusionDialog({
//   open,
//   onOpenChange,
// }: AddConclusionDialogProps) {
//   const [showSuccess, setShowSuccess] = useState(false);

//   const methods = useForm<ConclusionForm>({
//     resolver: zodResolver(ConclusionSchema),
//     defaultValues: {
//       conclusion_notes: "",
//       next_visit_date: "",
//       report_type: "",
//     },
//   });

//   const {
//     handleSubmit,
//     reset,
//     watch,
//     formState: { errors },
//   } = methods;

//   const onSubmit = (data: ConclusionForm) => {
//     // TODO: Implement API call to save conclusion
//     console.log("Conclusion data:", data);
    
//     // Show success dialog
//     setShowSuccess(true);
//   };

//   const handleSuccessClose = () => {
//     setShowSuccess(false);
//     reset();
//     onOpenChange(false);
//   };

//   const handleClose = () => {
//     reset();
//     onOpenChange(false);
//   };

//   return (
//     <>
//       <Dialog open={open} onOpenChange={onOpenChange}>
//         <DialogContent className="w-[95vw] max-w-lg rounded-xl">
//           <DialogHeader className="border-b pb-4">
//             <div className="flex items-center justify-between">
//               <DialogTitle className="text-lg font-semibold">
//                 Consultation Conclusion
//               </DialogTitle>
//             </div>
//           </DialogHeader>

//           <div>
//             <FormProvider {...methods}>
//               <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
//                 {/* Conclusion Notes */}
//                 <div className="space-y-2">
//                   <Label className="text-sm font-medium">Conclusion Notes</Label>
//                   <Textarea
//                     placeholder="Enter your summary or conclusion here..."
//                     value={watch("conclusion_notes") || ""}
//                     onChange={(e) =>
//                       methods.setValue("conclusion_notes", e.target.value, {
//                         shouldValidate: true,
//                       })
//                     }
//                     rows={4}
//                     className="resize-none"
//                   />
//                   {errors.conclusion_notes && (
//                     <p className="text-sm text-red-500">
//                       {errors.conclusion_notes.message}
//                     </p>
//                   )}
//                 </div>

//                 {/* Report Type */}
//                 <SelectField
//                   name="report_type"
//                   label="Report Type"
//                   placeholder="Select report type"
//                   options={[
//                     { value: "other", label: "Other" },
//                     { value: "medical-report", label: "Medical Report" },
//                   ]}
//                 />

//                 {/* Next Visit Date */}
//                 <div className="space-y-2">
//                   <Label className="text-sm font-medium">Next Visit Date</Label>
//                   <div className="relative">
//                     <Input
//                       type="date"
//                       value={watch("next_visit_date") || ""}
//                       onChange={(e) =>
//                         methods.setValue("next_visit_date", e.target.value, {
//                           shouldValidate: true,
//                         })
//                       }
//                     />
//                   </div>
//                   {errors.next_visit_date && (
//                     <p className="text-sm text-red-500">
//                       {errors.next_visit_date.message}
//                     </p>
//                   )}
//                 </div>

//                 {/* Submit Button */}
//                 <Button className="w-full" onClick={handleSubmit(onSubmit)}>
//                   Submit Conclusion
//                 </Button>
//               </form>
//             </FormProvider>
//           </div>
//         </DialogContent>
//       </Dialog>

//       <SuccessDialog open={showSuccess} onClose={handleSuccessClose} />
//     </>
//   );
// }

// function SuccessDialog({
//   open,
//   onClose,
// }: {
//   open: boolean;
//   onClose: () => void;
// }) {
//   return (
//     <Dialog open={open} onOpenChange={onClose}>
//       <DialogContent className="w-[90vw] max-w-sm rounded-xl">
//         <DialogHeader>
//           <DialogTitle className="text-lg font-semibold">Conclusion Submitted</DialogTitle>
//         </DialogHeader>
//         <p className="text-sm text-muted-foreground">
//           The consultation conclusion has been submitted successfully.
//         </p>
//         <Button onClick={onClose} className="mt-4 w-full h-10">
//           OK
//         </Button>
//       </DialogContent>
//     </Dialog>
//   );
// }

"use client";

import { useState } from "react";
import { useForm, FormProvider } from "react-hook-form";
import { z } from "zod";
import { zodResolver } from "@hookform/resolvers/zod";

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
import SelectField from "@/components/custom/SelectField";
import { useSubmitConclusion } from "@/mutations/useSubmitConclusion";

const ConclusionSchema = z.object({
  instructions_by_doctor: z
    .string()
    .min(1, "Conclusion notes are required"),
  next_visit_date: z
    .string()
    .min(1, "Next visit date is required"),
  type: z.string().optional(),
  files: z.array(z.instanceof(File)).optional(),
});

export type ConclusionForm = z.infer<typeof ConclusionSchema>;

interface AddConclusionDialogProps {
  appointmentId: string;
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

export default function AddConclusionDialog({
  appointmentId,
  open,
  onOpenChange,
}: AddConclusionDialogProps) {
  const [showSuccess, setShowSuccess] = useState(false);
  const { mutate, isPending } = useSubmitConclusion();

  const methods = useForm<ConclusionForm>({
    resolver: zodResolver(ConclusionSchema),
    defaultValues: {
      instructions_by_doctor: "",
      next_visit_date: "",
      type: "",
      files: [],
    },
  });

  const {
    handleSubmit,
    reset,
    watch,
    setValue,
    formState: { errors },
  } = methods;

  const onSubmit = (formData: ConclusionForm) => {
    mutate(
      {
        appointmentId,
        instructions_by_doctor: formData.instructions_by_doctor,
        next_visit_date: formData.next_visit_date,
        type: formData.type || undefined,
        files: formData.files || [],
      },
      {
        onSuccess: () => {
          setShowSuccess(true);
        },
        onError: (error) => {
          console.error("Submit conclusion error:", error);
        },
      }
    );
  };

  const handleSuccessClose = () => {
    setShowSuccess(false);
    reset();
    onOpenChange(false);
  };

  const handleClose = () => {
    reset();
    onOpenChange(false);
  };

  return (
    <>
      <Dialog
        open={open}
        onOpenChange={(value) => {
          if (!value) handleClose();
          else onOpenChange(value);
        }}
      >
        <DialogContent className="w-[95vw] max-w-lg rounded-xl">
          <DialogHeader className="border-b pb-4">
            <div className="flex items-center justify-between">
              <DialogTitle className="text-lg font-semibold">
                Consultation Conclusion
              </DialogTitle>
            </div>
          </DialogHeader>

          <div>
            <FormProvider {...methods}>
              <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
                <div className="space-y-2">
                  <Label className="text-sm font-medium">
                    Conclusion Notes
                  </Label>
                  <Textarea
                    placeholder="Enter your summary or conclusion here..."
                    value={watch("instructions_by_doctor") || ""}
                    onChange={(e) =>
                      setValue("instructions_by_doctor", e.target.value, {
                        shouldValidate: true,
                      })
                    }
                    rows={4}
                    className="resize-none"
                  />
                  {errors.instructions_by_doctor && (
                    <p className="text-sm text-red-500">
                      {errors.instructions_by_doctor.message}
                    </p>
                  )}
                </div>

                <SelectField
                  name="type"
                  label="Report Type"
                  placeholder="Select report type"
                  options={[
                    { value: "other", label: "Other" },
                    { value: "medical-report", label: "Medical Report" },
                  ]}
                />

                {watch("type") && (
                  <div className="space-y-2">
                    <Label className="text-sm font-medium">
                      Upload Files (Optional)
                    </Label>
                    <Input
                      type="file"
                      accept=".jpg,.jpeg,.png,.pdf"
                      multiple
                      onChange={(e) => {
                        const selectedFiles = Array.from(e.target.files || []);
                        setValue("files", selectedFiles, {
                          shouldValidate: true,
                        });
                      }}
                    />
                  </div>
                )}

                <div className="space-y-2">
                  <Label className="text-sm font-medium">Next Visit Date</Label>
                  <Input
                    type="date"
                    value={watch("next_visit_date") || ""}
                    onChange={(e) =>
                      setValue("next_visit_date", e.target.value, {
                        shouldValidate: true,
                      })
                    }
                  />
                  {errors.next_visit_date && (
                    <p className="text-sm text-red-500">
                      {errors.next_visit_date.message}
                    </p>
                  )}
                </div>

                <Button
                  type="submit"
                  className="w-full"
                  disabled={isPending}
                >
                  {isPending ? "Submitting..." : "Submit Conclusion"}
                </Button>
              </form>
            </FormProvider>
          </div>
        </DialogContent>
      </Dialog>

      <SuccessDialog open={showSuccess} onClose={handleSuccessClose} />
    </>
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
          <DialogTitle className="text-lg font-semibold">
            Conclusion Submitted
          </DialogTitle>
        </DialogHeader>
        <p className="text-sm text-muted-foreground">
          The consultation conclusion has been submitted successfully.
        </p>
        <Button onClick={onClose} className="mt-4 w-full h-10">
          OK
        </Button>
      </DialogContent>
    </Dialog>
  );
}