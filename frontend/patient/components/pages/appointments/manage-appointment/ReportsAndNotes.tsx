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
import { Button } from '@/components/ui';
import { Card, CardContent } from '@/components/ui';

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

        rescheduleMutation.mutate(payload, {
            onSuccess: (data) => {

                const message = data.message || 'Appointment rescheduled successfully';
                callbacks.onSuccess(message);

                if (appointmentStatus === "rescheduled") {
                    setIsAlreadyRescheduled(true); // ✅ LOCK
                    queryClient.invalidateQueries({ queryKey: ['appointment', appointmentId] });
                }

            },
            onError: (error: any) => {
                const errorMessage = error?.response?.data?.errors?.message
                    || error?.response?.data?.message
                    || 'Failed to reschedule appointment';
                callbacks.onError(errorMessage);
            }
        });
    };

    return (
        <Card className="lg:col-span-5 space-y-8 rounded-lg p-5 justify-between">

            <CardContent className='px-0'>

                <div className="flex items-center justify-between mb-8">
                    <h3 className="text-lg text-[#1F1E1E] font-semibold">Manage Reports & Notes</h3>
                    <button
                        onClick={onAddReport}
                        className="p-2 bg-[#055bd929] text-emerald-600 rounded-xl"
                    >
                        <Plus size={18} strokeWidth={3} color='#055BD9' />
                    </button>
                </div>

                {reports.length === 0 ? (
                    <div className="text-center py-12 px-4">
                        <div className="w-16 h-16 bg-[#055bd929] rounded-full flex items-center justify-center mx-auto mb-4 text-on-surface-variant/30">
                            <FileText size={32} color='#055BD9' />
                        </div>
                        <p className="text-sm text-[#4D4D4D] font-medium leading-relaxed">
                            You have not added any medical reports or notes. If you'd like to share them with your doctor,
                            <button onClick={onAddReport} className="text-[#055BD9] font-semibold hover:underline"> click here to upload</button>
                        </p>
                    </div>
                ) : (
                    <div className="space-y-4">
                        {reports.map((report, index) => (
                            <div key={`report-${report.id || index}`} className="p-5 rounded-lg rounded-3xl border border-light-gray relative">
                                <div className="flex justify-between items-start mb-1">
                                    <div>
                                        <h4 className="font-semibold text-[#1f1e1e] text-sm mb-1">{report.title}</h4>
                                        <p className="text-[10px] text-[#4D4D4D]">{report.date}</p>
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
                                                    className="absolute right-0 top-full mt-2 w-36 bg-white rounded-lg border-light-gray z-20 overflow-hidden"
                                                >
                                                    <button
                                                        onClick={() => {
                                                            onViewReport(report);
                                                            setActiveMenu(null);
                                                        }}
                                                        className="w-full px-4 py-3 text-left text-xs font-bold text-[#1F1E1E] hover:bg-surface-container-low flex items-center gap-2"
                                                    >
                                                        <Eye className="w-3.5 h-3.5 text-emerald-600" />
                                                        View
                                                    </button>
                                                    <button
                                                        onClick={() => {
                                                            onEditReport(report);
                                                            setActiveMenu(null);
                                                        }}
                                                        className="w-full px-4 py-3 text-left text-xs font-bold text-[#1F1E1E] hover:bg-surface-container-low flex items-center gap-2"
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
                                <p className="text-xs font-semibold text-[#1f1e1e]">Type: <span className="text-primary">{report.type}</span></p>
                            </div>
                        ))}

                        {/* Note Section */}
                        <div className="mt-8 pt-8 border-t border-outline-variant/10">
                            <div className="flex items-center justify-between mb-4">
                                <h4 className="text-lg text-[#1F1E1E] font-semibold">Note</h4>
                            </div>
                            <div className="p-5 bg-gray-100 rounded-lg border border-gray-200">
                                <p className="text-xs text-gray-700 font-semibold">
                                    {note ? note : 'Not Defined'}
                                </p>
                            </div>
                        </div>
                    </div>
                )}
            </CardContent>

            {/* Action Buttons */}
            <div className="grid grid-cols-2 gap-4">

                {/* ✅ Reschedule button only if NOT rescheduled */}
                {!isAlreadyRescheduled && (
                    <Button onClick={handleRescheduleClick} className='py-3 h-auto font-semibold cursor-pointer'>
                        Reschedule
                    </Button>
                )}

                <Button
                    onClick={onCancel}
                    className={`${isAlreadyRescheduled ? 'col-span-2' : ''} py-3 h-auto font-semibold cursor-pointer`}
                    variant="outline"
                >
                    Cancel
                </Button>

            </div>

            {/* Reschedule Dialog */}
            <RescheduleDialog
                isOpen={showRescheduleDialog}
                onClose={() => setShowRescheduleDialog(false)}
                doctorId={doctorId || ''}
                appointmentId={appointmentId}
                onConfirmReschedule={handleConfirmReschedule}
                isLoading={rescheduleMutation.isPending}
                isAlreadyRescheduled={isAlreadyRescheduled}
            />

        </Card>
    );
}
