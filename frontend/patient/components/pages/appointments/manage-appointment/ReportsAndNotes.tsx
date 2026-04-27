"use client"
import { useEffect, useState } from 'react';
import { FileText, Plus, MoreVertical, Eye, Edit3, Trash2 } from 'lucide-react';
import { motion, AnimatePresence } from 'motion/react';
import { Report } from '@/types/medical-reports';
import { SlotItem } from '@/types/slots';
import { toast } from 'sonner';
import RescheduleDialog from './RescheduleDialog';
import { useRescheduleAppointment } from '@/mutations/useRescheduleAppointment';
import { useQueryClient } from '@tanstack/react-query';

interface ReportsAndNotesProps {
    reports: Report[];
    note: string;
    doctorId?: string;
    appointmentId?: string;
    activeMenu: string | null;
    setActiveMenu: (id: string | null) => void;
    onAddReport: () => void;
    onViewReport: (report: Report) => void;
    onEditReport: (report: Report) => void;
    onDeleteReport: (id: string) => void;
    onCancel?: () => void;
    appointmentStatus?: string;
}

export default function ReportsAndNotes({
    reports,
    note,
    doctorId,
    appointmentId,
    activeMenu,
    setActiveMenu,
    onAddReport,
    onViewReport,
    onEditReport,
    onDeleteReport,
    onCancel,
    appointmentStatus
}: ReportsAndNotesProps) {
    const [showRescheduleDialog, setShowRescheduleDialog] = useState(false);
    const [isAlreadyRescheduled, setIsAlreadyRescheduled] = useState(
        appointmentStatus === "rescheduled"
    );
    const rescheduleMutation = useRescheduleAppointment();

    const queryClient = useQueryClient();

    useEffect(() => {
        if (appointmentStatus === "rescheduled") {
            setIsAlreadyRescheduled(true);
        }
    }, [appointmentStatus]);

    // console.log("APPOINTMENT STATUS PROP:", appointmentStatus);


    const handleRescheduleClick = () => {
        if (isAlreadyRescheduled) {
            toast.error("You already rescheduled this appointment");
            return;
        }


        if (!doctorId) {
            toast.error('Doctor information not available');
            return;
        }
        setShowRescheduleDialog(true);
    };

    const handleConfirmReschedule = (slot: SlotItem, callbacks: { onSuccess: (message: string) => void, onError: (message: string) => void }) => {
        if (!appointmentId) {
            callbacks.onError('Appointment ID not available');
            return;
        }

        const payload = {
            appointment_id: appointmentId,
            availability_id: slot.id,
            appointment_date: slot.date,
            appointment_time: slot.booking_start_time
        };

        // console.log("payload id", payload);

        rescheduleMutation.mutate(payload, {
            onSuccess: (data) => {
                // console.log("response status", data?.data?.appointment_status);
                const message = data.message || 'Appointment rescheduled successfully';
                callbacks.onSuccess(message);
                
                // const status = data?.data?.appointment_status;
                // console.log("status", status);

                if (appointmentStatus === "rescheduled") {
                    setIsAlreadyRescheduled(true); // ✅ LOCK
                    queryClient.invalidateQueries({ queryKey: ['appointment', appointmentId] });
                }

            },
            onError: (error: any) => {
                // console.error('Reschedule error:', error?.response?.data);
                const errorMessage = error?.response?.data?.errors?.message
                    || error?.response?.data?.message
                    || 'Failed to reschedule appointment';
                callbacks.onError(errorMessage);
            }
        });
    };

    return (
        <div className="lg:col-span-5 space-y-8">
            <motion.div
                initial={{ opacity: 0, x: 20 }}
                animate={{ opacity: 1, x: 0 }}
                className="bg-white rounded-[40px] p-8 shadow-sm border border-outline-variant/10"
            >
                <div className="flex items-center justify-between mb-8">
                    <h3 className="text-lg font-bold font-headline text-primary">Manage Reports & Notes</h3>
                    <button
                        onClick={onAddReport}
                        className="p-2 bg-emerald-50 text-emerald-600 rounded-xl hover:bg-emerald-100 transition-colors"
                    >
                        <Plus className="w-5 h-5" />
                    </button>
                </div>

                {reports.length === 0 ? (
                    <div className="text-center py-12 px-4">
                        <div className="w-16 h-16 bg-surface-container-low rounded-full flex items-center justify-center mx-auto mb-4 text-on-surface-variant/30">
                            <FileText className="w-8 h-8" />
                        </div>
                        <p className="text-sm text-on-surface-variant font-medium leading-relaxed">
                            You have not added any medical reports or notes. If you'd like to share them with your doctor, <button onClick={onAddReport} className="text-emerald-600 font-bold hover:underline">click here to upload</button>
                        </p>
                    </div>
                ) : (
                    <div className="space-y-4">
                        {reports.map((report, index) => (
                            <div key={`report-${report.id || index}`} className="p-5 bg-surface-container-low/30 rounded-3xl border border-outline-variant/5 relative">
                                <div className="flex justify-between items-start mb-2">
                                    <div>
                                        <h4 className="font-bold text-primary text-sm mb-1">{report.title}</h4>
                                        <p className="text-[10px] font-bold text-on-surface-variant/60 uppercase tracking-widest">{report.date}</p>
                                    </div>
                                    <div className="relative">
                                        <button
                                            onClick={() => setActiveMenu(activeMenu === report.id ? null : report.id)}
                                            className="p-2 hover:bg-surface-container rounded-xl transition-colors"
                                        >
                                            <MoreVertical className="w-4 h-4 text-on-surface-variant" />
                                        </button>

                                        <AnimatePresence>
                                            {activeMenu === report.id && (
                                                <motion.div
                                                    key={`report-menu-${report.id || index}`}
                                                    initial={{ opacity: 0, scale: 0.95, y: -10 }}
                                                    animate={{ opacity: 1, scale: 1, y: 0 }}
                                                    exit={{ opacity: 0, scale: 0.95, y: -10 }}
                                                    className="absolute right-0 top-full mt-2 w-36 bg-white rounded-2xl shadow-xl border border-outline-variant/10 z-20 overflow-hidden"
                                                >
                                                    <button
                                                        onClick={() => {
                                                            onViewReport(report);
                                                            setActiveMenu(null);
                                                        }}
                                                        className="w-full px-4 py-3 text-left text-xs font-bold text-primary hover:bg-surface-container-low flex items-center gap-2"
                                                    >
                                                        <Eye className="w-3.5 h-3.5 text-emerald-600" />
                                                        View
                                                    </button>
                                                    <button
                                                        onClick={() => {
                                                            onEditReport(report);
                                                            setActiveMenu(null);
                                                        }}
                                                        className="w-full px-4 py-3 text-left text-xs font-bold text-primary hover:bg-surface-container-low flex items-center gap-2"
                                                    >
                                                        <Edit3 className="w-3.5 h-3.5 text-blue-600" />
                                                        Edit
                                                    </button>
                                                    <button
                                                        onClick={() => onDeleteReport(report.id)}
                                                        className="w-full px-4 py-3 text-left text-xs font-bold text-red-600 hover:bg-red-50 flex items-center gap-2"
                                                    >
                                                        <Trash2 className="w-3.5 h-3.5" />
                                                        Delete
                                                    </button>
                                                </motion.div>
                                            )}
                                        </AnimatePresence>
                                    </div>
                                </div>
                                <p className="text-xs font-bold text-on-surface-variant">Type: <span className="text-primary">{report.type}</span></p>
                            </div>
                        ))}

                        {/* Note Section */}
                        <div className="mt-8 pt-8 border-t border-outline-variant/10">
                            <div className="flex items-center justify-between mb-4">
                                <h4 className="text-sm font-bold text-primary">Note</h4>
                            </div>
                            <div className="p-5 bg-emerald-50/50 rounded-2xl border border-emerald-100/50">
                                <p className="text-xs text-on-surface-variant leading-relaxed italic">
                                    {note ? note : 'Not Defined'}
                                </p>
                            </div>
                        </div>
                    </div>
                )}
            </motion.div>

            {/* Action Buttons */}
            <div className="grid grid-cols-2 gap-4">

                {/* ✅ Reschedule button only if NOT rescheduled */}
                {!isAlreadyRescheduled && (
                    <button
                        onClick={handleRescheduleClick}
                        className="py-4 bg-[#0A2E1F] text-white rounded-2xl font-bold text-sm shadow-lg shadow-primary/10 hover:opacity-90 transition-all"
                    >
                        Reschedule
                    </button>
                )}

                <button
                    onClick={onCancel}
                    className={`py-4 bg-white text-primary border border-outline-variant/20 rounded-full font-bold text-sm hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-200 transition-all shadow-sm 
            ${isAlreadyRescheduled ? 'col-span-2' : ''}
        `}
                >
                    Cancel
                </button>

            </div>

            {/* Reschedule Dialog */}
            <RescheduleDialog
                isOpen={showRescheduleDialog}
                onClose={() => setShowRescheduleDialog(false)}
                doctorId={doctorId || ''}
                appointmentId={appointmentId}
                onConfirmReschedule={handleConfirmReschedule}
                isLoading={rescheduleMutation.isPending}
                // isAlreadyRescheduled={isAlreadyRescheduled}
                isAlreadyRescheduled={isAlreadyRescheduled}
            />
        </div>
    );
}
