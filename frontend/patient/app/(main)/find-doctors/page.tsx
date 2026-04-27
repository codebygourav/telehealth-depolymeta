"use client";

import { useMemo, useState, useCallback } from 'react';
import { useRouter } from 'next/navigation';
import { useBrowseDoctors } from '@/queries/useBrowseDoctors';
import { useDepartmentsAndSymptoms } from '@/queries/useDepartmentsAndSymptoms';
import SearchBar from '@/components/pages/find-doctor/searchBar';
import FilterSidebar from '@/components/pages/find-doctor/FilterSidebar';
import SortDropdown from '@/components/pages/find-doctor/SortDropdown';
import DoctorCard from '@/components/pages/find-doctor/DoctorCard';
import LoadingSkeleton from '@/components/pages/find-doctor/LoadingSkeleton';
import CustomDialog from '@/components/custom/Dialogboxs';
import { AlertCircle, CheckCircle2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { ConsultationType, SortOption, Doctor } from '@/types/browse-doctors';

interface DialogState {
    open: boolean;
    type: 'danger' | 'success';
    title: string;
    description: string;
}

const FindDoctors = () => {
    const router = useRouter();

    // State
    const [searchTerm, setSearchTerm] = useState('');
    const [specialty, setSpecialty] = useState('all');
    const [consultationType, setConsultationType] = useState<ConsultationType>('all');
    const [sortBy, setSortBy] = useState<SortOption>('highest-rated');
    const [bookingDoctorId, setBookingDoctorId] = useState<string | null>(null);
    const [dialogState, setDialogState] = useState<DialogState>({
        open: false,
        type: 'danger',
        title: '',
        description: '',
    });

    // Queries
    const { data: doctorsData, error, isLoading, refetch } = useBrowseDoctors();

    console.log("doctor data", doctorsData);
    
    const { data: departmentsData } = useDepartmentsAndSymptoms();

    const doctors = doctorsData?.data || [];

    // Memoized specialty options
    const specialtyOptions = useMemo(() => {
        const apiOptions = departmentsData?.data?.map((item) => ({
            value: item.department.name.toLowerCase().replace(/\s+/g, "-"),
            label: item.department.name,
        })) ?? [];

        // Remove duplicates based on value
        const uniqueOptions = apiOptions.filter((option, index, self) =>
            index === self.findIndex((o) => o.value === option.value)
        );

        return [{ value: "all", label: "All Specialties" }, ...uniqueOptions];
    }, [departmentsData]);

    // Filter doctors
    const filteredDoctors = useMemo(() => {
        return doctors.filter((doctor: Doctor) => {
            // Handle speciality as string, single object, or array of objects
            const getDoctorSpecialities = (): string[] => {
                if (Array.isArray(doctor.speciality)) {
                    return doctor.speciality.map(s => typeof s === 'string' ? s.toLowerCase() : s.name.toLowerCase());
                }
                if (typeof doctor.speciality === 'string') {
                    return [doctor.speciality.toLowerCase()];
                }
                return [doctor.speciality?.name?.toLowerCase() || ''];
            };

            const doctorSpecialities = getDoctorSpecialities();

            const matchesSearch = searchTerm === '' ||
                doctor.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                doctorSpecialities.some(s => s.includes(searchTerm.toLowerCase())) ||
                (doctor.hospital && doctor.hospital.toLowerCase().includes(searchTerm.toLowerCase()));

            const matchesSpecialty = specialty === 'all' ||
                doctorSpecialities.some(s => s.replace(/\s+/g, "-") === specialty);

            const matchesConsultationType = consultationType === 'all' ||
                (Array.isArray(doctor.consultation_type_label)
                    ? doctor.consultation_type_label.some((label) => {
                        const normalizedLabel = label?.toLowerCase().replace(/[_\s-]+/g, '');
                        const normalizedType = consultationType.toLowerCase().replace(/[_\s-]+/g, '');
                        return normalizedLabel?.includes(normalizedType);
                    })
                    : typeof doctor.consultation_type_label === 'string' &&
                    (doctor.consultation_type_label as string).toLowerCase().replace(/[_\s-]+/g, '').includes(
                        consultationType.toLowerCase().replace(/[_\s-]+/g, '')
                    )
                );

            return matchesSearch && matchesSpecialty && matchesConsultationType;
        });
    }, [doctors, searchTerm, specialty, consultationType]);

    // Sort doctors
    const sortedDoctors = useMemo(() => {
        return [...filteredDoctors].sort((a: Doctor, b: Doctor) => {
            switch (sortBy) {
                case 'highest-rated':
                    return (b.rating || 0) - (a.rating || 0);
                case 'price-low-high':
                    return (a.consultation_fee || 0) - (b.consultation_fee || 0);
                case 'next-available':
                    // TODO: Implement availability sorting when data is available
                    return 0;
                default:
                    return 0;
            }
        });
    }, [filteredDoctors, sortBy]);

    // Handlers
    const handleClearFilters = useCallback(() => {
        setSpecialty("all");
        setConsultationType("all");
        setSearchTerm('');
    }, []);

    const handleBooking = useCallback(async (doctorId: string) => {
        setBookingDoctorId(doctorId);

        try {
            // Simulate API call - replace with actual booking API
            await new Promise(resolve => setTimeout(resolve, 1500));

            setDialogState({
                open: true,
                type: 'success',
                title: 'Appointment Booked!',
                description: 'Your appointment has been successfully scheduled. Check your email for details.',
            });

            setTimeout(() => {
                router.push('/appointments');
            }, 2000);
        } catch (error) {
            console.error('Booking failed:', error);
            setDialogState({
                open: true,
                type: 'danger',
                title: 'Booking Failed',
                description: error instanceof Error ? error.message : 'Unable to book appointment. Please try again later.',
            });
        } finally {
            setBookingDoctorId(null);
        }
    }, [router]);

    const handleCloseDialog = useCallback(() => {
        setDialogState(prev => ({ ...prev, open: false }));
    }, []);

    // Loading state
    if (isLoading) {
        return <LoadingSkeleton />;
    }

    // Error state
    if (error) {
        return (
            <div className="text-center py-12 px-4 sm:px-6">
                <AlertCircle className="mx-auto h-10 w-10 sm:h-12 sm:w-12 text-destructive mb-4" />
                <p className="text-destructive mb-4 text-sm sm:text-base">Failed to load doctors. Please try again.</p>
                <Button
                    onClick={() => refetch()}
                    variant="default"
                    size="default"
                >
                    Retry
                </Button>
            </div>
        );
    }

    return (
        <>
            <div className="space-y-6 sm:space-y-8 md:space-y-10 lg:space-y-12">
                {/* Hero Section */}
                <section>
                    <div className="max-w-3xl">
                        <h1 className="font-headline text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-extrabold text-primary-container tracking-tight mb-3 sm:mb-4">
                            Find the right care, <span className="text-on-primary-container">effortlessly.</span>
                        </h1>
                        <p className="text-on-surface-variant text-sm sm:text-base md:text-lg max-w-xl mb-5 sm:mb-6 md:mb-8">
                            Connect with world-class specialists curated for your health journey. Expert clinical care delivered with a human touch.
                        </p>
                        <SearchBar value={searchTerm} onChange={setSearchTerm} />
                    </div>
                </section>

                {/* Main Content */}
                <div className="flex flex-col lg:flex-row gap-6 md:gap-8 lg:gap-10">
                    <FilterSidebar
                        specialty={specialty}
                        consultationType={consultationType}
                        specialtyOptions={specialtyOptions}
                        onSpecialtyChange={setSpecialty}
                        onConsultationTypeChange={setConsultationType}
                        onClearFilters={handleClearFilters}
                    />

                    <div className="flex-grow">
                        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-4 mb-5 sm:mb-6 md:mb-8">
                            <p className="text-on-surface-variant font-medium text-sm sm:text-base">
                                Showing <span className="text-on-surface font-bold">{sortedDoctors.length} specialists</span> matching your criteria
                            </p>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-5 lg:gap-6">
                            {sortedDoctors.map((doctor) => (
                                <DoctorCard
                                    key={doctor.id}
                                    doctor={doctor}
                                    onBook={() => handleBooking(doctor.id)}
                                    isLoading={bookingDoctorId === doctor.id}
                                />
                            ))}
                        </div>

                        {sortedDoctors.length === 0 && (
                            <div className="text-center py-8 sm:py-10 md:py-12">
                                <p className="text-on-surface-variant mb-4 text-sm sm:text-base">No doctors found matching your criteria.</p>
                                <Button
                                    onClick={handleClearFilters}
                                    variant="outline"
                                    size="default"
                                >
                                    Clear all filters
                                </Button>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Custom Dialog */}
            <CustomDialog
                open={dialogState.open}
                onClose={handleCloseDialog}
                type={dialogState.type}
                title={dialogState.title}
                description={dialogState.description}
                confirmText="OK"
                cancelText="Cancel"
                onConfirm={handleCloseDialog}
                icon={dialogState.type === 'danger' ?
                    <AlertCircle className="h-5 w-5 sm:h-6 sm:w-6 text-destructive" /> :
                    <CheckCircle2 className="h-5 w-5 sm:h-6 sm:w-6 text-green-600" />
                }
            />
        </>
    );
};

export default FindDoctors;