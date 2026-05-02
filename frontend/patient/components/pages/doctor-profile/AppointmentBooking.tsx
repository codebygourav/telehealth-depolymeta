import { useState, useEffect } from 'react';
import { ChevronRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import AppointmentTypeSelector from './AppointmentTypeSelector';
import DateSelector from './DateSelector';
import TimeSelector from './TimeSelector';
import { useBookAppointment } from '@/mutations/useBookAppointment';
import type { DoctorDetailData, DoctorAvailabilitySlot } from '@/types/doctor-details';

interface AppointmentBookingProps {
    doctor: DoctorDetailData;
    onBookingSuccess: (appointmentId: string) => void;
    onBookingError: (error: string) => void;
}

const AppointmentBooking = ({ doctor, onBookingSuccess, onBookingError }: AppointmentBookingProps) => {

    // console.log("doctor", doctor);

    const [appointmentType, setAppointmentType] = useState<'in_person' | 'video' | null>(null);
    const [selectedSlot, setSelectedSlot] = useState<DoctorAvailabilitySlot | null>(null);
    const [selectedDateSlot, setSelectedDateSlot] = useState<DoctorAvailabilitySlot | null>(null);

    const { mutate: bookAppointment, isPending: isBooking } = useBookAppointment();

    // Set default appointment type: video first, then clinic
    useEffect(() => {
        if (doctor.appointment_types?.video) {
            setAppointmentType('video');
        } else if (doctor.appointment_types?.in_person) {
            setAppointmentType('in_person');
        }
    }, [doctor.appointment_types]);

    const availableSlots = doctor.availability?.flatMap(day => day.slots) || [];

    const filteredSlots = availableSlots.filter(slot => {
        if (appointmentType === 'in_person') return slot.consultation_type === 'in_person';
        if (appointmentType === 'video') return slot.consultation_type === 'video';
        return true;
    });

    // Get slots for selected date
    const slotsForSelectedDate = selectedDateSlot
        ? filteredSlots.filter(slot => slot.date === selectedDateSlot.date)
        : [];

    const handleDateSelect = (slot: DoctorAvailabilitySlot) => {
        setSelectedDateSlot(slot);
        setSelectedSlot(null);
    };

    const handleTimeSelect = (slot: DoctorAvailabilitySlot) => {
        setSelectedSlot(slot);
    };

    const handleBooking = () => {
        if (!selectedSlot) return;

        const payload = {
            doctor_id: doctor.id,
            availability_id: selectedSlot.id,
            appointment_date: selectedSlot.date,
            appointment_time: selectedSlot.start_time,
            consultation_type: selectedSlot.consultation_type,
            opd_type: 'general',
        };

        bookAppointment(payload, {
            onSuccess: (response) => {
                const appointmentId = response?.data?.appointment?.id;
                const appointmentData = response?.data;
                if (appointmentId && appointmentData) {
                    onBookingSuccess(appointmentId);
                } else {
                    onBookingError('Failed to get appointment details');
                }
            },
            onError: (error) => {
                const errorMessage = error.response?.data?.message || error.message || 'Failed to book appointment. Please try again.';
                onBookingError(errorMessage);
            },
        });
    };

    const isBookingDisabled = !appointmentType || !selectedSlot || isBooking;

    return (
        <div className="border-[#E7E8EB] border rounded-lg p-5 shadow-[0px_2px_4px_0px_#0000001A] space-y-5">

            <div className="space-y-2">
                <h3 className="text-[#1F1E1E] font-bold text-2xl">
                    Book Appointment
                </h3>
                <p className="text-[#4D4D4D] text-base">Choose your preferred date and time.</p>
            </div>

            <AppointmentTypeSelector
                value={appointmentType}
                onChange={setAppointmentType}
                inPersonAvailable={doctor.appointment_types?.in_person}
                videoAvailable={doctor.appointment_types?.video}
            />

            {appointmentType && (
                <div className="space-y-6 animate-in fade-in slide-in-from-top-4 duration-500">
                    <DateSelector
                        slots={filteredSlots}
                        selectedSlot={selectedDateSlot}
                        onSelectSlot={handleDateSelect}
                    />

                    {selectedDateSlot && slotsForSelectedDate.length > 0 && (
                        <TimeSelector
                            slots={slotsForSelectedDate}
                            selectedSlot={selectedSlot}
                            onSelectSlot={handleTimeSelect}
                        />
                    )}
                </div>
            )}

            <Button
                onClick={handleBooking}
                disabled={isBookingDisabled}
                variant="default"
                size="lg"
                className="w-full py-3 rounded-md font-semibold transition-all"
            >
                {isBooking ? "Booking..." : `Book Appointment (₹${selectedSlot?.consultation_fee || 0}.00)`}
                <ChevronRight size={14} color='#fff' strokeWidth={3} />
            </Button>
        </div>
    );
};

export default AppointmentBooking;