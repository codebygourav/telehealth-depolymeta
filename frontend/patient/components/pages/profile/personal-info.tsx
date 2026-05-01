"use client";

import { useEffect, useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import { Camera, CheckCircle, XCircle, Calendar } from "lucide-react";
import { updatePatientPersonalInfo } from "@/mutations/profile-update";
import { useAuth } from "@/context/userContext";
import CustomDialog from "@/components/custom/Dialogboxs";

interface PersonalInfoFormProps {
    user: any;
}

export default function PersonalInfoForm({ user }: PersonalInfoFormProps) {
    const { updateUser } = useAuth();

    const [formData, setFormData] = useState({
        first_name: user?.first_name || "",
        last_name: user?.last_name || "",
        email: user?.email || "",
        mobile_no: String(user?.mobile_no || ""),
        date_of_birth: user?.date_of_birth || "",
        bio: user?.bio || "",
    });
    const [dialogOpen, setDialogOpen] = useState(false);
    const [dialogMessage, setDialogMessage] = useState("");
    const [dialogType, setDialogType] = useState<"success" | "danger">("success");
    const [avatarFile, setAvatarFile] = useState<File | null>(null);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        if (user) {
            setFormData({
                first_name: user.first_name || "",
                last_name: user.last_name || "",
                email: user.email || "",
                mobile_no: user.mobile_no || "",
                date_of_birth: user.date_of_birth || "",
                bio: user.bio || "",
            });
        }
    }, [user]);

    const handleAvatarChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files?.[0]) {
            setAvatarFile(e.target.files[0]);
        }
    };

    const handleChange = (field: string, value: string) => {
        setFormData((prev) => ({ ...prev, [field]: value }));
    };

    const formatDate = (date: string) => {
        if (!date) return "";
        if (date.includes("-") && date.split("-")[0].length === 4) {
            return date;
        }
        const [day, month, year] = date.split("-");
        return `${year}-${month}-${day}`;
    };

    const handleSave = async () => {
        try {
            setLoading(true);

            const payload: any = {
                group: "personal_information",
                first_name: formData.first_name,
                last_name: formData.last_name,
                mobile_no: String(formData.mobile_no),
                date_of_birth: formatDate(formData.date_of_birth),
            };

            if (formData.bio && formData.bio.trim() !== "") {
                payload.bio = formData.bio;
            }

            if (avatarFile instanceof File) {
                payload.avatar = avatarFile;
            }

            const response = await updatePatientPersonalInfo(user.id, payload);

            await updateUser({
                ...user,
                ...response,
            });

            setDialogType("success");
            setDialogMessage("Profile updated successfully");
            setDialogOpen(true);

        } catch (err: any) {
            console.error("Error:", err);
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
        <form className="space-y-8 animate-in fade-in duration-500" onSubmit={(e) => e.preventDefault()}>
            <div className="space-y-8">
                {/* Profile Section */}
                <div className="flex items-center gap-6">
                    <div className="relative">
                        <img
                            src={
                                avatarFile
                                    ? URL.createObjectURL(avatarFile)
                                    : user?.avatar || "https://api.dicebear.com/7.x/avataaars/svg?seed=Felix"
                            }
                            alt="Profile"
                            className="w-[105px] h-[105px] rounded-full object-cover shrink-0"
                        />
                        <button
                            type="button"
                            onClick={() => document.getElementById("avatarInput")?.click()}
                            className="absolute bottom-3 right-0 p-1.5 bg-white dark:bg-slate-700 rounded-full border border-slate-200 dark:border-slate-600 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-600 transition-colors"
                        >
                            <Camera className="w-4 h-4 text-primary" />
                        </button>
                    </div>

                    <div className="space-y-1">
                        <h3 className="font-semibold text-slate-900 dark:text-white">Profile Picture</h3>
                        <p className="text-sm text-slate-500 dark:text-slate-400">
                            PNG, JPG or GIF. Max size of 1MB.
                        </p>
                        <div className="flex gap-4 mt-2">
                            <input
                                type="file"
                                accept="image/*"
                                onChange={handleAvatarChange}
                                className="hidden"
                                id="avatarInput"
                            />
                            <button
                                type="button"
                                onClick={() => document.getElementById("avatarInput")?.click()}
                                className="text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-blue-600 transition-colors bg-slate-100 dark:bg-slate-800 px-3 py-1.5 global-radius border border-slate-200 dark:border-slate-700"
                            >
                                Select Image
                            </button>
                            <button
                                type="button"
                                onClick={() => setAvatarFile(null)}
                                className="text-sm font-medium text-red-500 hover:text-red-600 transition-colors"
                            >
                                Remove
                            </button>
                        </div>
                    </div>
                </div>

                {/* Form Fields - Row 1 */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div className="space-y-2">
                        <Label className="text-sm font-medium text-slate-700 dark:text-slate-300">First Name</Label>
                        <Input
                            value={formData.first_name}
                            onChange={(e) => handleChange("first_name", e.target.value)}
                            className="h-11 global-radius-10 border-slate-200 dark:border-slate-700 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Enter first name"
                        />
                    </div>
                    <div className="space-y-2">
                        <Label className="text-sm font-medium text-slate-700 dark:text-slate-300">Last Name</Label>
                        <Input
                            value={formData.last_name}
                            onChange={(e) => handleChange("last_name", e.target.value)}
                            className="h-11 global-radius-10 border-slate-200 dark:border-slate-700 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Enter last name"
                        />
                    </div>
                    <div className="space-y-2">
                        <Label className="text-sm font-medium text-slate-700 dark:text-slate-300">Email Address</Label>
                        <Input
                            type="email"
                            value={formData.email}
                            disabled
                            className="h-11 global-radius-10 bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 text-slate-500 cursor-not-allowed"
                        />
                    </div>
                </div>

                {/* Form Fields - Row 2 */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="space-y-2">
                        <Label className="text-sm font-medium text-slate-700 dark:text-slate-300">Phone Number</Label>
                        <Input
                            type="tel"
                            value={formData.mobile_no}
                            disabled
                            className="h-11 global-radius-10 bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 text-slate-500 cursor-not-allowed"
                        />
                    </div>
                    <div className="space-y-2">
                        <Label className="text-sm font-medium text-slate-700 dark:text-slate-300">Date of Birth</Label>
                        <div className="relative">
                            <Input
                                type="date"
                                value={formData.date_of_birth}
                                disabled
                                className="h-11 global-radius-10 bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 text-slate-500 cursor-not-allowed pr-10"
                            />
                            <Calendar className="absolute right-3 top-3 w-5 h-5 text-slate-400" />
                        </div>
                    </div>
                </div>

                {/* Short Bio */}
                <div className="space-y-2">
                    <Label className="text-sm font-medium text-slate-700 dark:text-slate-300">Short Bio</Label>
                    <Textarea
                        rows={4}
                        value={formData.bio}
                        onChange={(e) => handleChange("bio", e.target.value)}
                        className="global-radius-10 border-slate-200 dark:border-slate-700 focus:ring-blue-500 focus:border-blue-500 resize-none"
                        placeholder="Tell us a little bit about yourself"
                    />
                </div>

                {/* Actions */}
                <div className="flex justify-end pt-4">
                    <Button
                        className="btn-primary-cta"
                        onClick={handleSave}
                        disabled={loading}
                    >
                        {loading ? "Saving..." : "Save"}
                    </Button>
                </div>
            </div>

            <CustomDialog
                open={dialogOpen}
                onClose={() => setDialogOpen(false)}
                icon={
                    dialogType === "success" ? (
                        <CheckCircle className="text-green-600 w-6 h-6" />
                    ) : (
                        <XCircle className="text-red-600 w-6 h-6" />
                    )
                }
                title={dialogType === "success" ? "Success" : "Error"}
                description={dialogMessage}
                confirmText="OK"
                onConfirm={() => setDialogOpen(false)}
                type={dialogType}
            />
        </form>
    );
}