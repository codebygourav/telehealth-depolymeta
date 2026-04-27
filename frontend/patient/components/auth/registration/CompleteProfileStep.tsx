"use client";

import React, { useEffect } from "react";
import { FormProvider, useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { useRouter } from "next/navigation";
import InputField from "../../custom/inputfield";
import MultiSelectField from "../../custom/MultiSelectField";
import { useCompleteProfile, CompleteProfileResponse } from "@/mutations/auth/useAuthMutations";
import { toast } from "sonner";

const profileSchema = z.object({
  email: z.string().email("Invalid email address"),
  password: z.string().min(6, "Password must be at least 6 characters"),
  first_name: z.string().min(1, "First name is required"),
  last_name: z.string().min(1, "Last name is required"),
  gender: z.enum(["male", "female", "other"]),
  date_of_birth: z.string().min(1, "Date of birth is required"),
  mobile_no: z.string().min(10, "Valid mobile number is required"),
  // Health profile fields
  allergies: z.array(z.string()).optional(),
  chronic_conditions: z.array(z.string()).optional(),
  blood_type: z.string().optional(),
  // Optional device info
  expo_push_token: z.string().optional(),
  device_type: z.string().optional(),
  device_name: z.string().optional(),
  app_version: z.string().optional(),
});

type ProfileValues = z.infer<typeof profileSchema>;

interface CompleteProfileStepProps {
  email: string;
}

const CompleteProfileStep: React.FC<CompleteProfileStepProps> = ({ email }) => {
  const router = useRouter();
  const { mutate: completeProfile, isPending } = useCompleteProfile();

  const methods = useForm<ProfileValues>({
    resolver: zodResolver(profileSchema),
    defaultValues: {
      email: email,
      password: "",
      first_name: "",
      last_name: "",
      gender: "male",
      date_of_birth: "",
      mobile_no: "",
      allergies: [],
      chronic_conditions: [],
      blood_type: "",
      expo_push_token: "",
      device_type: "web",
      device_name: "browser",
      app_version: "1.0.0",
    },
  });

  // Prefill email if it changes
  useEffect(() => {
    if (email) {
      methods.setValue("email", email);
    }
  }, [email, methods]);

  const onSubmit = (data: ProfileValues) => {
    completeProfile(data, {
      onSuccess: (response: CompleteProfileResponse) => {
        if (response.success) {
          toast.success(response.message || "Profile completed successfully!");
          router.push("/dashboard");
        } else {
          toast.error(response?.errors?.message || response.message || "Failed to complete profile.");
        }
      },
      onError: (err: any) => {
        const responseData = err?.response?.data || {};
        toast.error(responseData?.errors?.message || responseData?.message || err?.message || "Profile completion failed");
      },
    });
  };

  return (
    <div className="space-y-6 max-h-[70vh] overflow-y-auto px-1 pr-2">
      <FormProvider {...methods}>
        <form onSubmit={methods.handleSubmit(onSubmit)} className="space-y-5 pb-4">
          <InputField
            name="email"
            label="Email Address"
            placeholder="example@mail.com"
            required
            disabled={true} // Email is verified and locked
            type="email"
          />

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <InputField
              name="first_name"
              label="First Name"
              placeholder="First Name"
              required
              disabled={isPending}
            />
            <InputField
              name="last_name"
              label="Last Name"
              placeholder="Last Name"
              required
              disabled={isPending}
            />
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="flex flex-col space-y-2">
              <label className="text-sm font-medium text-muted-foreground">Gender *</label>
              <select
                {...methods.register("gender")}
                disabled={isPending}
                className="flex h-10 w-full rounded-md border border-input bg-accent/30 px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring focus-visible:ring-offset-0 disabled:cursor-not-allowed disabled:opacity-50"
              >
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="other">Other</option>
              </select>
            </div>
            <InputField
              name="date_of_birth"
              label="Date of Birth"
              type="date"
              required
              disabled={isPending}
            />
          </div>

          <InputField
            name="mobile_no"
            label="Mobile Number"
            placeholder="7325809632"
            required
            disabled={isPending}
          />

          <InputField
            name="password"
            label="Password"
            type="password"
            placeholder="••••••••"
            required
            disabled={isPending}
          />

          {/* Health Profile Section */}
          <div className="border-t border-border pt-6 mt-6">
            <h3 className="text-lg font-semibold mb-4 text-foreground">Health Information</h3>
            
            <div className="space-y-5">
              <MultiSelectField
                name="allergies"
                label="Allergies"
                description="Select any allergies you have"
                options={[
                  { label: "Penicillin", value: "penicillin" },
                  { label: "Sulfa drugs", value: "sulfa" },
                  { label: "Aspirin", value: "aspirin" },
                  { label: "Latex", value: "latex" },
                  { label: "Pollen", value: "pollen" },
                  { label: "Dust mites", value: "dust_mites" },
                  { label: "Pet dander", value: "pet_dander" },
                  { label: "Food - Nuts", value: "nuts" },
                  { label: "Food - Shellfish", value: "shellfish" },
                  { label: "Food - Eggs", value: "eggs" },
                  { label: "Food - Dairy", value: "dairy" },
                ]}
                disabled={isPending}
                direction="row"
              />

              <MultiSelectField
                name="chronic_conditions"
                label="Chronic Conditions"
                description="Select any chronic conditions you have been diagnosed with"
                options={[
                  { label: "Diabetes Type 1", value: "diabetes_type1" },
                  { label: "Diabetes Type 2", value: "diabetes_type2" },
                  { label: "Hypertension", value: "hypertension" },
                  { label: "Asthma", value: "asthma" },
                  { label: "Heart Disease", value: "heart_disease" },
                  { label: "COPD", value: "copd" },
                  { label: "Arthritis", value: "arthritis" },
                  { label: "Thyroid Disorder", value: "thyroid" },
                  { label: "Kidney Disease", value: "kidney_disease" },
                  { label: "Liver Disease", value: "liver_disease" },
                  { label: "Cancer", value: "cancer" },
                  { label: "Epilepsy", value: "epilepsy" },
                  { label: "Mental Health Condition", value: "mental_health" },
                ]}
                disabled={isPending}
                direction="row"
              />

              <div className="flex flex-col space-y-2">
                <label className="text-sm font-medium text-muted-foreground">Blood Type</label>
                <select
                  {...methods.register("blood_type")}
                  disabled={isPending}
                  className="flex h-10 w-full rounded-md border border-input bg-accent/30 px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring focus-visible:ring-offset-0 disabled:cursor-not-allowed disabled:opacity-50"
                >
                  <option value="">Select blood type</option>
                  <option value="A+">A+</option>
                  <option value="A-">A-</option>
                  <option value="B+">B+</option>
                  <option value="B-">B-</option>
                  <option value="AB+">AB+</option>
                  <option value="AB-">AB-</option>
                  <option value="O+">O+</option>
                  <option value="O-">O-</option>
                </select>
              </div>
            </div>
          </div>

          <button
            type="submit"
            disabled={isPending}
            className="w-full bg-primary text-primary-foreground hover:bg-primary/90 font-medium py-2.5 rounded-lg transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed font-source-sans mt-4"
          >
            {isPending ? "Saving Profile..." : "Complete Registration"}
          </button>
        </form>
      </FormProvider>
    </div>
  );
};

export default CompleteProfileStep;
