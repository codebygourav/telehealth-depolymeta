"use client";

import AppointmentCard from "@/components/pages/appoitment/AppointmentCard";
import AppointmentFilters from "@/components/pages/appoitment/AppointmentFilters";
import CustomTabs, { TabItem } from "@/components/custom/CustomTabs";
import { useMyAppointments } from "@/queries/useAppointments";
import { useRouter, useSearchParams } from "next/navigation";
import { Suspense, useEffect, useState } from "react";

const AppointmentsContent = () => {
  const searchParams = useSearchParams();
  const router = useRouter();
  const tabParam = searchParams.get("tab");
  const defaultTab =
    tabParam && ["today", "upcoming", "past", "all"].includes(tabParam)
      ? tabParam
      : "today";
  const [activeTab, setActiveTab] = useState(defaultTab);
  const [isMounted, setIsMounted] = useState(false);
  const [searchQuery, setSearchQuery] = useState("");
  const [selectedFilter, setSelectedFilter] = useState("all");

  useEffect(() => {
    setIsMounted(true);
  }, []);

  const { data, isLoading, error } = useMyAppointments(activeTab);
  const appointments = Array.isArray(data?.data) ? data.data : [];
  // ✅ Unique status list
  const statusOptions = [
    { value: "all", label: "All Status" },
    ...Array.from(
      new Map(
        appointments.map((apt: any) => [
          apt.status,
          { value: apt.status, label: apt.status_label },
        ]),
      ).values(),
    ),
  ];

  useEffect(() => {
    if (tabParam && ["today", "upcoming", "past", "all"].includes(tabParam)) {
      setActiveTab(tabParam);

    }
  }, [tabParam]);

  const handleTabChange = (value: string) => {
    setActiveTab(value);
  };

  // ✅ Split Data based on current date
  const today = appointments.filter((apt: any) => {
    const aptDate = new Date(apt.appointment_date);
    const todayDate = new Date();
    return (
      aptDate.getDate() === todayDate.getDate() &&
      aptDate.getMonth() === todayDate.getMonth() &&
      aptDate.getFullYear() === todayDate.getFullYear()
    );
  });

  const upcoming = appointments.filter((apt: any) => {
    const aptDate = new Date(apt.appointment_date);
    const todayDate = new Date();
    todayDate.setHours(0, 0, 0, 0);
    return aptDate > todayDate;
  });

  const past = appointments.filter((apt: any) => {
    const aptDate = new Date(apt.appointment_date);
    const todayDate = new Date();
    todayDate.setHours(0, 0, 0, 0);
    return aptDate < todayDate;
  });

  // ✅ Filters
  const applyFilters = (list: any[]) => {
    return list.filter((apt) => {
      const name = apt.patient?.name?.toLowerCase() || "";
      const matchesSearch = name.includes(searchQuery.toLowerCase());
      const matchesStatus =
        selectedFilter === "all" || apt.status === selectedFilter;
      return matchesSearch && matchesStatus;
    });
  };

  // ✅ Render Cards
  const renderCards = (list: any[]) => {
    if (!list.length) {
      return (
        <div className="text-center py-12">
          <p className="text-muted-foreground">No appointments found</p>
        </div>
      );
    }

    return (
      <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 mt-5">
        {list.map((apt: any, index: number) => (
          <AppointmentCard
            key={apt.appointment_id || index}
            appointment={apt}
            variant={activeTab as "today" | "upcoming" | "past" | "all"}
          />
        ))}
      </div>
    );
  };

  // ✅ Tabs with correct data
  const appointmentTabs: TabItem[] = [
    {
      key: "all",
      label: "All",
      content: renderCards(applyFilters(appointments)),
    },
    {
      key: "today",
      label: "Today",
      content: renderCards(applyFilters(today)),
    },
    {
      key: "upcoming",
      label: "Upcoming",
      content: renderCards(applyFilters(upcoming)),
    },
    {
      key: "past",
      label: "Past",
      content: renderCards(applyFilters(past)),
    },
  ];

  // ✅ Loading & Error
  if (!isMounted || isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="text-center py-12">
        <p className="text-red-500">Something went wrong. Please try again.</p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="space-y-1 sm:space-y-2">
        <h1 className="text-xl sm:text-2xl md:text-3xl font-bold tracking-tight text-primary">
          Appointments
        </h1>
        <p className="text-xs sm:text-sm text-muted-foreground">
          Manage all patient appointments
        </p>
      </div>

      {/* Filters */}
      <AppointmentFilters
        searchQuery={searchQuery}
        selectedFilter={selectedFilter}
        setSearchQuery={setSearchQuery}
        setSelectedFilter={setSelectedFilter}
        statusOptions={statusOptions}
      />

      {/* Tabs */}
      <CustomTabs
        tabs={appointmentTabs}
        activeTab={activeTab}
        onTabChange={handleTabChange}
        tabsListClassName="w-full md:max-w-lg overflow-x-auto overflow-y-hidden scrollbar-hide flex-nowrap justify-start sm:justify-start md:justify-start lg:justify-start [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]"
      />
    </div>
  );
};

const Appointments = () => {
  return (
    <Suspense
      fallback={
        <div className="flex items-center justify-center h-64">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
        </div>
      }
    >
      <AppointmentsContent />
    </Suspense>
  );
};

export default Appointments;
