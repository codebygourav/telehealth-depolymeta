"use client";

import { useState } from "react";
import { useForm, FormProvider } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { useApplyLeave } from "@/mutations/useApplyLeave";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { ChevronRight } from "lucide-react";
import SelectField from "@/components/custom/SelectField";
import InputField from "@/components/custom/inputfield";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";

const leaveSchema = z.object({
    leaveType: z.string().min(1, "Leave type is required"),
    startDate: z.string().min(1, "Start date is required"),
    endDate: z.string().min(1, "End date is required"),
    reason: z.string().min(1, "Reason is required"),
});

type LeaveFormValues = z.infer<typeof leaveSchema>;

interface ApplyLeaveModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export function ApplyLeaveModal({ open, onOpenChange }: ApplyLeaveModalProps) {
    const { mutate: applyLeave, isPending } = useApplyLeave();
    const [submitError, setSubmitError] = useState<string | null>(null);

    const methods = useForm<LeaveFormValues>({
        resolver: zodResolver(leaveSchema),
        defaultValues: {
            leaveType: "",
            startDate: "",
            endDate: "",
            reason: "",
        },
    });

    const { handleSubmit, reset, register, formState: { errors } } = methods;

    const onSubmit = (data: LeaveFormValues) => {
        setSubmitError(null);
        const typeMap: Record<string, string> = {
            annual_vacation: "annual",
            sick_leave: "sick",
            casual_leave: "casual",
        };

        applyLeave(
            {
                type: typeMap[data.leaveType] || data.leaveType,
                start_date: data.startDate,
                end_date: data.endDate,
                reason: data.reason,
            },
            {
                onSuccess: () => {
                    reset();
                    onOpenChange(false);
                },
                onError: (err: any) => {
                    const errorData = err?.response?.data;
                    setSubmitError(
                        errorData?.errors?.message || err.message || "Failed to apply leave. Please try again."
                    );
                },
            }
        );
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle className="text-xl font-bold">Apply Leave</DialogTitle>
                </DialogHeader>

                <div className="max-h-[80vh] overflow-y-auto pt-4">
                    <FormProvider {...methods}>
                        <form onSubmit={handleSubmit(onSubmit)} className="space-y-5">
                            {submitError && (
                                <div className="text-sm font-medium text-destructive bg-destructive/10 p-3 rounded-md">
                                    {submitError}
                                </div>
                            )}

                            <SelectField
                                name="leaveType"
                                label="Leave Type"
                                placeholder="Select Leave Type"
                                required
                                options={[
                                    { label: "Annual Leave", value: "annual" },
                                    { label: "Sick Leave", value: "sick" },
                                    { label: "Casual Leave", value: "casual" },
                                    { label: "Telehealth Leave", value: "telehealth" },
                                ]}
                            />

                            <InputField
                                name="startDate"
                                label="Start Date"
                                type="date"
                                required
                            />

                            <InputField
                                name="endDate"
                                label="End Date"
                                type="date"
                                required
                            />

                            <div className="space-y-2">
                                <Label className="text-sm font-medium">
                                    Reason for Leave<span className="text-red-500 ml-1">*</span>
                                </Label>
                                <Textarea
                                    {...register("reason")}
                                    placeholder="Write your reason here..."
                                    className={errors.reason ? "border-red-500" : ""}
                                />
                                {errors.reason && (
                                    <p className="text-sm text-red-500">
                                        {errors.reason.message}
                                    </p>
                                )}
                            </div>

                            <Button
                                type="submit"
                                className="w-full mt-2 cursor-pointer py-2.5 h-auto "
                                disabled={isPending}
                            >
                                {isPending ? "Applying..." : "Apply Leave"}
                            </Button>
                        </form>
                    </FormProvider>
                </div>
            </DialogContent>
        </Dialog>
    );
}
