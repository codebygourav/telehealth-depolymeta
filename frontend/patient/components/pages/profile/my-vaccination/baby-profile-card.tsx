import { Card, CardContent, Skeleton } from '@/components/ui';
import { usePatientVaccinations } from '@/queries/usePatientVaccinations';

const BabyProfileCard = () => {

    const { data, isLoading, error } = usePatientVaccinations();

    const profile = data?.data?.profile;

    console.log("Patient Vaccinations:", data);

    if (isLoading) {
        return (
            <Card className="rounded-lg p-4 sm:p-5 md:p-6 flex-1">
                <CardContent className="p-0">
                    <div className="flex flex-col sm:flex-row gap-4 sm:gap-5">
                        <Skeleton className="w-20 h-20 sm:w-24 sm:h-24 rounded-full mx-auto sm:mx-0" />

                        <div className="flex-1 space-y-3">
                            <Skeleton className="h-7 w-28 mx-auto sm:mx-0" />
                            <Skeleton className="h-8 w-44 mx-auto sm:mx-0" />

                            <div className="flex flex-wrap justify-center sm:justify-start gap-3">
                                <Skeleton className="h-8 w-24 rounded-xl" />
                                <Skeleton className="h-8 w-24 rounded-xl" />
                                <Skeleton className="h-8 w-32 rounded-xl" />
                                <Skeleton className="h-8 w-24 rounded-xl" />
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        );
    }

    if (error) {
        return (
            <Card className="rounded-lg p-4 sm:p-5 md:p-6 flex-1">
                <CardContent className="p-0">
                    <p className="text-sm font-medium text-red-600">
                        Failed to load profile data.
                    </p>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card className="rounded-lg p-4 sm:p-5 md:p-6 flex-1">
            <CardContent className="p-0">
                <div className="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4">
                    <div className="flex flex-col sm:flex-row gap-4 sm:gap-5 flex-1 min-w-0">
                        <img
                            src={
                                profile?.photo ||
                                "https://lh3.googleusercontent.com/aida-public/AB6AXuB-6uK4pbaY5lBjR0S0IXH0H8DAOh05YLegvt4moAn6gdnJPtPtAJNuMnNNjNX0ktStjkmDAxE4gptZcft5Mi0wkH4OOHqyOYXotuej6DTj3t9HxDrJE1ls_yzx-7Uo3iiCgmV20lRkamwvpzJ664yNwGlrTzBi0XmZkRC9iaWPQmozgKqBtH3zYeXiYOwaHs7PoWCUqi3N83qJ0ptdRf9Wv1HlsAvFn122qH5f5xhnqh22tUoSHFdL5cTWh94GhAj0oS9Enh8ES8wr"
                            }
                            alt="Baby Profile"
                            className="w-20 h-20 sm:w-24 sm:h-24 rounded-full object-cover mx-auto sm:mx-0 shrink-0"
                        />

                        <div className="text-center sm:text-left flex-1 min-w-0">
                            <span className="inline-block px-3 py-1.5 bg-primary/10 text-primary rounded-md text-xs sm:text-sm font-semibold">
                                {profile?.age || "N/A"}
                            </span>

                            <h2 className="text-xl sm:text-2xl font-bold text-[#1F1E1E] mt-2 break-words">
                                {profile?.name || "Unknown"}
                            </h2>

                            <div className="flex flex-wrap justify-center sm:justify-start gap-2 sm:gap-3 md:gap-4 mt-2">
                                <div className="flex items-center gap-x-1.5 bg-light-gray rounded-xl py-2 px-2.5 h-fit">
                                    <p className="text-xs text-[#4D4D4D] whitespace-nowrap">Weight: </p>
                                    <p className="text-xs font-semibold text-[#4D4D4D] whitespace-nowrap">
                                        {profile?.weight || "N/A"}
                                    </p>
                                </div>

                                <div className="flex items-center gap-x-1.5 bg-light-gray rounded-xl py-2 px-2.5 h-fit">
                                    <p className="text-xs text-[#4D4D4D] whitespace-nowrap">Height: </p>
                                    <p className="text-xs font-semibold text-[#4D4D4D] whitespace-nowrap">
                                        {profile?.height || "N/A"}
                                    </p>
                                </div>

                                <div className="flex items-center gap-x-1.5 bg-light-gray rounded-xl py-2 px-2.5 h-fit">
                                    <p className="text-xs text-[#4D4D4D] whitespace-nowrap">Blood Group: </p>
                                    <p className="text-xs font-semibold text-[#4D4D4D] whitespace-nowrap">
                                        {profile?.blood_group || "N/A"}
                                    </p>
                                </div>

                                <div className="flex items-center gap-x-1.5 bg-light-gray rounded-xl py-2 px-2.5 h-fit">
                                    <p className="text-xs text-[#4D4D4D] whitespace-nowrap">Gender: </p>
                                    <p className="text-xs font-semibold text-[#4D4D4D] whitespace-nowrap">
                                        {profile?.gender || "N/A"}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    )
}

export default BabyProfileCard;
