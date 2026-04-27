"use client"

import { useState, use, useEffect } from 'react';
import { ChevronLeft, X, Upload, ChevronDown } from 'lucide-react';
import { AnimatePresence, motion } from 'motion/react';
import { useRouter } from 'next/navigation';
import { Report } from '@/types/medical-reports';
import { useAppointmentDetail } from '@/queries/useAppointmentSummary';
import { useUpdateAppointmentInformation, useDeleteMedicalReport } from '@/queries/useManageAppointment';
import { useCancelAppointment } from '@/mutations/useCancelAppointment';
import { toast } from 'sonner';
import { useMedicalReports } from '@/queries/useGetMedicalReports';
import { useAuth } from '@/context/userContext';

// Components
import DoctorInfoCard from '@/components/pages/appointments/manage-appointment/DoctorInfoCard';
import AppointmentInfo from '@/components/pages/appointments/manage-appointment/AppointmentInfo';
import ReportsAndNotes from '@/components/pages/appointments/manage-appointment/ReportsAndNotes';
import AddReportModal from '@/components/pages/appointments/manage-appointment/AddReportModal';
import CancelConfirmationModal from '@/components/pages/appointments/manage-appointment/CancelConfirmationModal';
import { DetailHeader } from '@/components/custom/DetailHeader';

interface PageProps {
    params: Promise<{
        id: string;
    }>;
}

const REPORT_TYPES = [
    "Blood Test",
    "X-Ray",
    "MRI Scan",
    "Ultrasound",
    "Prescription",
    "Other"
];

export default function ManageAppointment({ params }: PageProps) {

    const { id: appointmentId } = use(params);
    const router = useRouter();

    // Fetch appointment details using the proper hook
    const { data, isLoading, error } = useAppointmentDetail(appointmentId);
    const appointment = data?.data;
    // console.log("Appointments : ", appointment?.status);
    

    const { user } = useAuth();

    const { data: medicalReports, isLoading: isLoadingMedicalReports } = useMedicalReports(user?.id);
    // Mutations
    const { mutate: updateInformation, isPending: isUpdatingInfo } = useUpdateAppointmentInformation();
    const { mutate: deleteReport } = useDeleteMedicalReport();
    const { mutate: cancelAppointment, isPending: isCancelling } = useCancelAppointment();


    const [reports, setReports] = useState<Report[]>([]);
    const [note, setNote] = useState('');

    // UI State
    const [activeMenu, setActiveMenu] = useState<string | null>(null);
    const [showAddReport, setShowAddReport] = useState(false);
    const [showEditReport, setShowEditReport] = useState<Report | null>(null);
    const [showEditNote, setShowEditNote] = useState(false);
    const [showCancelConfirm, setShowCancelConfirm] = useState(false);

    // Edit Modal State
    const [editTitle, setEditTitle] = useState('');
    const [editType, setEditType] = useState('');
    const [editFile, setEditFile] = useState<File | null>(null);

    useEffect(() => {
        if (showEditReport) {
            setEditTitle(showEditReport.title);
            setEditType(showEditReport.type);
            setEditFile(null);
        }
    }, [showEditReport]);

    // Sync state with API data
    useEffect(() => {
        if (appointment) {
            if (appointment.medical_reports) {
                const mappedReports: Report[] = appointment.medical_reports.map((r, index) => ({
                    id: r.id || `api-${index}`,
                    title: r.title || 'Medical Report',
                    date: r.report_date || '',
                    type: r.type_label || r.type || 'General',
                    fileName: r.file_url ? r.file_url.split('/').pop() || 'report.pdf' : 'report.pdf',
                    fileUrl: r.file_url
                }));
                setReports(mappedReports);
            }
            if (appointment.notes) {
                setNote(appointment.notes);
            }
        }
    }, [appointment]);

    const handleDeleteReport = (id: string) => {
        // If it's a server ID (API fallback is 'api-', local random is short), delete from server
        if (id && !id.startsWith('api-') && id.length > 15) {
            deleteReport(id, {
                onSuccess: () => {
                    toast.success('Report deleted successfully');
                    setActiveMenu(null);
                },
                onError: (err) => {
                    toast.error('Failed to delete report');
                    console.error('Delete error:', err);
                }
            });
        } else {
            // Local-only report (newly added), just remove from state
            setReports(prev => prev.filter(r => r.id !== id));
            setActiveMenu(null);
        }
    };

    const handleAddReport = (newReport: Report) => {
        setReports(prev => [...prev, newReport]);
    };

    const handleViewReport = (report: Report) => {
        if (report.fileUrl) {
            window.open(report.fileUrl, '_blank');
        } else if (report.file) {
            // If it's a newly added file (local), we can create a temporary URL
            const url = URL.createObjectURL(report.file);
            window.open(url, '_blank');
        } else {
            toast.error('Report file is not available');
        }
    };

    const handleSaveEditedReport = () => {
        if (!showEditReport) return;

        const updatedReports = reports.map(r => {
            if (r.id === showEditReport.id) {
                return {
                    ...r,
                    title: editTitle,
                    type: editType,
                    file: editFile || r.file,
                    fileName: editFile ? editFile.name : r.fileName
                };
            }
            return r;
        });

        setReports(updatedReports);

        // Trigger backend update
        updateInformation({
            appointmentId,
            notes: note,
            reports: updatedReports.map(r => ({
                id: (r.id && !r.id.startsWith('api-') && r.id.length > 15) ? r.id : undefined,
                name: r.title,
                type: r.type,
                file: r.file
            }))
        }, {
            onSuccess: () => {
                toast.success('Report updated successfully');
                setShowEditReport(null);
            },
            onError: (err) => {
                toast.error('Failed to update report');
                console.error('Update report error:', err);
            }
        });
    };

    const handleConfirmCancel = () => {
        cancelAppointment(appointmentId, {
            onSuccess: () => {
                toast.success('Appointment cancelled successfully');
                setShowCancelConfirm(false);
                router.push('/appointments');
            },
            onError: (err) => {
                toast.error('Failed to cancel appointment');
                console.error('Cancel appointment error:', err);
            }
        });
    };

    // console.log("appointment status", appointment?.status);

    const handleModalSubmit = (newNote: string) => {
        updateInformation({
            appointmentId,
            notes: newNote,
            reports: reports.map(r => ({
                // Use the real ID if it's not our local fallback or a short random string
                id: (r.id && !r.id.startsWith('api-') && r.id.length > 15) ? r.id : undefined,
                name: r.title,
                type: r.type,
                file: r.file
            }))
        }, {
            onSuccess: () => {
                toast.success('Information updated successfully');
                setShowAddReport(false);
            },
            onError: (err: any) => {
                toast.error('Failed to update information');
                console.error('Update error:', err);
            }
        });
    };

    return (
        <div>

            {/* Header */}
            <header className="flex items-center gap-4 mb-8">
                <DetailHeader
                    title='Manage Appointment'
                />
            </header>

            <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">

                {/* Left Column: Info */}
                <div className="lg:col-span-7 space-y-8">
                    <DoctorInfoCard doctor={appointment?.doctor} />
                    <AppointmentInfo
                        date={appointment?.schedule?.date_formatted}
                        time={appointment?.schedule?.time_formatted}
                        booking_type={appointment?.schedule?.consultation_type_label}
                        patient_name={appointment?.patient?.name}
                        patient_age={appointment?.patient?.age_formatted}
                        patient_gender={appointment?.patient?.gender_formatted}
                        appointment_status={appointment?.status_label}
                    />
                </div>

                {/* Right Column: Reports & Notes */}
                <ReportsAndNotes
                    reports={reports}
                    note={note}
                    doctorId={appointment?.doctor?.id}
                    appointmentId={appointmentId}
                    activeMenu={activeMenu}
                    setActiveMenu={setActiveMenu}
                    onAddReport={() => setShowAddReport(true)}
                    onViewReport={handleViewReport}
                    onEditReport={setShowEditReport}
                    onDeleteReport={handleDeleteReport}
                    onCancel={() => setShowCancelConfirm(true)}
                    appointmentStatus={appointment?.status}
                />
            </div>

            {/* Modals */}
            <AnimatePresence>

                {/* Add Report Modal */}
                <AddReportModal
                    key="add-report-modal"
                    isOpen={showAddReport}
                    onClose={() => setShowAddReport(false)}
                    reports={reports}
                    onAddReport={handleAddReport}
                    onDeleteReport={handleDeleteReport}
                    onSubmit={handleModalSubmit}
                    initialNote={note}
                    isUpdating={isUpdatingInfo}
                    patientReports={medicalReports?.data || []}
                    isLoadingReports={isLoadingMedicalReports}
                />

                <CancelConfirmationModal
                    key="cancel-confirm-modal"
                    isOpen={showCancelConfirm}
                    onClose={() => setShowCancelConfirm(false)}
                    onConfirm={handleConfirmCancel}
                    isPending={isCancelling}
                />

                {/* Edit Report Modal */}
                {showEditReport && (
                    <div key="edit-report-modal" className="fixed inset-0 z-50 flex items-center justify-center p-4">
                        <motion.div
                            initial={{ opacity: 0 }}
                            animate={{ opacity: 1 }}
                            exit={{ opacity: 0 }}
                            onClick={() => setShowEditReport(null)}
                            className="absolute inset-0 bg-primary/40 backdrop-blur-sm"
                        />
                        <motion.div
                            initial={{ opacity: 0, scale: 0.9, y: 20 }}
                            animate={{ opacity: 1, scale: 1, y: 0 }}
                            exit={{ opacity: 0, scale: 0.9, y: 20 }}
                            className="relative w-full max-w-lg bg-white rounded-[40px] shadow-2xl overflow-hidden"
                        >
                            <div className="p-8">
                                <div className="flex items-center justify-between mb-8">
                                    <h3 className="text-xl font-bold font-headline text-primary italic">Edit</h3>
                                    <button onClick={() => setShowEditReport(null)} className="p-2 hover:bg-surface-container rounded-full">
                                        <X className="w-6 h-6" />
                                    </button>
                                </div>

                                <div className="space-y-6">
                                    <div>
                                        <label className="block text-[10px] font-bold text-on-surface-variant uppercase tracking-widest mb-2 italic">Report Title</label>
                                        <input
                                            type="text"
                                            value={editTitle}
                                            onChange={(e) => setEditTitle(e.target.value)}
                                            className="w-full p-4 bg-surface-container-low border border-outline-variant/10 rounded-2xl font-bold text-primary focus:outline-none focus:ring-2 focus:ring-emerald-500/20 italic"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-[10px] font-bold text-on-surface-variant uppercase tracking-widest mb-2 italic">Report Type</label>
                                        <div className="relative">
                                            <select
                                                value={editType}
                                                onChange={(e) => setEditType(e.target.value)}
                                                className="w-full p-4 bg-surface-container-low border border-outline-variant/10 rounded-2xl font-bold text-primary focus:outline-none focus:ring-2 focus:ring-emerald-500/20 italic appearance-none"
                                            >
                                                <option value="">Select an option</option>
                                                {REPORT_TYPES.map(type => (
                                                    <option key={type} value={type}>{type}</option>
                                                ))}
                                            </select>
                                            <ChevronDown className="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-outline-variant pointer-events-none" />
                                        </div>
                                    </div>
                                    <div>
                                        <label className="block text-[10px] font-bold text-on-surface-variant uppercase tracking-widest mb-2 italic">Replace File (optional)</label>
                                        <div className="relative group">
                                            <input
                                                type="file"
                                                onChange={(e) => setEditFile(e.target.files?.[0] || null)}
                                                className="absolute inset-0 opacity-0 cursor-pointer z-10"
                                            />
                                            <div className="p-4 bg-surface-container-low border border-outline-variant/10 rounded-2xl flex items-center justify-between italic group-hover:bg-surface-container-low/80 transition-all">
                                                <span className="text-xs text-on-surface-variant truncate max-w-[200px]">
                                                    {editFile ? editFile.name : (showEditReport.fileName || 'report.pdf')}
                                                </span>
                                                <Upload className="w-4 h-4 text-emerald-600" />
                                            </div>
                                        </div>
                                        <p className="text-[10px] text-on-surface-variant/60 mt-2 italic">
                                            Current file: {showEditReport.fileName || 'report.pdf'}
                                        </p>
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-4 mt-10">
                                    <button
                                        onClick={() => setShowEditReport(null)}
                                        className="py-4 bg-white text-primary border border-outline-variant/20 rounded-full font-bold text-sm italic hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-200 transition-all"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        onClick={handleSaveEditedReport}
                                        className="py-4 bg-[#0A2E1F] text-white rounded-2xl font-bold text-sm italic hover:bg-emerald-950 transition-all flex items-center justify-center"
                                    >
                                        Update
                                    </button>
                                </div>
                            </div>
                        </motion.div>
                    </div>
                )}

                {/* Edit Note Modal */}
                {showEditNote && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                        <motion.div
                            initial={{ opacity: 0 }}
                            animate={{ opacity: 1 }}
                            exit={{ opacity: 0 }}
                            onClick={() => setShowEditNote(false)}
                            className="absolute inset-0 bg-primary/40 backdrop-blur-sm"
                        />
                        <motion.div
                            initial={{ opacity: 0, scale: 0.9, y: 20 }}
                            animate={{ opacity: 1, scale: 1, y: 0 }}
                            exit={{ opacity: 0, scale: 0.9, y: 20 }}
                            className="relative w-full max-w-lg bg-white rounded-[40px] shadow-2xl overflow-hidden"
                        >
                            <div className="p-8">
                                <div className="flex items-center justify-between mb-8">
                                    <h3 className="text-xl font-bold font-headline text-primary italic">Edit</h3>
                                    <button onClick={() => setShowEditNote(false)} className="p-2 hover:bg-surface-container rounded-full">
                                        <X className="w-6 h-6" />
                                    </button>
                                </div>

                                <div>
                                    <label className="block text-[10px] font-bold text-on-surface-variant uppercase tracking-widest mb-2 italic">Notes</label>
                                    <textarea
                                        rows={4}
                                        defaultValue={note}
                                        onChange={(e) => setNote(e.target.value)}
                                        className="w-full p-4 bg-surface-container-low border border-outline-variant/10 rounded-2xl font-bold text-primary focus:outline-none focus:ring-2 focus:ring-emerald-500/20 italic resize-none"
                                    />
                                </div>

                                <div className="grid grid-cols-2 gap-4 mt-10">
                                    <button
                                        onClick={() => setShowEditNote(false)}
                                        className="py-4 bg-white text-primary border border-outline-variant/20 rounded-full font-bold text-sm italic hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-200 transition-all"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        onClick={() => setShowEditNote(false)}
                                        className="py-4 bg-[#0A2E1F] text-white rounded-2xl font-bold text-sm italic"
                                    >
                                        Update
                                    </button>
                                </div>
                            </div>
                        </motion.div>
                    </div>
                )}

            </AnimatePresence>
        </div>
    );
}
