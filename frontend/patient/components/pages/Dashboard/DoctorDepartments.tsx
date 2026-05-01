"use client";

import { DashboardSection } from "@/components/pages/Dashboard/DashboardSection";
import { SectionHeader } from "@/components/custom/SectionHeader";
import { Avatar, AvatarFallback, AvatarImage, Card, CardContent, CardTitle } from "@/components/ui";
import type { HomeScreenDepartmentCard } from "@/types/home-screen";
import { CircleDot, FolderIcon } from "lucide-react";
import { EmptyState } from "@/components/custom/EmptyState";
import Link from "next/link";
import { DashboardCarousel } from "@/components/pages/Dashboard/dashboard-carousel";

interface DoctorDepartmentsProps {
    departments: HomeScreenDepartmentCard[];
    onShowAll?: () => void;
    carouselOnMobile?: boolean;
}

export function DoctorDepartments({
    departments,
    onShowAll,
    carouselOnMobile = true,
}: DoctorDepartmentsProps) {
    const renderDepartmentCard = (department: HomeScreenDepartmentCard) => (
        <Link key={department.id} href={department.href} className="flex h-full">
            <Card className="w-full p-0 shadow-card-sm bg-light-gray global-radius-10">
                <CardContent className="flex h-full flex-col gap-5 p-5">
                    <div className="flex flex-col items-center gap-3 md:flex-col">
                        <div className="rounded-full bg-white p-5">
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
                            <CardTitle className="text-span-14">{department.name}</CardTitle>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </Link>
    );

    return (
        <DashboardSection>
            <SectionHeader
                title="Specialised Doctors Categories"
                showAction={Boolean(onShowAll)}
                actionText="Show All"
                onActionClick={onShowAll}
                subtitle="With Super specialist doctors and state-of-the-art technology, we cover the complete spectrum of medical specialties"
            />

            {departments.length === 0 ? (
                <EmptyState
                    title="No departments found"
                    description="No departments found"
                    icon={<FolderIcon className="size-10" />}
                />
            ) : carouselOnMobile ? (
                <>
                    <div className="md:hidden">
                        <DashboardCarousel
                            items={departments}
                            basisClassName="basis-[75%] sm:basis-1/2"
                            renderItem={(department) => renderDepartmentCard(department)}
                        />
                    </div>

                    <div className="hidden md:grid grid-cols-1 gap-4 md:grid-cols-5 xl:grid-cols-6">
                        {departments.map((department) => renderDepartmentCard(department))}
                    </div>
                </>
            ) : (
                <div className="grid grid-cols-1 gap-4 md:grid-cols-5 xl:grid-cols-6">
                    {departments.map((department) => renderDepartmentCard(department))}
                </div>
            )}
        </DashboardSection>
    );
}
