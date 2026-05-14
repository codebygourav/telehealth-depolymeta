import { useState } from 'react';
import {
    Plus,
    Utensils,

    Eye,
    ArrowRight,
    TrendingUp,
    History
} from 'lucide-react';
import { DietMeal, DietTemplate } from '@/types/types';
import { cn } from '@/lib/utils';
import { DIET_TEMPLATES } from '@/src/utils/constants';


export function DietPlanManagement() {
    const [assignedMeals, setAssignedMeals] = useState<DietMeal[]>([]);
    const [isTemplateModalOpen, setIsTemplateModalOpen] = useState(false);
    const [previewTemplate, setPreviewTemplate] = useState<DietTemplate | null>(null);

    const handleAssignTemplate = (template: DietTemplate) => {
        const initializedMeals = template.meals.map(m => ({
            ...m,
            status: 'Pending' as const
        }));
        setAssignedMeals(initializedMeals);
        setIsTemplateModalOpen(false);
    };

    const markFollowed = (id: string) => {
        setAssignedMeals(prev => prev.map(m =>
            m.id === id ? { ...m, status: 'Followed', completedAt: new Date().toLocaleTimeString() } : m
        ));
    };

    const completionPercentage = assignedMeals.length > 0
        ? Math.round((assignedMeals.filter(m => m.status === 'Followed').length / assignedMeals.length) * 100)
        : 0;

    return (
        <div className="space-y-8 animate-stagger-fade">
            {/* Top Controls & Tracker */}
            <section className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div className="lg:col-span-2 rounded-lg border bg-white p-4 sm:p-5 md:p-6 shadow-sm flex flex-col md:flex-row items-center gap-6">
                    <div className="relative w-48 h-48 shrink-0">
                        <svg className="w-full h-full transform -rotate-90 ">

                            {/* Background Circle */}
                            <circle
                                className="text-muted  border-light-gray"
                                cx="96"
                                cy="96"
                                fill="transparent"
                                r="84"
                                stroke="currentColor"
                                strokeWidth="16"
                            />

                            {/* Progress Circle */}
                            <circle
                                className="text-primary transition-all duration-500"
                                cx="96"
                                cy="96"
                                fill="transparent"
                                r="84"
                                stroke="currentColor"
                                strokeWidth="16"
                                strokeLinecap="round"
                                strokeDasharray={2 * Math.PI * 84}
                                strokeDashoffset={
                                    2 * Math.PI * 84 -
                                    (completionPercentage / 100) * (2 * Math.PI * 84)
                                }
                            />

                        </svg>
                        <div className="absolute inset-0 flex flex-col items-center justify-center">
                            <span className="text-5xl font-black text-on-surface tracking-tighter">{completionPercentage}%</span>
                            <span className="text-[10px] font-black  uppercase tracking-widest">Daily Progress</span>
                        </div>
                    </div>
                    <div className="flex-1 space-y-6 text-center md:text-left">
                        <h3 className="text-2xl font-bold text-on-surface">Precision Nutrition Tracker</h3>
                        <p className="text-sm font-medium leading-relaxed">
                            Real-time monitoring of dietary adherence. Ensure all required calories and micronutrients are met for the current growth phase.
                        </p>
                        <div className="flex flex-wrap gap-4 justify-center md:justify-start">
                            <div className="px-5 py-3  rounded-lg shadow-sm border border-outline-variant/20">
                                <p className="text-[10px] font-black uppercase tracking-widest mb-1">Calories Met</p>
                                <p className="text-lg font-bold text-primary">
                                    {assignedMeals.filter(m => m.status === 'Followed').reduce((acc, curr) => acc + curr.calories, 0)} kcal
                                </p>
                            </div>
                            <div className="px-4 py-3 rounded-lg border bg-white shadow-sm">
                                <p className="text-[10px] font-black uppercase tracking-widest mb-1">Meals Followed</p>
                                <p className="text-lg font-bold text-on-surface">
                                    {assignedMeals.filter(m => m.status === 'Followed').length} / {assignedMeals.length}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="space-y-6">
                    <button
                        onClick={() => setIsTemplateModalOpen(true)}
                        className="w-full rounded-lg border bg-white p-4 sm:p-5 md:p-6 shadow-sm flex items-center justify-between hover:border-primary transition-all"                    >
                        <div className="text-left">
                            <span className="text-[10px] font-black uppercase tracking-widest block mb-1 opacity-60">Action</span>
                            <span className="text-lg font-bold">Assign Diet Plan</span>
                        </div>
                        <ArrowRight className="w-5 h-5 group-hover:translate-x-2 transition-transform" />
                    </button>
                    <button className="w-full rounded-lg border bg-white p-4 sm:p-5 md:p-6 shadow-sm flex items-center justify-between hover:border-primary transition-all">
                        <div className="text-left">
                            <span className="text-[10px] font-black uppercase tracking-widest block mb-1 opacity-60">Customization</span>
                            <span className="text-lg font-bold">Create Custom Plan</span>
                        </div>
                        <Plus className="w-5 h-5 group-hover:rotate-90 transition-transform" />
                    </button>
                </div>
            </section>

            {/* Assigned Diet Table */}
            <section className="rounded-lg border bg-white p-4 sm:p-5 md:p-6 shadow-sm space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b pb-4">
                    <h4 className="text-base sm:text-lg font-semibold text-[#1F1E1E] flex items-center gap-2 sm:gap-3">
                        <Utensils className="w-5 h-5 sm:w-6 sm:h-6 text-primary shrink-0" />
                        <span>Assigned Diet Chart</span>
                    </h4>

                    <div className="flex items-center gap-2 w-fit rounded-md bg-green-100 px-3 py-1.5">
                        <TrendingUp className="w-4 h-4 sm:w-5 sm:h-5 text-green-600 shrink-0" />

                        <span className="text-[10px] sm:text-xs font-semibold text-green-600 uppercase tracking-wide whitespace-nowrap">
                            Growth Phase Active
                        </span>
                    </div>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full text-left">
                        <thead className="bg-[#F8F8F8]">
                            <tr>
                                <th className="px-6 py-4 text-xs font-semibold text-[#4D4D4D]">Meal Time</th>
                                <th className="px-6 py-4 text-xs font-semibold text-[#4D4D4D]">Meal Type</th>
                                <th className="px-6 py-4 text-xs font-semibold text-[#4D4D4D]">Food Items</th>
                                <th className="px-6 py-4 text-xs font-semibold text-[#4D4D4D]">Calories</th>
                                <th className="px-6 py-4 text-xs font-semibold text-[#4D4D4D]">Status</th>
                                <th className="px-6 py-4 text-xs font-semibold text-[#4D4D4D]">Action</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-outline-variant/10">
                            {assignedMeals.length > 0 ? assignedMeals.map((meal) => (
                                <tr key={meal.id} className="hover:bg-primary/5 transition-colors group">

                                    <td className="px-6 py-6">
                                        <div className="flex items-center whitespace-nowrap">
                                            <span className="text-sm font-semibold text-[#1F1E1E]">
                                                {meal.time}
                                            </span>
                                        </div>
                                    </td>

                                    <td className="px-6 py-6">
                                        <div className="flex items-center whitespace-nowrap">
                                            <span className="text-sm font-medium text-[#4D4D4D]">
                                                {meal.type}
                                            </span>
                                        </div>
                                    </td>

                                    <td className="px-6 py-6 min-w-[260px]">
                                        <div className="flex items-center">
                                            <span className="text-sm font-medium text-[#4D4D4D]">
                                                {meal.items}
                                            </span>
                                        </div>
                                    </td>

                                    <td className="px-6 py-6">
                                        <div className="flex items-center whitespace-nowrap">
                                            <span className="text-sm font-semibold text-primary">
                                                {meal.calories} kcal
                                            </span>
                                        </div>
                                    </td>

                                    <td className="px-6 py-6">
                                        <div className="flex items-center whitespace-nowrap">
                                            <span
                                                className={cn(
                                                    "px-3 py-1 rounded-md text-xs font-semibold",
                                                    meal.status === "Followed"
                                                        ? "bg-green-50 text-green-600"
                                                        : meal.status === "Missed"
                                                            ? "bg-error/10 text-error"
                                                            : "bg-primary/10 text-primary"
                                                )}
                                            >
                                                {meal.status}
                                            </span>
                                        </div>
                                    </td>

                                    <td className="px-6 py-6">
                                        <div className="flex justify-end whitespace-nowrap">
                                            {meal.status === "Pending" ? (
                                                <button
                                                    onClick={() => markFollowed(meal.id)}
                                                    className="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium shadow-sm hover:opacity-90 transition-all"
                                                >
                                                    Mark Followed
                                                </button>
                                            ) : (
                                                <div className="text-[10px] font-black uppercase opacity-60">
                                                    {meal.completedAt}
                                                </div>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            )) : (
                                <tr>
                                    <td colSpan={6} className="px-6 py-20 text-center">
                                            <div className="flex flex-col items-center gap-3 opacity-50">
                                            <History className="w-16 h-16" />
                                            <p className="text-sm font-bold">No active diet plan. Assign one to start tracking.</p>
                                        </div>
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </section>

            {/* Template Selection Modal */}
            <section>
                {isTemplateModalOpen && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4">
                        <div
                            onClick={() => setIsTemplateModalOpen(false)}
                            className="absolute inset-0 bg-black/20 backdrop-blur-sm"
                        />

                        <div className="relative w-full max-w-4xl bg-white rounded-lg border shadow-sm overflow-hidden flex flex-col max-h-[92vh]">
                            <div className="px-4 sm:px-5 md:px-6 py-4 border-b flex items-center justify-between gap-3">
                                <h3 className="text-base sm:text-lg font-semibold text-[#1F1E1E]">
                                    Select Nutrition Template
                                </h3>

                                {!previewTemplate ? (
                                    <button
                                        onClick={() => setIsTemplateModalOpen(false)}
                                        className="p-2 rounded-md border bg-white hover:bg-gray-50 transition-all shrink-0"
                                    >
                                        <Plus className="w-5 h-5 sm:w-6 sm:h-6 rotate-45" />
                                    </button>
                                ) : (
                                    <button
                                        onClick={() => setPreviewTemplate(null)}
                                        className="flex items-center gap-2 text-primary font-semibold text-[10px] sm:text-xs uppercase tracking-wide shrink-0"
                                    >
                                        <Plus className="w-4 h-4 rotate-45" />
                                        <span className="whitespace-nowrap">View All Plans</span>
                                    </button>
                                )}
                            </div>

                            <div className="flex-1 overflow-y-auto p-4 sm:p-5 md:p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                                {previewTemplate ? (
                                    <div className="col-span-full space-y-5">
                                        <div className="rounded-lg border bg-white p-4 sm:p-5 md:p-6 shadow-sm">
                                            <div className="flex items-start justify-between gap-3 sm:gap-4 mb-6">
                                                <div className="min-w-0 flex-1">
                                                    <h4 className="text-base sm:text-xl md:text-2xl font-bold text-[#1F1E1E] leading-snug break-words">
                                                        {previewTemplate.name}
                                                    </h4>

                                                    <span className="inline-block mt-2 bg-primary/10 text-primary px-2.5 sm:px-3 py-1 rounded-md text-[10px] sm:text-xs font-semibold">
                                                        {previewTemplate.duration}
                                                    </span>
                                                </div>

                                                <div className="shrink-0 text-right">
                                                    <p className="text-2xl sm:text-3xl md:text-4xl font-bold text-[#1F1E1E] leading-none">
                                                        {previewTemplate.calories.split(" ")[0]}
                                                    </p>

                                                    <p className="text-[10px] sm:text-xs font-semibold text-[#4D4D4D] mt-1 whitespace-nowrap">
                                                        Total Kcal/Day
                                                    </p>
                                                </div>
                                            </div>

                                            <div className="overflow-x-auto rounded-lg border mb-6">
                                                <table className="w-full min-w-[520px] text-left bg-white">
                                                    <thead className="bg-[#F8F8F8]">
                                                        <tr>
                                                            <th className="px-4 py-3 text-xs font-semibold text-[#4D4D4D]">Time</th>
                                                            <th className="px-4 py-3 text-xs font-semibold text-[#4D4D4D]">Meal</th>
                                                            <th className="px-4 py-3 text-xs font-semibold text-[#4D4D4D]">Calories</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody className="divide-y">
                                                        {previewTemplate.meals.map((m) => (
                                                            <tr key={m.id}>
                                                                <td className="px-4 py-3 text-sm text-[#4D4D4D] whitespace-nowrap">
                                                                    {m.time}
                                                                </td>
                                                                <td className="px-4 py-3 text-sm text-[#4D4D4D] min-w-[240px]">
                                                                    {m.type} - {m.items}
                                                                </td>
                                                                <td className="px-4 py-3 text-sm font-semibold text-primary whitespace-nowrap">
                                                                    {m.calories}
                                                                </td>
                                                            </tr>
                                                        ))}
                                                    </tbody>
                                                </table>
                                            </div>

                                            <button
                                                onClick={() => handleAssignTemplate(previewTemplate)}
                                                className="w-full bg-primary text-white py-3 rounded-md text-sm font-semibold shadow-sm hover:opacity-90 transition-all"
                                            >
                                                Assign This Diet Plan
                                            </button>
                                        </div>
                                    </div>
                                ) : (
                                    DIET_TEMPLATES.map((template) => (
                                        <div
                                            key={template.id}
                                            className="rounded-lg border bg-white p-4 sm:p-5 md:p-6 shadow-sm flex flex-col justify-between"
                                        >
                                            <div>
                                                <div className="flex justify-between items-start mb-4">
                                                    <div className="p-3 bg-primary/10 rounded-md text-primary">
                                                        <Utensils className="w-5 h-5" />
                                                    </div>

                                                    <span className="text-2xl sm:text-3xl font-bold text-[#1F1E1E]/10">
                                                        {template.calories.split(" ")[0]}
                                                    </span>
                                                </div>

                                                <h4 className="text-lg font-semibold text-[#1F1E1E] mb-2">
                                                    {template.name}
                                                </h4>

                                                <div className="space-y-2">
                                                    <p className="text-xs text-[#4D4D4D] flex items-center justify-between gap-3">
                                                        Meal Count:
                                                        <span className="font-semibold text-[#1F1E1E]">
                                                            {template.mealCount}
                                                        </span>
                                                    </p>

                                                    <p className="text-xs text-[#4D4D4D] flex items-center justify-between gap-3">
                                                        Duration:
                                                        <span className="font-semibold text-primary">
                                                            {template.duration}
                                                        </span>
                                                    </p>
                                                </div>
                                            </div>

                                            <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 mt-6">
                                                <button
                                                    onClick={() => setPreviewTemplate(template)}
                                                    className="flex-1 py-3 px-4 border rounded-md bg-white text-sm font-medium flex items-center justify-center gap-2 hover:bg-gray-50 transition-all"
                                                >
                                                    <Eye className="w-4 h-4" />
                                                    Preview
                                                </button>

                                                <button
                                                    onClick={() => handleAssignTemplate(template)}
                                                    className="flex-1 py-3 px-4 bg-primary text-white rounded-md text-sm font-semibold flex items-center justify-center gap-2 shadow-sm hover:opacity-90 transition-all"
                                                >
                                                    Assign
                                                </button>
                                            </div>
                                        </div>
                                    ))
                                )}
                            </div>
                        </div>
                    </div>
                )}
            </section>
        </div>
    );
}
