import { useState } from "react";
import {

    Plus,
    Utensils,
    Eye,
    ArrowRight,
    TrendingUp,
    History,
    XCircle,
    ChevronDown,
    Info
} from 'lucide-react';

import { cn } from '@/lib/utils';

import { useDietTemplates, usePatientDietPlan } from '@/queries/useDietTemplates';
import { useAssignDietTemplate } from '@/mutations/assign-diet-Template';
import CustomDialog from '@/components/custom/Dialogboxs';

interface DietPlanManagementProps {
    patientId?: string;
}

export function DietPlanManagement({
    patientId,
}: DietPlanManagementProps) {

    console.log("patient id", patientId);

    const [isTemplateModalOpen, setIsTemplateModalOpen] = useState(false);
    const [previewTemplate, setPreviewTemplate] = useState<any | null>(null);
    const [openDay, setOpenDay] = useState<string | null>(null);
    const [openMealDay, setOpenMealDay] = useState<string | null>(null);
    const [assignedDietPlan, setAssignedDietPlan] = useState<any | null>(null);
    const [assignSuccessOpen, setAssignSuccessOpen] = useState(false);
    const [assignDialogOpen, setAssignDialogOpen] = useState(false);
    const [selectedTemplate, setSelectedTemplate] = useState<any | null>(null);

    const [startDate, setStartDate] = useState("2026-05-18");
    const [durationDays, setDurationDays] = useState(7);
    const [specialInstructions, setSpecialInstructions] = useState(
        "Avoid sugar and fried snacks. Keep dinner before 8 PM."
    );

    const { data, isLoading, error } = useDietTemplates();
    const dietTemplates = data?.data || [];

    const {
        data: patientDietData,
        refetch: refetchPatientDietPlan,
    } = usePatientDietPlan();

    const dietPlan = assignedDietPlan || patientDietData?.data;

    const assignDietMutation = useAssignDietTemplate();

    const getTotalCalories = (template: any) => {
        return template.days?.reduce(
            (total: number, day: any) =>
                total +
                day.meals?.reduce(
                    (sum: number, meal: any) => sum + (meal.calories || 0),
                    0
                ),
            0
        ) || 0;
    };
    console.log("Diet Templates:", data);

    if (isLoading) {
        return (
            <div className="flex flex-col items-center justify-center py-20 gap-4">
                <div className="h-14 w-14 rounded-full border-4 border-primary/20 border-t-primary animate-spin" />

                <p className="text-sm font-semibold text-[#4D4D4D]">
                    Loading templates...
                </p>
            </div>
        );
    }

    if (error) {
        return (
            <div className="flex flex-col items-center justify-center py-20 gap-4 text-center">
                <div className="flex h-14 w-14 items-center justify-center rounded-full bg-red-100">
                    <XCircle className="w-7 h-7 text-red-600" />
                </div>

                <div>
                    <h3 className="text-base font-bold text-[#1F1E1E]">
                        Failed to load templates
                    </h3>

                    <p className="mt-1 text-sm text-[#4D4D4D]">
                        Something went wrong while fetching diet templates.
                    </p>
                </div>
            </div>
        );
    }

    const handleAssignTemplate = async () => {
        if (!selectedTemplate) return;

        try {
            const response = await assignDietMutation.mutateAsync({
                patient_id: patientId || "",
                template_id: selectedTemplate.id,
                start_date: startDate,
                duration_days: durationDays,
                special_instructions: specialInstructions,
            });

            console.log("Assign API Response:", response);

            setAssignedDietPlan(response?.data);

            await refetchPatientDietPlan();

            setAssignDialogOpen(false);
            setIsTemplateModalOpen(false);
            setPreviewTemplate(null);
            setAssignSuccessOpen(true);
        } catch (error) {
            console.log("Assign diet failed:", error);
        }
    };

    const markFollowed = async (id: string) => {
        console.log("Mark followed:", id);
    };

    const apiAssignedMeals =
        dietPlan?.days?.flatMap((day: any) =>
            day.meals.map((meal: any) => ({
                id: meal.id,
                weekDay: day.week_day,
                dayNumber: day.day_number,
                date: day.date,
                time: meal.meal_time,
                type: meal.meal_type,
                items: meal.meal_name,
                instructions: meal.instructions,
                calories: meal.calories,
                status: meal.status || "pending",
                completedAt: meal.completed_at,
            }))
        ) || [];

    const assignedMeals = apiAssignedMeals;

    const completionPercentage =
        assignedMeals.length > 0
            ? Math.round(
                (assignedMeals.filter((m: any) => m.status === "followed").length /
                    assignedMeals.length) *
                100
            )
            : 0;
    const groupedMeals = assignedMeals.reduce(
        (acc: Record<string, any[]>, meal: any) => {
            const weekDay = meal.weekDay || "DAY";

            if (!acc[weekDay]) {
                acc[weekDay] = [];
            }

            acc[weekDay].push(meal);

            return acc;
        },
        {}
    );

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
                                    {assignedMeals
                                        .filter((m: any) => m.status === "followed")
                                        .reduce(
                                            (acc: number, curr: any) => acc + curr.calories,
                                            0
                                        )} kcal
                                </p>
                            </div>
                            <div className="px-4 py-3 rounded-lg border bg-white shadow-sm">
                                <p className="text-[10px] font-black uppercase tracking-widest mb-1">Meals Followed</p>
                                <p className="text-lg font-bold text-on-surface">
                                    {assignedMeals.filter((m: any) => m.status === "followed").length} / {assignedMeals.length}
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

                <div className="space-y-3">
                    {Object.keys(groupedMeals).length > 0 ? (
                        Object.entries(groupedMeals).map(([day, meals]: any) => (
                            <div
                                key={day}
                                className="rounded-lg border overflow-hidden bg-white"
                            >
                                <button
                                    type="button"
                                    onClick={() =>
                                        setOpenMealDay(
                                            openMealDay === day ? null : String(day)
                                        )
                                    }
                                    className="w-full px-4 py-3 bg-[#F8F8F8] flex items-center justify-between text-left"
                                >
                                    <div>
                                        <p className="text-base font-bold text-[#1F1E1E] uppercase">
                                            {day}
                                        </p>

                                        <p className="text-sm text-[#4D4D4D]">
                                            Day {meals[0]?.dayNumber}
                                        </p>
                                    </div>

                                    <div className="flex items-center gap-2 text-primary shrink-0">
                                        <span className="text-sm font-semibold whitespace-nowrap">
                                            {meals.length} Meals
                                        </span>

                                        <ChevronDown
                                            className={cn(
                                                "w-5 h-5 transition-transform duration-300",
                                                openMealDay === day && "rotate-180"
                                            )}
                                        />
                                    </div>
                                </button>

                                <div
                                    className={cn(
                                        "grid transition-all duration-300 ease-in-out",
                                        openMealDay === day
                                            ? "grid-rows-[1fr]"
                                            : "grid-rows-[0fr]"
                                    )}
                                >
                                    <div className="overflow-hidden">
                                        <div className="overflow-x-auto">
                                            <table className="w-full text-left">
                                                <thead className="bg-white">
                                                    <tr>
                                                        <th className="px-6 py-4 text-xs font-semibold text-[#4D4D4D]">
                                                            Meal Time
                                                        </th>

                                                        <th className="px-6 py-4 text-xs font-semibold text-[#4D4D4D]">
                                                            Meal Type
                                                        </th>

                                                        <th className="px-6 py-4 text-xs font-semibold text-[#4D4D4D]">
                                                            Food Items
                                                        </th>

                                                        <th className="px-6 py-4 text-xs font-semibold text-[#4D4D4D]">
                                                            Calories
                                                        </th>

                                                        <th className="px-6 py-4 text-xs font-semibold text-[#4D4D4D]">
                                                            Status
                                                        </th>

                                                        <th className="px-6 py-4 text-xs font-semibold text-[#4D4D4D]">
                                                            Action
                                                        </th>
                                                    </tr>
                                                </thead>

                                                <tbody className="divide-y divide-outline-variant/10">
                                                    {meals.map((meal: any) => (

                                                        <tr
                                                            key={meal.id}
                                                            className="hover:bg-primary/5 transition-colors"
                                                        >
                                                            <td className="px-6 py-6">
                                                                {meal.time}
                                                            </td>

                                                            <td className="px-6 py-6">
                                                                {meal.type}
                                                            </td>

                                                            <td className="px-6 py-6 min-w-65">
                                                                <div className="flex flex-col">
                                                                    <span className="text-sm font-semibold text-[#1F1E1E]">
                                                                        {meal.items}
                                                                    </span>
                                                                    <span className="text-xs text-[#4D4D4D] mt-1">
                                                                        {meal.instructions}
                                                                    </span>
                                                                </div>
                                                            </td>

                                                            <td className="px-6 py-6 text-primary font-semibold">
                                                                {meal.calories} kcal
                                                            </td>

                                                            <td className="px-6 py-6">
                                                                <span
                                                                    className={cn(
                                                                        "px-3 py-1 rounded-md text-xs font-semibold",
                                                                        meal.status === "followed"
                                                                            ? "bg-green-50 text-green-600"
                                                                            : meal.status === "missed"
                                                                                ? "bg-error/10 text-error"
                                                                                : "bg-primary/10 text-primary"
                                                                    )}
                                                                >
                                                                    {meal.status}
                                                                </span>
                                                            </td>

                                                            <td className="px-6 py-6">
                                                                {meal.status === "pending" ? (
                                                                    <button
                                                                        onClick={() =>
                                                                            markFollowed(meal.id)
                                                                        }
                                                                        className="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium"
                                                                    >
                                                                        Mark Followed
                                                                    </button>
                                                                ) : (
                                                                    <div className="text-[10px] font-black uppercase opacity-60">
                                                                        {meal.completedAt}
                                                                    </div>
                                                                )}
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ))
                    ) : (
                        <div className="px-6 py-20 text-center">
                            <div className="flex flex-col items-center gap-3 opacity-50">
                                <History className="w-16 h-16" />

                                <p className="text-sm font-bold">
                                    No active diet plan. Assign one to start tracking.
                                </p>
                            </div>
                        </div>
                    )}
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
                                                    <h4 className="text-base sm:text-xl md:text-2xl font-bold text-[#1F1E1E] leading-snug wrap-break-word">
                                                        {previewTemplate.name}
                                                    </h4>

                                                    <span className="inline-block mt-2 bg-primary/10 text-primary px-2.5 sm:px-3 py-1 rounded-md text-[10px] sm:text-xs font-semibold">
                                                        {previewTemplate.duration_days} Days
                                                    </span>
                                                </div>

                                                <div className="shrink-0 text-right">
                                                    <p className="text-2xl sm:text-3xl md:text-4xl font-bold text-[#1F1E1E] leading-none">
                                                        {getTotalCalories(previewTemplate)}
                                                    </p>

                                                    <p className="text-[10px] sm:text-xs font-semibold text-[#4D4D4D] mt-1 whitespace-nowrap">
                                                        Total Kcal/Day
                                                    </p>
                                                </div>
                                            </div>

                                            <div className="space-y-3 mb-6">
                                                {previewTemplate.days?.map((day: any) => (
                                                    <div
                                                        key={day.id}
                                                        className="rounded-lg border bg-white overflow-hidden"
                                                    >
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                setOpenDay(openDay === day.id ? null : day.id)
                                                            }
                                                            className="w-full cursor-pointer px-4 py-3 bg-[#F8F8F8] flex items-center justify-between gap-3 text-left"
                                                        >
                                                            <div>
                                                                <p className="text-sm font-bold text-[#1F1E1E]">
                                                                    {day.week_day}
                                                                </p>
                                                                <p className="text-xs text-[#4D4D4D]">
                                                                    Day {day.day_number}
                                                                </p>
                                                            </div>

                                                            <div className="flex items-center gap-2 text-primary shrink-0">
                                                                <span className="text-xs font-semibold whitespace-nowrap">
                                                                    {day.meals?.length || 0} Meals
                                                                </span>

                                                                <ChevronDown
                                                                    className={cn(
                                                                        "w-5 h-5 transition-transform duration-300",
                                                                        openDay === day.id && "rotate-180"
                                                                    )}
                                                                />
                                                            </div>
                                                        </button>

                                                        <div
                                                            className={cn(
                                                                "grid transition-all duration-300 ease-in-out",
                                                                openDay === day.id
                                                                    ? "grid-rows-[1fr]"
                                                                    : "grid-rows-[0fr]"
                                                            )}
                                                        >
                                                            <div className="overflow-hidden">
                                                                <div className="overflow-x-auto">
                                                                    <table className="w-full min-w-130 text-left bg-white">
                                                                        <thead className="bg-white">
                                                                            <tr>
                                                                                <th className="px-4 py-3 text-xs font-semibold text-[#4D4D4D]">
                                                                                    Time
                                                                                </th>
                                                                                <th className="px-4 py-3 text-xs font-semibold text-[#4D4D4D]">
                                                                                    Meal
                                                                                </th>
                                                                                <th className="px-4 py-3 text-xs font-semibold text-[#4D4D4D]">
                                                                                    Calories
                                                                                </th>
                                                                            </tr>
                                                                        </thead>

                                                                        <tbody className="divide-y">
                                                                            {day.meals?.map((m: any) => (
                                                                                <tr key={m.id}>
                                                                                    <td className="px-4 py-3 text-sm text-[#4D4D4D] whitespace-nowrap">
                                                                                        {m.start_time}
                                                                                    </td>

                                                                                    <td className="px-4 py-3 min-w-60">
                                                                                        <div className="flex flex-col">
                                                                                            <span className="text-sm font-semibold text-[#1F1E1E]">
                                                                                                {m.meal_type} - {m.meal_name}
                                                                                            </span>

                                                                                            <div className="flex items-center gap-1">
                                                                                                <Info className="w-3 h-3 text-primary shrink-0 " />

                                                                                                <span className="text-xs text-[#4D4D4D] leading-relaxed">
                                                                                                    {m.instructions}
                                                                                                </span>
                                                                                            </div>
                                                                                        </div>
                                                                                    </td>

                                                                                    <td className="px-4 py-3 text-sm font-semibold text-primary whitespace-nowrap">
                                                                                        {m.calories} kcal
                                                                                    </td>
                                                                                </tr>
                                                                            ))}
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>

                                            <button
                                                onClick={() => {
                                                    if (!previewTemplate) return;

                                                    setSelectedTemplate(previewTemplate);
                                                    setStartDate("2026-05-18");
                                                    setDurationDays(previewTemplate?.duration_days || 7);
                                                    setSpecialInstructions(
                                                        "Avoid sugar and fried snacks. Keep dinner before 8 PM."
                                                    );

                                                    setAssignDialogOpen(true);
                                                }}
                                                className="w-full bg-primary text-white py-3 rounded-md text-sm font-semibold shadow-sm hover:opacity-90 transition-all"
                                            >
                                                Assign This Diet Plan
                                            </button>
                                        </div>
                                    </div>
                                ) : (
                                    dietTemplates.map((template: any) => (
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
                                                        {getTotalCalories(template)}
                                                    </span>
                                                </div>

                                                <h4 className="text-lg font-semibold text-[#1F1E1E] mb-2">
                                                    {template.name}
                                                </h4>

                                                <div className="space-y-2">
                                                    <p className="text-xs text-[#4D4D4D] flex items-center justify-between gap-3">
                                                        Meal Count:
                                                        <span className="font-semibold text-[#1F1E1E]">
                                                            {template.days?.reduce((total: number, day: any) => total + (day.meals?.length || 0), 0)}
                                                        </span>
                                                    </p>

                                                    <p className="text-xs text-[#4D4D4D] flex items-center justify-between gap-3">
                                                        Duration:
                                                        <span className="font-semibold text-primary">
                                                            {template.duration_days} Days
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
                                                    onClick={() => {
                                                        setSelectedTemplate(template);
                                                        setStartDate("2026-05-18");
                                                        setDurationDays(template.duration_days || 7);
                                                        setSpecialInstructions("Avoid sugar and fried snacks. Keep dinner before 8 PM.");
                                                        setAssignDialogOpen(true);
                                                    }}
                                                    disabled={assignDietMutation.isPending}
                                                    className="flex-1 py-3 px-4 bg-primary text-white rounded-md text-sm font-semibold flex items-center justify-center gap-2 shadow-sm hover:opacity-90 transition-all"
                                                >
                                                    {assignDietMutation.isPending ? "Assigning..." : "Assign"}
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
            <CustomDialog
                open={assignDialogOpen}
                onClose={() => setAssignDialogOpen(false)}
                title="Assign Diet Plan"
                description="Fill diet plan details before assigning."
                confirmText="Assign Diet Plan"
                loading={assignDietMutation.isPending}
                type="success"
                onConfirm={handleAssignTemplate}
            >
                <div className="space-y-4">
                    <div>
                        <label className="text-xs font-semibold text-[#4D4D4D]">
                            Start Date
                        </label>
                        <input
                            type="date"
                            value={startDate}
                            onChange={(e) => setStartDate(e.target.value)}
                            className="mt-1 w-full rounded-md border px-3 py-2 text-sm outline-none focus:border-primary"
                        />
                    </div>

                    <div>
                        <label className="text-xs font-semibold text-[#4D4D4D]">
                            Duration Days
                        </label>
                        <input
                            type="number"
                            value={durationDays}
                            onChange={(e) => setDurationDays(Number(e.target.value))}
                            className="mt-1 w-full rounded-md border px-3 py-2 text-sm outline-none focus:border-primary"
                        />
                    </div>

                    <div>
                        <label className="text-xs font-semibold text-[#4D4D4D]">
                            Special Instructions
                        </label>
                        <textarea
                            value={specialInstructions}
                            onChange={(e) => setSpecialInstructions(e.target.value)}
                            rows={4}
                            className="mt-1 w-full resize-none rounded-md border px-3 py-2 text-sm outline-none focus:border-primary"
                        />
                    </div>
                </div>
            </CustomDialog>
        </div>
    );
  };



// function mapApiMealToAssignedMeal(
//   meal: ApiDietTemplateMeal,
//   dayNumber: number,
// ): AssignedDietMeal {
//   return {
//     id: meal.id,
//     time: formatMealTime(meal.meal_time ?? meal.start_time),
//     type: formatMealType(meal.meal_type),
//     items: meal.meal_name,
//     calories: meal.calories ?? 0,
//     notes: `Day ${dayNumber}`,
//     status:
//       meal.status === "completed"
//         ? "Followed"
//         : meal.status === "missed"
//           ? "Missed"
//           : "Pending",
//     completedAt: meal.completed_at
//       ? new Date(meal.completed_at).toLocaleTimeString()
//       : undefined,
//   };
// }

// function mealCount(template: ApiDietTemplate): number {
//   return template.days.reduce((count, day) => count + day.meals.length, 0);
// }

// function totalCalories(template: ApiDietTemplate): number {
//   return template.days.reduce(
//     (total, day) =>
//       total +
//       day.meals.reduce((dayTotal, meal) => dayTotal + (meal.calories ?? 0), 0),
//     0,
//   );
// }

// function formatMealType(mealType?: string | null): string {
//   if (!mealType) {
//     return "Meal";
//   }

//   return mealType
//     .split("_")
//     .map((word) => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
//     .join(" ");
// }

// function formatMealTime(time?: string | null): string {
//   if (!time) {
//     return "-";
//   }

//   return time.slice(0, 5);
// }
        
