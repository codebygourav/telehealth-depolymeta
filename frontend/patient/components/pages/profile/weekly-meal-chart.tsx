import { useMemo, useState } from 'react'
import { addWeeks, endOfWeek, format, startOfWeek } from 'date-fns'
import { ChevronLeft, ChevronRight, Sun, CloudSun, Moon, Zap } from 'lucide-react';
import { useDietPlan } from '@/queries/useDietPlan';

const WeeklyMealChart = () => {

    const { data, isLoading } = useDietPlan();
    const [currentWeek, setCurrentWeek] = useState(new Date())
    const weekStart = startOfWeek(currentWeek, {
        weekStartsOn: 1,
    })
    const weekEnd = endOfWeek(currentWeek, {
        weekStartsOn: 1,
    })

    const days = useMemo(() => {
        return Array.from({ length: 7 }).map((_, index) => {
            const date = new Date(weekStart)
            date.setDate(weekStart.getDate() + index)
            return {
                day: format(date, 'EEE').toUpperCase(),
                date: format(date, 'dd MMM'),
                fullDate: format(date, 'yyyy-MM-dd'),
            }
        })
    }, [weekStart])

    const formatMealTime = (timeString: string) => {
        if (!timeString) return '';
        const [hours, minutes] = timeString.split(':');
        const h = parseInt(hours, 10);
        const ampm = h >= 12 ? 'PM' : 'AM';
        const formattedHours = h % 12 || 12;
        return `${formattedHours < 10 ? '0' + formattedHours : formattedHours}:${minutes} ${ampm}`;
    };

    const rows = useMemo(() => {
        const categories = [
            { id: 'BREAKFAST', label: 'MORNING', icon: <Sun color='#055BD9' /> },
            { id: 'LUNCH', label: 'AFTERNOON', icon: <CloudSun color='#055BD9' /> },
            { id: 'SNACK', label: 'EVENING', icon: <Moon color='#055BD9' /> },
            { id: 'DINNER', label: 'NIGHT', icon: <Zap color='#055BD9' /> },
        ];

        return categories.map((category) => {
            const rowMeals = days.map((dayItem) => {
                if (!data?.data?.days) return null;
                
                const apiDay = data.data.days.find((d: any) => d.date === dayItem.fullDate);
                if (!apiDay) return null;

                const meal = apiDay.meals.find((m: any) => m.meal_type === category.id);
                if (!meal) return null;

                return {
                    title: meal.meal_name,
                    time: meal.meal_time ? formatMealTime(meal.meal_time) : null,
                    variant: meal.status === 'completed' ? 'default' : meal.status === 'pending' ? 'active' : 'default',
                    action: meal.status === 'pending' ? 'LOG NOW' : null,
                };
            });

            return {
                label: category.label,
                icon: category.icon,
                meals: rowMeals,
            };
        });
    }, [data, days]);

    return (
        <div className="min-h-screen">

            <div className="mb-10 flex flex-col gap-6 md:flex-row md:items-center md:justify-between">

                <h1 className="text-2xl font-semibold text-black">
                    Weekly Meal Chart
                </h1>

                <div className="flex items-center gap-4">
                    <button
                        onClick={() => setCurrentWeek(addWeeks(currentWeek, -1))}
                        className="p-2 rounded-full border-light-gray hover:bg-surface-variant/50 transition-colors"
                    >
                        <ChevronLeft color='#4D4D4D' />
                    </button>

                    <div className="px-5 py-2.5 bg-[#e5eeff] rounded-md font-bold text-primary text-sm">
                        {format(weekStart, 'MMM dd')} - {format(weekEnd, 'MMM dd')}
                    </div>

                    <button
                        onClick={() => setCurrentWeek(addWeeks(currentWeek, 1))}
                        className="p-2 rounded-full border-light-gray hover:bg-surface-variant/50 transition-colors"
                    >
                        <ChevronRight color='#4D4D4D' />
                    </button>
                </div>
            </div>

            <div className="overflow-x-auto">
                <div>
                    <div className="grid grid-cols-[140px_repeat(7,1fr)] gap-5 pb-5">
                        <div />

                        {days.map((item) => (
                            <div
                                key={item.day}
                                className="flex flex-col items-center justify-center bg-[#F5F6F8] py-4 rounded-md"
                            >
                                <span className="text-base font-bold text-[#4D4D4D]">
                                    {item.day}
                                </span>
                                <span className="text-xs text-[#4D4D4D] font-semibold tracking-wide uppercase pt-1">
                                    {item.date}
                                </span>
                            </div>
                        ))}
                    </div>

                    <div className="space-y-6">
                        {rows.map((row) => (
                            <div
                                key={row.label}
                                className="grid grid-cols-[140px_repeat(7,1fr)] gap-5"
                            >
                                <div className="h-auto flex flex-col items-center justify-center glass-card rounded-2xl text-center p-4">
                                    <span className="mb-4 text-3xl">{row.icon}</span>
                                    <span className="text-[10px] font-black uppercase tracking-[0.2em]">
                                        {row.label}
                                    </span>
                                </div>

                                {Array.from({ length: 7 }).map((_, index) => {
                                    const meal = row.meals[index]

                                    if (!meal) {
                                        return (
                                            <div
                                                key={index}
                                                className="h-auto rounded-[28px] bg-[#EEF2FB] opacity-70"
                                            />
                                        )
                                    }

                                    if (meal.variant === 'dashed') {
                                        return (
                                            <div
                                                key={index}
                                                className="flex h-auto cursor-pointer flex-col items-center justify-center rounded-[28px] border-2 border-dashed border-[#BFD3FF] bg-[#F8FBFF] transition hover:border-[#0B5ED7]"
                                            >
                                                <span className="mb-3 text-4xl font-light text-[#0B5ED7]">
                                                    +
                                                </span>
                                                <span className="text-sm font-bold tracking-[2px] text-[#0B5ED7]">
                                                    {meal.title}
                                                </span>
                                            </div>
                                        )
                                    }

                                    return (
                                        <div
                                            key={index}
                                            className={`group relative flex h-auto flex-col justify-between rounded-md p-3 border ${meal.variant === 'active'
                                                ? 'border-primary bg-primary'
                                                : meal.variant === 'upcoming'
                                                    ? 'border-[#E7E8EB] bg-white'
                                                    : 'border-[#E7E8EB]'
                                                }`}
                                        >
                                            <div>
                                                <h3
                                                    className={`font-bold text-sm leading-tight ${meal.variant === 'active' ? 'text-white' : 'text-[#1F1E1E]'}`}
                                                >
                                                    {meal.title}
                                                </h3>
                                            </div>

                                            {meal.time && (
                                                <div className="flex w-fit items-center gap-2 rounded-full bg-[#EEF4FF] px-3 py-2 text-xs font-semibold text-primary mt-5">
                                                    {meal.time}
                                                </div>
                                            )}

                                        </div>
                                    )
                                })}
                            </div>
                        ))}
                    </div>
                </div>
            </div>

        </div>
    )
}

export default WeeklyMealChart