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
    Info,
    Leaf,
    Moon,
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
    const activePlan = data?.data;

    const [pageIndex, setPageIndex] = useState(0);
    const [selectedDayNumber, setSelectedDayNumber] = useState<number | null>(null);
    const [activeMeal, setActiveMeal] = useState<Meal | null>(null);
    const [isDetailsOpen, setIsDetailsOpen] = useState(false);
    const [isLogOpen, setIsLogOpen] = useState(false);
    const [logNotes, setLogNotes] = useState('');
    const [loadedPlanId, setLoadedPlanId] = useState<string | null>(null);

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
    const completedMeals = selectedDayMeals.filter((meal) => meal.status === 'completed').length;
    const totalCalories = selectedDayMeals.reduce(
        (total, meal) => total + Number(meal.calories || 0),
        0,
    );

    const visibleStart = visibleDays[0]?.date;
    const visibleEnd = visibleDays[visibleDays.length - 1]?.date;

    const handleLogMeal = async () => {
        if (!activeMeal) return;

        await completeMealMutation.mutateAsync({
            mealId: activeMeal.id,
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

    if (!activePlan?.days?.length) {
        return (
            <div className="flex min-h-80 flex-col items-center justify-center border border-dashed border-[#E7E8EB] bg-[#FAFAFA] p-8 text-center global-radius">
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
            {/* Elegant Compact Header Banner */}
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border border-[#E7E8EB] bg-white p-4 global-radius shadow-sm">
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
            <section className="border border-[#E7E8EB] bg-white p-3.5 global-radius shadow-sm space-y-3">
                <div className="flex items-center justify-between gap-3">
                    <div>
                        <h2 className="text-sm font-bold text-[#1F1E1E]">Meal Calendar</h2>
                    </div>

                    <div className="flex h-8 items-center border border-[#E7E8EB] bg-white rounded-md">
                        <button
                            type="button"
                            aria-label="Previous seven days"
                            disabled={pageIndex === 0}
                            onClick={handlePrevPage}
                            className="flex h-full w-8 items-center justify-center text-[#4D4D4D] hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-30 border-r border-[#E7E8EB]"
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
                            className="flex h-full w-8 items-center justify-center text-[#4D4D4D] hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-30 border-l border-[#E7E8EB]"
                        >
                            <ChevronRight className="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>

                <div className="grid grid-cols-4 gap-1.5 sm:grid-cols-7">
                    {visibleDays.map((day) => {
                        const isSelected = day.day_number === selectedDay?.day_number;
                        const date = day.date ? parseISO(day.date) : null;

                        return (
                            <button
                                key={day.id}
                                type="button"
                                onClick={() => setSelectedDayNumber(day.day_number)}
                                className={`relative flex flex-col items-center justify-center py-2 px-1 text-center transition-all duration-200 rounded-md border ${
                                    isSelected
                                        ? 'border-primary bg-primary text-white shadow-sm shadow-primary/20'
                                        : 'border-[#E7E8EB] bg-slate-50/50 text-[#1F1E1E] hover:border-primary/35 hover:bg-primary/5'
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
                    <div className="border border-[#E7E8EB] bg-white p-4 global-radius shadow-sm">
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

                    {/* Doctor's Remarks */}
                    {activePlan.doctor_remark && (
                        <div className="flex items-start gap-2.5 border border-[#F5D38A] bg-[#FFF9EC] p-3.5 global-radius shadow-sm">
                            <Info className="mt-0.5 h-4 w-4 shrink-0 text-[#A86600]" />
                            <div>
                                <p className="text-xs font-bold text-[#7A4A00]">Doctor&apos;s Remarks</p>
                                <p className="mt-1 text-xs leading-relaxed text-[#5F4A25]">{activePlan.doctor_remark}</p>
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
                        <div className="flex items-start gap-2.5 border border-red-200 bg-red-50 p-3.5 global-radius shadow-sm">
                            <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0 text-red-650" />
                            <div>
                                <p className="text-xs font-bold text-red-800">Avoid / Restrictions</p>
                                <p className="mt-1 text-xs leading-relaxed text-red-700">{activePlan.restrictions}</p>
                            </div>
                        </div>
                    )}

                    {/* Allowed Food Notes */}
                    {activePlan.allowed_food_notes && (
                        <div className="flex items-start gap-2.5 border border-green-200 bg-green-50 p-3.5 global-radius shadow-sm">
                            <Leaf className="mt-0.5 h-4 w-4 shrink-0 text-green-700" />
                            <div>
                                <p className="text-xs font-bold text-green-800">Allowed Foods</p>
                                <p className="mt-1 text-xs leading-relaxed text-green-700">{activePlan.allowed_food_notes}</p>
                            </div>
                        </div>
                    )}

                    {/* Exercise & Lifestyle */}
                    {activePlan.exercise_advice && (
                        <div className="flex items-start gap-2.5 border border-blue-200 bg-blue-50 p-3.5 global-radius shadow-sm">
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
                    {selectedDayMeals.length ? (
                        selectedDayMeals.map((meal) => {
                            const MealIcon = getMealIcon(meal.meal_type);
                            const isCompleted = meal.status === 'completed';

                            return (
                                <article
                                    key={meal.id}
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
                                                setIsDetailsOpen(true);
                                            }}
                                            className="flex items-start gap-3 flex-1 min-w-0 text-left"
                                        >
                                            <div className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border ${
                                                isCompleted 
                                                    ? 'border-green-200 bg-green-50 text-green-600' 
                                                    : 'border-primary/10 bg-primary/5 text-primary'
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
                                                    <p className="mt-1 text-xs text-[#5F4A25] leading-relaxed bg-[#FFF9EC] border-l-2 border-[#F5A623] px-2 py-1 rounded-r-sm">
                                                        {meal.instructions}
                                                    </p>
                                                )}
                                            </div>
                                        </button>

                                        <div className="flex sm:flex-col items-center sm:items-end justify-between gap-2 border-t border-slate-100 pt-2 sm:border-0 sm:pt-0 shrink-0">
                                            {isCompleted ? (
                                                <span className="inline-flex items-center gap-1 rounded-full bg-green-50 px-2.5 py-1 text-xs font-bold text-green-700">
                                                    <CheckCircle2 className="h-3.5 w-3.5" />
                                                    Done
                                                </span>
                                            ) : (
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        setActiveMeal(meal);
                                                        setIsLogOpen(true);
                                                    }}
                                                    className="btn-primary-cta !h-8 !px-3 !text-xs !py-1"
                                                >
                                                    Log Meal
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                </article>
                            );
                        })
                    ) : (
                        <div className="border border-dashed border-[#E7E8EB] bg-[#FAFAFA] p-8 text-center global-radius">
                            <Utensils className="mx-auto h-7 w-7 text-[#B0B0B0]" />
                            <p className="mt-2 text-xs font-semibold text-[#6D6D6D]">No meals scheduled for Day {selectedDay?.day_number}.</p>
                        </div>
                    )}
                </div>
            </div>

            {/* Details Dialog */}
            <Dialog open={isDetailsOpen} onOpenChange={setIsDetailsOpen}>
                <DialogContent className="max-w-md bg-white global-radius">
                    <DialogHeader>
                        <DialogTitle>{activeMeal?.meal_name}</DialogTitle>
                        <DialogDescription>
                            {activeMeal ? getMealTypeLabel(activeMeal.meal_type) : ''} meal details
                        </DialogDescription>
                    </DialogHeader>
                    {activeMeal && (
                        <div className="space-y-4">
                            <div className="grid grid-cols-2 gap-3">
                                <div className="border border-[#E7E8EB] bg-[#FAFAFA] p-3 global-radius">
                                    <p className="text-[10px] font-bold uppercase text-[#7A7A7A]">Meal Time</p>
                                    <p className="mt-1 text-sm font-bold text-[#1F1E1E]">
                                        {formatMealTime(activeMeal.meal_time) || 'Not specified'}
                                    </p>
                                </div>
                                <div className="border border-[#E7E8EB] bg-[#FAFAFA] p-3 global-radius">
                                    <p className="text-[10px] font-bold uppercase text-[#7A7A7A]">Calories</p>
                                    <p className="mt-1 text-sm font-bold text-[#1F1E1E]">
                                        {activeMeal.calories || 0} kcal
                                    </p>
                                </div>
                            </div>
                            <div>
                                <p className="text-xs font-bold text-[#4D4D4D]">Food Items</p>
                                <div className="mt-2 flex flex-wrap gap-2">
                                    {getMealItems(activeMeal.meal_name).map((item) => (
                                        <span key={item} className="border border-[#E7E8EB] bg-[#FAFAFA] px-2 py-1 text-xs text-[#4D4D4D] global-radius">
                                            {item}
                                        </span>
                                    ))}
                                </div>
                            </div>
                            {activeMeal.instructions && (
                                <div className="border-l-2 border-[#F5A623] bg-[#FFF9EC] p-3 text-sm text-[#6A4A12]">
                                    {activeMeal.instructions}
                                </div>
                            )}
                        </div>
                    )}
                    <DialogFooter>
                        <button type="button" onClick={() => setIsDetailsOpen(false)} className="btn-primary-cta-outline !h-9 !px-4 !py-2 !text-xs">
                            Close
                        </button>
                        {activeMeal?.status !== 'completed' && (
                            <button type="button" onClick={() => setIsLogOpen(true)} className="btn-primary-cta !h-9 !px-4 !py-2 !text-xs">
                                Log Meal
                            </button>
                        )}
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Log Meal Dialog */}
            <Dialog open={isLogOpen} onOpenChange={setIsLogOpen}>
                <DialogContent className="max-w-sm bg-white global-radius">
                    <DialogHeader>
                        <DialogTitle>Log Meal</DialogTitle>
                        <DialogDescription>Optionally add a note before marking this meal complete.</DialogDescription>
                    </DialogHeader>
                    <textarea
                        value={logNotes}
                        onChange={(event) => setLogNotes(event.target.value)}
                        rows={4}
                        placeholder="Add notes..."
                        className="w-full resize-none border border-[#E7E8EB] bg-white p-3 text-sm outline-none focus:border-primary global-radius"
                    />
                    <DialogFooter>
                        <button type="button" onClick={() => setIsLogOpen(false)} className="btn-primary-cta-outline !h-9 !px-4 !py-2 !text-xs">
                            Cancel
                        </button>
                        <button
                            type="button"
                            onClick={handleLogMeal}
                            disabled={completeMealMutation.isPending}
                            className="btn-primary-cta !h-9 !px-4 !py-2 !text-xs disabled:opacity-60"
                        >
                            {completeMealMutation.isPending ? 'Saving...' : 'Mark Complete'}
                        </button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}

