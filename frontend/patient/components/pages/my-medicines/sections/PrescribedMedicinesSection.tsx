'use client';

import { MedicineItem } from '@/components/pages/my-medicines/MedicineItem';
import { Button } from '@/components/ui/button';
import { MedicineDetail } from '@/types/prescriptions';
import { ArrowUpRight, Clock, Pill, Volume2, VolumeX, Languages } from 'lucide-react';
import { useState, useEffect } from 'react';
import { cn } from '@/lib/utils';
import {
    SpeechLanguage,
    speakText,
    stopSpeaking,
    generateMedicineSpeechText,
    generatePrescriptionIntroText
} from '@/lib/medicineVoiceHelper';

interface PrescribedMedicinesSectionProps {
    medicines: MedicineDetail[];
    pdfUrl?: string;
    doctorName?: string;
    prescribedAt?: string;
    /** Show the PDF link inline next to the section title (used when DoctorInfoHeader is hidden) */
    showInlinePdfLink?: boolean;
    cardGrid?: string;
}

export const PrescribedMedicinesSection = ({
    medicines,
    pdfUrl,
    doctorName,
    prescribedAt,
    showInlinePdfLink = false,
    cardGrid = 'grid-cols-1 gap-6',
}: PrescribedMedicinesSectionProps) => {
    const [language, setLanguage] = useState<SpeechLanguage>('en');
    const [isPlayingAll, setIsPlayingAll] = useState(false);
    const [currentPlayingIndex, setCurrentPlayingIndex] = useState<number | null>(null); // -1 for intro, 0+ for medicine index

    useEffect(() => {
        if (typeof window !== 'undefined') {
            const saved = localStorage.getItem('medicine_voice_lang') as SpeechLanguage;
            if (saved === 'en' || saved === 'hi' || saved === 'pa') {
                setLanguage(saved);
            }
        }
        return () => {
            stopSpeaking();
        };
    }, []);

    const handleLanguageChange = (lang: SpeechLanguage) => {
        setLanguage(lang);
        localStorage.setItem('medicine_voice_lang', lang);
        stopSpeaking();
        setIsPlayingAll(false);
        setCurrentPlayingIndex(null);
    };

    const playAll = () => {
        if (isPlayingAll) {
            stopSpeaking();
            setIsPlayingAll(false);
            setCurrentPlayingIndex(null);
            return;
        }

        if (medicines.length === 0) return;

        setIsPlayingAll(true);
        setCurrentPlayingIndex(0);

        const firstMedText = generateMedicineSpeechText(medicines[0], language);
        speakNext(firstMedText, 0);
    };

    const speakNext = (text: string, index: number) => {
        speakText(
            text,
            language,
            undefined, // onStart
            () => {
                // onEnd
                if (index < medicines.length - 1) {
                    const nextIndex = index + 1;
                    setCurrentPlayingIndex(nextIndex);
                    const nextMedText = generateMedicineSpeechText(medicines[nextIndex], language);
                    speakNext(nextMedText, nextIndex);
                } else {
                    setIsPlayingAll(false);
                    setCurrentPlayingIndex(null);
                }
            },
            (err) => {
                console.error(err);
                setIsPlayingAll(false);
                setCurrentPlayingIndex(null);
            }
        );
    };

    return (
        <div className="space-y-6">
            {/* Header row: title + optional inline PDF link */}
            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div className="flex items-start gap-2 text-lg font-bold sm:text-xl g-text-dark font-headline">
                    <div className="p-3 sm:p-4 bg-light-gray text-primary global-radius shrink-0 h-full">
                        <Pill className="size-6" />
                    </div>
                
                    {(doctorName || prescribedAt) && (
                        <div>
                            <h2 className="text-lg font-bold sm:text-xl g-text-dark font-headline">Prescribed Medicines</h2>
                            {doctorName ? (
                                <p className="g-text-sm g-text-muted font-medium">
                                    Prescribed by:{" "}
                                    <span className="font-s g-text-muted">{doctorName}</span>
                                </p>
                            ) : null}
                            {prescribedAt ? (
                                <p className="g-text-sm g-text-muted font-medium">
                                    Prescribed at:{" "}
                                    <span className="font-s g-text-muted">{prescribedAt}</span>
                                </p>
                            ) : null}
                        </div>
                    )}
                </div>
               
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
                    {showInlinePdfLink && pdfUrl ? (
                        <Button asChild className="text-white btn-primary-cta w-full sm:w-auto m-0">
                            <a href={pdfUrl} target="_blank" rel="noopener noreferrer">
                                <span className="font-bold text-white">View PDF</span>
                                <ArrowUpRight className="w-5 h-5 m-0 text-white" />
                            </a>
                        </Button>
                    ) : null}
                </div>
            </div>

            {/* Voice Announcement Preferences Panel */}
            <div className="flex flex-wrap items-center justify-between gap-4 p-3 bg-light-gray global-radius-10 shadow-card-sm border border-outline-variant/10">
                <div className="flex flex-wrap items-center gap-3">
                    <div className="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider g-text-muted">
                        <Languages className="w-4 h-4 text-primary" />
                        <span>Voice Language</span>
                    </div>
                    <div className="flex items-center gap-1 bg-white p-1 rounded-lg border border-outline-variant/5">
                        {(
                            [
                                { key: 'en', label: 'English (IN)' },
                                { key: 'hi', label: 'Hindi (हिंदी)' },
                                { key: 'pa', label: 'Punjabi (ਪੰਜਾਬੀ)' },
                            ] as const
                        ).map((lang) => (
                            <button
                                key={lang.key}
                                onClick={() => handleLanguageChange(lang.key)}
                                className={cn(
                                    "px-3 py-1 text-xs font-bold rounded-md transition-all cursor-pointer",
                                    language === lang.key
                                        ? "bg-[#013220] text-white shadow-sm font-extrabold"
                                        : "text-gray-600 hover:bg-gray-100 hover:text-gray-800"
                                )}
                            >
                                {lang.label}
                            </button>
                        ))}
                    </div>
                </div>

                <Button
                    onClick={playAll}
                    className={cn(
                        "text-xs font-bold py-1 px-4 h-8 transition-all shrink-0 cursor-pointer rounded-lg m-0",
                        isPlayingAll 
                            ? "bg-red-600 hover:bg-red-700 text-white border-none shadow-sm" 
                            : "bg-emerald-700 hover:bg-emerald-800 text-white border-none shadow-sm"
                    )}
                >
                    {isPlayingAll ? (
                        <>
                            <VolumeX className="w-4 h-4 mr-1.5" />
                            Stop
                        </>
                    ) : (
                        <>
                            <Volume2 className="w-4 h-4 mr-1.5" />
                            Listen to All
                        </>
                    )}
                </Button>
            </div>

            {/* Medicines grid */}
            <div className={`grid ${cardGrid}`}>
                {medicines.map((med, idx) => (
                    <MedicineItem 
                        key={idx} 
                        medicine={med} 
                        language={language}
                        isCurrentlySpeaking={isPlayingAll && currentPlayingIndex === idx}
                    />
                ))}
            </div>
        </div>
    );
};
