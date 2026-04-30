"use client";

import { DashboardSection } from "@/components/pages/Dashboard/DashboardSection";
import { SectionHeader } from "@/components/custom/SectionHeader";
import { Avatar, AvatarFallback, AvatarImage, Badge, Button, Card, CardContent, CardDescription, CardTitle } from "@/components/ui";
import type { HomeScreenDepartmentCard } from "@/types/home-screen";
import { ArrowRight, CircleDot } from "lucide-react";
import Link from "next/link";

interface DoctorDepartmentsProps {
    departments: HomeScreenDepartmentCard[];
    showAction?: boolean;
    onShowAll?: () => void;
}

export function DoctorDepartments({
    departments,
    showAction = false,
    onShowAll,
}: DoctorDepartmentsProps) {
    if (!departments.length) {
        return null;
    }

    return (
        <DashboardSection>
            <SectionHeader
                title="Specialised Doctors Categories"
                showAction={showAction}
                subtitle="With Super specialist doctors and state-of-the-art technology, we cover the complete spectrum of medical specialties"
                onActionClick={onShowAll}
            />

            <div className="grid grid-cols-1 gap-4 md:grid-cols-5 xl:grid-cols-6 ">
                {departments.map((department) => (
                    <Link key={department.id} href={department.href} className="flex h-full">
                        <Card className="custom-card-design w-full bg-light-gray rounded-[10px] border-light-gray p-0 transition-shadow hover:shadow-card-lg">
                            <CardContent className="flex h-full flex-col gap-5 p-5">
                            <div className="flex items-center gap-3 flex-col md:flex-col">
                                <div className="bg-white rounded-full p-5">
                                        <Avatar className="size-7 rounded-full bg-white after:hidden">
                                            <AvatarImage
                                                src={department.icon}
                                                alt={department.name}
                                                className="rounded-2xl object-cover"
                                            />
                                            <AvatarFallback className="rounded-2xl bg-primary/10 text-primary">
                                                <CircleDot className="size-5" />
                                            </AvatarFallback>
                                        </Avatar>
                                        </div>
                                        <div className="space-y-1">
                                            <CardTitle className="font-xs text-foreground">
                                                {department.name}
                                            </CardTitle>
                                            
                                        </div>
                                    </div>

                            </CardContent>
                        </Card>
                    </Link>
                ))}
            </div>
        </DashboardSection>
    );
}
