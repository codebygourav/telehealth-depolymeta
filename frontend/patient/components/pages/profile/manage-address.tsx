"use client";

import { useEffect, useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { updatePatientPersonalInfo } from "@/mutations/profile-update";
import { useAuth } from "@/context/userContext";
import CustomDialog from "@/components/custom/Dialogboxs";
import { CheckCircle, XCircle } from "lucide-react";

interface ManageAddressFormProps {
    user: any;
}

export default function ManageAddressForm({ user }: ManageAddressFormProps) {
    const { updateUser } = useAuth();

    const [formData, setFormData] = useState({
        address: "",
        area: "",
        landmark: "",
        city: "",
        state: "",
        pincode: "",
    });

    const [loading, setLoading] = useState(false);
    const [dialogOpen, setDialogOpen] = useState(false);
    const [dialogMessage, setDialogMessage] = useState("");
    const [dialogType, setDialogType] = useState<"success" | "danger">("success");

    useEffect(() => {
        if (user?.address) {
            setFormData({
                address: user.address.address || "",
                area: user.address.area || "",
                landmark: user.address.landmark || "",
                city: user.address.city || "",
                state: user.address.state || "",
                pincode: user.address.pincode || "",
            });
        }
    }, [user]);

    const handleChange = (field: string, value: string) => {
        setFormData((prev) => ({ ...prev, [field]: value }));
    };

    const handleSave = async () => {
        try {
            setLoading(true);

            const payload = {
                group: "address",
                address: formData.address,
                area: formData.area,
                landmark: formData.landmark,
                city: formData.city,
                state: formData.state,
                pincode: formData.pincode,
            };

            const response = await updatePatientPersonalInfo(user.id, payload);

            updateUser({
                ...user,
                address: {
                    ...user?.address,
                    ...response,
                },
            });

            setDialogType("success");
            setDialogMessage("Address updated successfully!");
            setDialogOpen(true);

        } catch (err: any) {
            console.error("Error updating address:", err);
            const errorMsg =
                err?.response?.data?.errors?.message ||
                err?.response?.data?.message ||
                "Something went wrong";

            setDialogType("danger");
            setDialogMessage(errorMsg);
            setDialogOpen(true);
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="space-y-8 animate-in fade-in duration-500">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-2 md:col-span-2">
                    <Label className="text-sm font-medium text-slate-700 dark:text-slate-300">
                        House / Floor / Flat Number
                    </Label>
                    <Input
                        value={formData.address}
                        onChange={(e) => handleChange("address", e.target.value)}
                        className="h-11 rounded-xl border-slate-200 dark:border-slate-700 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Enter house/flat number"
                    />
                </div>

                <div className="space-y-2">
                    <Label className="text-sm font-medium text-slate-700 dark:text-slate-300">Area</Label>
                    <Input
                        value={formData.area}
                        onChange={(e) => handleChange("area", e.target.value)}
                        className="h-11 rounded-xl border-slate-200 dark:border-slate-700 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Enter area"
                    />
                </div>

                <div className="space-y-2">
                    <Label className="text-sm font-medium text-slate-700 dark:text-slate-300">Landmark</Label>
                    <Input
                        value={formData.landmark}
                        onChange={(e) => handleChange("landmark", e.target.value)}
                        className="h-11 rounded-xl border-slate-200 dark:border-slate-700 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Enter landmark"
                    />
                </div>

                <div className="space-y-2">
                    <Label className="text-sm font-medium text-slate-700 dark:text-slate-300">Pincode</Label>
                    <Input
                        value={formData.pincode}
                        onChange={(e) => handleChange("pincode", e.target.value)}
                        className="h-11 rounded-xl border-slate-200 dark:border-slate-700 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Enter pincode"
                    />
                </div>

                <div className="space-y-2">
                    <Label className="text-sm font-medium text-slate-700 dark:text-slate-300">City</Label>
                    <Input
                        value={formData.city}
                        onChange={(e) => handleChange("city", e.target.value)}
                        className="h-11 rounded-xl border-slate-200 dark:border-slate-700 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Enter city"
                    />
                </div>

                <div className="space-y-2 md:col-span-2">
                    <Label className="text-sm font-medium text-slate-700 dark:text-slate-300">State</Label>
                    <Input
                        value={formData.state}
                        onChange={(e) => handleChange("state", e.target.value)}
                        className="h-11 rounded-xl border-slate-200 dark:border-slate-700 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Enter state"
                    />
                </div>
            </div>

            <div className="flex justify-end pt-4">
                <Button
                    className="btn-primary-cta"
                    onClick={handleSave}
                    disabled={loading}
                >
                    {loading ? "Saving..." : "Save"}
                </Button>
            </div>

            <CustomDialog
                open={dialogOpen}
                onClose={() => setDialogOpen(false)}
                icon={
                    dialogType === "success"
                        ? <CheckCircle className="text-green-600 w-6 h-6" />
                        : <XCircle className="text-red-600 w-6 h-6" />
                }
                title={dialogType === "success" ? "Success" : "Error"}
                description={dialogMessage}
                confirmText="OK"
                onConfirm={() => setDialogOpen(false)}
                type={dialogType}
            />
        </div>
    );
}