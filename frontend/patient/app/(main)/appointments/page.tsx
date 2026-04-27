'use client';
import { useState } from 'react';
import UpcomingAppointmentCard from '@/components/pages/appointments/UpcomingAppointmentCard';
import PastAppointmentCard from '@/components/pages/appointments/PastAppointmentCard';
import PreAppointmentChecklist from '@/components/pages/appointments/PreAppointmentChecklist';
import CustomTabs from '@/components/custom/CustomTabs';
import PaginationControls from '@/components/ui/PaginationControls';
import { useAppointments } from '@/queries/useAppointments';
import { Loader2, Calendar } from 'lucide-react';
import { AppointmentResponse } from '@/types/appointment';
import { useRouter } from 'next/navigation';

const AppointmentsPage = () => {

    const [activeTab, setActiveTab] = useState<'upcoming' | 'past'>('upcoming');
    const [currentPage, setCurrentPage] = useState(1);
    const [selectedAppointment, setSelectedAppointment] = useState<string | null>(null);

    // Fetch appointments based on active tab and page
    const { data, isLoading, isError, error, refetch } = useAppointments(activeTab, currentPage);
    const router = useRouter();

    const handleManageAppointment = (appointmentId: string) => {
        setSelectedAppointment(appointmentId);
    };

    const handleViewDetails = (id: string) => {
        router.push(`/appointments/appoitment-detail/${id}`);
    };

    const handlePageChange = (page: number) => {
        setCurrentPage(page);
        // Refetch data when page changes
        refetch();
    };

    // Transform API response to match component props
    const transformToAppointment = (app: AppointmentResponse) => {
        return {
            id: app.appointment_id,
            doctorId: app.doctor?.id || '',
            doctorName: app.doctor?.name || '',
            doctorImage: app.doctor?.avatar || '',
            date: app.schedule?.date_formatted || app.appointment_date_formatted || app.appointment_date,
            time: app.schedule?.time_formatted || app.appointment_time_formatted || app.appointment_time,
            status: app.status as 'upcoming' | 'completed' | 'cancelled',
            type: app.consultation_type || app.schedule?.consultation_type || 'video',
            reason: app.notes || undefined,
        };
    };

    const transformToDoctor = (app: AppointmentResponse) => {
        if (!app.doctor) return undefined;

        return {
            id: app.doctor.id,
            name: app.doctor.name,
            specialty: app.doctor.department,
            rating: app.doctor.average_rating || 0,
            reviews: app.ratings_count || 0,
            experience: app.doctor.years_experience || '',
            location: '',
            languages: [],
            fee: parseFloat(app.fee_amount) || 0,
            image: app.doctor.avatar,
            verified: false,
        };
    };

    // Loading Component
    const LoadingState = () => (
        <div className="flex items-center justify-center min-h-[400px]">
            <div className="text-center">
                <Loader2 className="w-8 h-8 animate-spin text-primary mx-auto mb-4" />
                <p className="text-on-surface-variant">Loading appointments...</p>
            </div>
        </div>
    );

    // Error Component
    const ErrorState = () => (
        <div className="flex items-center justify-center min-h-[400px]">
            <div className="text-center">
                <div className="bg-red-100 text-red-800 p-4 rounded-lg">
                    <p className="font-bold">Error loading appointments</p>
                    <p className="text-sm mt-1">{error?.message || 'Please try again later'}</p>
                </div>
            </div>
        </div>
    );

    // Empty State Component
    const EmptyState = ({ type }: { type: 'upcoming' | 'past' }) => (
        <div className="flex items-center justify-center min-h-[400px]">
            <div className="text-center">
                <div className="w-16 h-16 bg-surface-container-low rounded-full flex items-center justify-center mx-auto mb-4">
                    <Calendar className="w-8 h-8 text-on-surface-variant/50" />
                </div>
                <h3 className="text-lg font-bold text-on-surface mb-1">No {type} appointments</h3>
                <p className="text-on-surface-variant text-sm">
                    {type === 'upcoming'
                        ? 'You have no upcoming appointments scheduled.'
                        : 'You have no past appointments to show.'}
                </p>
            </div>
        </div>
    );

    // Filter appointments based on active tab
    // The API already handles filtering by "upcoming" or "past", but we also filter out cancelled ones
    const filterAppointments = (appointments: AppointmentResponse[] | undefined) => {
        if (!appointments) return [];
        return appointments.filter(app => app.status !== 'cancelled');
    };

    // Upcoming Tab Content
    const UpcomingContent = () => {
        if (isLoading) return <LoadingState />;
        if (isError) return <ErrorState />;

        const upcomingApps = filterAppointments(data?.data);
        const pagination = data?.pagination;

        if (upcomingApps.length === 0 && (!pagination || pagination.total === 0)) {
            return <EmptyState type="upcoming" />;
        }

        return (
            <div className="space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {upcomingApps.map((app: AppointmentResponse) => {
                        const transformedApp = transformToAppointment(app);
                        const transformedDoctor = transformToDoctor(app);
                        return (
                            <UpcomingAppointmentCard
                                key={app.appointment_id}
                                appointment={transformedApp}
                                doctor={transformedDoctor}
                                onManageClick={handleManageAppointment}
                                consultationType={app.consultation_type_label || app.schedule?.consultation_type_label}
                                fee={app.fee_amount}
                                joinUrl={app.join_url || app.video_consultation?.join_url}
                                call_now={app.call_now}
                            />
                        );
                    })}
                </div>

                {pagination && pagination.last_page > 1 && (
                    <PaginationControls
                        currentPage={pagination.current_page}
                        totalPages={pagination.last_page}
                        totalItems={pagination.total}
                        itemsPerPage={pagination.per_page}
                        onPageChange={handlePageChange}
                    />
                )}

                <PreAppointmentChecklist />
            </div>
        );
    };

    // Past Tab Content
    const PastContent = () => {
        if (isLoading) return <LoadingState />;
        if (isError) return <ErrorState />;

        const pastApps = filterAppointments(data?.data);
        const pagination = data?.pagination;

        if (pastApps.length === 0 && (!pagination || pagination.total === 0)) {
            return <EmptyState type="past" />;
        }

        return (
            <>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {pastApps.map((app: AppointmentResponse) => {
                        const transformedApp = transformToAppointment(app);
                        const transformedDoctor = transformToDoctor(app);
                        return (
                            <PastAppointmentCard
                                key={app.appointment_id}
                                appointment={transformedApp}
                                doctor={transformedDoctor}
                                onViewDetails={handleViewDetails}
                                consultationType={app.consultation_type_label || app.schedule?.consultation_type_label}
                                fee={app.fee_amount}
                                statusLabel={app.status_label}
                            />
                        );
                    })}
                </div>

                {pagination && pagination.last_page > 1 && (
                    <PaginationControls
                        currentPage={pagination.current_page}
                        totalPages={pagination.last_page}
                        totalItems={pagination.total}
                        itemsPerPage={pagination.per_page}
                        onPageChange={handlePageChange}
                    />
                )}
            </>
        );
    };

    // Reset to page 1 when tab changes
    const handleTabChange = (value: string) => {
        setActiveTab(value as 'upcoming' | 'past');
        setCurrentPage(1);
    };

    // Define tabs with dynamic content
    const tabs = [
        {
            key: 'upcoming',
            label: 'Upcoming',
            content: <UpcomingContent />,
        },
        {
            key: 'past',
            label: 'Past',
            content: <PastContent />,
        },
    ];

    return (
        <>
            <div className="space-y-6">
                <CustomTabs
                    variant="pill"
                    activeTabBg="#013220"
                    activeTabColor="white"
                    tabs={tabs}
                    defaultTab="upcoming"
                    activeTab={activeTab}
                    onTabChange={handleTabChange}
                    tabsListClassName="max-w-md"
                />
            </div>

        </>
    );
};

export default AppointmentsPage;