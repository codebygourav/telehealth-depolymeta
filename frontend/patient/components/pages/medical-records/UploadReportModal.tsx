'use client';

import React, { useState } from 'react';
import {
    ChevronRight,
    X,
    Upload,
    CheckCircle2,
    AlertCircle,
} from 'lucide-react';
import { motion, AnimatePresence } from 'motion/react';
import { useForm, FormProvider } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';

import { useAuth } from '@/context/userContext';
import { useUploadMedicalReport } from '@/queries/useUploadMedicalReport';
import { toast } from 'sonner';
import InputField from '@/components/custom/inputfield';
import { Button } from '@/components/ui/button';

export const REPORT_TYPES = [
    'Abdominal Ultrasound Report',
    'Blood Test',
    'X-Ray',
    'Diabetes Screening Report',
    'Endoscopy Report',
    'MRI Scan',
    'Other',
];

interface UploadReportModalProps {
    isOpen: boolean;
    onClose: () => void;
}

const uploadSchema = z.object({
    reportName: z.string().min(1, "Report name is required"),
    reportType: z.string().min(1, "Report type is required"),
});

type UploadValues = z.infer<typeof uploadSchema>;

export const UploadReportModal: React.FC<UploadReportModalProps> = ({
    isOpen,
    onClose,
}) => {
    const { user } = useAuth();
    const { mutate: uploadReport, isPending: isUploading } = useUploadMedicalReport();
    const [step, setStep] = useState(1);
    const [file, setFile] = useState<File | null>(null);
    const [uploadSuccess, setUploadSuccess] = useState(false);
    const [uploadError, setUploadError] = useState<string | null>(null);

    const methods = useForm<UploadValues>({
        resolver: zodResolver(uploadSchema),
        defaultValues: {
            reportName: '',
            reportType: '',
        }
    });

    const handleClose = () => {
        onClose();
        setTimeout(() => {
            setStep(1);
            methods.reset();
            setFile(null);
            setUploadSuccess(false);
            setUploadError(null);
        }, 300);
    };

    const handleTypeSelect = (type: string) => {
        methods.setValue('reportType', type);
        setStep(2);
    };

    const onSubmit = (data: UploadValues) => {
        if (!user?.id || !file) {
            toast.error('Missing required information');
            return;
        }

        uploadReport({
            patientId: user.id,
            name: data.reportName,
            type: data.reportType,
            file: file,
            // notes: "some notes" // Optional
        }, {
            onSuccess: () => {
                setUploadSuccess(true);
                // Keeps success card visible for a moment before closing
                setTimeout(handleClose, 3000);
            },
            onError: (err: any) => {
                setUploadError(err?.response?.data?.message || err?.message || 'Failed to upload report');
            }
        });
    };

    return (
        <AnimatePresence>
            {isOpen && (
                <div className="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
                    <motion.div
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        onClick={handleClose}
                        className="absolute inset-0 bg-black/40 backdrop-blur-sm"
                    />
                    <motion.div
                        initial={{ opacity: 0, y: 100 }}
                        animate={{ opacity: 1, y: 0 }}
                        exit={{ opacity: 0, y: 100 }}
                        className="relative w-full max-w-lg bg-white rounded-t-[2.5rem] sm:rounded-[2.5rem] shadow-2xl overflow-hidden"
                    >
                        <div className="p-8">
                            <div className="flex items-center justify-between mb-8">
                                <h2 className="text-2xl font-bold text-[#0A2E1F]">
                                    {step === 1 ? 'Select Report Type' : 'Upload Your Report'}
                                </h2>
                                <button
                                    onClick={handleClose}
                                    className="p-2 hover:bg-gray-100 rounded-full text-gray-500 transition-all"
                                >
                                    <X className="w-6 h-6" />
                                </button>
                            </div>

                            {uploadSuccess ? (
                                <div className="py-12 text-center">
                                    <div className="w-20 h-20 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-6">
                                        <CheckCircle2 className="w-10 h-10 text-emerald-600" />
                                    </div>
                                    <h3 className="text-xl font-bold text-[#0A2E1F] mb-2">
                                        Upload Successful!
                                    </h3>
                                    <p className="text-gray-500">
                                        Your report has been added to your records.
                                    </p>
                                </div>
                            ) : uploadError ? (
                                <div className="py-12 text-center">
                                    <div className="w-20 h-20 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-6">
                                        <AlertCircle className="w-10 h-10 text-red-600" />
                                    </div>
                                    <h3 className="text-xl font-bold text-[#0A2E1F] mb-2">
                                        Upload Failed
                                    </h3>
                                    <p className="text-gray-500 mb-8 max-w-xs mx-auto">
                                        {uploadError}
                                    </p>
                                    <Button
                                        onClick={() => setUploadError(null)}
                                        className="bg-[#0A2E1F] text-white rounded-2xl px-12 py-6 h-auto font-bold shadow-lg hover:opacity-90 transition-all"
                                    >
                                        Try Again
                                    </Button>
                                </div>
                            ) : step === 1 ? (
                                <div className="space-y-1 max-h-[60vh] overflow-y-auto pr-2 custom-scrollbar">
                                    {REPORT_TYPES.map((type) => (
                                        <button
                                            key={type}
                                            onClick={() => handleTypeSelect(type)}
                                            className="w-full text-left px-6 py-5 rounded-2xl hover:bg-gray-50 border-b border-gray-100 last:border-0 transition-all group flex items-center justify-between"
                                        >
                                            <span className="text-lg font-medium text-gray-800 group-hover:text-[#0A2E1F]">
                                                {type}
                                            </span>
                                            <ChevronRight className="w-5 h-5 text-gray-400 opacity-0 group-hover:opacity-100 transition-all" />
                                        </button>
                                    ))}
                                </div>
                            ) : (
                                <FormProvider {...methods}>
                                    <form onSubmit={methods.handleSubmit(onSubmit)} className="space-y-8">
                                        <InputField
                                            name="reportType"
                                            label="Report Type"
                                            disabled
                                            inputClassName="w-full px-6 py-4 bg-gray-50 border border-gray-200 rounded-2xl outline-none text-gray-600 font-medium h-auto"
                                        />

                                        <InputField
                                            name="reportName"
                                            label="Name"
                                            placeholder="Enter report name"
                                            required
                                            inputClassName="w-full px-6 py-4 bg-white border border-gray-200 rounded-2xl focus:ring-2 focus:ring-[#0A2E1F]/20 focus:border-[#0A2E1F] transition-all outline-none h-auto"
                                        />

                                        <div className="space-y-2">
                                            <label className="text-sm font-bold text-gray-500 ml-1">
                                                Attach a document
                                            </label>
                                            <div className="relative group">
                                                <input
                                                    type="file"
                                                    required
                                                    onChange={(e) =>
                                                        setFile(e.target.files?.[0] || null)
                                                    }
                                                    className="absolute inset-0 opacity-0 cursor-pointer z-10"
                                                />
                                                <div className="w-full px-6 py-4 border border-gray-200 rounded-2xl flex items-center justify-between bg-white group-hover:bg-gray-50 transition-all">
                                                    <span className="text-gray-500 font-medium">
                                                        {file ? file.name : 'Choose File'}
                                                    </span>
                                                    <Upload className="w-5 h-5 text-[#0A2E1F]/40" />
                                                </div>
                                            </div>
                                        </div>

                                        <div className="space-y-6">
                                            <Button
                                                type="submit"
                                                disabled={isUploading}
                                                className="w-full h-auto py-4 bg-[#0A2E1F] text-white font-bold rounded-2xl shadow-lg hover:opacity-90 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-2 disabled:opacity-50 border-none"
                                            >
                                                {isUploading ? (
                                                    <>
                                                        <div className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                                                        Uploading...
                                                    </>
                                                ) : (
                                                    'Submit'
                                                )}
                                            </Button>
                                            <Button
                                                onClick={() => setStep(1)}
                                                className="w-full py-3 text-gray-500 font-medium text-sm hover:text-gray-700 transition-all bg-transparent border-none"
                                            >
                                                ← Back to report types
                                            </Button>
                                            <p className="text-sm text-gray-400 text-center leading-relaxed font-source-sans">
                                                Upload your medical documents or images (up to 10 files/photos)
                                            </p>
                                        </div>
                                    </form>
                                </FormProvider>
                            )}
                        </div>
                    </motion.div>
                </div>
            )}
        </AnimatePresence>
    );
};
