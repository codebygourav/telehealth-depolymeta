"use client";

import { useMemo, useState, useCallback, useEffect, Suspense } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { useBrowseDoctors } from '@/queries/useBrowseDoctors';
import { useDepartmentsAndSymptoms } from '@/queries/useDepartmentsAndSymptoms';
import SearchBar from '@/components/pages/find-doctor/searchBar'
import DoctorCard from '@/components/DoctorCard';
import LoadingSkeleton from '@/components/pages/find-doctor/LoadingSkeleton';
import CustomDialog from '@/components/custom/Dialogboxs';
import { AlertCircle, CheckCircle2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { ConsultationType, SortOption, Doctor } from '@/types/browse-doctors';
import SelectField from '@/components/custom/SelectField';

import HeroSection from '@/components/hero-section';
import PaginationControls from '@/components/ui/PaginationControls';

interface DialogState {
    open: boolean;
    type: 'danger' | 'success';
    title: string;
    description: string;
}

const FindDoctorsContent = () => {
    const router = useRouter();
    const searchParams = useSearchParams();

    // State
    const [searchTerm, setSearchTerm] = useState('');
    const [specialty, setSpecialty] = useState('all');
    const [consultationType, setConsultationType] = useState<ConsultationType>('all');
    const [sortBy, setSortBy] = useState<SortOption>('highest-rated');
    const [bookingDoctorId, setBookingDoctorId] = useState<string | null>(null);
    const [currentPage, setCurrentPage] = useState(1);
    const itemsPerPage = 9;
    const [dialogState, setDialogState] = useState<DialogState>({
        open: false,
        type: 'danger',
        title: '',
        description: '',
    });

    useEffect(() => {
        const consultationTypeParam = searchParams.get('consultationType');
        const specialtyParam = searchParams.get('specialty');

        if (
            consultationTypeParam === 'all' ||
            consultationTypeParam === 'video' ||
            consultationTypeParam === 'in-person'
        ) {
            setConsultationType(consultationTypeParam);
        }

        if (specialtyParam) {
            setSpecialty(specialtyParam);
        }
    }, [searchParams]);

    const consultationTypeOptions = [
        { value: "all", label: "All Types" },
        { value: "video", label: "Video Consultation" },
        { value: "in-person", label: "In-person Consultation" },
    ];

    // Queries
    const { data: doctorsData, error, isLoading, refetch } = useBrowseDoctors();

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

    // Reset pagination when filters change
    useEffect(() => {
        setCurrentPage(1);
    }, [searchTerm, specialty, consultationType, sortBy]);

    // Paginate doctors
    const paginatedDoctors = useMemo(() => {
        const startIndex = (currentPage - 1) * itemsPerPage;
        return sortedDoctors.slice(startIndex, startIndex + itemsPerPage);
    }, [sortedDoctors, currentPage, itemsPerPage]);

    // Handlers
    const handleClearFilters = useCallback(() => {
        setSpecialty("all");
        setConsultationType("all");
        setSearchTerm('');
    }, []);

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

                <HeroSection
                    title="Find the right care, effortlessly."
                    description="Connect with world-class specialists curated for your health journey. Expert clinical care delivered with a human touch."
                />

                <div className='container-max-width w-full mx-auto'>
                    <div className='flex items-center justify-between lg:flex-row flex-col'>

                        <div className='lg:basis-1/5 basis-full lg:w-auto w-full'>
                            <h3 className='text-2xl font-semibold text-black'>Find a Doctor</h3>
                        </div>

                        <div className='lg:basis-4/5 basis-full lg:w-auto w-full flex md:items-center items-start justify-end gap-2 md:flex-row flex-col flex-wrap lg:mt-0 mt-2 !justify-end sm:justify-start'>
                            <SearchBar value={searchTerm} onChange={setSearchTerm} />

                            <div className='sm:max-w-[500px] w-full flex-col sm:flex-row md:flex-col lg:flex-row flex-1 flex !justify-between gap-2'>
                                <SelectField
                                    name="specialty"
                                    value={consultationType}
                                    onChange={(value) =>
                                        setConsultationType(value as ConsultationType)
                                    }
                                    options={consultationTypeOptions}
                                    placeholder="Select specialty"
                                    className="w-full!"
                                    triggerClassName="w-full !h-auto bg-transparent border border-light-gray rounded-md px-5 py-3.5 text-sm font-medium"
                                />
                                <SelectField
                                    name="specialty"
                                    value={specialty}
                                    onChange={setSpecialty}
                                    options={specialtyOptions}
                                    placeholder="Select specialty"
                                    className="w-full!"
                                    triggerClassName="w-full !h-auto bg-transparent border border-light-gray rounded-md px-5 py-3.5 text-sm font-medium"
                                />
                                {(searchTerm.trim() !== '' || specialty !== "all" || consultationType !== "all") && (
                                    <button
                                        onClick={handleClearFilters}
                                        className="text-sm text-left text-surface-tint font-semibold hover:underline transition-colors pr-4 w-40"
                                    >
                                        Clear all
                                    </button>
                                )}
                            </div>


                        </div>

                    </div>
                </div>

                {/* Main Content */}
                <div className="container-max-width w-full mx-auto flex flex-col lg:flex-row gap-6 md:gap-8 lg:gap-10">

                    <div className="flex-grow">
                        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-4 mb-5 sm:mb-6 md:mb-8">
                            <p className="text-on-surface-variant font-medium text-sm sm:text-base">
                                Showing <span className="text-on-surface font-bold">{sortedDoctors.length} specialists</span> matching your criteria
                            </p>
                        </div>

                        <div className="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4 md:gap-5 lg:gap-6">
                            {paginatedDoctors.map((doctor) => (
                                <DoctorCard
                                    key={doctor.id}
                                    doctor={doctor}
                                    onBook={() => setBookingDoctorId(doctor.id)}
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

                        {sortedDoctors.length > 0 && (
                            <PaginationControls
                                currentPage={currentPage}
                                totalPages={Math.ceil(sortedDoctors.length / itemsPerPage)}
                                totalItems={sortedDoctors.length}
                                itemsPerPage={itemsPerPage}
                                onPageChange={setCurrentPage}
                            />
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

const FindDoctors = () => {
    return (
        <Suspense fallback={<LoadingSkeleton />}>
            <FindDoctorsContent />
        </Suspense>
    );
};

export default FindDoctors;
