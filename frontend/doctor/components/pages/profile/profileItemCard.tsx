// import { Card, CardContent } from "@/components/ui/card";
// import { ReactNode } from "react";

// interface ProfileItemCardProps {
//   icon: ReactNode;
//   title: string;
//   subtitle?: string;
//   meta?: string;
//   badge?: ReactNode;
//   description?: string;
//   actions?: ReactNode;
// }

// export function ProfileItemCard({
//   icon,
//   title,
//   subtitle,
//   meta,
//   badge,
//   description,
//   actions,
// }: ProfileItemCardProps) {
//   return (
//     <Card className="border-border">
//       <CardContent className="pt-6">
//         <div className="flex gap-4">
//           <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10 text-primary">
//             {icon}
//           </div>

//           <div className="flex-1">
//             <div className="flex items-start justify-between mb-2">
//               <div>
//                 <h4 className="font-semibold">{title}</h4>
//                 {subtitle && (
//                   <p className="text-sm text-muted-foreground">{subtitle}</p>
//                 )}
//               </div>
//               {badge}
//             </div>

//             {meta && <p className="text-sm text-muted-foreground mb-2">{meta}</p>}
//             {description && <p className="text-sm">{description}</p>}
//             {actions}
//           </div>
//         </div>
//       </CardContent>
//     </Card>
import Image from "next/image";
import { Card, CardContent } from "@/components/ui/card";
import { ReactNode } from "react";
import { Button } from "@/components/ui";

interface ProfileItemCardProps {
  icon?: ReactNode;
  imageSrc?: string | null;
  imageAlt?: string;
  title: string;
  subtitle?: string;
  meta?: string;
  badge?: ReactNode;
  description?: string;
  actions?: ReactNode;
  iconClassName?: string;
  isView?: boolean;
  viewUrl?: string | null;
}

export function ProfileItemCard({
  icon,
  imageSrc,
  imageAlt = "Profile item image",
  title,
  subtitle,
  meta,
  badge,
  description,
  actions,
  iconClassName = "bg-primary/10 text-primary",
  isView = false,
  viewUrl,
}: ProfileItemCardProps) {
  const handleView = () => {
    if (viewUrl) {
      window.open(viewUrl, "_blank", "noopener,noreferrer");
    }
  };
  return (
    <Card className="inline-flex border-border">
      <CardContent className="">
        <div className="flex gap-4">
          <div
            className={`flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-lg ${iconClassName}`}
          >
            {imageSrc ? (
              <Image
                src={imageSrc}
                alt={imageAlt}
                width={48}
                height={48}
                className="h-full w-full object-cover"
              />
            ) : (
              icon
            )}
          </div>

          <div className="flex-1 min-w-0">
            <div className="mb-2 flex items-start justify-between gap-3">
              <div className="min-w-0">
                <div className="flex items-center gap-4">
                  <h4 className="font-semibold wrap-break-word">{title}</h4>
                  {badge}
                </div>

                {subtitle && (
                  <p className="text-sm text-muted-foreground wrap-break-word">
                    {subtitle}
                  </p>
                )}
              </div>
            </div>

            {meta && (
              <p className="mb-2 text-sm text-muted-foreground wrap-break-word">
                {meta}
              </p>
            )}

            {description && (
              <p className="text-sm wrap-break-word">{description}</p>
            )}

            {actions && <div className="mt-3">{actions}</div>}
          </div>
          {isView && viewUrl && (
            <div className="mt-3">
              <Button onClick={handleView}>View</Button>
            </div>
          )}
        </div>
      </CardContent>
    </Card>
  );
}