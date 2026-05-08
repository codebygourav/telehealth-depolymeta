"use client";

import StatsCard from "@/components/pages/home/stats-card";
import AppointmentCard from "@/components/pages/appoitment/AppointmentCard";
import { useAuth } from "@/context/userContext";
import { useDoctorHome } from "@/queries/useHome";
import { useNotifications, useReadNotification } from "@/queries/notifications";
import Link from "next/link";
import { useRouter } from "next/navigation";
import {
    Calendar,
    Clock3,
    XCircle,
    Loader2,
    Users,
    MessageSquare,
    CreditCard,
    Star,
    Bell,
    FileText,
    CalendarCheck,
    Star as StarIcon,
    ChevronRight,
    Leaf
} from "lucide-react";
import QuickActionsCard, { QuickActionItem } from "@/components/pages/home/quick-actions-card";
import NotificationsCardContent from "@/components/ui/notifications-card-content";

const Home = () => {

    const { user } = useAuth();
    const router = useRouter();
    const { data, isLoading, isError, error } = useDoctorHome();
    const {
        data: notificationsData,
        isLoading: notificationsLoading,
        error: notificationsError
    } = useNotifications();
    const { mutate: markAsRead } = useReadNotification();

    const dashboard = data?.data;
    const summary = dashboard?.summary;
    const notifications = notificationsData?.data || [];

    const getNotificationIcon = (group: string) => {
        switch (group) {
            case "appointment":
                return <CalendarCheck className="h-4 w-4" />;
            case "review":
                return <StarIcon className="h-4 w-4" />;
            case "document":
                return <FileText className="h-4 w-4" />;
            case "availability":
                return <Bell className="h-4 w-4" />;
            default:
                return <Bell className="h-4 w-4" />;
        }
    };

    const stats = [
        {
            title: "Today Appointments",
            value: summary?.todays_appointments ?? 0,
            badgeText: "Today",
            icon: <Calendar size={18} color="#18CE1E" />,
            iconBgColor: "#F4FBF7",
            progress: '80%',
            progressBgColor: '#18CE1E',
            subTitle: "Scheduled consultations for today."
        },
        {
            title: "Upcoming Appointments",
            value: summary?.upcoming_appointments ?? 0,
            badgeText: "Scheduled",
            icon: <Clock3 size={18} color="#FABD2E" />,
            iconBgColor: "#FEF8EA",
            progress: '70%',
            progressBgColor: '#FABD2E',
            subTitle: "Your upcoming patient bookings."
        },
        {
            title: "Cancelled Appointments",
            value: summary?.cancelled_appointments ?? 0,
            badgeText: "Missed",
            icon: <XCircle size={18} color="#EF1E1E" />,
            iconBgColor: "#FDE9ED",
            progress: '40%',
            progressBgColor: '#EF1E1E',
            subTitle: "Appointments cancelled by patients or system."
        },
        {
            title: "Average Ratings",
            value: summary?.average_review_score ?? 0,
            badgeText: "Avg",
            icon: <Star size={18} color="#48A3D7" />,
            iconBgColor: "#EDF6FB",
            progress: '20%',
            progressBgColor: '#48A3D7',
            subTitle: "Your overall patient satisfaction score."
        }
    ];

    const quickActions: QuickActionItem[] = [
        {
            id: 1,
            title: "All Patients",
            icon: <Users color="#1F1E1E" size={30} />,
            href: "/all-patients",
        },
        {
            id: 2,
            title: "Patient Reports",
            icon: <FileText color="#1F1E1E" size={30} />,
            href: "/patient-reports",
        },
        {
            id: 3,
            title: "Medicine Inventory",
            icon: <MessageSquare color="#1F1E1E" size={30} />,
            href: "/medicine-inventory",
        },
        {
            id: 4,
            title: "Payment History",
            icon: <CreditCard color="#1F1E1E" size={30} />,
            href: "/payment-history",
        },
        {
            id: 5,
            title: "My Leaves",
            icon: <Leaf color="#1F1E1E" size={30} />,
            href: "/my-leaves",
        },
    ];

    return (
        <div className="container-max-width w-full mx-auto">

            {/* Welcome Section */}
            <section className="border-light-gray p-5 rounded-lg shadow-[0px_2px_4px_0px_#0000001A] mb-5">
                <h1 className="text-[#1F1E1E] font-bold text-2xl">
                    Welcome back, Dr. {user?.first_name || dashboard?.first_name || "Doctor"} {user?.last_name || dashboard?.last_name || ""}
                </h1>
                <p className="text-[#4D4D4D] text-base mt-1">
                    Manage your consultations, review patient records, and deliver quality care seamlessly from your dashboard.
                </p>
            </section>

            {/* Loading State */}
            {isLoading ? (
                <section className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                    {[1, 2, 3, 4].map((item) => (
                        <div
                            key={item}
                            className="flex h-32 sm:h-36 lg:h-42.5 items-center justify-center rounded-xl sm:rounded-2xl lg:rounded-3xl border bg-muted/30"
                        >
                            <Loader2 className="h-5 w-5 animate-spin" />
                        </div>
                    ))}
                </section>
            ) : isError ? (
                <div className="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-600">
                    {(error as any)?.response?.data?.message ||
                        (error as any)?.message ||
                        "Failed to load dashboard data."}
                </div>
            ) : (
                <>
                    {/* Stats Cards Grid */}
                    <section className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-5">
                        {stats.map((card, index) => (
                            <StatsCard
                                key={index}
                                title={card.title}
                                value={card.value}
                                badgeText={card.badgeText}
                                icon={card.icon}
                                subTitle={card.subTitle}
                                iconBgColor={card.iconBgColor}
                                progress={card.progress}
                                progressBgColor={card.progressBgColor}
                            />
                        ))}
                    </section>

                    {/* Today's Appointments & Notifications Section */}
                    <section className="flex flex-col lg:flex-row gap-4 md:gap-6">

                        {/* Appointments Section */}
                        <div className="flex-1 rounded-md shadow-[0px_2px_4px_0px_#0000001A] border-light-gray bg-white shadow-sm">

                            <div className="flex items-center justify-between border-b border-[#E7E8EB] p-5">

                                <div>
                                    <h2 className="text-[#1F1E1E] text-base font-bold">Today's Appointments</h2>
                                    <span className="text-xs sm:text-sm text-muted-foreground">
                                        You have {dashboard?.todays_appointments?.length ?? 0} appointments scheduled
                                    </span>
                                </div>

                                <div>
                                    <Link
                                        href="/appointments"
                                        className="text-[#1F1E1E] text-sm font-medium flex items-center gap-x-2"
                                    >
                                        View All
                                        <ChevronRight size={15} color="#1F1E1E" strokeWidth={3} />
                                    </Link>
                                </div>

                            </div>

                            <div className="p-3 sm:p-4">
                                {dashboard?.todays_appointments?.length ? (
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                                        {dashboard.todays_appointments.slice(0, 3).map((appointment) => (
                                            <AppointmentCard
                                                key={appointment.id}
                                                appointment={appointment}
                                                variant="today"
                                            />
                                        ))}
                                    </div>
                                ) : (
                                    <div className="text-center py-8 sm:py-12">
                                        <Calendar className="h-12 w-12 text-gray-300 mx-auto mb-3" />
                                        <p className="text-sm text-muted-foreground">
                                            No appointments for today.
                                        </p>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Notifications Section */}
                        <div className="w-full lg:w-[30%] min-w-[280px] rounded-lg border bg-white shadow-sm">
                            <div className="flex flex-col p-3 sm:p-4 border-b">
                                <h2 className="text-[#1E1E1E] font-bold text-lg">Notifications</h2>
                                <span className="text-xs sm:text-sm text-muted-foreground">
                                    You have {notificationsData?.unread_count ?? 0} unread notifications
                                </span>
                            </div>
                            <div className="max-h-[400px] overflow-y-auto">
                                <NotificationsCardContent
                                    notifications={notifications}
                                    loading={notificationsLoading}
                                    error={notificationsError?.message || null}
                                    limit={3}
                                    onClickItem={(id) => markAsRead(id)}
                                    onViewAll={() => router.push("/notifications")}
                                    getIcon={(group) => getNotificationIcon(group)}
                                />
                            </div>
                        </div>

                    </section>

                    {/* Quick Actions Section */}
                    <section className="mt-5">
                        <QuickActionsCard actions={quickActions} />
                    </section>
                </>
            )}
        </div>
    );
};

export default Home;