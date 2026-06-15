import { useState, useEffect } from "react";
import { AlertCircle, Calendar, CheckCircle2, Clock, MoreHorizontal, ChevronUp, ChevronDown, Eye, X } from "lucide-react";
import { AnimatePresence, motion } from "motion/react";
import { cn } from "@/lib/utils";
import { Card, CardContent, CardHeader } from "@/components/ui";
import { usePatientVaccinations } from "@/queries/usePatientVaccinations";
import { createPortal } from 'react-dom';


// vaccination table rows
function VaccineRow({
    name,
    desc,
    age,
    due,
    status,
    information,
}: any) {

    const [open, setOpen] = useState(false);
    const [mounted, setMounted] = useState(false);

    useEffect(() => {
        setMounted(true);
    }, []);


    return (
        <>
            <tr className="hover:bg-surface-container-low/30 transition-colors group">
                <td className="px-8 py-6 min-w-[240px]">
                    <div className="flex flex-col whitespace-nowrap">
                        <div className="flex items-center gap-2">
                            <span className="font-bold text-[#1F1E1E] text-base">
                                {name}
                            </span>

                            <button
                                type="button"
                                onClick={() => setOpen(true)}
                                className="p-1 rounded-md hover:bg-primary/10 text-primary transition-all"
                            >
                                <Eye className="w-4 h-4" />
                            </button>
                        </div>

                        <span className="text-xs font-medium text-[#4D4D4D] mt-0.5">
                            {desc}
                        </span>
                    </div>
                </td>

                <td className="px-8 py-6 min-w-[150px]">
                    <div className="flex items-center whitespace-nowrap text-sm font-semibold text-[#1F1E1E]">
                        {age}
                    </div>
                </td>

                <td className="px-8 py-6 min-w-[150px]">
                    <div className="flex items-center whitespace-nowrap text-sm font-semibold text-[#1F1E1E]">
                        {due}
                    </div>
                </td>

                <td className="px-8 py-6 min-w-[140px]">
                    <div className="flex items-center whitespace-nowrap">
                        {status === "Completed" ? (
                            <span className="inline-flex items-center gap-2 px-3 py-1 bg-green-50 text-green-700 border border-green-200 rounded-full text-[10px] font-black uppercase tracking-wider">
                                <CheckCircle2 className="w-3 h-3 fill-green-700 text-white" />
                                {status}
                            </span>
                        ) : status === "Pending" ? (
                            <span className="inline-flex items-center gap-2 px-3 py-1 bg-yellow-50 text-yellow-700 border border-yellow-200 rounded-full text-[10px] font-black uppercase tracking-wider">
                                <Clock className="w-3 h-3" />
                                {status}
                            </span>
                        ) : (
                            <span className="inline-flex items-center gap-2 px-3 py-1 bg-[#fbf5f7] text-[#ba1a1a] border border-[#ba1a1a]/20 rounded-full text-[10px] font-black uppercase tracking-wider">
                                <AlertCircle className="w-3 h-3 fill-[#ba1a1a] text-white" />
                                {status}
                            </span>
                        )}
                    </div>
                </td>
            </tr>
            {open && mounted && createPortal(
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div
                        className="absolute inset-0 bg-black/20 backdrop-blur-sm"
                        onClick={() => setOpen(false)}
                    />

                    <Card className="relative w-full max-w-md rounded-lg p-4 sm:p-5 md:p-6">
                        <CardHeader className="px-0">
                            <div className="flex items-center justify-between gap-3 mb-2">
                                <div className="flex items-center gap-3">
                                    <CheckCircle2 className="w-6 h-6 text-primary/60 shrink-0" />

                                    <h3 className="font-semibold text-[#1F1E1E] text-lg">
                                        {information?.name || name}
                                    </h3>
                                </div>

                                <button
                                    onClick={() => setOpen(false)}
                                    className="p-2 rounded-md border border-light-gray bg-white hover:bg-gray-50 transition-all shrink-0"
                                >
                                    <X className="w-4 h-4" />
                                </button>
                            </div>

                            <p className="text-sm text-[#4D4D4D]">
                                {information?.description || desc || "No details found."}
                            </p>
                        </CardHeader>

                        <CardContent className="space-y-3 px-0 mt-2">
                            <p className="text-[10px] font-black text-on-surface-variant uppercase tracking-widest">
                                Side Effects:
                            </p>

                            <ul className="text-xs font-semibold text-secondary space-y-1">
                                {(
                                    information?.side_effects
                                        ? information.side_effects.split(",")
                                        : ["No side effects data found."]
                                ).map((effect: string) => (
                                        <li
                                            key={effect}
                                            className="flex items-center gap-2 text-[#4D4D4D]"
                                        >
                                            <div className="w-1.5 h-1.5 rounded-full bg-primary/30" />
                                            {effect}
                                        </li>
                                    )
                                )}
                            </ul>
                        </CardContent>
                    </Card>
                </div>,
                document.body
            )}

        </>
    );
}


// vacination accordian heading
function VaccinationSet({
    id,
    title,
    subtitle,
    status,
    vaccinations = [],
    isExpanded,
    onToggle,
}: any) {
    return (
        <div className="bg-white rounded-md border-light-gray overflow-hidden shadow-sm hover:shadow-md transition-all duration-300">
            <button
                onClick={onToggle}
                className={cn(
                    "w-full flex items-center justify-between md:px-8 px-4 py-6 border-b border-outline-variant transition-colors",
                    isExpanded ? "bg-[#F5F6F8]" : "bg-white"
                )}
            >
                <div className="flex items-center gap-6 text-left">
                    <div
                        className={cn(
                            "w-10 h-10 min-w-10 min-h-10 shrink-0 rounded-2xl flex items-center justify-center transition-all",
                            status === "Completed"
                                ? "bg-green-50 text-green-600 scale-110 shadow-sm"
                                : status === "Upcoming"
                                    ? "bg-primary/5 text-primary"
                                    : "bg-red-50 text-red-500"
                        )}
                    >
                        {status === "Completed" ? (
                            <CheckCircle2 className="w-6 h-6" />
                        ) : status === "Upcoming" ? (
                            <Clock className="w-6 h-6" />
                        ) : (
                            <AlertCircle className="w-6 h-6" />
                        )}
                    </div>
                    <div>
                        <div className="flex items-center gap-3">
                            <h3 className="text-lg text-[#1F1E1E] font-semibold">{title}</h3>
                            <span className="text-xs text-[#4D4D4D] bg-surface-container-low px-2 py-1 rounded-full">{(vaccinations || []).length} vaccines</span>
                        </div>
                        <p className="text-sm text-[#4D4D4D]">{subtitle}</p>
                    </div>
                </div>
                <div className="flex items-center gap-8">
                    {status === 'Upcoming' && (
                        <span className="hidden sm:inline-flex items-center gap-2 px-4 py-1 bg-yellow-50 text-yellow-700 border border-yellow-200 rounded-full text-[10px] font-black uppercase tracking-widest">
                            <AlertCircle className="w-3 h-3" />
                            Upcoming
                        </span>
                    )}
                    {isExpanded ? <ChevronUp color="#1F1E1E" size={24} /> : <ChevronDown color="#1F1E1E" size={24} />}
                </div>
            </button>

            <AnimatePresence>
                {isExpanded && (
                    <motion.div
                        initial={{ height: 0, opacity: 0 }}
                        animate={{ height: 'auto', opacity: 1 }}
                        exit={{ height: 0, opacity: 0 }}
                        className="overflow-x-auto overflow-hidden"
                    >
                        <table className="w-full text-left">
                            <thead className="bg-surface-bright border-b border-outline-variant">
                                <tr>
                                    <th className="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em]">Vaccine Name</th>
                                    <th className="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em]">Recommended Age</th>
                                    <th className="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em]">Due Date</th>
                                    <th className="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em]">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-outline-variant/30">
                                {vaccinations?.map((v: any) => (
                                    <VaccineRow
                                        key={v.id}
                                        name={v.vaccine_name}
                                        desc={v.short_description}
                                        age={v.recommended_age}
                                        due={v.due_date}
                                        status={v.status_label}
                                        information={v.information}
                                        manufacturer={v.manufacturer}
                                        doctorNotes={v.doctor_notes}
                                        documents={v.documents}
                                    />
                                ))}
                            </tbody>
                        </table>
                    </motion.div>
                )}
            </AnimatePresence>
        </div>
    );
}

const VaccinationSchedule = () => {

    const [expandedSet, setExpandedSet] = useState<string | null>("1");

    const { data } = usePatientVaccinations();
    const schedule = data?.data?.vaccination_schedule || [];

    return (
        <section className="space-y-8">
            <h2 className="font-display text-2xl font-bold text-on-surface flex items-center gap-4">
                <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary shadow-sm">
                    <Calendar className="w-5 h-5" />
                </div>
                Vaccination Schedule
            </h2>

            <div className="space-y-6">
                {schedule.map((set) => (
                    <VaccinationSet
                        key={set.set_id}
                        id={String(set.set_id)}
                        title={set.set_name}
                        subtitle={set.description}
                        status={
                            set.status === "completed"
                                ? "Completed"
                                : set.status === "upcoming"
                                    ? "Upcoming"
                                    : "Pending"
                        }
                        vaccinations={set.vaccinations}
                        isExpanded={expandedSet === String(set.set_id)}
                        onToggle={() =>
                            setExpandedSet(
                                expandedSet === String(set.set_id)
                                    ? null
                                    : String(set.set_id)
                            )
                        }
                    />
                ))}
            </div>
        </section>
    )
}

export default VaccinationSchedule