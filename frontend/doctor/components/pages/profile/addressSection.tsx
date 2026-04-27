import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { MapPin, Plus } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { ActionButtons } from "./actionButtons";
import { SectionHeader } from "./sectionHeader";

interface AddressItem {
  address_line1: string | null;
  address_line2: string | null;
  area: string | null;
  landmark: string | null;
  city: string | null;
  state: string | null;
  country: string | null;
  pincode: string | null;
}

interface AddressSectionProps {
  address: AddressItem | null;
}

export default function AddressSection({ address }: AddressSectionProps) {

  if (!address) {
    return (
      <div className="space-y-4">
        <SectionHeader
          title="Address Management"
          description="Manage your practice locations"
          actionLabel="Add Address"
          actionIcon={<Plus className="h-4 w-4 mr-2" />}
        />

        <Card className="border-border">
          <CardContent className="pt-6">
            <p className="text-sm text-muted-foreground">
              No address available.
            </p>
          </CardContent>
        </Card>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      <SectionHeader
        title="Address Management"
        description="Manage your practice locations"
      // actionLabel="Add Address"
      // actionIcon={<Plus className="h-4 w-4 mr-2" />}
      />

      <div className="grid gap-4 md:grid-cols-2">
        <Card className="border-border">
          <CardHeader>
            <div className="flex items-start justify-between">
              <div className="flex items-center gap-2">
                <MapPin className="h-5 w-5 text-primary" />
                <CardTitle className="text-base">Primary Address</CardTitle>
              </div>

              <Badge className="bg-primary text-primary-foreground">
                Primary
              </Badge>
            </div>
          </CardHeader>

          <CardContent className="space-y-3">
            <div className="space-y-1">
              {address.address_line1 && (
                <p className="text-sm">{address.address_line1}</p>
              )}

              {address.address_line2 && (
                <p className="text-sm">{address.address_line2}</p>
              )}

              {address.area && <p className="text-sm">{address.area}</p>}

              {address.landmark && (
                <p className="text-sm">{address.landmark}</p>
              )}

              <p className="text-sm">
                {address.city || "-"}, {address.state || "-"}{" "}
                {address.pincode || "-"}
              </p>

              {address.country && (
                <p className="text-sm">{address.country}</p>
              )}
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}