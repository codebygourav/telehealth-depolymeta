// import { FileText, Plus } from "lucide-react";
// import { Badge } from "@/components/ui/badge";
// import { SectionHeader } from "./sectionHeader";
// import { ProfileItemCard } from "./profileItemCard";
// import { ActionButtons } from "./actionButtons";

// interface CertificateItem {
//   id: number;
//   name: string;
//   issuer: string;
//   issueDate: string;
//   expiryDate: string;
// }

// interface CertificatesSectionProps {
//   certificates: CertificateItem[];
// }

// export default function CertificatesSection({
//   certificates,
// }: CertificatesSectionProps) {
//   const safeCertificates = Array.isArray(certificates.certifications_info)
//     ? certificates.certifications_info
//     : [];
//   console.log("Doctor Certificates : ", safeCertificates);

//   return (
//     <div className="space-y-4">
//       <SectionHeader
//         title="Certificates & Licenses"
//         description="Your professional certifications"
//         actionLabel="Add Certificate"
//         actionIcon={<Plus className="h-4 w-4 mr-2" />}
//       />

//       <div className="space-y-4">
//         {safeCertificates.length === 0 ? (
//           <p className="text-center text-muted-foreground">No certificates found</p>
//         ) : (
//           {safeCertificates.map((index, cert) => (
//           <ProfileItemCard
//             key={index}
//             icon={<FileText className="h-6 w-6" />}
//             title={cert.name}
//             subtitle={cert.issuer}
//             meta={`Issued: ${cert.issueDate} • Expires: ${cert.expiryDate}`}
//             badge={<Badge variant="secondary">Valid</Badge>}
//             actions={<ActionButtons />}
//           />
//         ))}
//         )}
//       </div>
//     </div>
//   );
// }

import { FileText, Plus } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { SectionHeader } from "./sectionHeader";
import { ProfileItemCard } from "./profileItemCard";

interface CertificateItem {
  id?: number | string;
  name?: string | null;
  issuer?: string | null;
  issue_date?: string | null;
  expiry_date?: string | null;
  organization?: string | null;
  certification_image?: string | null;
}

interface CertificatesSectionProps {
  certificates?: CertificateItem[];
}

export default function CertificatesSection({
  certificates = [],
}: CertificatesSectionProps) {
  const safeCertificates = Array.isArray(certificates) ? certificates : [];

  return (
    <div className="space-y-4">
      <SectionHeader
        title="Certificates & Licenses"
        description="Your professional certifications"
      // actionLabel="Add Certificate"
      // actionIcon={<Plus className="h-4 w-4 mr-2" />}
      />

      <div className="space-y-4">
        {safeCertificates.length === 0 ? (
          <p className="text-center text-muted-foreground">
            No certificates found
          </p>
        ) : (
          safeCertificates.map((cert, index) => (
            <ProfileItemCard
              key={index}
              icon={<FileText className="h-6 w-6" />}
              title={cert.name || "Untitled Certificate"}
              subtitle={cert.organization || "Issuer not provided"}
              meta={`${cert.issue_date || "N/A"} - ${cert.expiry_date || "N/A"
                }`}
                isView={true}
                viewUrl={cert.certification_image}
            // badge={<Badge variant="secondary">Valid</Badge>}
            // actions={<ActionButtons />}
            />
          ))
        )}
      </div>
    </div>
  );
}