'use client';

import { fetchAppointmentById } from '@/api/appointment-detail';
import AddReviewsDialouge from '@/components/pages/appointments/addReviewsDialouge';
import { AppointmentInfoCards } from '@/components/pages/appointments/AppointmentInfoCards';
import { MedicineCard } from '@/components/MedicineCard';
import { Pill } from 'lucide-react';
import { useParams } from 'next/navigation';
import { useEffect, useState } from 'react';
import { MedicineDetailView } from '@/components/pages/my-medicines/MedicineDetailView';
import { MedicineActionPlan } from '@/components/pages/my-medicines/MedicineActionPlan';
import HeroSection from '@/components/hero-section';

type AppointmentDetail = {
    notes?: string;
    doctor: {
        user_id: string;
        id?: string;
        name?: string;
    };
    status?: string;
    can_add_review?: boolean;
    appointment_id?: string;
    prescriptions?: any;
};

export default function AppointmentDetailPage() {

    const { id } = useParams();
    const [data, setData] = useState<AppointmentDetail | null>(null);
    const [loading, setLoading] = useState(true);
    const [selectedMedicineId, setSelectedMedicineId] = useState<string | null>(null);

    useEffect(() => {
        const getData = async () => {
            try {
                const res = await fetchAppointmentById(id as string);

                setData(res);
            } catch (err) {
                console.error(err);
            } finally {
                setLoading(false);
            }
        };

        if (id) getData();
    }, [id]);

    if (loading) return <p className="mt-10 text-center">Loading...</p>;
    if (!data) return <p className="mt-10 text-center">No Data</p>;

    if (selectedMedicineId) {
        return (
            <MedicineDetailView
                prescriptionId={selectedMedicineId}
                onBack={() => setSelectedMedicineId(null)}
            />
        );
    }

    const { notes } = data;
    const doctorId = data?.doctor?.id || "";
    const nextVisitDate = data?.prescriptions?.next_visit_date || "";

    return (
        <div className="min-h-screen bg-gray-50">

            <HeroSection
                title="Appointments Detail"
                description="Connect with world-class specialists curated for your health journey. Expert clinical care delivered with a human touch."
            />

            <div className="container-max-width mx-auto w-full">

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3 mt-12">

                    {/* LEFT COLUMN - Main Content */}
                    <div className="space-y-6 lg:col-span-3">
                        <AppointmentInfoCards data={data} />
                    </div>
                </div>

                {/* Medical Details & prescriptions - MedicineDetailView shown by default */}
                <div className="overflow-hidden mb-10">
                    <div className="pt-8 pb-4 border-b border-gray-100 bg-gray-50">
                        <h3 className="flex items-center gap-2 text-lg font-semibold text-[#1F1E1E]">
                            <Pill size={20} color='#055BD9' />
                            Medical Details & Prescriptions
                        </h3>
                    </div>

                    {/* Show notes if they exist */}
                    {notes && (
                        <div className="p-6 italic text-gray-600 bg-gray-100 border-l-4 rounded-xl border-[#055BD9]">
                            <h3 className="text-sm">Symptoms Reported</h3>
                            <div className="mt-2 text-base">
                                &quot;{notes}&quot;
                            </div>
                        </div>
                    )}

                    <div className="mt-6 grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
                        <div className="lg:col-span-8 h-full w-full g-border global-radius p-4 bg-white flex flex-col">

                            <div className="flex-1 h-full">
                                {data.prescriptions ? (
                                    <MedicineCard
                                        prescription={data.prescriptions}
                                        onViewDetail={(id) => setSelectedMedicineId(id)}
                                    />
                                ) : (
                                    <div className="p-8 h-full flex items-center justify-center text-center text-gray-400 bg-white border border-gray-100 rounded-2xl">
                                        No prescription details available.
                                    </div>
                                )}
                            </div>
                        </div>

                        <div className="lg:col-span-4 h-full">
                            <MedicineActionPlan
                                showConclusion={false}
                                nextVisitDate={nextVisitDate}
                                doctor_id={doctorId}
                                footerActionGridClassName="grid-cols-1 h-full p-0"
                                buttonClass="!p-2"
                                nextVisitCardClassName="!p-4"
                            />
                        </div>
                    </div>

                    <AddReviewsDialouge
                        appointmentStatus={data?.status}
                        hasExistingReview={false}
                        doctorName={data?.doctor?.name}
                        doctorId={data?.doctor?.id}
                        canSubmit={data?.can_add_review}
                        appointmentId={data?.appointment_id}
                        onSubmit={(result) => console.log(result)}
                    />

                </div>
            </div>
        </div>
    );
}
