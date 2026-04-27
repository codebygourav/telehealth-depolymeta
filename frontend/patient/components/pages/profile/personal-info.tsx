// "use client";

// import { useEffect, useState } from "react";
// import { Button } from "@/components/ui/button";
// import { Input } from "@/components/ui/input";
// import { Textarea } from "@/components/ui/textarea";
// import { Label } from "@/components/ui/label";
// import { Camera, CheckCircle, XCircle } from "lucide-react";
// import { updatePatientPersonalInfo } from "@/mutations/profile-update";
// import { useAuth } from "@/context/userContext";
// import CustomDialog from "@/components/custom/Dialogboxs";

// interface PersonalInfoFormProps {
//     user: any;
// }

// export default function PersonalInfoForm({ user }: PersonalInfoFormProps) {

//     const { updateUser } = useAuth();

//     const [formData, setFormData] = useState({
//         first_name: user?.first_name || "",
//         last_name: user?.last_name || "",
//         email: user?.email || "",
//         mobile_no: user?.mobile_no || "",
//         date_of_birth: user?.date_of_birth || "",
//         bio: user?.bio || "",
//         avatar: user?.avatar || "",
//     });
//     const [dialogOpen, setDialogOpen] = useState(false);
//     const [dialogMessage, setDialogMessage] = useState("");
//     const [dialogType, setDialogType] = useState<"success" | "danger">("success");

//     useEffect(() => {
//         if (user) {
//             setFormData({
//                 first_name: user.first_name || "",
//                 last_name: user.last_name || "",
//                 email: user.email || "",
//                 mobile_no: user.mobile_no || "",
//                 date_of_birth: user.date_of_birth || "",
//                 bio: user.bio || "",
//                 avatar: user?.avatar || "",

//             });
//         }
//     }, [user]);

//     const [avatarFile, setAvatarFile] = useState<File | null>(null);

//     const [loading, setLoading] = useState(false);

//     const handleAvatarChange = (e: React.ChangeEvent<HTMLInputElement>) => {
//         if (e.target.files?.[0]) {
//             setAvatarFile(e.target.files[0]);
//         }
//     };

//     const handleChange = (field: string, value: string) => {
//         setFormData((prev) => ({ ...prev, [field]: value }));
//     };

//     const formatDate = (date: string) => {
//         if (!date) return "";

//         // agar already YYYY-MM-DD hai to return
//         if (date.includes("-") && date.split("-")[0].length === 4) {
//             return date;
//         }

//         // convert DD-MM-YYYY → YYYY-MM-DD
//         const [day, month, year] = date.split("-");
//         return `${year}-${month}-${day}`;
//     };

//     const handleSave = async () => {
//         try {
//             setLoading(true);

//             const payload = {
//                 group: "personal_information",
//                 first_name: formData.first_name,
//                 last_name: formData.last_name,
//                 mobile_no: formData.mobile_no,
//                 bio: formData.bio,
//                 date_of_birth: formatDate(formData.date_of_birth),
//                 ...(avatarFile && { avatar: avatarFile }),
//             };

//             const response = await updatePatientPersonalInfo(user.id, payload);

//             await updateUser({
//                 ...user,
//                 ...response,
//             });

//             // ✅ success dialog
//             setDialogType("success");
//             setDialogMessage("Profile updated successfully");
//             setDialogOpen(true);

//         } catch (err: any) {
//             console.error("Error:", err);

//             const errorMsg =
//                 err?.response?.data?.errors?.message ||
//                 err?.response?.data?.message ||
//                 "Something went wrong";

//             setDialogType("danger");
//             setDialogMessage(errorMsg);
//             setDialogOpen(true);

//         } finally {
//             setLoading(false);
//         }
//     };

//     return (
//         <form className="space-y-10 animate-in fade-in duration-500 mt-7" onSubmit={(e) => e.preventDefault()}>
//             <div className="rounded-2xl">
//                 <div className="space-y-10">
//                     {/* Profile Section */}
//                     <div className="flex flex-col md:flex-row items-center gap-8 pb-10 border-primary border-b">
//                         <div className="relative group">
//                             <div className="h-32 w-32 rounded-3xl overflow-hidden ring-4 ring-primary/20 shadow-xl">
//                                 <img
//                                     src={
//                                         avatarFile
//                                             ? URL.createObjectURL(avatarFile)
//                                             : user?.avatar || "https://picsum.photos/seed/user_settings/400/400"
//                                     }
//                                     alt="Profile"
//                                     className="h-full w-full object-cover"
//                                 />
//                             </div>
//                         </div>

//                         <div className="text-center md:text-left">
//                             <h2 className="font-semibold text-xl text-primary">Profile Picture</h2>
//                             <p className="text-sm text-muted-foreground mt-1 mb-4">
//                                 PNG, JPG or GIF. Max size of 1MB.
//                             </p>
//                             <div className="flex gap-3 z-50 justify-center md:justify-start">
//                                 {/* Hidden file input */}
//                                 <input
//                                     type="file"
//                                     accept="image/*"
//                                     onChange={handleAvatarChange}
//                                     className="hidden"
//                                     id="avatarInput"
//                                 />

//                                 <Button
//                                     type="button"
//                                     variant="outline"
//                                     onClick={() => document.getElementById("avatarInput")?.click()}
//                                 >
//                                     Select Image
//                                 </Button>

//                                 {/* Remove button */}
//                                 <Button
//                                     type="button"
//                                     variant="ghost"
//                                     className="text-red-500"
//                                     onClick={() => setAvatarFile(null)}
//                                 >
//                                     Remove
//                                 </Button>
//                             </div>
//                         </div>
//                     </div>

//                     {/* Form Fields */}
//                     <div className="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-8">
//                         {[
//                             { label: "Full Name", fields: ["first_name", "last_name"], type: "text" },
//                             { label: "Email Address", fields: ["email"], type: "email", disabled: true },
//                             { label: "Phone Number", fields: ["mobile_no"], type: "tel" },
//                             { label: "Date of Birth", fields: ["date_of_birth"], type: "date" }
//                         ].map((field) => (
//                             <div key={field.label} className="space-y-2">
//                                 <Label className="text-xs uppercase tracking-widest text-primary font-bold">
//                                     {field.label}
//                                 </Label>
//                                 <Input
//                                     type={field.type}
//                                     value={
//                                         field.fields.length === 2
//                                             ? `${formData.first_name} ${formData.last_name}`
//                                             : formData[field.fields[0] as keyof typeof formData]
//                                     }
//                                     onChange={(e) => {
//                                         if (field.fields.length === 2) {
//                                             const [first, last] = e.target.value.split(" ");
//                                             handleChange("first_name", first || "");
//                                             handleChange("last_name", last || "");
//                                         } else {
//                                             handleChange(field.fields[0], e.target.value);
//                                         }
//                                     }}
//                                     disabled={field.disabled}  // ✅ ADD THIS
//                                     className="h-12 border-primary rounded-xl disabled:opacity-50 disabled:cursor-not-allowed"
//                                 />
//                             </div>
//                         ))}
//                         <div className="space-y-2 md:col-span-2">
//                             <Label className="text-xs uppercase tracking-widest text-primary font-bold">
//                                 Short Bio
//                             </Label>
//                             <Textarea
//                                 rows={3}
//                                 value={formData.bio}
//                                 onChange={(e) => handleChange("bio", e.target.value)}
//                                 className="rounded-xl border-primary"
//                             />
//                         </div>

//                     </div>

//                     {/* Actions */}
//                     <div className="flex items-center justify-end gap-4 pt-6">
//                         <Button variant="ghost">Discard</Button>
//                         <Button className="px-6" onClick={handleSave} disabled={loading}>
//                             {loading ? "Saving..." : "Save Changes"}
//                         </Button>
//                     </div>
//                 </div>
//             </div>

//             <CustomDialog
//                 open={dialogOpen}
//                 onClose={() => setDialogOpen(false)}
//                 icon={
//                     dialogType === "success" ? (
//                         <CheckCircle className="text-green-600 w-6 h-6" />
//                     ) : (
//                         <XCircle className="text-red-600 w-6 h-6" />
//                     )
//                 }
//                 title={dialogType === "success" ? "Success" : "Error"}
//                 description={dialogMessage}
//                 confirmText="OK"
//                 onConfirm={() => setDialogOpen(false)}
//                 type={dialogType}
//             />
//         </form>
//     );
// }


"use client";

import { useEffect, useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import { Camera, CheckCircle, XCircle } from "lucide-react";
import { updatePatientPersonalInfo } from "@/mutations/profile-update";
import { useAuth } from "@/context/userContext";
import CustomDialog from "@/components/custom/Dialogboxs";

interface PersonalInfoFormProps {
    user: any;
}

export default function PersonalInfoForm({ user }: PersonalInfoFormProps) {

    // console.log("user", user);

    const { updateUser } = useAuth();

    // console.log("update user" ,updateUser);


    const [formData, setFormData] = useState({
        first_name: user?.first_name || "",
        last_name: user?.last_name || "",
        email: user?.email || "",
        mobile_no: String(user?.mobile_no || ""),
        date_of_birth: user?.date_of_birth || "",
        bio: user?.bio || "", // ✅ always string
        // avatar: user?.avatar || "",
    });
    const [dialogOpen, setDialogOpen] = useState(false);
    const [dialogMessage, setDialogMessage] = useState("");
    const [dialogType, setDialogType] = useState<"success" | "danger">("success");

    useEffect(() => {
        if (user) {
            setFormData({
                first_name: user.first_name || "",
                last_name: user.last_name || "",
                email: user.email || "",
                mobile_no: user.mobile_no || "",
                date_of_birth: user.date_of_birth || "",
                bio: user.bio || "",
                // avatar: user?.avatar || "",

            });
        }
    }, [user]);

    const [avatarFile, setAvatarFile] = useState<File | null>(null);

    const [loading, setLoading] = useState(false);

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

        // agar already YYYY-MM-DD hai to return
        if (date.includes("-") && date.split("-")[0].length === 4) {
            return date;
        }

        // convert DD-MM-YYYY → YYYY-MM-DD
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
                mobile_no: String(formData.mobile_no), // ✅ ensure string
                      // ✅ ensure string
                date_of_birth: formatDate(formData.date_of_birth),
            };

            if (formData.bio && formData.bio.trim() !== "") {
                payload.bio = formData.bio;
            }

            // ✅ ONLY if file exists
            if (avatarFile instanceof File) {
                payload.avatar = avatarFile;
            }

            // console.log("avatarFile:", avatarFile);

            const response = await updatePatientPersonalInfo(user.id, payload);

            await updateUser({
                ...user,
                ...response,
            });

            // ✅ success dialog
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
        <form className="space-y-10 animate-in fade-in duration-500 mt-7" onSubmit={(e) => e.preventDefault()}>
            <div className="rounded-2xl">
                <div className="space-y-10">
                    {/* Profile Section */}
                    <div className="flex flex-col md:flex-row items-center gap-8 pb-10 border-primary border-b">
                        <div className="relative group">
                            <div className="h-32 w-32 rounded-3xl overflow-hidden ring-4 ring-primary/20 shadow-xl">
                                <img
                                    src={
                                        avatarFile
                                            ? URL.createObjectURL(avatarFile)
                                            : user?.avatar || "https://picsum.photos/seed/user_settings/400/400"
                                    }
                                    alt="Profile"
                                    className="h-full w-full object-cover"
                                />
                            </div>
                        </div>

                        <div className="text-center md:text-left">
                            <h2 className="font-semibold text-xl text-primary">Profile Picture</h2>
                            <p className="text-sm text-muted-foreground mt-1 mb-4">
                                PNG, JPG or GIF. Max size of 1MB.
                            </p>
                            <div className="flex gap-3 z-50 justify-center md:justify-start">
                                {/* Hidden file input */}
                                <input
                                    type="file"
                                    accept="image/*"
                                    onChange={handleAvatarChange}
                                    className="hidden"
                                    id="avatarInput"
                                />

                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => document.getElementById("avatarInput")?.click()}
                                >
                                    Select Image
                                </Button>

                                {/* Remove button */}
                                <Button
                                    type="button"
                                    variant="ghost"
                                    className="text-red-500"
                                    onClick={() => setAvatarFile(null)}
                                >
                                    Remove
                                </Button>
                            </div>
                        </div>
                    </div>

                    {/* Form Fields */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-8">
                        {[
                            { label: "First Name", name: "first_name", type: "text" },
                            { label: "Last Name", name: "last_name", type: "text" },
                            { label: "Email Address", name: "email", type: "email", disabled: true },
                            { label: "Phone Number", name: "mobile_no", type: "tel", disabled: true },
                            { label: "Date of Birth", name: "date_of_birth", type: "date", disabled: true },
                        ].map((field) => (
                            <div key={field.label} className="space-y-2">
                                <Label className="text-xs uppercase tracking-widest text-primary font-bold">
                                    {field.label}
                                </Label>

                                <Input
                                    type={field.type}
                                    value={formData[field.name as keyof typeof formData]}
                                    onChange={(e) => handleChange(field.name, e.target.value)}
                                    disabled={field.disabled}
                                    className="h-12 border-primary rounded-xl disabled:opacity-50 disabled:cursor-not-allowed"
                                />
                            </div>
                        ))}
                        <div className="space-y-2 md:col-span-2">
                            <Label className="text-xs uppercase tracking-widest text-primary font-bold">
                                Short Bio
                            </Label>
                            <Textarea
                                rows={3}
                                value={formData.bio}
                                onChange={(e) => handleChange("bio", e.target.value)}
                                className="rounded-xl border-primary"
                            />
                        </div>

                    </div>

                    {/* Actions */}
                    <div className="flex items-center justify-end gap-4 pt-6">
                        <Button variant="ghost">Discard</Button>
                        <Button className="px-6" onClick={handleSave} disabled={loading}>
                            {loading ? "Saving..." : "Save Changes"}
                        </Button>
                    </div>
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