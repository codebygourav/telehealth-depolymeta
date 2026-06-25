import { useEffect, useMemo, useState } from "react";
import {

    Plus,
    Utensils,
    Eye,
    ArrowRight,
    TrendingUp,
    History,
    XCircle,
    ChevronDown,
    Info,
    ChevronLeft,
    ChevronRight,
    Link as LinkIcon,
    PlayCircle
} from 'lucide-react';
import { addDays, format, parseISO } from 'date-fns';

import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

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

    // console.log("patient id", patientId);

    const [isTemplateModalOpen, setIsTemplateModalOpen] = useState(false);
    const [previewTemplate, setPreviewTemplate] = useState<any | null>(null);
    const [openDay, setOpenDay] = useState<string | null>(null);
    const [openMealDay, setOpenMealDay] = useState<string | null>(null);
    const [selectedDayNumber, setSelectedDayNumber] = useState<number | null>(null);
    const [pageIndex, setPageIndex] = useState(0);
    const [mealTab, setMealTab] = useState<'all' | 'pending' | 'completed' | 'past'>('all');
    const [assignSuccessOpen, setAssignSuccessOpen] = useState(false);
    const [apiDialogType, setApiDialogType] = useState<"success" | "danger">("success");
    const [apiDialogTitle, setApiDialogTitle] = useState("");
    const [apiDialogMessage, setApiDialogMessage] = useState("");
    const [assignDialogOpen, setAssignDialogOpen] = useState(false);
    const [selectedTemplate, setSelectedTemplate] = useState<any | null>(null);

    const [startDate, setStartDate] = useState("2026-05-18");
    const [durationDays, setDurationDays] = useState(7);
    const [specialInstructions, setSpecialInstructions] = useState(
        "Avoid sugar and fried snacks. Keep dinner before 8 PM."
    );
    const [doctorRemark, setDoctorRemark] = useState("");

    const [detailsDialogOpen, setDetailsDialogOpen] = useState(false);
    const [activeMeal, setActiveMeal] = useState<any | null>(null);
    const [sidebarMediaIndex, setSidebarMediaIndex] = useState(0);

    const { data, isLoading, error } = useDietTemplates();
    const dietTemplates = data?.data || [];

    const {
        data: patientDietData,
        refetch: refetchPatientDietPlan,
    } = usePatientDietPlan(patientId);

    const dietPlanData = patientDietData?.data;
    const dietPlans = useMemo(() => {
        if (!dietPlanData) {
            return [];
        }

        if (Array.isArray(dietPlanData?.plans)) {
            return dietPlanData.plans;
        }

        if (dietPlanData?.id) {
            return [dietPlanData];
        }

        return [];
    }, [dietPlanData]);

    const planDays = useMemo(() => {
        if (!dietPlans.length) return [];

        const dateMap = new Map<string, any>();

        dietPlans.forEach((plan: any) => {
            const planName = plan?.template_name || 'Diet plan';
            (plan?.days || []).forEach((day: any) => {
                if (!day?.date) {
                    return;
                }

                if (!dateMap.has(day.date)) {
                    dateMap.set(day.date, {
                        id: day.date,
                        date: day.date,
                        week_day: day.week_day,
                        meals: [],
                    });
                }

                const entry = dateMap.get(day.date);
                const meals = Array.isArray(day.meals) ? day.meals : [];
                meals.forEach((meal: any) => {
                    entry.meals.push({
                        ...meal,
                        plan_name: planName,
                        plan_id: plan?.id,
                    });
                });
            });
        });

        return [...dateMap.values()]
            .sort((a: any, b: any) => String(a.date).localeCompare(String(b.date)))
            .map((day: any, index: number) => {
                const parsedDate = parseISO(day.date);
                const computedWeekDay = Number.isNaN(parsedDate.getTime())
                    ? day.week_day
                    : format(parsedDate, 'EEEE').toUpperCase();

                return {
                    ...day,
                    day_number: index + 1,
                    week_day: computedWeekDay,
                };
            });
    }, [dietPlans]);

    const daysPerPage = 7;
    const pageCount = Math.max(1, Math.ceil(planDays.length / daysPerPage));
    const visibleDays = planDays.slice(pageIndex * daysPerPage, pageIndex * daysPerPage + daysPerPage);
    useEffect(() => {
        if (!planDays.length) return;

        const today = format(new Date(), 'yyyy-MM-dd');
        const todayIndex = planDays.findIndex((day: any) => day.date === today);
        const initialIndex = todayIndex >= 0 ? todayIndex : 0;

        setPageIndex(Math.floor(initialIndex / daysPerPage));
        setSelectedDayNumber(planDays[initialIndex].day_number);
    }, [planDays]);

    const selectedDay = useMemo(
        () => planDays.find((day: any) => day.day_number === selectedDayNumber) || visibleDays[0] || planDays[0],
        [planDays, selectedDayNumber, visibleDays],
    );

    const selectedDayMeals = selectedDay?.meals || [];
    const normalizedDayMeals = useMemo(
        () => selectedDayMeals.map((meal: any) => ({
            id: meal.id,
            key: `${meal.occurrence_date || selectedDay?.date || 'day'}-${meal.id}`,
            date: selectedDay?.date,
            occurrenceDate: meal.occurrence_date || selectedDay?.date,
            time: meal.meal_time,
            meal_time: meal.meal_time,
            type: meal.meal_type,
            items: meal.meal_name,
            instructions: meal.instructions,
            mealImage: meal.meal_image || null,
            helpfulLinks: Array.isArray(meal.helpful_links) ? meal.helpful_links : [],
            planName: meal.plan_name || 'Diet plan',
            planId: meal.plan_id || null,
            patientNotes: meal.notes || meal.patient_notes,
            calories: meal.calories,
            status: meal.status || 'pending',
            completedAt: meal.completed_at,
            completedByRole: meal.completed_by_role,
            completedByName: meal.completed_by_name,
        })),
        [selectedDay?.date, selectedDayMeals],
    );

    const filteredDayMeals = useMemo(() => {
        const today = format(new Date(), 'yyyy-MM-dd');

        return normalizedDayMeals.filter((meal: any) => {
            if (mealTab === 'completed') return meal.status === 'completed';
            if (mealTab === 'pending') return meal.status === 'pending';
            if (mealTab === 'past') return Boolean(selectedDay?.date && selectedDay.date < today);
            return true;
        });
    }, [mealTab, normalizedDayMeals, selectedDay?.date]);

    const completedMeals = selectedDayMeals.filter((meal: any) => meal.status === 'completed').length;

    const youtubeLinks = (links: any[] = []) => links.filter((link) => String(link?.type || '').toLowerCase() === 'youtube');
    const otherLinks = (links: any[] = []) => links.filter((link) => String(link?.type || '').toLowerCase() !== 'youtube');
    const sidebarMediaItems = useMemo(() => {
        return normalizedDayMeals
            .map((meal: any) => {
            const links = Array.isArray(meal.helpfulLinks) ? meal.helpfulLinks : [];
            const validLinks = links
                .filter((link: any) => Boolean(link?.url))
                .map((link: any) => ({
                    url: link.url,
                    title: link.title || 'Open link',
                    type: String(link.type || 'link').toLowerCase(),
                }));

            return {
                mealId: meal.id,
                mealName: meal.items,
                mealType: meal.type,
                links: validLinks,
            };
        })
            .filter((entry: any) => entry.links.length > 0)
            .map((entry: any) => ({
                ...entry,
                summary: `${entry.links.length} link${entry.links.length > 1 ? 's' : ''}`,
            }));
    }, [normalizedDayMeals]);

    const assignedStartDateLabel = useMemo(() => {
        const rawDate = dietPlans
            .map((plan: any) => plan?.start_date)
            .filter(Boolean)
            .sort((a: string, b: string) => String(a).localeCompare(String(b)))[0]
            || planDays?.[0]?.date;

        if (!rawDate) {
            return null;
        }

        const parsed = parseISO(rawDate);
        if (Number.isNaN(parsed.getTime())) {
            return rawDate;
        }

        return `${format(parsed, 'dd MMM yyyy')} (${format(parsed, 'EEEE')})`;
    }, [dietPlans, planDays]);

    const dayTabLabel = (day: any): string => {
        if (day?.week_day) {
            const value = String(day.week_day).toLowerCase();
            return value.charAt(0).toUpperCase() + value.slice(1);
        }

        if (day?.date) {
            const parsed = parseISO(day.date);
            if (!Number.isNaN(parsed.getTime())) {
                return format(parsed, 'EEEE');
            }
        }

        return `Day ${day?.day_number}`;
    };

    const dayTabDate = (day: any): string => {
        if (!day?.date) {
            return `Day ${day?.day_number}`;
        }

        const parsed = parseISO(day.date);
        if (Number.isNaN(parsed.getTime())) {
            return `Day ${day?.day_number}`;
        }

        return format(parsed, 'dd MMM');
    };
    const visibleStart = visibleDays[0]?.date;
    const visibleEnd = visibleDays[visibleDays.length - 1]?.date;

    useEffect(() => {
        setSidebarMediaIndex(0);
    }, [selectedDay?.day_number]);

    const handlePrevPage = () => {
        const newIndex = Math.max(0, pageIndex - 1);
        setPageIndex(newIndex);
        setSelectedDayNumber(planDays[newIndex * daysPerPage]?.day_number || planDays[0]?.day_number || null);
    };

    const handleNextPage = () => {
        const newIndex = Math.min(pageCount - 1, pageIndex + 1);
        setPageIndex(newIndex);
        setSelectedDayNumber(planDays[newIndex * daysPerPage]?.day_number || planDays[0]?.day_number || null);
    };



    const assignDietMutation = useAssignDietTemplate();

    const isAlreadyAssigned = (templateId: string) => {
        return dietPlans.some((plan: any) => plan.template_id === templateId && plan.status === 'active');
    };

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
                doctor_remark: doctorRemark,
            });

            await refetchPatientDietPlan();

            setAssignDialogOpen(false);
            setIsTemplateModalOpen(false);
            setPreviewTemplate(null);
            setDoctorRemark("");

            setApiDialogType("success");
            setApiDialogTitle("Success");
            setApiDialogMessage(
                response?.message || "Diet plan assigned successfully."
            );

            setAssignSuccessOpen(true);

        } catch (error: any) {
            console.log("Assign diet failed:", error);

            const errorMessage =
                error?.response?.data?.message ||
                error?.response?.data?.errors?.message ||
                "Something went wrong.";

            setApiDialogType("danger");
            setApiDialogTitle("Error");
            setApiDialogMessage(errorMessage);

            setAssignSuccessOpen(true);
        }
    };
    const apiAssignedMeals =
        planDays?.flatMap((day: any) =>
            day.meals.map((meal: any) => ({
                id: meal.id,
                key: `${meal.occurrence_date || day.date}-${meal.id}`,
                weekDay: day.week_day,
                dayNumber: day.day_number,
                date: day.date,
                time: meal.meal_time,
                type: meal.meal_type,
                items: meal.meal_name,
                instructions: meal.instructions,
                planName: meal.plan_name || 'Diet plan',
                patientNotes: meal.notes || meal.patient_notes,
                calories: meal.calories,
                status: meal.status || "pending",
                completedAt: meal.completed_at,
                completedByRole: meal.completed_by_role,
                completedByName: meal.completed_by_name,
                occurrenceDate: meal.occurrence_date || day.date,
            }))
        ) || [];

    const mealTabs = [
        { key: 'all', label: 'All' },
        { key: 'pending', label: 'Pending' },
        { key: 'completed', label: 'Completed' },
        { key: 'past', label: 'Past' },
    ] as const;

    const assignedMeals = apiAssignedMeals;

    const completionPercentage =
        assignedMeals.length > 0
            ? Math.round(
                (assignedMeals.filter((m: any) => m.status === "completed").length /
                    assignedMeals.length) *
                100
            )
            : 0;
    const currentDayMeals = selectedDayMeals;

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
                                        .filter((m: any) => m.status === "completed")
                                        .reduce(
                                            (acc: number, curr: any) => acc + curr.calories,
                                            0
                                        )} kcal
                                </p>
                            </div>
                            <div className="px-4 py-3 rounded-lg border bg-white shadow-sm">
                                <p className="text-[10px] font-black uppercase tracking-widest mb-1">Meals Followed</p>
                                <p className="text-lg font-bold text-on-surface">
                                    {assignedMeals.filter((m: any) => m.status === "completed").length} / {assignedMeals.length}
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

            {/* Assigned Diet Timeline */}
            <section className="rounded-lg border bg-white p-4 sm:p-5 md:p-6 shadow-sm space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-[#BFD4F5] pb-4">
                    <h4 className="text-base sm:text-lg font-semibold text-[#1F1E1E] flex items-center gap-2 sm:gap-3">
                        <Utensils className="w-5 h-5 sm:w-6 sm:h-6 text-primary shrink-0" />
                        <span>Assigned Diet Timeline</span>
                    </h4>

                    <div className="flex items-center gap-2 w-fit rounded-md bg-green-100 px-3 py-1.5">
                        <TrendingUp className="w-4 h-4 sm:w-5 sm:h-5 text-green-600 shrink-0" />

                        <span className="text-[10px] sm:text-xs font-semibold text-green-600 uppercase tracking-wide whitespace-nowrap">
                            Growth Phase Active
                        </span>
                    </div>
                </div>

                {selectedDay ? (
                    <div className="space-y-4">
                        <div className="flex items-center justify-between gap-3">
                            <div>
                                <h5 className="text-sm font-bold text-[#1F1E1E]">Meal Calendar</h5>
                                <p className="text-xs text-[#4D4D4D]">Select a day to review current or past meal completion status.</p>
                                {assignedStartDateLabel && (
                                    <p className="mt-1 text-[11px] font-semibold text-primary">
                                        Diet assigned from: {assignedStartDateLabel}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="flex flex-col gap-2 rounded-md border border-[#BFD4F5] bg-[#F7FAFF] px-3 py-2 sm:flex-row sm:items-center sm:justify-between">
                            <div className="flex flex-wrap gap-2">
                                {mealTabs.map((tab) => (
                                    <button
                                        key={tab.key}
                                        type="button"
                                        onClick={() => setMealTab(tab.key)}
                                        className={`rounded-full border px-3 py-1.5 text-xs font-semibold transition-all ${
                                            mealTab === tab.key
                                                ? 'border-primary bg-primary/10 text-primary'
                                                : 'border-[#BFD4F5] bg-white text-[#4D4D4D] hover:border-primary/35 hover:text-primary'
                                        }`}
                                    >
                                        {tab.label}
                                    </button>
                                ))}
                            </div>

                            <div className="flex h-8 items-center rounded-md border border-[#BFD4F5] bg-white overflow-hidden">
                                <button
                                    type="button"
                                    aria-label="Previous seven days"
                                    disabled={pageIndex === 0}
                                    onClick={handlePrevPage}
                                    className="flex h-full w-8 items-center justify-center text-primary hover:bg-primary/5 disabled:cursor-not-allowed disabled:opacity-30 border-r border-[#BFD4F5]"
                                >
                                    <ChevronDown className="h-3.5 w-3.5 rotate-90" />
                                </button>
                                <span className="px-3 text-center text-xs font-bold text-[#4D4D4D] min-w-28">
                                    {visibleStart && visibleEnd
                                        ? `${format(parseISO(visibleStart), 'dd MMM')} - ${format(parseISO(visibleEnd), 'dd MMM')}`
                                        : `Days ${pageIndex * daysPerPage + 1}-${Math.min((pageIndex + 1) * daysPerPage, planDays.length)}`}
                                </span>
                                <button
                                    type="button"
                                    aria-label="Next seven days"
                                    disabled={pageIndex >= pageCount - 1}
                                    onClick={handleNextPage}
                                    className="flex h-full w-8 items-center justify-center text-primary hover:bg-primary/5 disabled:cursor-not-allowed disabled:opacity-30 border-l border-[#BFD4F5]"
                                >
                                    <ChevronDown className="h-3.5 w-3.5 -rotate-90" />
                                </button>
                            </div>
                        </div>

                        <div className="flex flex-wrap items-center justify-center gap-2">
                            {visibleDays.map((day: any) => {
                                const isSelected = day.day_number === selectedDay?.day_number;

                                return (
                                    <button
                                        key={`${day.date || day.day_number}-${day.id}`}
                                        type="button"
                                        onClick={() => setSelectedDayNumber(day.day_number)}
                                        className={`relative min-w-24 rounded-md border px-3 py-2.5 text-center transition-all duration-200 ${
                                            isSelected
                                                ? 'border-primary bg-primary text-white shadow-sm shadow-primary/20'
                                                : 'border-[#BFD4F5] bg-[#F7FAFF] text-[#1F1E1E] hover:border-primary/35 hover:bg-primary/5'
                                        }`}
                                    >
                                        <span className={`block text-[11px] font-bold ${isSelected ? 'text-white' : 'text-[#1F1E1E]'}`}>
                                            {dayTabLabel(day)}
                                        </span>
                                        <span className={`mt-0.5 block text-[10px] font-semibold ${isSelected ? 'text-white/80' : 'text-[#6B7280]'}`}>
                                            {dayTabDate(day)}
                                        </span>
                                        {day.meals?.some((meal: any) => meal.status !== 'completed') && (
                                            <span className="absolute right-1.5 top-1.5 h-1.5 w-1.5 rounded-full bg-amber-500" title="Some meals still pending" />
                                        )}
                                    </button>
                                );
                            })}
                        </div>

                        <div className="grid grid-cols-1 gap-4 lg:grid-cols-[360px_minmax(0,1fr)]">
                            <aside className="space-y-3">
                                <div className="border border-[#E7E8EB] bg-white p-4 global-radius shadow-sm">
                                    <p className="text-xs font-bold text-[#1F1E1E]">Day {selectedDay?.day_number} Progress</p>
                                    <div className="mt-2.5 flex items-center justify-between">
                                        <span className="text-sm font-semibold text-[#4D4D4D]">
                                            {completedMeals} / {selectedDayMeals.length} Completed
                                        </span>
                                        <span className="inline-flex items-center gap-1 rounded bg-primary/10 px-2 py-0.5 text-xs font-bold text-primary">
                                            🔥 {selectedDayMeals.reduce((total: number, meal: any) => total + Number(meal.calories || 0), 0)} kcal
                                        </span>
                                    </div>
                                    {selectedDayMeals.length > 0 && (
                                        <div className="mt-3 h-1.5 w-full rounded-full bg-slate-100 overflow-hidden">
                                            <div
                                                className="h-full bg-primary transition-all duration-300"
                                                style={{ width: `${(completedMeals / selectedDayMeals.length) * 100}%` }}
                                            />
                                        </div>
                                    )}
                                </div>

                                <div className="border border-[#E7E8EB] bg-white p-4 global-radius shadow-sm">
                                    <div className="flex items-center justify-between gap-2">
                                        <p className="text-xs font-bold text-[#1F1E1E]">Recipe & Video Links</p>
                                        {sidebarMediaItems.length > 1 && (
                                            <div className="flex items-center gap-1">
                                                <button
                                                    type="button"
                                                    aria-label="Previous media"
                                                    onClick={() => setSidebarMediaIndex((prev) => (prev - 1 + sidebarMediaItems.length) % sidebarMediaItems.length)}
                                                    className="h-7 w-7 rounded-full border border-[#BFD4F5] bg-white text-primary hover:bg-primary/5"
                                                >
                                                    <ChevronLeft className="mx-auto h-4 w-4" />
                                                </button>
                                                <button
                                                    type="button"
                                                    aria-label="Next media"
                                                    onClick={() => setSidebarMediaIndex((prev) => (prev + 1) % sidebarMediaItems.length)}
                                                    className="h-7 w-7 rounded-full border border-[#BFD4F5] bg-white text-primary hover:bg-primary/5"
                                                >
                                                    <ChevronRight className="mx-auto h-4 w-4" />
                                                </button>
                                            </div>
                                        )}
                                    </div>

                                    {sidebarMediaItems.length ? (
                                        <div className="mt-3 rounded-lg border border-[#BFD4F5] bg-[#F7FAFF] p-3">
                                            <p className="text-[10px] font-bold uppercase tracking-wide text-primary">
                                                {(sidebarMediaItems[sidebarMediaIndex]?.mealType || 'Meal').toString().replaceAll('_', ' ')}
                                            </p>
                                            <p className="mt-1 text-sm font-semibold text-[#1F1E1E] line-clamp-2">
                                                {sidebarMediaItems[sidebarMediaIndex]?.mealName}
                                            </p>
                                            <p className="mt-1 text-[11px] font-semibold text-[#4D4D4D]">
                                                {sidebarMediaItems[sidebarMediaIndex]?.summary}
                                            </p>
                                            <div className="mt-3 space-y-2">
                                                {(sidebarMediaItems[sidebarMediaIndex]?.links || []).map((link: any, index: number) => (
                                                    <a
                                                        key={`sidebar-link-${sidebarMediaIndex}-${index}`}
                                                        href={link.url}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        className="inline-flex w-full items-center justify-between gap-2 rounded-lg border border-[#BFD4F5] bg-white px-2.5 py-1.5 text-xs font-semibold text-primary"
                                                    >
                                                        <span className="truncate">
                                                            {link.type === 'youtube' ? 'YouTube' : link.type}: {link.title}
                                                        </span>
                                                        {link.type === 'youtube' ? <PlayCircle className="h-3.5 w-3.5 shrink-0" /> : <LinkIcon className="h-3.5 w-3.5 shrink-0" />}
                                                    </a>
                                                ))}
                                            </div>
                                            {sidebarMediaItems.length > 1 && (
                                                <p className="mt-2 text-[10px] font-semibold uppercase tracking-wide text-[#6B6B6B]">
                                                    {sidebarMediaIndex + 1} / {sidebarMediaItems.length}
                                                </p>
                                            )}
                                        </div>
                                    ) : (
                                        <div className="mt-3 rounded-lg border border-dashed border-[#BFD4F5] bg-[#FAFCFF] p-4 text-center text-xs font-semibold text-[#6B6B6B]">
                                            No media links available for this day.
                                        </div>
                                    )}
                                </div>
                            </aside>

                            <div className="space-y-3">
                                <div className="flex items-center justify-between rounded-md border border-[#BFD4F5] bg-[#F7FAFF] px-3 py-2">
                                    <div className="text-xs font-bold text-[#1F1E1E]">
                                        {selectedDay?.week_day
                                            ? `${selectedDay.week_day.charAt(0)}${selectedDay.week_day.slice(1).toLowerCase()} Meals`
                                            : `Day ${selectedDay?.day_number} Meals`}
                                    </div>
                                    <div className="text-[11px] font-semibold text-[#4D4D4D]">
                                        {filteredDayMeals.length} shown
                                    </div>
                                </div>

                                {filteredDayMeals.length ? (
                                    filteredDayMeals.map((meal: any) => {
                                        const isCompleted = meal.status === 'completed';

                                        return (
                                            <article
                                                key={meal.key}
                                                className={`border bg-white p-3.5 global-radius shadow-sm transition-all duration-200 ${
                                                    isCompleted
                                                        ? 'border-green-150 bg-green-50/10'
                                                        : 'border-[#E7E8EB] hover:border-primary/25 hover:shadow-md'
                                                }`}
                                            >
                                                <div className="flex flex-col sm:flex-row sm:items-center gap-3 justify-between">
                                                    <button
                                                        type="button"
                                                        onClick={() => {
                                                            setActiveMeal(meal);
                                                            setDetailsDialogOpen(true);
                                                        }}
                                                        className="flex items-start gap-3 flex-1 min-w-0 text-left"
                                                    >
                                                        <div className={`flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-lg border ${
                                                            isCompleted
                                                                ? 'border-green-200 bg-green-50 text-green-600'
                                                                : 'border-primary/10 bg-primary/5 text-primary'
                                                        }`}>
                                                            {meal.mealImage ? (
                                                                <img src={meal.mealImage} alt={meal.items} className="h-full w-full object-cover" />
                                                            ) : (
                                                                <Utensils className="h-4.5 w-4.5" />
                                                            )}
                                                        </div>
                                                        <div className="min-w-0 flex-1">
                                                            <div className="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                                                                <span className="inline-flex items-center rounded-full bg-[#EEF4FF] px-2 py-0.5 text-[10px] font-bold text-[#2D466B]">
                                                                    {meal.planName}
                                                                </span>
                                                                <span className="text-[10px] font-bold uppercase tracking-wider text-primary">
                                                                    {meal.type}
                                                                </span>
                                                                <span className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold ${
                                                                    isCompleted ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700'
                                                                }`}>
                                                                    {isCompleted ? 'Completed' : 'Not completed yet'}
                                                                </span>
                                                                {meal.time && (
                                                                    <span className="inline-flex items-center gap-1 text-[10px] font-semibold text-[#6B6B6B]">
                                                                        <Info className="h-3 w-3" />
                                                                        {meal.time}
                                                                    </span>
                                                                )}
                                                            </div>
                                                            <h3 className="text-sm font-bold text-[#1F1E1E] mt-0.5 hover:text-primary transition-colors">
                                                                {meal.items}
                                                            </h3>
                                                            {meal.instructions && (
                                                                <p className="mt-1 text-xs text-[#2D466B] leading-relaxed bg-[#F1F6FF] border-l-2 border-[#BFD4F5] px-2 py-1 rounded-r-sm">
                                                                    {meal.instructions}
                                                                </p>
                                                            )}

                                                            {(meal.mealImage || (meal.helpfulLinks || []).length > 0) && (
                                                                <div className="mt-2 overflow-x-auto pb-1">
                                                                    <div className="flex items-center gap-2 min-w-max">
                                                                        {meal.mealImage && (
                                                                            <a href={meal.mealImage} target="_blank" rel="noreferrer" className="inline-flex items-center gap-1 rounded-full border border-[#BFD4F5] bg-[#F4F8FF] px-2.5 py-1 text-[10px] font-semibold text-primary">
                                                                                <Eye className="h-3 w-3" /> Meal image
                                                                            </a>
                                                                        )}
                                                                        {youtubeLinks(meal.helpfulLinks).map((link: any, idx: number) => (
                                                                            <a
                                                                                key={`yt-${meal.id}-${idx}`}
                                                                                href={link.url}
                                                                                target="_blank"
                                                                                rel="noreferrer"
                                                                                className="inline-flex items-center gap-1 rounded-full border border-[#F5D38A] bg-[#FFF9EC] px-2.5 py-1 text-[10px] font-semibold text-[#A86600]"
                                                                            >
                                                                                YouTube: {link.title || 'Watch recipe'}
                                                                            </a>
                                                                        ))}
                                                                        {otherLinks(meal.helpfulLinks).map((link: any, idx: number) => (
                                                                            <a
                                                                                key={`lnk-${meal.id}-${idx}`}
                                                                                href={link.url}
                                                                                target="_blank"
                                                                                rel="noreferrer"
                                                                                className="inline-flex items-center gap-1 rounded-full border border-[#CFE8D7] bg-[#F2FBF5] px-2.5 py-1 text-[10px] font-semibold text-green-700"
                                                                            >
                                                                                {(link.type || 'Link')}: {link.title || 'Open'}
                                                                            </a>
                                                                        ))}
                                                                    </div>
                                                                </div>
                                                            )}
                                                        </div>
                                                    </button>

                                                    <div className="flex sm:flex-col items-center sm:items-end justify-between gap-2 border-t border-[#BFD4F5] pt-2 sm:border-0 sm:pt-0 shrink-0">
                                                        {isCompleted ? (
                                                            <div className="flex flex-col items-end gap-1 text-right">
                                                                <span className="inline-flex items-center gap-1 rounded-full bg-green-50 px-2.5 py-1 text-xs font-bold text-green-700">
                                                                    <ChevronDown className="h-3.5 w-3.5 rotate-180" />
                                                                    Done
                                                                </span>
                                                                {(meal.completedByName || meal.completedByRole) && (
                                                                    <span className="text-[10px] font-semibold text-[#4D4D4D]">
                                                                        Marked by {meal.completedByName || meal.completedByRole}
                                                                    </span>
                                                                )}
                                                            </div>
                                                        ) : (
                                                            <span className="rounded-full border border-[#E7E8EB] bg-[#FAFAFA] px-2.5 py-1 text-xs font-bold text-[#6B6B6B]">
                                                                Pending
                                                            </span>
                                                        )}
                                                    </div>
                                                </div>
                                            </article>
                                        );
                                    })
                                ) : (
                                    <div className="border border-dashed border-[#E7E8EB] bg-[#FAFAFA] p-8 text-center global-radius">
                                        <Utensils className="mx-auto h-7 w-7 text-[#B0B0B0]" />
                                        <p className="mt-2 text-xs font-semibold text-[#6D6D6D]">
                                            No meals scheduled for Day {selectedDay?.day_number}.
                                        </p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
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
            </section>

            <Dialog open={detailsDialogOpen} onOpenChange={setDetailsDialogOpen}>
                <DialogContent className="max-w-3xl border border-[#BFD4F5] bg-white global-radius">
                    {activeMeal && (
                        <div className="space-y-5">
                            <DialogHeader className="space-y-2 text-left">
                                <div className="inline-flex w-fit items-center gap-2 rounded-full border border-[#BFD4F5] bg-primary/5 px-3 py-1 text-xs font-semibold text-primary">
                                    <Utensils className="h-3.5 w-3.5" />
                                    Meal details
                                </div>
                                <DialogTitle className="text-2xl font-bold text-[#1F1E1E]">
                                    {activeMeal.items}
                                </DialogTitle>
                                <DialogDescription className="text-sm text-[#4D4D4D]">
                                    {activeMeal.type}
                                    {activeMeal.time ? ` • ${activeMeal.time}` : ''}
                                    {activeMeal.occurrenceDate ? ` • ${activeMeal.occurrenceDate}` : ''}
                                </DialogDescription>
                            </DialogHeader>

                            <div className="grid grid-cols-2 gap-3">
                                <div className="rounded-xl border border-[#BFD4F5] bg-[#FAFAFA] p-4">
                                    <p className="text-[10px] font-bold uppercase tracking-[0.18em] text-[#7A7A7A]">Status</p>
                                    <p className="mt-1 text-sm font-bold capitalize text-[#1F1E1E]">
                                        {activeMeal.status || 'pending'}
                                    </p>
                                </div>
                                <div className="rounded-xl border border-[#BFD4F5] bg-[#FAFAFA] p-4">
                                    <p className="text-[10px] font-bold uppercase tracking-[0.18em] text-[#7A7A7A]">Calories</p>
                                    <p className="mt-1 text-sm font-bold text-[#1F1E1E]">
                                        {activeMeal.calories || 0} kcal
                                    </p>
                                </div>
                            </div>

                            <div className="rounded-xl border border-[#BFD4F5] bg-white p-4 shadow-sm">
                                <p className="text-[10px] font-bold uppercase tracking-[0.18em] text-[#7A7A7A]">Instructions</p>
                                <p className="mt-1 text-sm leading-relaxed text-[#1F1E1E]">
                                    {activeMeal.instructions || 'No instructions provided.'}
                                </p>
                            </div>

                            {(activeMeal.mealImage || (activeMeal.helpfulLinks || []).length > 0) && (
                                <div className="rounded-xl border border-[#BFD4F5] bg-[#F4F8FF] p-4 space-y-3">
                                    <p className="text-[10px] font-bold uppercase tracking-[0.18em] text-primary">Recipe Media</p>
                                    {activeMeal.mealImage && (
                                        <img src={activeMeal.mealImage} alt="Meal" className="max-h-56 w-full rounded-lg object-cover border border-[#BFD4F5]" />
                                    )}
                                    {(activeMeal.helpfulLinks || []).length > 0 && (
                                        <div className="overflow-x-auto pb-1">
                                            <div className="flex min-w-max gap-2">
                                                {(activeMeal.helpfulLinks || []).map((link: any, idx: number) => (
                                                    <a
                                                        key={`dlg-link-${idx}`}
                                                        href={link.url}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        className="rounded-full border border-[#BFD4F5] bg-white px-3 py-1 text-xs font-semibold text-primary"
                                                    >
                                                        {link.type || 'Link'}: {link.title || 'Open'}
                                                    </a>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}

                            {activeMeal.completedAt && (
                                <div className="rounded-xl border border-[#CFE8D7] bg-[#F2FBF5] p-4 text-sm text-green-800">
                                    <p className="font-semibold">Completed at {activeMeal.completedAt}</p>
                                    {activeMeal.completedByName && (
                                        <p className="mt-1 text-xs text-[#4D4D4D]">
                                            Marked by {activeMeal.completedByName}
                                        </p>
                                    )}
                                </div>
                            )}

                            {activeMeal.patientNotes && (
                                <div className="rounded-xl border border-[#BFD4F5] bg-[#F4F8FF] p-4 text-sm text-[#1F1E1E]">
                                    <p className="text-[10px] font-bold uppercase tracking-[0.18em] text-primary">Patient notes</p>
                                    <p className="mt-1 leading-relaxed">{activeMeal.patientNotes}</p>
                                </div>
                            )}

                            <DialogFooter className="flex flex-wrap justify-end gap-2 sm:justify-end">
                                <button type="button" onClick={() => setDetailsDialogOpen(false)} className="btn-primary-cta-outline h-10 px-4 py-2 text-xs">
                                    Close
                                </button>
                            </DialogFooter>
                        </div>
                    )}
                </DialogContent>
            </Dialog>

            {/* Template Selection Modal */}
            <section>
                {isTemplateModalOpen && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4">
                        <div
                            onClick={() => setIsTemplateModalOpen(false)}
                            className="absolute inset-0 bg-black/20 backdrop-blur-sm"
                        />

                        <div className="relative w-full max-w-4xl bg-white rounded-lg border shadow-sm overflow-hidden flex flex-col max-h-[92vh]">
                            <div className="px-4 sm:px-5 md:px-6 py-4 border-b border-[#BFD4F5] flex items-center justify-between gap-3">
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
                                        type="button"
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
                                                    setStartDate(new Date().toISOString().split("T")[0]);
                                                    setDurationDays(previewTemplate?.duration_days || 7);
                                                    setSpecialInstructions(
                                                        "Avoid sugar and fried snacks. Keep dinner before 8 PM."
                                                    );
                                                    setDoctorRemark(previewTemplate?.doctor_remark || "");

                                                    setAssignDialogOpen(true);
                                                }}
                                                disabled={isAlreadyAssigned(previewTemplate.id)}
                                                className={cn(
                                                    "w-full py-3 rounded-md text-sm font-semibold shadow-sm transition-all",
                                                    isAlreadyAssigned(previewTemplate.id)
                                                        ? "bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed"
                                                        : "bg-primary text-white hover:opacity-90"
                                                )}
                                            >
                                                {isAlreadyAssigned(previewTemplate.id) ? "Already Assigned" : "Assign This Diet Plan"}
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
                                                        setStartDate(new Date().toISOString().split("T")[0]);
                                                        setDurationDays(template.duration_days || 7);
                                                        setSpecialInstructions("Avoid sugar and fried snacks. Keep dinner before 8 PM.");
                                                        setDoctorRemark(template.doctor_remark || "");
                                                        setAssignDialogOpen(true);
                                                    }}
                                                    disabled={assignDietMutation.isPending || isAlreadyAssigned(template.id)}
                                                    className={cn(
                                                        "flex-1 py-3 px-4 rounded-md text-sm font-semibold flex items-center justify-center gap-2 shadow-sm transition-all",
                                                        isAlreadyAssigned(template.id)
                                                            ? "bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed"
                                                            : "bg-primary text-white hover:opacity-90"
                                                    )}
                                                >
                                                    {isAlreadyAssigned(template.id) ? "Already Assigned" : assignDietMutation.isPending ? "Assigning..." : "Assign"}
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
                onClose={() => {
                    setAssignDialogOpen(false);
                    setSelectedTemplate(null);
                }}
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
                            Doctor Remark
                        </label>
                        <textarea
                            value={doctorRemark}
                            onChange={(e) => setDoctorRemark(e.target.value)}
                            rows={3}
                            placeholder="Example: Patient should follow this diet with regular sugar monitoring..."
                            className="mt-1 w-full resize-none rounded-md border px-3 py-2 text-sm outline-none focus:border-primary"
                        />
                    </div>

                    <div>
                        <label className="text-xs font-semibold text-[#4D4D4D]">
                            Special Instructions
                        </label>
                        <textarea
                            value={specialInstructions}
                            onChange={(e) => setSpecialInstructions(e.target.value)}
                            rows={3}
                            className="mt-1 w-full resize-none rounded-md border px-3 py-2 text-sm outline-none focus:border-primary"
                        />
                    </div>
                </div>
            </CustomDialog>

            <CustomDialog
                open={assignSuccessOpen}
                onClose={() => setAssignSuccessOpen(false)}
                title={apiDialogTitle}
                description={apiDialogMessage}
                confirmText="OK"
                type={apiDialogType}
                onConfirm={() => setAssignSuccessOpen(false)}
            />
        </div>
    );
};
