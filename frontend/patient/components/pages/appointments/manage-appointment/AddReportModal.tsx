"use client"
import { useState, useEffect } from 'react';
import { X, ChevronDown, Upload, Trash2 } from 'lucide-react';
import { motion, AnimatePresence } from 'motion/react';
import { Report } from '@/types/medical-reports';

interface AddReportModalProps {
    isOpen: boolean;
    onClose: () => void;
    reports: Report[];
    onAddReport: (newReport: Report) => void;
    onDeleteReport: (id: string) => void;
    onSubmit: (note: string) => void;
    initialNote: string;
    isUpdating?: boolean;
    patientReports: any[];
    isLoadingReports: boolean;
}

const REPORT_TYPES = [
    "Blood Test",
    "X-Ray",
    "MRI Scan",
    "Ultrasound",
    "Prescription",
    "Other"
];

export default function AddReportModal({
    isOpen,
    onClose,
    reports,
    onAddReport,
    onDeleteReport,
    onSubmit,
    initialNote,
    isUpdating,
    patientReports,
    isLoadingReports
}: AddReportModalProps) {
    const [modalNote, setModalNote] = useState(initialNote);
    const [showMyReports, setShowMyReports] = useState(false);
    const [newReportTitle, setNewReportTitle] = useState('');
    const [newReportType, setNewReportType] = useState('');
    const [newFile, setNewFile] = useState<File | null>(null);
    const [isUploading, setIsUploading] = useState(false);

    useEffect(() => {
        if (isOpen) {
            setModalNote(initialNote);
        }
    }, [isOpen, initialNote]);

    const handleAddReport = () => {
        if (!newReportTitle || !newReportType) return;

        setIsUploading(true);
        // Simulate upload
        setTimeout(() => {
            const newReport: Report = {
                id: Math.random().toString(36).substr(2, 9),
                title: newReportTitle,
                date: new Date().toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' }),
                type: newReportType,
                fileName: newFile?.name || 'document.pdf',
                file: newFile || undefined
            };
            onAddReport(newReport);
            setNewReportTitle('');
            setNewReportType('');
            setNewFile(null);
            setIsUploading(false);
        }, 1000);
    };

    const handleSelectExisting = (record: any) => {
        const title = record.report_name || record.title;
        const alreadyExists = reports.find(r => r.title === title);
        if (!alreadyExists) {
            onAddReport({
                id: record.id,
                title: title,
                date: record.report_date_formatted || record.date,
                type: record.type_label || record.type,
                fileName: record.file_name || 'report.pdf',
                fileUrl: record.file_url
            });
        }
        setShowMyReports(false);
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <motion.div
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                exit={{ opacity: 0 }}
                onClick={onClose}
                className="absolute inset-0 bg-black/40 backdrop-blur-sm"
            />
            <motion.div
                initial={{ opacity: 0, scale: 0.95, y: 20 }}
                animate={{ opacity: 1, scale: 1, y: 0 }}
                exit={{ opacity: 0, scale: 0.95, y: 20 }}
                className="relative w-full max-w-xl bg-white rounded-[32px] shadow-2xl overflow-hidden"
            >
                <div className="p-8 max-h-[90vh] overflow-y-auto custom-scrollbar">
                    <div className="flex items-center justify-between mb-8">
                        <h2 className="text-2xl font-bold text-primary font-headline">Upload Reports & Notes</h2>
                        <button
                            onClick={onClose}
                            className="p-2 hover:bg-surface-container rounded-full text-on-surface-variant transition-all"
                        >
                            <X className="w-6 h-6" />
                        </button>
                    </div>

                    <div className="space-y-6">
                        {/* Notes Section */}
                        <div className="space-y-2">
                            <label className="text-sm font-bold text-on-surface-variant">Write your health problem to Doctor</label>
                            <textarea
                                value={modalNote}
                                onChange={(e) => setModalNote(e.target.value)}
                                placeholder="Describe symptoms or notes..."
                                rows={4}
                                className="w-full px-6 py-4 bg-white border border-outline-variant/30 rounded-2xl focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all outline-none resize-none"
                            />
                        </div>

                        {/* Select from My Reports */}
                        <div className="space-y-2">
                            <label className="text-sm font-bold text-on-surface-variant">Select from My Reports</label>
                            <div className="relative">
                                <button
                                    onClick={() => setShowMyReports(!showMyReports)}
                                    className="w-full px-6 py-4 bg-white border border-outline-variant/30 rounded-2xl flex items-center justify-between text-on-surface-variant hover:bg-surface-container-low transition-all"
                                >
                                    <span className="font-medium">Select reports</span>
                                    <ChevronDown className={`w-5 h-5 transition-transform ${showMyReports ? 'rotate-180' : ''}`} />
                                </button>

                                <AnimatePresence>
                                    {showMyReports && (
                                        <motion.div
                                            initial={{ opacity: 0, y: -10 }}
                                            animate={{ opacity: 1, y: 0 }}
                                            exit={{ opacity: 0, y: -10 }}
                                            className="absolute top-full left-0 right-0 mt-2 bg-white border border-outline-variant/30 rounded-2xl shadow-xl z-30 overflow-hidden"
                                        >
                                            <div className="max-h-48 overflow-y-auto custom-scrollbar">
                                                {isLoadingReports ? (
                                                    <div className="p-8 flex justify-center">
                                                        <div className="w-6 h-6 border-2 border-primary/30 border-t-primary rounded-full animate-spin" />
                                                    </div>
                                                ) : patientReports.length === 0 ? (
                                                    <div className="p-6 text-center text-sm text-on-surface-variant font-medium">
                                                        No reports found in your records.
                                                    </div>
                                                ) : (
                                                    patientReports.map((record, index) => (
                                                        <button
                                                            key={`history-${record.id || index}`}
                                                            onClick={() => handleSelectExisting(record)}
                                                            className="w-full px-6 py-4 text-left hover:bg-surface-container-low border-b border-outline-variant/5 last:border-0 transition-all font-medium text-primary"
                                                        >
                                                            {record.report_name || record.title}
                                                        </button>
                                                    ))
                                                )}
                                            </div>
                                        </motion.div>
                                    )}
                                </AnimatePresence>
                            </div>
                        </div>

                        {/* Or Separator */}
                        <div className="flex items-center gap-4 py-2">
                            <div className="flex-1 h-px bg-outline-variant/20" />
                            <span className="text-lg font-bold text-primary">Or</span>
                            <div className="flex-1 h-px bg-outline-variant/20" />
                        </div>

                        {/* New Report Form */}
                        <div className="space-y-4">
                            <div className="space-y-2">
                                <label className="text-sm font-bold text-on-surface-variant">Report Title</label>
                                <input
                                    type="text"
                                    value={newReportTitle}
                                    onChange={(e) => setNewReportTitle(e.target.value)}
                                    placeholder="Report Title"
                                    className="w-full px-6 py-4 bg-white border border-outline-variant/30 rounded-2xl focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all outline-none"
                                />
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-bold text-on-surface-variant">Report Type</label>
                                <div className="relative">
                                    <select
                                        value={newReportType}
                                        onChange={(e) => setNewReportType(e.target.value)}
                                        className="w-full px-6 py-4 bg-white border border-outline-variant/30 rounded-2xl focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all outline-none appearance-none font-medium text-primary"
                                    >
                                        <option value="">Select an option</option>
                                        {REPORT_TYPES.map(type => (
                                            <option key={type} value={type}>{type}</option>
                                        ))}
                                    </select>
                                    <ChevronDown className="absolute right-6 top-1/2 -translate-y-1/2 w-5 h-5 text-outline-variant pointer-events-none" />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-bold text-on-surface-variant">Upload File</label>
                                <div className="relative group">
                                    <input
                                        type="file"
                                        onChange={(e) => setNewFile(e.target.files?.[0] || null)}
                                        className="absolute inset-0 opacity-0 cursor-pointer z-10"
                                    />
                                    <div className="w-full px-6 py-4 border border-outline-variant/30 rounded-2xl flex items-center justify-between bg-white group-hover:bg-surface-container-low transition-all">
                                        <span className="text-on-surface-variant font-medium">
                                            {newFile ? newFile.name : 'Choose File'}
                                        </span>
                                        <Upload className="w-5 h-5 text-primary/40" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Add Report Button */}
                        <button
                            onClick={handleAddReport}
                            className="w-full py-4 bg-primary text-white font-bold rounded-2xl shadow-lg transition-all flex items-center justify-center gap-2"
                        >
                            Add Report
                        </button>

                        <div className="space-y-3 pt-4">
                            {reports.map((report, index) => (
                                <div key={`upload-${report.id || index}`} className="p-4 border border-primary rounded-2xl flex items-center justify-between group">
                                    <div>
                                        <h4 className="font-bold text-primary">{report.title}</h4>
                                        <p className="text-xs text-on-surface-variant/60 font-medium">
                                            {report.type.toLowerCase().replace(/\s+/g, '_')}
                                        </p>
                                    </div>
                                    <button
                                        onClick={() => onDeleteReport(report.id)}
                                        className="p-2 text-red-500 hover:bg-red-50 rounded-xl transition-all"
                                    >
                                        <Trash2 className="w-5 h-5" />
                                    </button>
                                </div>
                            ))}
                        </div>

                        {/* Footer Buttons */}
                        <div className="grid grid-cols-2 gap-4 pt-8">
                            <button
                                onClick={onClose}
                                className="py-4 bg-white text-primary border border-primary rounded-2xl font-bold hover:bg-surface-container-low transition-all"
                            >
                                Cancel
                            </button>
                            <button
                                onClick={() => onSubmit(modalNote)}
                                disabled={isUpdating}
                                className="py-4 bg-[#0A2E1F] text-white rounded-2xl font-bold hover:bg-opacity-90 transition-all shadow-lg flex items-center justify-center gap-2"
                            >
                                {isUpdating ? (
                                    <div className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                                ) : (
                                    'Submit'
                                )}
                            </button>
                        </div>
                    </div>
                </div>
            </motion.div>
        </div>
    );
}
