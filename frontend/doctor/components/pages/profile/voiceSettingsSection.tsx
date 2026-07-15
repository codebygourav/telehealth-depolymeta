"use client";

import { useEffect, useState } from "react";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Play, Square, Save, Loader2, Volume2 } from "lucide-react";
import { updateDoctorProfile } from "@/api/profile";
import { useQueryClient } from "@tanstack/react-query";
import { doctorProfileKeys } from "@/queries/useProfile";
import { toast } from "sonner";

interface VoiceSettings {
    voice_name: string | null;
    speech_rate: number;
    speech_pitch: number;
    speech_locale: string | null;
}

interface VoiceSettingsSectionProps {
    voiceSettings: VoiceSettings | null;
    userId: string | undefined;
}

export default function VoiceSettingsSection({ voiceSettings, userId }: VoiceSettingsSectionProps) {
    const queryClient = useQueryClient();
    const [voices, setVoices] = useState<SpeechSynthesisVoice[]>([]);
    const [isPlaying, setIsPlaying] = useState(false);
    const [isSaving, setIsSaving] = useState(false);

    // States for settings
    const [locale, setLocale] = useState("en-IN");
    const [selectedVoice, setSelectedVoice] = useState("");
    const [rate, setRate] = useState(1.0);
    const [pitch, setPitch] = useState(1.0);

    // Fetch browser voices on mount
    useEffect(() => {
        const loadVoices = () => {
            if (typeof window !== "undefined" && window.speechSynthesis) {
                const availableVoices = window.speechSynthesis.getVoices();
                setVoices(availableVoices);
            }
        };

        loadVoices();
        if (typeof window !== "undefined" && window.speechSynthesis) {
            window.speechSynthesis.onvoiceschanged = loadVoices;
        }
    }, []);

    // Set initial values from backend
    useEffect(() => {
        if (voiceSettings) {
            setLocale(voiceSettings.speech_locale || "en-IN");
            setSelectedVoice(voiceSettings.voice_name || "");
            setRate(voiceSettings.speech_rate || 1.0);
            setPitch(voiceSettings.speech_pitch || 1.0);
        }
    }, [voiceSettings]);

    // Test playback
    const handleTestVoice = () => {
        if (typeof window === "undefined" || !window.speechSynthesis) return;

        if (isPlaying) {
            window.speechSynthesis.cancel();
            setIsPlaying(false);
            return;
        }

        window.speechSynthesis.cancel();
        const text = "This is a preview of your personalized voice configuration. Speech synthesis is active.";
        const utterance = new SpeechSynthesisUtterance(text);
        
        utterance.rate = rate;
        utterance.pitch = pitch;

        if (selectedVoice) {
            const voice = voices.find((v) => v.name === selectedVoice);
            if (voice) utterance.voice = voice;
        }

        utterance.onend = () => setIsPlaying(false);
        utterance.onerror = () => setIsPlaying(false);

        setIsPlaying(true);
        window.speechSynthesis.speak(utterance);
    };

    // Save configuration
    const handleSaveSettings = async () => {
        if (!userId) {
            toast.error("User session not found.");
            return;
        }

        setIsSaving(true);
        try {
            await updateDoctorProfile(userId, "voice_settings", {
                voice_name: selectedVoice || null,
                speech_rate: rate,
                speech_pitch: pitch,
                speech_locale: locale,
            });
            
            toast.success("Voice settings updated successfully!");
            queryClient.invalidateQueries({ queryKey: doctorProfileKeys.all });
        } catch (error) {
            console.error("Failed to save voice settings:", error);
            toast.error("Failed to update voice settings.");
        } finally {
            setIsSaving(false);
        }
    };

    return (
        <div className="space-y-6">
            <div>
                <h2 className="text-[#1F1E1E] font-semibold text-lg mb-1.5 flex items-center gap-2">
                    <Volume2 className="h-5 w-5 text-primary" /> Voice & Speech Settings
                </h2>
                <p className="text-[#4D4D4D] text-sm">
                    Customize speech recognition locale and speech synthesis output parameters. These configurations are applied globally when dictating prescriptions and listening to announcers.
                </p>
            </div>

            <Card className="border-border overflow-hidden bg-gradient-to-br from-background via-background to-muted/20 shadow-md">
                <CardContent className="p-6 space-y-6">
                    {/* Speech Recognition Accent Section */}
                    <div className="space-y-3 pb-6 border-b border-border">
                        <h3 className="text-sm font-bold text-foreground">Speech-to-Text Accent Recognition</h3>
                        <div className="grid md:grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="locale">Accent / Language Locale</Label>
                                <Select value={locale} onValueChange={setLocale}>
                                    <SelectTrigger id="locale" className="bg-background">
                                        <SelectValue placeholder="Select Locale" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="en-IN">English (India)</SelectItem>
                                        <SelectItem value="en-US">English (United States)</SelectItem>
                                        <SelectItem value="en-GB">English (United Kingdom)</SelectItem>
                                        <SelectItem value="hi-IN">Hindi (India)</SelectItem>
                                        <SelectItem value="pa-IN">Punjabi (India)</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p className="text-[10px] text-muted-foreground">
                                    Selecting your natural accent locale dramatically increases native voice recognition accuracy.
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Speech Synthesis Voice Selection Section */}
                    <div className="space-y-4 pb-6 border-b border-border">
                        <h3 className="text-sm font-bold text-foreground">Text-to-Speech Output (Synthesizer)</h3>
                        
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="voice">Preferred Synthesizer Voice</Label>
                                <Select value={selectedVoice || "system_default"} onValueChange={(val) => setSelectedVoice(val === "system_default" ? "" : val)}>
                                    <SelectTrigger id="voice" className="bg-background">
                                        <SelectValue placeholder="Default System Voice" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="system_default">Default System Voice</SelectItem>
                                        {voices.map((voice) => (
                                            <SelectItem key={voice.name} value={voice.name}>
                                                {voice.name} ({voice.lang})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* Voice Testing Panel */}
                            <div className="flex items-end">
                                <Button
                                    type="button"
                                    onClick={handleTestVoice}
                                    variant={isPlaying ? "destructive" : "secondary"}
                                    className="w-full flex items-center justify-center gap-2 h-10 shadow-sm"
                                >
                                    {isPlaying ? (
                                        <>
                                            <Square className="h-4 w-4 fill-current" /> Stop Test
                                        </>
                                    ) : (
                                        <>
                                            <Play className="h-4 w-4 fill-current" /> Test Pronunciation
                                        </>
                                    )}
                                </Button>
                            </div>
                        </div>

                        {/* Speech Parameters Sliders */}
                        <div className="grid gap-6 md:grid-cols-2 pt-2">
                            <div className="space-y-2.5">
                                <div className="flex justify-between items-center">
                                    <Label htmlFor="rate" className="text-xs font-semibold text-muted-foreground">Speech Rate (Speed)</Label>
                                    <span className="text-xs font-bold text-primary">{rate}x</span>
                                </div>
                                <input
                                    id="rate"
                                    type="range"
                                    min="0.5"
                                    max="2.0"
                                    step="0.1"
                                    value={rate}
                                    onChange={(e) => setRate(parseFloat(e.target.value))}
                                    className="w-full h-1.5 bg-muted rounded-lg appearance-none cursor-pointer accent-primary"
                                />
                            </div>

                            <div className="space-y-2.5">
                                <div className="flex justify-between items-center">
                                    <Label htmlFor="pitch" className="text-xs font-semibold text-muted-foreground">Speech Pitch</Label>
                                    <span className="text-xs font-bold text-primary">{pitch}</span>
                                </div>
                                <input
                                    id="pitch"
                                    type="range"
                                    min="0.5"
                                    max="2.0"
                                    step="0.1"
                                    value={pitch}
                                    onChange={(e) => setPitch(parseFloat(e.target.value))}
                                    className="w-full h-1.5 bg-muted rounded-lg appearance-none cursor-pointer accent-primary"
                                />
                            </div>
                        </div>
                    </div>

                    {/* Action buttons */}
                    <div className="flex gap-2 justify-end">
                        <Button onClick={handleSaveSettings} disabled={isSaving} className="px-6 h-10 font-medium flex items-center gap-2 shadow-md">
                            {isSaving ? (
                                <>
                                    <Loader2 className="h-4 w-4 animate-spin" /> Saving...
                                </>
                            ) : (
                                <>
                                    <Save className="h-4 w-4" /> Save Configuration
                                </>
                            )}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
