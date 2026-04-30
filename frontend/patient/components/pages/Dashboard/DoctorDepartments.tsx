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
                        <Card className="w-full p-0 transition-shadow custom-card-design bg-light-gray global-radius-10 border-light-gray hover:shadow-card-lg">
                            <CardContent className="flex flex-col h-full gap-5 p-5">
                            <div className="flex flex-col items-center gap-3 md:flex-col">
                                <div className="p-5 bg-white rounded-full">
                                        <Avatar className="bg-white rounded-full size-7 after:hidden">
                                            <AvatarImage
                                                src={department.icon}
                                                alt={department.name}
                                                className="object-cover rounded-2xl"
                                            />
                                            <AvatarFallback className="rounded-2xl bg-primary/10 text-primary">
                                                <CircleDot className="size-5" />
                                            </AvatarFallback>
                                        </Avatar>
                                        </div>
                                        <div className="space-y-1">
                                            <CardTitle className="text-span-14">
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
