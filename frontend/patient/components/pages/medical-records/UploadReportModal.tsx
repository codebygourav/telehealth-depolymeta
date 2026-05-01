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
                <div className="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4">
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
                        className="relative w-full max-w-lg bg-white global-radius-10 sm:global-radius-10 shadow-2xl overflow-hidden"
                    >
                        <div className="p-1">
                            <div className="flex items-center justify-between mb-2 p-5 pb-0">
                                <h2 className="text-2xl font-bold text-[#0A2E1F]">
                                    {step === 1 ? 'Select Report Type' : 'Upload Your Report'}
                                </h2>
                                <button
                                    onClick={handleClose}
                                    className="p-2 text-gray-500 transition-all global-radius-10 hover:bg-gray-100"
                                >
                                    <X className="w-6 h-6" />
                                </button>
                            </div>

                            {uploadSuccess ? (
                                <div className="py-12 text-center">
                                    <div className="flex items-center justify-center w-20 h-20 mx-auto mb-6 global-radius-10 bg-emerald-50">
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
                                    <div className="flex items-center justify-center w-20 h-20 mx-auto mb-6 global-radius-10 bg-red-50">
                                        <AlertCircle className="w-10 h-10 text-red-600" />
                                    </div>
                                    <h3 className="text-xl font-bold text-[#0A2E1F] mb-2">
                                        Upload Failed
                                    </h3>
                                    <p className="max-w-xs mx-auto mb-8 text-gray-500">
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
                                <div className="max-h-[60vh] overflow-y-auto  custom-scrollbar p-5 flex flex-col gap-2 text-right">
                                    {REPORT_TYPES.map((type) => (
                                        <Button
                                            key={type}
                                            onClick={() => handleTypeSelect(type)}
                                            className="w-full g-border global-radius-10  bg-light-gray p-4 py-6 group flex items-center justify-between"
                                        >
                                            <span className="text-lg font-medium text-gray-800 group-hover:text-primary text-right">
                                                {type}
                                            </span>
                                            <ChevronRight className="w-5 h-5 text-gray-400 transition-all opacity-0 group-hover:opacity-100" />
                                        </Button>
                                    ))}
                                </div>
                            ) : (
                                <FormProvider {...methods}>
                                    <form onSubmit={methods.handleSubmit(onSubmit)} className="space-y-8 p-5">
                                        <div className="mb-6">
                                            <label className="block mb-1 text-sm font-medium text-gray-500">
                                                Report Type
                                            </label>
                                            <div
                                                className="w-full px-6 py-4 bg-light-gray global-radius-10 outline-none text-gray-600 font-medium h-auto"
                                            >
                                                {methods.getValues("reportType") || "-"}
                                            </div>
                                        </div>
                                   

                                        <InputField
                                            name="reportName"
                                            label="Name"
                                            placeholder="Enter report name"
                                            required
                                            inputClassName="w-full px-6 py-4 bg-light-gray global-radius-10 outline-none text-gray-600 font-medium h-auto"
                                        />

                                        <div className="space-y-2">
                                            <label className="ml-1 text-sm font-medium text-gray-500">
                                                Attach a document
                                            </label>
                                            <div className="relative group">
                                                <input
                                                    type="file"
                                                    required
                                                    onChange={(e) =>
                                                        setFile(e.target.files?.[0] || null)
                                                    }
                                                    className="absolute inset-0 z-10 opacity-0 cursor-pointer"
                                                />
                                                <div className="flex items-center justify-between w-full px-6 py-4 transition-all bg-light-gray global-radius-10 text-gray-600 font-medium h-auto">
                                                    <span className="font-medium text-gray-500">
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
                                                className="w-full h-auto py-4 btn-primary-cta"
                                            >
                                                {isUploading ? (
                                                    <>
                                                        <div className="w-5 h-5 border-2 global-radius-10 border-white/30 border-t-white animate-spin" />
                                                        Uploading...
                                                    </>
                                                ) : (
                                                    'Submit'
                                                )}
                                            </Button>
                                            <Button
                                                onClick={() => setStep(1)}
                                                className="w-full py-3 text-sm font-medium text-gray-500 transition-all bg-transparent border-none hover:text-gray-700"
                                            >
                                                ← Back to report types
                                            </Button>
                                            <p className="text-sm leading-relaxed text-center text-gray-400 font-source-sans">
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
