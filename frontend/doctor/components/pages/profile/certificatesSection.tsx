import { FileText } from "lucide-react";
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

            <h2 className="text-[#1F1E1E] font-semibold text-lg mb-1.5">Certificates & Licenses</h2>
            <p className="text-[#4D4D4D] text-sm">Your professional certifications</p>

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
                        />
                    ))
                )}
            </div>
        </div>
    );
}