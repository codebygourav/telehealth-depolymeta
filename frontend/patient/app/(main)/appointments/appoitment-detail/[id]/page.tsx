'use client';

import { fetchAppointmentById } from '@/api/appointment-detail';
import { DetailHeader } from '@/components/custom/DetailHeader';
import AddReviewsDialouge from '@/components/pages/appointments/addReviewsDialouge';
import { AppointmentInfoCards } from '@/components/pages/appointments/AppointmentInfoCards';
import { MedicineDetailView } from '@/components/pages/my-medicines/MedicineDetailView';
import { Pill } from 'lucide-react';
import { useParams, useRouter } from 'next/navigation';
import { useEffect, useState } from 'react';

export default function AppointmentDetailPage() {
    const { id } = useParams();
    const router = useRouter();

    const [data, setData] = useState<any>(null);
    const [loading, setLoading] = useState(true);

    console.log(data);
    console.log("appointment id :", data?.appointment_id);
    console.log("appointment status :", data?.status);
    console.log("appointment doctor id :", data?.doctor?.id);
    console.log("appointment doctor name :", data?.doctor?.name);


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

    if (loading) return <p className="text-center mt-10">Loading...</p>;
    if (!data) return <p className="text-center mt-10">No Data</p>;

    const { medical_reports, notes } = data;

    return (
        <div className="min-h-screen bg-gray-50">
            <div className="max-w-8xl mx-auto">
                <DetailHeader
                    title="Appointment Details"
                />

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* LEFT COLUMN - Main Content */}
                    <div className="lg:col-span-3 space-y-6">
                        <AppointmentInfoCards data={data} />
                    </div>

                    {/* Medical Details & Prescription - MedicineDetailView shown by default */}
                </div>
                <div className="overflow-hidden">
                    <div className="pt-8 pb-4 border-b border-gray-100 bg-gray-50">
                        <h3 className="font-semibold md:text-2xl text-lg text-gray-900 flex items-center gap-2">
                            <Pill className="w-5 h-5 text-emerald-600" />
                            Medical Details & Prescription
                        </h3>
                    </div>

                    {/* Show notes if they exist */}
                    {notes && (
                        <div className=" p-6 bg-gray-100  rounded-xl italic text-gray-600 border-l-4 border-emerald-500">
                            <h3 className="text-sm">Symptoms Reported</h3>
                            <div className="text-base mt-2">"{notes}"</div>
                        </div>
                    )}

                    {/* Show medicine details without header */}
                    <div className="mt-6 w-full [&>div]:max-w-full [&>div]:mx-0">
                        <MedicineDetailView
                            prescriptionId={id as string}
                            doctorUserId={data.doctor.user_id}
                            onBack={() => { }}
                            showTopHeader={false}
                            showDoctorHead={false}
                            cardGrid="grid-cols-1 md:grid-cols-2 gap-6"
                            footerActionGrid="grid-cols-1 md:grid-cols-2 gap-6"
                        />
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
