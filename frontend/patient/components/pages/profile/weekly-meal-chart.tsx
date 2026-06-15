import { useDietPlan } from '@/queries/useDietPlan';
import { addWeeks, endOfWeek, format, startOfWeek } from 'date-fns';
import { ChevronDown, ChevronLeft, ChevronRight, CloudSun, Moon, Sun, Zap } from 'lucide-react';
import { useMemo, useState } from 'react';

const WeeklyMealChart = () => {

    const { data, isLoading } = useDietPlan();
    const [currentWeek, setCurrentWeek] = useState(new Date())
    const [openMealRow, setOpenMealRow] = useState<string | null>("MORNING");
    const [mobileBaseDate, setMobileBaseDate] = useState(new Date());

    console.log("patient deit plan" , data);
    

    const mobileDays = useMemo(() => {
        return Array.from({ length: 1 }).map((_, index) => {
            const date = new Date(mobileBaseDate);
            date.setDate(mobileBaseDate.getDate() + index);

            return {
                day: format(date, "EEE").toUpperCase(),
                date: format(date, "dd MMM"),
                fullDate: format(date, "yyyy-MM-dd"),
            };
        });
    }, [mobileBaseDate]);

    const handleMobilePrev = () => {
        setMobileBaseDate((prev) => {
            const date = new Date(prev);
            date.setDate(prev.getDate() - 1);
            return date;
        });
    };

    const handleMobileNext = () => {
        setMobileBaseDate((prev) => {
            const date = new Date(prev);
            date.setDate(prev.getDate() + 1);
            return date;
        });
    };

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

                const apiDay = data.data.days.find((d: DietPlanDay) => d.date === dayItem.fullDate);
                if (!apiDay) return null;
                const meal = apiDay.meals.find((m: Meal) => m.meal_type === category.id);
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

    const hasDietPlan = Array.isArray(data?.data?.days) && data?.data?.days.length > 0;

    if (!hasDietPlan) {
        return (
            <div className="flex min-h-[320px] flex-col items-center justify-center rounded-3xl border border-dashed border-[#E7E8EB] bg-[#F9FAFB] p-10 text-center">
                <div className="mb-4 rounded-full bg-primary/10 p-4 text-primary">
                    <Sun className="w-6 h-6" />
                </div>
                <h2 className="text-xl font-semibold text-[#1F1E1E]">No diet plan assigned yet</h2>
                <p className="mt-2 max-w-md text-sm text-[#4D4D4D]">
                    Your personalized meal chart will appear here once your doctor assigns a diet plan.
                </p>
            </div>
        );
    }

    return (
        <div className="min-h-screen">
            <div className="mb-10 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div className="flex items-center justify-between gap-3">
                    <h1 className="text-xl sm:text-2xl font-semibold text-black">
                        Weekly Meal Chart
                    </h1>
                    <div className="hidden items-center gap-2 md:hidden">
                        <button
                            onClick={() => setCurrentWeek(addWeeks(currentWeek, -1))}
                            className="p-2 rounded-full border-light-gray"
                        >
                            <ChevronLeft color="#4D4D4D" />
                        </button>

                        <button
                            onClick={() => setCurrentWeek(addWeeks(currentWeek, 1))}
                            className="p-2 rounded-full border-light-gray"
                        >
                            <ChevronRight color="#4D4D4D" />
                        </button>
                    </div>
                </div>

                <div className="hidden md:flex items-center gap-4">
                    <button
                        onClick={() => setCurrentWeek(addWeeks(currentWeek, -1))}
                        className="p-2 rounded-full border-light-gray hover:bg-surface-variant/50 transition-colors"
                    >
                        <ChevronLeft color="#4D4D4D" />
                    </button>

                    <div className="px-5 py-2.5 bg-[#e5eeff] rounded-md font-bold text-primary text-sm">
                        {format(weekStart, "MMM dd")} - {format(weekEnd, "MMM dd")}
                    </div>

                    <button
                        onClick={() => setCurrentWeek(addWeeks(currentWeek, 1))}
                        className="p-2 rounded-full border-light-gray hover:bg-surface-variant/50 transition-colors"
                    >
                        <ChevronRight color="#4D4D4D" />
                    </button>
                </div>


            </div>

            <div className="overflow-x-auto">
                <div>
                    <div className="hidden md:grid grid-cols-[140px_repeat(7,1fr)] gap-5 pb-5">
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

                    <div className="md:hidden pb-5">
                        <div className="flex items-center justify-between rounded-md bg-[#F5F6F8] px-3 py-3">
                            <button
                                onClick={handleMobilePrev}
                                className="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-white border border-[#E7E8EB]"
                            >
                                <ChevronLeft color="#4D4D4D" />
                            </button>

                            <div className="flex-1 text-center">
                                <span className="block text-base font-bold text-[#1F1E1E]">
                                    {mobileDays[0]?.day}
                                </span>

                                <span className="block pt-1 text-xs font-semibold text-[#4D4D4D]">
                                    {mobileDays[0]?.date}
                                </span>
                            </div>

                            <button
                                onClick={handleMobileNext}
                                className="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-white border border-[#E7E8EB]"
                            >
                                <ChevronRight color="#4D4D4D" />
                            </button>
                        </div>
                    </div>

                    <div className="space-y-6">
                        {rows.map((row) => {
                            const isOpen = openMealRow === row.label;

                            return (
                                <section key={row.label}>
                                    <div
                                        className="hidden md:grid grid-cols-[140px_repeat(7,1fr)] gap-5"
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

                                    <div className="md:hidden border border-[#E7E8EB] rounded-md overflow-hidden">
                                        <button
                                            type="button"
                                            onClick={() =>
                                                setOpenMealRow(isOpen ? null : row.label)
                                            }
                                            className="w-full flex items-center justify-between p-4 bg-[#F5F6F8] border-b border-[#E7E8EB]"
                                        >
                                            <div className="flex items-center gap-3">
                                                <span className="text-2xl">
                                                    {row.icon}
                                                </span>

                                                <span className="text-[10px] font-black uppercase tracking-[0.2em]">
                                                    {row.label}
                                                </span>
                                            </div>

                                            <ChevronDown
                                                className={`w-5 h-5 text-[#4D4D4D] transition-transform duration-300 ${isOpen ? "rotate-180" : ""
                                                    }`}
                                            />
                                        </button>

                                        <div
                                            className={`grid transition-all duration-300 ease-in-out ${isOpen
                                                ? "grid-rows-[1fr]"
                                                : "grid-rows-[0fr]"
                                                }`}
                                        >
                                            <div className="overflow-hidden divide-y divide-[#E7E8EB]">
                                                {mobileDays.map((dayItem, index) => {
                                                    const meal = row.meals[index];

                                                    return (
                                                        <div
                                                            key={dayItem.fullDate}
                                                            className="grid grid-cols-[70px_1fr]"
                                                        >
                                                            {/* Day */}
                                                            <div className="flex flex-col items-center justify-center border-r border-[#E7E8EB] bg-[#FAFAFA] p-3">
                                                                <span className="text-sm font-bold text-[#1F1E1E]">
                                                                    {dayItem.day}
                                                                </span>

                                                                <span className="text-[10px] font-medium text-[#4D4D4D] mt-1">
                                                                    {dayItem.date}
                                                                </span>
                                                            </div>

                                                            {/* Meal */}
                                                            <div className="p-3">
                                                                {!meal ? (
                                                                    <p className="text-sm text-[#4D4D4D]">
                                                                        No meal
                                                                    </p>
                                                                ) : (
                                                                    <div
                                                                        className={`rounded-md border p-3 ${meal.variant === "active"
                                                                            ? "border-primary bg-primary"
                                                                            : "border-[#E7E8EB] bg-white"
                                                                            }`}
                                                                    >
                                                                        <h3
                                                                            className={`font-bold text-sm leading-tight ${meal.variant === "active"
                                                                                ? "text-white"
                                                                                : "text-[#1F1E1E]"
                                                                                }`}
                                                                        >
                                                                            {meal.title}
                                                                        </h3>

                                                                        {meal.time && (
                                                                            <div className="mt-3 inline-flex rounded-full bg-[#EEF4FF] px-2 py-1 text-[10px] font-semibold text-primary">
                                                                                {meal.time}
                                                                            </div>
                                                                        )}
                                                                    </div>
                                                                )}
                                                            </div>
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        </div>

                                    </div>
                                </section>
                            );
                        })}
                    </div>
                </div>
            </div>

        </div>
    )
}

export default WeeklyMealChart