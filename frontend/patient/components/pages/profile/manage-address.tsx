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

    // ✅ FORM STATE
    const [formData, setFormData] = useState({
        address: "",
        area: "",
        landmark: "",
        city: "",
        state: "",
        pincode: "",
    });

    const [loading, setLoading] = useState(false);

    // ✅ DIALOG STATE
    const [dialogOpen, setDialogOpen] = useState(false);
    const [dialogMessage, setDialogMessage] = useState("");
    const [dialogType, setDialogType] = useState<"success" | "danger">("success");

    // ✅ USER DATA SYNC
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

    // ✅ HANDLE CHANGE
    const handleChange = (field: string, value: string) => {
        setFormData((prev) => ({ ...prev, [field]: value }));
    };

    // ✅ SAVE FUNCTION
    const handleSave = async () => {
        try {
            setLoading(true);

            const payload = {
                group: "address", // 🔥 VERY IMPORTANT
                address: formData.address,
                area: formData.area,
                landmark: formData.landmark,
                city: formData.city,
                state: formData.state,
                pincode: formData.pincode,
            };

            const response = await updatePatientPersonalInfo(user.id, payload);


            // ✅ CONTEXT UPDATE (CORRECT FIX)
            updateUser({
                ...user,
                address: {
                    ...user?.address,
                    ...response,
                },
            });

            // ✅ INSTANT UI UPDATE
            setFormData((prev) => ({
                ...prev,
                ...response,
            }));

            // ✅ SUCCESS DIALOG
            setDialogType("success");
            setDialogMessage("Address updated successfully!");
            setDialogOpen(true);

        } catch (err: any) {
            console.error("Error updating address:", err);

            const errorMsg =
                err?.response?.data?.errors?.message ||
                err?.response?.data?.message ||
                "Something went wrong";

            // ❌ ERROR DIALOG
            setDialogType("danger");
            setDialogMessage(errorMsg);
            setDialogOpen(true);

        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="w-full mt-7">

            <div className="space-y-6">

                {/* Address */}
                <div className="space-y-2">
                    <Label className="text-sm text-primary font-bold">
                        House / Floor / Flat Number
                    </Label>
                    <Input
                        value={formData.address}
                        onChange={(e) => handleChange("address", e.target.value)}
                        className="h-11 rounded-lg bg-gray-50 border-primary"
                    />
                </div>

                {/* Area */}
                <div className="space-y-2">
                    <Label className="text-sm text-primary font-bold">Area</Label>
                    <Input
                        value={formData.area}
                        onChange={(e) => handleChange("area", e.target.value)}
                        className="h-11 rounded-lg bg-gray-50 border-primary"
                    />
                </div>

                {/* Landmark */}
                <div className="space-y-2">
                    <Label className="text-sm text-primary font-bold">Landmark</Label>
                    <Input
                        value={formData.landmark}
                        onChange={(e) => handleChange("landmark", e.target.value)}
                        className="h-11 rounded-lg bg-gray-50 border-primary"
                    />
                </div>

                {/* Grid */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div className="space-y-2">
                        <Label className="text-sm text-primary font-bold">Pincode</Label>
                        <Input
                            value={formData.pincode}
                            onChange={(e) => handleChange("pincode", e.target.value)}
                            className="h-11 rounded-lg bg-gray-50 border-primary"
                        />
                    </div>

                    <div className="space-y-2">
                        <Label className="text-sm text-primary font-bold">City</Label>
                        <Input
                            value={formData.city}
                            onChange={(e) => handleChange("city", e.target.value)}
                            className="h-11 rounded-lg bg-gray-50 border-primary"
                        />
                    </div>

                </div>

                {/* State */}
                <div className="space-y-2">
                    <Label className="text-sm text-primary font-bold">State</Label>
                    <Input
                        value={formData.state}
                        onChange={(e) => handleChange("state", e.target.value)}
                        className="h-11 rounded-lg bg-gray-50 border-primary"
                    />
                </div>

                {/* Button */}
                <div className="flex justify-end">
                    <Button
                        className="py-6 px-4 font-bold text-sm"
                        onClick={handleSave}
                        disabled={loading}
                    >
                        {loading ? "Saving..." : "Save"}
                    </Button>
                </div>

            </div>

            {/* ✅ DIALOG */}
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