import { useState } from "react";
import { AlertCircle, Calendar, CheckCircle2, Clock, MoreHorizontal, ChevronUp, ChevronDown } from "lucide-react";
import { AnimatePresence, motion } from "motion/react";
import { cn } from "@/lib/utils";

function VaccineRow({ name, desc, age, due, status }: any) {
    return (
        <tr className="hover:bg-surface-container-low/30 transition-colors group">
            <td className="px-8 py-6">
                <div className="flex flex-col">
                    <span className="font-bold text-[#1F1E1E] text-base">{name}</span>
                    <span className="text-xs font-medium text-[#4D4D4D] mt-0.5">{desc}</span>
                </div>
            </td>
            <td className="px-8 py-6 text-sm font-semibold text-[#1F1E1E]">{age}</td>
            <td className="px-8 py-6 text-sm font-semibold text-[#1F1E1E]">{due}</td>
            <td className="px-8 py-6">
                {status === 'Completed' ? (
                    <span className="inline-flex items-center gap-2 px-3 py-1 bg-green-50 text-green-700 border border-green-200 rounded-full text-[10px] font-black uppercase tracking-wider">
                        <CheckCircle2 className="w-3 h-3 fill-green-700 text-white" />
                        Completed
                    </span>
                ) : (
                    <span className="inline-flex items-center gap-2 px-3 py-1 bg-[#fbf5f7] text-[#ba1a1a] border border-[#ba1a1a]/20 rounded-full text-[10px] font-black uppercase tracking-wider">
                        <AlertCircle className="w-3 h-3 fill-[#ba1a1a] text-white" />
                        Missed
                    </span>
                )}
            </td>
        </tr>
    );
}

function VaccinationSet({ id, title, subtitle, status, isExpanded, onToggle }: any) {
    return (
        <div className="bg-white rounded-md border-light-gray overflow-hidden shadow-sm hover:shadow-md transition-all duration-300">
            <button
                onClick={onToggle}
                className={cn(
                    "w-full flex items-center justify-between px-8 py-6 border-b border-outline-variant transition-colors",
                    isExpanded ? "bg-[#F5F6F8]" : "bg-white"
                )}
            >
                <div className="flex items-center gap-6 text-left">
                    <div className={cn(
                        "w-12 h-12 rounded-2xl flex items-center justify-center transition-all",
                        status === 'Completed' ? "bg-green-50 text-green-600 scale-110 shadow-sm" :
                            status === 'Upcoming' ? "bg-primary/5 text-primary" :
                                "bg-surface-container-highest text-outline"
                    )}>
                        {status === 'Completed' ? <CheckCircle2 className="w-6 h-6" /> :
                            status === 'Upcoming' ? <Clock className="w-6 h-6" /> :
                                <MoreHorizontal className="w-6 h-6" />}
                    </div>
                    <div>
                        <h3 className="text-lg text-[#1F1E1E] font-semibold">{title}</h3>
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
                                <VaccineRow
                                    name="BCG"
                                    desc="Tuberculosis Prevention"
                                    age="At Birth"
                                    due="05 Feb 2023"
                                    status="Completed"
                                />
                                <VaccineRow
                                    name="HepB - 1"
                                    desc="Hepatitis B First Dose"
                                    age="At Birth"
                                    due="05 Feb 2023"
                                    status="Completed"
                                />
                                <VaccineRow
                                    name="Polio (OPV)"
                                    desc="Oral Polio Vaccine"
                                    age="6 Weeks"
                                    due="19 Mar 2023"
                                    status="Missed"
                                />
                            </tbody>
                        </table>
                    </motion.div>
                )}
            </AnimatePresence>
        </div>
    );
}

const VaccinationSchedule = () => {

    const [expandedSet, setExpandedSet] = useState<string | null>('set1');

    return (
        <section className="space-y-8">
            <h2 className="font-display text-2xl font-bold text-on-surface flex items-center gap-4">
                <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary shadow-sm">
                    <Calendar className="w-5 h-5" />
                </div>
                Vaccination Schedule
            </h2>

            <div className="space-y-6">
                <VaccinationSet
                    id="set1"
                    title="Set 1 (0-6 Months)"
                    subtitle="All primary immunizations completed successfully."
                    status="Completed"
                    isExpanded={expandedSet === 'set1'}
                    onToggle={() => setExpandedSet(expandedSet === 'set1' ? null : 'set1')}
                />
                <VaccinationSet
                    id="set2"
                    title="Set 2 (6-12 Months)"
                    subtitle="Upcoming immunizations for the current growth phase."
                    status="Upcoming"
                    isExpanded={expandedSet === 'set2'}
                    onToggle={() => setExpandedSet(expandedSet === 'set2' ? null : 'set2')}
                />
                <VaccinationSet
                    id="set3"
                    title="Set 3 (Future Doses)"
                    subtitle="Long-term vaccination roadmap."
                    status="Future"
                    isExpanded={expandedSet === 'set3'}
                    onToggle={() => setExpandedSet(expandedSet === 'set3' ? null : 'set3')}
                />
            </div>
        </section>
    )
}

export default VaccinationSchedule