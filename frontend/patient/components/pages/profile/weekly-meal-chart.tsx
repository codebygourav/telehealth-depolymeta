'use client';

import { useCompleteDietMeal, useDietPlan } from '@/queries/useDietPlan';
import { DietPlanDay, Meal } from '@/types/meal-chart';
import { useQueryClient } from '@tanstack/react-query';
import { addDays, format, parseISO } from 'date-fns';
import {
    AlertTriangle,
    Apple,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    Clock3,
    Droplet,
    Dumbbell,
    Eye,
    Info,
    Leaf,
    Link as LinkIcon,
    Moon,
    PlayCircle,
    Sun,
    Utensils,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

const DAYS_PER_PAGE = 7;

const getMealTypeLabel = (mealType: string) => {
    const labels: Record<string, string> = {
        MORNING: 'Morning',
        BREAKFAST: 'Breakfast',
        MID_MEAL: 'Mid Meal',
        LUNCH: 'Lunch',
        EVENING_SNACK: 'Evening Snack',
        EVENING: 'Evening',
        DINNER: 'Dinner',
        NIGHT: 'Night',
    };

    return labels[mealType?.toUpperCase()] || mealType || 'Meal';
};

const getMealIcon = (mealType: string) => {
    switch (mealType?.toUpperCase()) {
        case 'MORNING':
        case 'BREAKFAST':
            return Sun;
        case 'MID_MEAL':
            return Apple;
        case 'EVENING':
        case 'EVENING_SNACK':
        case 'NIGHT':
            return Moon;
        default:
            return Utensils;
    }
};

const formatMealTime = (time?: string | null) => {
    if (!time) return '';

    const [hours, minutes] = time.split(':');
    const hour = Number(hours);

    if (Number.isNaN(hour) || !minutes) return time;

    return `${String(hour % 12 || 12).padStart(2, '0')}:${minutes} ${hour >= 12 ? 'PM' : 'AM'}`;
};

const getMealItems = (mealName: string) =>
    mealName
        .split(/,| with | and /i)
        .map((item) => item.trim())
        .filter(Boolean);

const normalizePlanDays = (
    days: DietPlanDay[],
    startDate: string,
    durationDays: number,
): DietPlanDay[] => {
    const byDayNumber = new Map(days.map((day) => [Number(day.day_number), day]));
    const byDate = new Map(days.filter((day) => day.date).map((day) => [day.date, day]));
    const parsedStartDate = startDate ? parseISO(startDate) : null;
    const totalDays = Math.max(durationDays || 0, days.length);

    return Array.from({ length: totalDays }, (_, index) => {
        const dayNumber = index + 1;
        const date = parsedStartDate ? format(addDays(parsedStartDate, index), 'yyyy-MM-dd') : '';
        const sourceDay = byDayNumber.get(dayNumber) || (date ? byDate.get(date) : undefined);

        return {
            id: sourceDay?.id || `plan-day-${dayNumber}`,
            day_number: dayNumber,
            week_day: sourceDay?.week_day || (date ? format(parseISO(date), 'EEEE').toUpperCase() : ''),
            date: sourceDay?.date || date,
            meals: sourceDay?.meals || [],
        };
    });
};

export default function WeeklyMealChart() {
    const queryClient = useQueryClient();
    const { data, isLoading } = useDietPlan();
    const completeMealMutation = useCompleteDietMeal();
    const planPayload = data?.data;

    const [pageIndex, setPageIndex] = useState(0);
    const [selectedDayNumber, setSelectedDayNumber] = useState<number | null>(null);
    const [activeMeal, setActiveMeal] = useState<Meal | null>(null);
    const [isDetailsOpen, setIsDetailsOpen] = useState(false);
    const [isLogOpen, setIsLogOpen] = useState(false);
    const [mealTab, setMealTab] = useState<'all' | 'pending' | 'completed' | 'past'>('all');
    const [logNotes, setLogNotes] = useState('');
    const [loadedPlanId, setLoadedPlanId] = useState<string | null>(null);
    const [selectedPlanId, setSelectedPlanId] = useState<string | null>(null);
    const [sidebarMediaIndex, setSidebarMediaIndex] = useState(0);

    const allPlans = useMemo(() => {
        if (!planPayload) {
            return [];
        }

        const plans = Array.isArray((planPayload as any)?.plans)
            ? (planPayload as any).plans
            : ((planPayload as any)?.id ? [planPayload as any] : []);

        return [...plans].sort((a: any, b: any) => String(a.start_date || '').localeCompare(String(b.start_date || '')));
    }, [planPayload]);

    const activePlan = useMemo(() => {
        if (!allPlans.length) {
            return null;
        }

        if (selectedPlanId) {
            const selected = allPlans.find((plan: any) => plan.id === selectedPlanId);
            if (selected) {
                return selected;
            }
        }

        return allPlans[allPlans.length - 1];
    }, [allPlans, selectedPlanId]);

    useEffect(() => {
        if (!allPlans.length) {
            setSelectedPlanId(null);
            return;
        }

        setSelectedPlanId((current) => {
            if (current && allPlans.some((plan: any) => plan.id === current)) {
                return current;
            }

            return allPlans[allPlans.length - 1]?.id || null;
        });
    }, [allPlans]);

    const planDays = useMemo(() => {
        if (!activePlan?.days?.length) return [];

        return normalizePlanDays(
            [...activePlan.days].sort((a, b) => Number(a.day_number) - Number(b.day_number)),
            activePlan.start_date,
            activePlan.duration_days,
        );
    }, [activePlan]);

    const pageCount = Math.max(1, Math.ceil(planDays.length / DAYS_PER_PAGE));
    const visibleDays = planDays.slice(
        pageIndex * DAYS_PER_PAGE,
        pageIndex * DAYS_PER_PAGE + DAYS_PER_PAGE,
    );
    const todayDate = format(new Date(), 'yyyy-MM-dd');
    const isToday = (date?: string | null) => date === todayDate;

    // Only set initial selected day and page index on initial mount or when plan ID changes
    useEffect(() => {
        if (!activePlan?.id || !planDays.length) return;

        if (loadedPlanId !== activePlan.id) {
            setLoadedPlanId(activePlan.id);
            const today = format(new Date(), 'yyyy-MM-dd');
            const todayIndex = planDays.findIndex((day) => day.date === today);
            const initialIndex = todayIndex >= 0 ? todayIndex : 0;

            setPageIndex(Math.floor(initialIndex / DAYS_PER_PAGE));
            setSelectedDayNumber(planDays[initialIndex].day_number);
        }
    }, [activePlan?.id, planDays, loadedPlanId]);

    const handlePrevPage = () => {
        const newIndex = Math.max(0, pageIndex - 1);
        setPageIndex(newIndex);
        setSelectedDayNumber(planDays[newIndex * DAYS_PER_PAGE].day_number);
    };

    const handleNextPage = () => {
        const newIndex = Math.min(pageCount - 1, pageIndex + 1);
        setPageIndex(newIndex);
        setSelectedDayNumber(planDays[newIndex * DAYS_PER_PAGE].day_number);
    };

    const selectedDay = useMemo(
        () => planDays.find((day) => day.day_number === selectedDayNumber) || visibleDays[0] || planDays[0],
        [planDays, selectedDayNumber, visibleDays],
    );

    const selectedDayMeals = selectedDay?.meals || [];
    const filteredDayMeals = useMemo(() => {
        const today = format(new Date(), 'yyyy-MM-dd');

        return selectedDayMeals.filter((meal) => {
            if (mealTab === 'completed') return meal.status === 'completed';
            if (mealTab === 'pending') return meal.status !== 'completed';
            if (mealTab === 'past') return Boolean(selectedDay?.date && selectedDay.date < today);
            return true;
        });
    }, [mealTab, selectedDay?.date, selectedDayMeals]);

    const completedMeals = selectedDayMeals.filter((meal) => meal.status === 'completed').length;
    const totalCalories = selectedDayMeals.reduce(
        (total, meal) => total + Number(meal.calories || 0),
        0,
    );
    const sidebarMediaItems = useMemo(() => {
        return selectedDayMeals
            .map((meal) => {
                const links = Array.isArray(meal.helpful_links) ? meal.helpful_links : [];
                const validLinks = links
                    .filter((link) => Boolean(link?.url))
                    .map((link) => ({
                        url: link.url,
                        title: link.title || 'Open link',
                        type: String(link.type || 'link').toLowerCase(),
                    }));

                return {
                    mealId: meal.id,
                    mealName: meal.meal_name,
                    mealType: meal.meal_type,
                    links: validLinks,
                };
            })
            .filter((entry) => entry.links.length > 0)
            .map((entry) => ({
                ...entry,
                summary: `${entry.links.length} link${entry.links.length > 1 ? 's' : ''}`,
            }));
    }, [selectedDayMeals]);

    const visibleStart = visibleDays[0]?.date;
    const visibleEnd = visibleDays[visibleDays.length - 1]?.date;

    useEffect(() => {
        setSidebarMediaIndex(0);
    }, [selectedDay?.day_number]);

    const handleLogMeal = async () => {
        if (!activeMeal) return;
        const occurrenceDate = activeMeal.occurrence_date || selectedDay?.date || '';
        if (!isToday(occurrenceDate)) return;

        await completeMealMutation.mutateAsync({
            mealId: activeMeal.id,
            occurrenceDate,
            notes: logNotes,
        });

        setIsLogOpen(false);
        setIsDetailsOpen(false);
        setLogNotes('');
        await queryClient.invalidateQueries({ queryKey: ['diet-plan'] });
    };

    if (isLoading) {
        return (
            <div className="flex min-h-80 flex-col items-center justify-center gap-3">
                <div className="h-10 w-10 animate-spin rounded-full border-4 border-primary/15 border-t-primary" />
                <p className="text-sm font-semibold text-[#4D4D4D]">Loading your diet plan...</p>
            </div>
        );
    }

    if (!allPlans.length || !activePlan?.days?.length) {
        return (
            <div className="flex min-h-80 flex-col items-center justify-center border border-dashed border-[#BFD4F5] bg-[#FAFAFA] p-8 text-center global-radius">
                <Utensils className="mb-3 h-8 w-8 text-primary" />
                <h2 className="text-lg font-bold text-[#1F1E1E]">No active diet plan assigned</h2>
                <p className="mt-2 max-w-md text-sm text-[#4D4D4D]">
                    Your meal chart will appear here after your doctor assigns a diet plan.
                </p>
            </div>
        );
    }

    return (
        <div className="space-y-4">
            <section className="border border-[#BFD4F5] bg-white p-3.5 global-radius shadow-sm space-y-2">
                <div className="flex flex-wrap items-center gap-2">
                    {allPlans.map((plan: any, index: number) => {
                        const isActive = plan.id === activePlan?.id;
                        const assignedLabel = plan.start_date ? format(parseISO(plan.start_date), 'dd MMM yyyy') : `Plan ${index + 1}`;

                        return (
                            <button
                                key={`plan-toggle-${plan.id}`}
                                type="button"
                                onClick={() => setSelectedPlanId(plan.id)}
                                className={`rounded-md border px-3 py-1.5 text-left transition-all ${
                                    isActive
                                        ? 'border-primary bg-primary/10 text-primary'
                                        : 'border-[#BFD4F5] bg-white text-[#1F1E1E] hover:border-primary/35 hover:bg-primary/5'
                                }`}
                            >
                                <span className="block text-[11px] font-bold leading-tight">{plan.template_name || `Diet Plan ${index + 1}`}</span>
                                <span className="block text-[10px] font-semibold opacity-75">Assigned: {assignedLabel}</span>
                            </button>
                        );
                    })}
                </div>
            </section>

            {/* Elegant Compact Header Banner */}
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border border-[#BFD4F5] bg-white p-4 global-radius shadow-sm">
                <div className="flex items-center gap-3">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <Leaf className="h-5 w-5" />
                    </div>
                    <div>
                        <h1 className="text-base font-bold text-[#1F1E1E]">
                            {activePlan.template_name || 'General Diet Plan'}
                        </h1>
                        <p className="text-xs text-[#6B6B6B]">
                            {activePlan.template_description || 'Personalized meal and wellness recommendations'}
                        </p>
                    </div>
                </div>
                <div className="flex flex-wrap items-center gap-3 text-xs">
                    <span className="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-800">
                        ⏱️ {activePlan.duration_days} Days
                    </span>
                    <span className="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-800">
                        🧑‍⚕️ Dr. {activePlan.doctor_name || 'Doctor'}
                    </span>
                </div>
            </div>

            {/* Day Selector Calendar */}
            <section className="border border-[#BFD4F5] bg-white p-3.5 global-radius shadow-sm space-y-3">
                <div className="flex flex-col gap-2 rounded-md border border-[#BFD4F5] bg-[#F7FAFF] px-3 py-2 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex flex-wrap gap-2">
                        {(['all', 'pending', 'completed', 'past'] as const).map((tab) => (
                            <button
                                key={tab}
                                type="button"
                                onClick={() => setMealTab(tab)}
                                className={`rounded-full border px-3 py-1.5 text-xs font-semibold transition-all ${
                                    mealTab === tab
                                        ? 'border-primary bg-primary/10 text-primary'
                                        : 'border-[#BFD4F5] bg-white text-[#4D4D4D] hover:border-primary/35 hover:text-primary'
                                }`}
                            >
                                {tab[0].toUpperCase() + tab.slice(1)}
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
                            <ChevronLeft className="h-3.5 w-3.5" />
                        </button>
                        <span className="px-3 text-center text-xs font-bold text-[#4D4D4D] min-w-28">
                            {visibleStart && visibleEnd
                                ? `${format(parseISO(visibleStart), 'dd MMM')} - ${format(parseISO(visibleEnd), 'dd MMM')}`
                                : `Days ${pageIndex * DAYS_PER_PAGE + 1}-${Math.min((pageIndex + 1) * DAYS_PER_PAGE, planDays.length)}`}
                        </span>
                        <button
                            type="button"
                            aria-label="Next seven days"
                            disabled={pageIndex >= pageCount - 1}
                            onClick={handleNextPage}
                            className="flex h-full w-8 items-center justify-center text-primary hover:bg-primary/5 disabled:cursor-not-allowed disabled:opacity-30 border-l border-[#BFD4F5]"
                        >
                            <ChevronRight className="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>

                <div className="flex flex-wrap items-center justify-center gap-2">
                    {visibleDays.map((day) => {
                        const isSelected = day.day_number === selectedDay?.day_number;
                        const date = day.date ? parseISO(day.date) : null;

                        return (
                            <button
                                key={`${day.date || day.day_number}-${day.id}`}
                                type="button"
                                onClick={() => setSelectedDayNumber(day.day_number)}
                                className={`relative min-w-20 rounded-md border px-3 py-2.5 text-center transition-all duration-200 ${
                                    isSelected
                                        ? 'border-primary bg-primary text-white shadow-sm shadow-primary/20'
                                        : 'border-[#BFD4F5] bg-[#F7FAFF] text-[#1F1E1E] hover:border-primary/35 hover:bg-primary/5'
                                }`}
                            >
                                <span className={`text-[9px] font-bold uppercase tracking-wider ${isSelected ? 'text-white/80' : 'text-[#7A7A7A]'}`}>
                                    {date ? format(date, 'EEE') : day.week_day.slice(0, 3)}
                                </span>
                                <span className="text-sm font-extrabold mt-0.5 leading-none">
                                    {date ? format(date, 'dd') : day.day_number}
                                </span>
                                <span className={`text-[8px] font-medium mt-0.5 ${isSelected ? 'text-white/70' : 'text-[#7A7A7A]'}`}>
                                    Day {day.day_number}
                                </span>
                                {day.meals.length > 0 && !isSelected && (
                                    <span className="absolute right-1.5 top-1.5 h-1.5 w-1.5 rounded-full bg-primary" />
                                )}
                            </button>
                        );
                    })}
                </div>
            </section>

            {/* Layout Columns */}
            <div className="grid grid-cols-1 gap-4 lg:grid-cols-[280px_minmax(0,1fr)]">
                {/* Left Side: Day Summary & Instructions */}
                <aside className="space-y-3">
                    {/* Day Progress Card */}
                    <div className="border border-[#BFD4F5] bg-white p-4 global-radius shadow-sm">
                        <p className="text-xs font-bold text-[#1F1E1E]">Day {selectedDay?.day_number} Progress</p>
                        <div className="mt-2.5 flex items-center justify-between">
                            <span className="text-sm font-semibold text-[#4D4D4D]">
                                {completedMeals} / {selectedDayMeals.length} Completed
                            </span>
                            {totalCalories > 0 && (
                                <span className="inline-flex items-center gap-1 rounded bg-primary/10 px-2 py-0.5 text-xs font-bold text-primary">
                                    🔥 {totalCalories} kcal
                                </span>
                            )}
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

                    <div className="border border-[#BFD4F5] bg-white p-4 global-radius shadow-sm">
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
                                    {(sidebarMediaItems[sidebarMediaIndex]?.links || []).map((link, index) => (
                                        <a
                                            key={`patient-sidebar-link-${sidebarMediaIndex}-${index}`}
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

                    {/* Doctor's Remarks */}
                    {activePlan.doctor_remark && (
                        <div className="flex items-start gap-2.5 border border-[#CFE0F8] bg-[#F4F8FF] p-3.5 global-radius shadow-sm">
                            <Info className="mt-0.5 h-4 w-4 shrink-0 text-blue-700" />
                            <div>
                                <p className="text-xs font-bold text-blue-800">Doctor&apos;s Remarks</p>
                                <p className="mt-1 text-xs leading-relaxed text-blue-700">{activePlan.doctor_remark}</p>
                            </div>
                        </div>
                    )}

                    {/* Hydration / Water Advice */}
                    <div className="flex items-start gap-2.5 border border-[#BFD4F5] bg-[#F4F8FF] p-3.5 global-radius shadow-sm">
                        <Droplet className="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                        <div>
                            <p className="text-xs font-bold text-primary">Water & Hydration</p>
                            <p className="mt-1 text-xs leading-relaxed text-slate-700">
                                {activePlan.hydration_advice || 'Drink 8-10 glasses (2-2.5L) of water daily.'}
                            </p>
                        </div>
                    </div>

                    {/* Food Restrictions */}
                    {activePlan.restrictions && (
                        <div className="flex items-start gap-2.5 border border-[#F7CACA] bg-[#FFF5F5] p-3.5 global-radius shadow-sm">
                            <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0 text-red-650" />
                            <div>
                                <p className="text-xs font-bold text-red-800">Avoid / Restrictions</p>
                                <p className="mt-1 text-xs leading-relaxed text-red-700">{activePlan.restrictions}</p>
                            </div>
                        </div>
                    )}

                    {/* Allowed Food Notes */}
                    {activePlan.allowed_food_notes && (
                        <div className="flex items-start gap-2.5 border border-[#CFE8D7] bg-[#F2FBF5] p-3.5 global-radius shadow-sm">
                            <Leaf className="mt-0.5 h-4 w-4 shrink-0 text-green-700" />
                            <div>
                                <p className="text-xs font-bold text-green-800">Allowed Foods</p>
                                <p className="mt-1 text-xs leading-relaxed text-green-700">{activePlan.allowed_food_notes}</p>
                            </div>
                        </div>
                    )}

                    {/* Exercise & Lifestyle */}
                    {activePlan.exercise_advice && (
                        <div className="flex items-start gap-2.5 border border-[#CFE0F8] bg-[#F4F8FF] p-3.5 global-radius shadow-sm">
                            <Dumbbell className="mt-0.5 h-4 w-4 shrink-0 text-blue-700" />
                            <div>
                                <p className="text-xs font-bold text-blue-800">Lifestyle & Exercise</p>
                                <p className="mt-1 text-xs leading-relaxed text-blue-700">{activePlan.exercise_advice}</p>
                            </div>
                        </div>
                    )}
                </aside>

                {/* Right Side: High-Density Timeline of Meals */}
                <div className="space-y-3">
                    <div className="flex items-center justify-between rounded-md border border-[#BFD4F5] bg-[#F7FAFF] px-3 py-2">
                        <div className="text-xs font-bold text-[#1F1E1E]">
                            {selectedDay?.week_day ? selectedDay.week_day.charAt(0) + selectedDay.week_day.slice(1).toLowerCase() : `Day ${selectedDay?.day_number}`} Meals
                        </div>
                        <div className="text-[11px] font-semibold text-[#4D4D4D]">
                            {filteredDayMeals.length} shown
                        </div>
                    </div>

                    {filteredDayMeals.length ? (
                        filteredDayMeals.map((meal) => {
                            const MealIcon = getMealIcon(meal.meal_type);
                            const isCompleted = meal.status === 'completed';
                            const canMarkComplete = isToday(meal.occurrence_date || selectedDay?.date);

                            return (
                                <article
                                    key={`${meal.occurrence_date || selectedDay?.date || 'day'}-${meal.id}`}
                                    className={`border bg-white p-3.5 global-radius shadow-sm transition-all duration-200 ${
                                        isCompleted 
                                            ? 'border-[#CFE8D7] bg-[#F2FBF5]' 
                                            : 'border-[#BFD4F5] hover:border-primary/25 hover:shadow-md'
                                    }`}
                                >
                                    <div className="flex flex-col sm:flex-row sm:items-center gap-3 justify-between">
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setActiveMeal(meal);
                                                setIsDetailsOpen(true);
                                            }}
                                            className="flex items-start gap-3 flex-1 min-w-0 text-left"
                                        >
                                                <div className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border ${
                                                isCompleted 
                                                    ? 'border-[#CFE8D7] bg-[#F2FBF5] text-green-700' 
                                                    : 'border-[#BFD4F5] bg-primary/5 text-primary'
                                            }`}>
                                                <MealIcon className="h-4.5 w-4.5" />
                                            </div>
                                            <div className="min-w-0 flex-1">
                                                <div className="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                                                    <span className="text-[10px] font-bold uppercase tracking-wider text-primary">
                                                        {getMealTypeLabel(meal.meal_type)}
                                                    </span>
                                                    {meal.meal_time && (
                                                        <span className="inline-flex items-center gap-1 text-[10px] font-semibold text-[#6B6B6B]">
                                                            <Clock3 className="h-3 w-3" />
                                                            {formatMealTime(meal.meal_time)}
                                                        </span>
                                                    )}
                                                    {meal.calories && (
                                                        <span className="text-[10px] font-semibold bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded">
                                                            🔥 {meal.calories} kcal
                                                        </span>
                                                    )}
                                                </div>
                                                <h3 className="text-sm font-bold text-[#1F1E1E] mt-0.5 hover:text-primary transition-colors">
                                                    {meal.meal_name}
                                                </h3>
                                                {meal.instructions && (
                                                    <p className="mt-1 text-xs text-[#2D466B] leading-relaxed bg-[#F1F6FF] border-l-2 border-[#BFD4F5] px-2 py-1 rounded-r-sm">
                                                        {meal.instructions}
                                                    </p>
                                                )}

                                                            {(meal.meal_image || (meal.helpful_links || []).length > 0) && (
                                                                <div className="mt-2 overflow-x-auto pb-1">
                                                                    <div className="flex items-center gap-2 min-w-max">
                                                                        {meal.meal_image && (
                                                                            <a href={meal.meal_image} target="_blank" rel="noreferrer" className="inline-flex items-center gap-1 rounded-full border border-[#BFD4F5] bg-[#F4F8FF] px-2.5 py-1 text-[10px] font-semibold text-primary">
                                                                                <Eye className="h-3 w-3" /> Meal image
                                                                            </a>
                                                                        )}
                                                                        {(meal.helpful_links || []).map((link, idx) => (
                                                                            <a
                                                                                key={`plink-${meal.id}-${idx}`}
                                                                                href={link.url}
                                                                                target="_blank"
                                                                                rel="noreferrer"
                                                                                className="inline-flex items-center gap-1 rounded-full border border-[#CFE8D7] bg-[#F2FBF5] px-2.5 py-1 text-[10px] font-semibold text-green-700"
                                                                            >
                                                                                {String(link.type || 'link').toLowerCase() === 'youtube' ? 'YouTube' : (link.type || 'Link')}: {link.title || 'Open'}
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
                                                    <span className="inline-flex items-center gap-1 rounded-full bg-[#F2FBF5] px-2.5 py-1 text-xs font-bold text-green-700 border border-[#CFE8D7]">
                                                        <CheckCircle2 className="h-3.5 w-3.5" />
                                                        Done
                                                    </span>
                                                    {(meal.completed_by_name || meal.completed_by_role) && (
                                                        <span className="text-[10px] font-semibold text-[#4D4D4D]">
                                                            Marked by {meal.completed_by_name || meal.completed_by_role}
                                                        </span>
                                                    )}
                                                </div>
                                            ) : canMarkComplete ? (
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        setActiveMeal(meal);
                                                        setIsLogOpen(true);
                                                    }}
                                                    className="btn-primary-cta h-8! px-3! text-xs! py-1!"
                                                >
                                                    Mark Complete
                                                </button>
                                            ) : (
                                                <span className="rounded-full border border-[#E7E8EB] bg-[#FAFAFA] px-2.5 py-1 text-xs font-bold text-[#6B6B6B]">
                                                    Available today only
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                </article>
                            );
                        })
                    ) : (
                        <div className="border border-dashed border-[#BFD4F5] bg-[#FAFAFA] p-8 text-center global-radius">
                            <Utensils className="mx-auto h-7 w-7 text-[#B0B0B0]" />
                            <p className="mt-2 text-xs font-semibold text-[#6D6D6D]">No meals scheduled for Day {selectedDay?.day_number}.</p>
                        </div>
                    )}
                </div>
            </div>

            {/* Details Dialog */}
            <Dialog open={isDetailsOpen} onOpenChange={setIsDetailsOpen}>
                <DialogContent className="max-w-lg bg-white global-radius border border-[#BFD4F5]">
                    <DialogHeader className="space-y-2 text-left">
                        <div className="inline-flex w-fit items-center gap-2 rounded-full border border-[#BFD4F5] bg-primary/5 px-3 py-1 text-xs font-semibold text-primary">
                            <Utensils className="h-3.5 w-3.5" />
                            Meal details
                        </div>
                        <DialogTitle className="text-2xl font-bold text-[#1F1E1E]">
                            {activeMeal?.meal_name}
                        </DialogTitle>
                        <DialogDescription className="text-sm text-[#4D4D4D]">
                            {activeMeal ? getMealTypeLabel(activeMeal.meal_type) : ''}
                            {activeMeal?.meal_time ? ` • ${formatMealTime(activeMeal.meal_time)}` : ''}
                            {activeMeal?.occurrence_date ? ` • ${activeMeal.occurrence_date}` : ''}
                        </DialogDescription>
                    </DialogHeader>

                    {activeMeal && (
                        <div className="space-y-4">
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
                                <p className="text-[10px] font-bold uppercase tracking-[0.18em] text-[#7A7A7A]">Food items</p>
                                <div className="mt-3 flex flex-wrap gap-2">
                                    {getMealItems(activeMeal.meal_name).map((item) => (
                                        <span key={item} className="rounded-full border border-[#BFD4F5] bg-[#FAFAFA] px-3 py-1 text-xs font-medium text-[#4D4D4D]">
                                            {item}
                                        </span>
                                    ))}
                                </div>
                            </div>

                            {activeMeal.instructions && (
                                <div className="rounded-xl border border-[#CFE0F8] bg-[#F4F8FF] p-4 text-sm leading-relaxed text-[#2D466B]">
                                    <p className="text-[10px] font-bold uppercase tracking-[0.18em] text-blue-800">Instructions</p>
                                    <p className="mt-1">{activeMeal.instructions}</p>
                                </div>
                            )}

                            {(activeMeal.meal_image || (activeMeal.helpful_links || []).length > 0) && (
                                <div className="rounded-xl border border-[#BFD4F5] bg-[#F4F8FF] p-4 space-y-3">
                                    <p className="text-[10px] font-bold uppercase tracking-[0.18em] text-primary">Recipe Media</p>
                                    {activeMeal.meal_image && (
                                        <img src={activeMeal.meal_image} alt="Meal" className="max-h-56 w-full rounded-lg object-cover border border-[#BFD4F5]" />
                                    )}
                                    {(activeMeal.helpful_links || []).length > 0 && (
                                        <div className="overflow-x-auto pb-1">
                                            <div className="flex min-w-max gap-2">
                                                {(activeMeal.helpful_links || []).map((link, idx) => (
                                                    <a
                                                        key={`pdlg-link-${idx}`}
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

                            {activeMeal.completed_at && (
                                <div className="rounded-xl border border-[#CFE8D7] bg-[#F2FBF5] p-4 text-sm text-green-800">
                                    <p className="font-semibold">Completed at {activeMeal.completed_at}</p>
                                    {(activeMeal.completed_by_name || activeMeal.completed_by_role) && (
                                        <p className="mt-1 text-xs text-[#4D4D4D]">
                                            Marked by {activeMeal.completed_by_name || activeMeal.completed_by_role}
                                        </p>
                                    )}
                                </div>
                            )}
                        </div>
                    )}
                    <DialogFooter className="flex flex-wrap justify-end gap-2">
                        <button type="button" onClick={() => setIsDetailsOpen(false)} className="btn-primary-cta-outline h-10! px-4! py-2! text-xs!">
                            Close
                        </button>
                        {activeMeal?.status !== 'completed' && isToday(activeMeal?.occurrence_date || selectedDay?.date) && (
                            <button type="button" onClick={() => setIsLogOpen(true)} className="btn-primary-cta h-10! px-4! py-2! text-xs!">
                                Mark complete
                            </button>
                        )}
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Log Meal Dialog */}
            <Dialog open={isLogOpen} onOpenChange={setIsLogOpen}>
                <DialogContent className="max-w-sm bg-white global-radius border border-[#BFD4F5]">
                    <DialogHeader>
                        <DialogTitle>Complete Meal</DialogTitle>
                        <DialogDescription>
                            Optionally add a note before marking this occurrence complete.
                        </DialogDescription>
                    </DialogHeader>
                    <textarea
                        value={logNotes}
                        onChange={(event) => setLogNotes(event.target.value)}
                        rows={4}
                        placeholder="Add notes..."
                        className="w-full resize-none border border-[#BFD4F5] bg-white p-3 text-sm outline-none focus:border-primary global-radius"
                    />
                    <DialogFooter>
                        <button type="button" onClick={() => setIsLogOpen(false)} className="btn-primary-cta-outline h-9! px-4! py-2! text-xs!">
                            Cancel
                        </button>
                        <button
                            type="button"
                            onClick={handleLogMeal}
                            disabled={completeMealMutation.isPending || !isToday(activeMeal?.occurrence_date || selectedDay?.date)}
                            className="btn-primary-cta h-9! px-4! py-2! text-xs! disabled:opacity-60"
                        >
                            {completeMealMutation.isPending ? 'Saving...' : 'Mark Complete'}
                        </button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
