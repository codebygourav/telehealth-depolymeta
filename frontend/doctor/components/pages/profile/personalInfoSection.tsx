import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Edit } from "lucide-react";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Badge } from "@/components/ui/badge";
import InfoField from "./infoField";

interface DepartmentItem {
  department_id: string;
  department_name: string;
  role: string;
}

interface ProfileData {
  email?: string | null;
  bio?: string | null;
  avatar?: string | null;
  doctor_departments?: DepartmentItem[];
}

interface PersonalInfoSectionProps {
  isEditing: boolean;
  setIsEditing: (value: boolean) => void;
  formData: {
    first_name: string;
    last_name: string;
    email: string;
    bio: string;
    phone: string;
    medical_license: string;
  };
  profileData?: ProfileData;
  fullName: string;
  primaryDepartment: string;
  primaryRole: string;
  isSaving: boolean;
  onInputChange: (field: string, value: string) => void;
  onSave: () => void;
  onCancel: () => void;
  averageRating?: {
    average_rating: number;
    total_reviews: number;
  };
}

export default function PersonalInfoSection({
  isEditing,
  setIsEditing,
  formData,
  profileData,
  fullName,
  primaryDepartment,
  primaryRole,
  isSaving,
  onInputChange,
  onSave,
  onCancel,
}: PersonalInfoSectionProps) {
  return (
    <Card className="border-border">
      <CardHeader className="flex flex-row items-center justify-between space-y-0">
        <div>
          <CardTitle>Personal Information</CardTitle>
          <CardDescription>Manage your personal details and bio</CardDescription>
        </div>

        {/* <Button variant="outline" onClick={() => setIsEditing(!isEditing)}>
          <Edit className="h-4 w-4 mr-2" />
          {isEditing ? "Cancel" : "Edit"}
        </Button> */}
      </CardHeader>

      <CardContent className="space-y-4">
        {isEditing ? (
          <>
            <div className="grid gap-4 md:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="first_name">First Name</Label>
                <Input
                  id="first_name"
                  value={formData.first_name}
                  onChange={(e) => onInputChange("first_name", e.target.value)}
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="last_name">Last Name</Label>
                <Input
                  id="last_name"
                  value={formData.last_name}
                  onChange={(e) => onInputChange("last_name", e.target.value)}
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="email">Email</Label>
                <Input
                  id="email"
                  type="email"
                  value={formData.email}
                  onChange={(e) => onInputChange("email", e.target.value)}
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="phone">Phone</Label>
                <Input
                  id="phone"
                  value={formData.phone}
                  onChange={(e) => onInputChange("phone", e.target.value)}
                  placeholder="Enter phone number"
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="medical_license">License Number</Label>
                <Input
                  id="medical_license"
                  value={formData.medical_license}
                  onChange={(e) => onInputChange("medical_license", e.target.value)}
                  placeholder="Enter license number"
                />
              </div>
            </div>

            <div className="space-y-2">
              <Label htmlFor="bio">Bio</Label>
              <Textarea
                id="bio"
                rows={4}
                className="resize-none"
                value={formData.bio}
                onChange={(e) => onInputChange("bio", e.target.value)}
              />
            </div>

            <div className="flex gap-2 justify-end">
              <Button variant="outline" onClick={onCancel}>
                Cancel
              </Button>

              <Button onClick={onSave} disabled={isSaving}>
                {isSaving ? "Saving..." : "Save Changes"}
              </Button>
            </div>
          </>
        ) : (
          <div className="space-y-4">
            <div className="grid gap-4 md:grid-cols-2">
              <InfoField label="Full Name" value={fullName} />
              <InfoField label="Department" value={primaryDepartment} />
              <InfoField label="Email" value={profileData?.email} />
              <InfoField label="Phone" value={formData.phone} />
              <InfoField label="License Number" value={formData.medical_license} />
              <InfoField label="Role" value={primaryRole} />
            </div>

            <div>
              <p className="text-sm text-muted-foreground mb-1">Bio</p>
              <p className="text-sm">{profileData?.bio || "-"}</p>
            </div>

            {!!profileData?.doctor_departments?.length && (
              <div>
                <p className="text-sm text-muted-foreground mb-2">All Departments</p>
                <div className="flex flex-wrap gap-2">
                  {profileData.doctor_departments.map((dept) => (
                    <Badge
                      key={dept.department_id}
                      variant="secondary"
                      className="bg-primary/10 text-primary"
                    >
                      {dept.department_name}
                      {dept.role ? ` • ${dept.role}` : ""}
                    </Badge>
                  ))}
                </div>
              </div>
            )}
          </div>
        )}
      </CardContent>
    </Card>
  );
}