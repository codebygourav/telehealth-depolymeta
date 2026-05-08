import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Key, Mail, Phone, Star, Stethoscope } from "lucide-react";
import Link from "next/link";

interface ProfileHeaderProps {
    fullName: string;
    avatar?: string | null;
    initials: string;
    department: string;
    role?: string;
    email?: string | null;
    phone?: string | null;
    license?: string | null;
    reviewSummary?: {
        average_rating: number;
        total_reviews: number;
    };
}

export default function ProfileHeader({
    fullName,
    avatar,
    initials,
    department,
    role,
    email,
    phone,
    license,
    reviewSummary,
}: ProfileHeaderProps) {

    const averageRatingValue = reviewSummary?.average_rating || 0;
    const totalReviewsValue = reviewSummary?.total_reviews || 0;

    return (
        <Card className="rounded-md !border-light-gray shadow-[0px_2px_4px_0px_#0000001A] p-3 lg:p-5">
            <CardContent className="p-0">

                {/* Desktop Layout */}
                <div className="hidden md:flex md:flex-row gap-5 items-center">

                    {/* Avatar */}
                    <div className="relative">
                        <Avatar className="h-24 w-24">
                            <AvatarImage src={avatar || ""} alt={fullName} />
                            <AvatarFallback className="bg-primary/10 text-primary text-2xl">
                                {initials}
                            </AvatarFallback>
                        </Avatar>
                    </div>

                    {/* Info */}
                    <div className="flex-1">
                        <p className="flex items-center gap-x-1.5 text-xs font-semibold text-[#055BD9] uppercase tracking-wide">
                            <Stethoscope color="#055BD9" size={14} />
                            {department}
                        </p>
                        <h1 className="text-[#1F1E1E] text-2xl font-bold mb-2 mt-1.5">{fullName}</h1>
                        <div className="flex flex-wrap gap-2">
                            {email && (
                                <Badge variant="secondary" className="text-[#4D4D4D] text-xs font-medium py-1.5 px-2.5 rounded-md bg-[#F5F6F8] h-auto">
                                    <Mail className="h-3 w-3 mr-1" />
                                    {email}
                                </Badge>
                            )}
                            {phone && (
                                <Badge variant="secondary" className="text-[#4D4D4D] text-xs font-medium py-1.5 px-2.5 rounded-md bg-[#F5F6F8] h-auto">
                                    <Phone className="h-3 w-3 mr-1" />
                                    {phone}
                                </Badge>
                            )}
                            {license && (
                                <Badge variant="secondary" className="text-[#4D4D4D] text-xs font-medium py-1.5 px-2.5 rounded-md bg-[#F5F6F8] h-auto">
                                    License: {license}
                                </Badge>
                            )}
                        </div>
                    </div>

                    {/* Actions Desktop */}
                    <div className="flex flex-col gap-4 min-w-[140px]">
                        <Button variant="outline" size="sm" className="w-full text-xs" asChild>
                            <Link href="/profile/change-password">
                                <Key className="mr-2 h-3 w-3" />
                                Change Password
                            </Link>
                        </Button>
                        <div className="text-center">
                            <div className="text-[#1F1E1E] font-bold text-2xl">
                                {averageRatingValue}
                            </div>
                            <div className="flex items-center justify-center gap-1 mb-1">
                                {Array.from({ length: 5 }).map((_, i) => (
                                    <Star
                                        key={i}
                                        className={`h-4 w-4 ${i < Math.round(averageRatingValue)
                                            ? "fill-amber-400 text-amber-400"
                                            : "text-gray-300"
                                            }`}
                                    />
                                ))}
                            </div>
                            <p className="text-xs text-muted-foreground">{totalReviewsValue} reviews</p>
                        </div>
                    </div>
                </div>

                {/* Mobile Layout */}
                <div className="flex flex-col md:hidden">

                    {/* Top Row: Left Image + Right Name */}
                    <div className="flex gap-4 items-start">

                        {/* Avatar - Left */}
                        <div className="relative shrink-0">
                            <Avatar className="h-20 w-20">
                                <AvatarImage src={avatar || ""} alt={fullName} />
                                <AvatarFallback className="bg-primary/10 text-primary text-xl">
                                    {initials}
                                </AvatarFallback>
                            </Avatar>
                        </div>

                        {/* Name & Info - Right */}
                        <div className="flex-1 min-w-0">
                            <h1 className="text-lg font-bold mb-1 break-words">{fullName}</h1>
                            <p className="text-xs text-muted-foreground mb-2">
                                {department}
                                {role ? ` • ${role}` : ""}
                            </p>
                            <div className="flex flex-wrap gap-1.5">
                                {email && (
                                    <Badge variant="secondary" className="text-[#4D4D4D] text-xs font-medium py-1.5 px-2.5 rounded-md bg-[#F5F6F8] h-auto">
                                        <Mail className="h-2 w-2 mr-0.5" />
                                        <span className="truncate max-w-[100px]">{email}</span>
                                    </Badge>
                                )}
                                {phone && (
                                    <Badge variant="secondary" className="text-[#4D4D4D] text-xs font-medium py-1.5 px-2.5 rounded-md bg-[#F5F6F8] h-auto">
                                        <Phone className="h-2 w-2 mr-0.5" />
                                        {phone}
                                    </Badge>
                                )}
                                {license && (
                                    <Badge variant="secondary" className="text-[#4D4D4D] text-xs font-medium py-1.5 px-2.5 rounded-md bg-[#F5F6F8] h-auto">
                                        License: {license}
                                    </Badge>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Bottom Row: Change Password + Ratings */}
                    <div className="flex flex-row items-center gap-3 mt-4 pt-3 border-t border-border/50">
                        <Button
                            variant="outline"
                            size="sm"
                            className="flex-1 text-[11px] h-8 order-2"
                            asChild
                        >
                            <Link href="/profile/change-password">
                                <Key className="mr-1.5 h-3 w-3" />
                                Change Password
                            </Link>
                        </Button>

                        <div className="flex-1 text-center order-1">
                            <div className="text-[#1F1E1E] font-bold text-2xl">
                                {averageRatingValue}
                            </div>
                            <div className="flex items-center justify-center gap-0.5">
                                {Array.from({ length: 5 }).map((_, i) => (
                                    <Star
                                        key={i}
                                        className={`h-3 w-3 ${i < Math.round(averageRatingValue)
                                            ? "fill-amber-400 text-amber-400"
                                            : "text-gray-300"
                                            }`}
                                    />
                                ))}
                            </div>
                            <p className="text-[9px] text-muted-foreground">{totalReviewsValue} reviews</p>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}