import { Doctor, Appointment } from "./types/appointment";
import { MedicalRecord } from "./types/medical-reports";

export const DOCTORS: Doctor[] = [
    {
        id: '1',
        name: 'Dr. Sarah Chen',
        specialty: 'Senior Cardiologist',
        rating: 4.8,
        reviews: 1200,
        experience: '15 years',
        location: 'Evergreen Medical Center',
        languages: ['English', 'Hindi', 'Punjabi'],
        fee: 100,
        image: 'https://picsum.photos/seed/doctor1/400/400',
        verified: true,
        education: ['Harvard Medical School', 'Johns Hopkins University'],
        summary: 'Dr. Sarah Chen is a board-certified cardiologist with over 15 years of experience in managing complex heart conditions. She specializes in preventative cardiology and non-invasive diagnostic procedures.',
        availability: [
            { day: 'Mon', slots: ['09:00 AM', '10:30 AM', '02:00 PM'] },
            { day: 'Wed', slots: ['11:00 AM', '01:30 PM', '04:00 PM'] },
            { day: 'Fri', slots: ['09:30 AM', '12:00 PM', '03:30 PM'] },
        ]
    },
    {
        id: '2',
        name: 'Dr. Marcus Thorne',
        specialty: 'Neurologist',
        rating: 4.9,
        reviews: 120,
        experience: '12 years',
        location: 'NYC Health',
        languages: ['English', 'Spanish'],
        fee: 120,
        image: 'https://picsum.photos/seed/doctor2/400/400',
        verified: true,
        summary: 'Dr. Marcus Thorne is a specialist in neurological disorders with a focus on migraine management and sleep disorders.'
    },
    {
        id: '3',
        name: 'Dr. Elena Rodriguez',
        specialty: 'Pediatrician',
        rating: 4.8,
        reviews: 95,
        experience: '10 years',
        location: 'Children\'s First',
        languages: ['English', 'Spanish', 'Portuguese'],
        fee: 90,
        image: 'https://picsum.photos/seed/doctor3/400/400',
        verified: true,
        summary: 'Dr. Elena Rodriguez provides compassionate care for children from infancy through adolescence.'
    },
    {
        id: '4',
        name: 'Dr. James Wilson',
        specialty: 'Dermatologist',
        rating: 5.0,
        reviews: 210,
        experience: '22 years',
        location: 'Skin Care Inst.',
        languages: ['English', 'French'],
        fee: 150,
        image: 'https://picsum.photos/seed/doctor4/400/400',
        verified: true,
        summary: 'Dr. James Wilson is a world-renowned dermatologist specializing in skin cancer detection and aesthetic treatments.'
    }
];

export const APPOINTMENTS: Appointment[] = [
    {
        id: 'app-1',
        doctorId: '2',
        doctorName: 'Dr. Marcus Thorne',
        doctorImage: 'https://picsum.photos/seed/doctor2/400/400',
        date: 'Sat, Jun 18',
        time: '2:30 PM',
        status: 'upcoming',
        type: 'video',
        reason: 'Follow-up on hypertension'
    },
    {
        id: 'app-2',
        doctorId: '3',
        doctorName: 'Dr. Elena Rodriguez',
        doctorImage: 'https://picsum.photos/seed/doctor3/400/400',
        date: 'Sat, Jun 18',
        time: '2:30 PM',
        status: 'upcoming',
        type: 'video',
        reason: 'Annual checkup'
    },
    {
        id: 'app-3',
        doctorId: '1',
        doctorName: 'Dr. Sarah Chen',
        doctorImage: 'https://picsum.photos/seed/doctor1/400/400',
        date: 'Sat, Jun 10',
        time: '10:00 AM',
        status: 'completed',
        type: 'video',
        reason: 'Initial consultation'
    },
    {
        id: 'app-4',
        doctorId: '4',
        doctorName: 'Dr. James Wilson',
        doctorImage: 'https://picsum.photos/seed/doctor4/400/400',
        date: 'Jun 05, 2024',
        time: '11:30 AM',
        status: 'cancelled',
        type: 'video',
        reason: 'Skin rash'
    }
];


export const MEDICAL_RECORDS: MedicalRecord[] = [
    {
        id: 'rec-1',
        title: 'Complete Blood Count',
        date: 'Oct 15, 2024',
        doctor: 'Dr. Sarah Chen',
        type: 'Lab Result',
        status: 'Final'
    },
    {
        id: 'rec-2',
        title: 'Lisinopril 10mg Refill',
        date: 'Oct 12, 2024',
        doctor: 'Dr. Sarah Chen',
        type: 'Prescription',
        status: 'Final'
    },
    {
        id: 'rec-3',
        title: 'Chest X-Ray',
        date: 'Sep 20, 2024',
        doctor: 'Dr. Marcus Thorne',
        type: 'Imaging',
        status: 'Final'
    }
];