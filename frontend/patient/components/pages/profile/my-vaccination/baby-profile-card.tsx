import { Card, CardContent } from '@/components/ui';

const BabyProfileCard = () => {
    return (
        <Card className="rounded-lg p-4 sm:p-5 md:p-6 flex-1">
            <CardContent className="p-0">
                <div className="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4">

                    <div className="flex flex-col sm:flex-row gap-4 sm:gap-5 flex-1">
                        <img
                            src="https://lh3.googleusercontent.com/aida-public/AB6AXuB-6uK4pbaY5lBjR0S0IXH0H8DAOh05YLegvt4moAn6gdnJPtPtAJNuMnNNjNX0ktStjkmDAxE4gptZcft5Mi0wkH4OOHqyOYXotuej6DTj3t9HxDrJE1ls_yzx-7Uo3iiCgmV20lRkamwvpzJ664yNwGlrTzBi0XmZkRC9iaWPQmozgKqBtH3zYeXiYOwaHs7PoWCUqi3N83qJ0ptdRf9Wv1HlsAvFn122qH5f5xhnqh22tUoSHFdL5cTWh94GhAj0oS9Enh8ES8wr"
                            alt="Baby Profile"
                            className="w-24 h-24 rounded-full object-cover mx-auto sm:mx-0"
                        />

                        <div className="text-center sm:text-left flex-1">

                            <span className="px-3 py-1.5 bg-primary/10 text-primary rounded-md text-sm font-semibold">
                                8 months old
                            </span>

                            <h2 className="text-2xl font-bold text-[#1F1E1E] mt-2">
                                Baby Aryan
                            </h2>

                            {/* Weight Cards */}
                            <div className="flex flex-wrap justify-center sm:justify-start gap-3 sm:gap-4 mt-1.5">

                                {/* Weight Card */}
                                <div className="flex items-center gap-x-1.5 bg-light-gray rounded-xl py-2 px-2.5 h-fit">
                                    <p className="text-xs text-[#4D4D4D]">Weight: </p>
                                    <p className="text-xs font-semibold text-[#4D4D4D]">
                                        8.5 kg
                                    </p>
                                </div>

                                {/* Height Card */}
                                <div className="flex items-center gap-x-1.5 bg-light-gray rounded-xl py-2 px-2.5 h-fit">
                                    <p className="text-xs text-[#4D4D4D]">Height: </p>
                                    <p className="text-xs font-semibold text-[#4D4D4D]">
                                        70 cm
                                    </p>
                                </div>

                                {/* Blood Group Card */}
                                <div className="flex items-center gap-x-1.5 bg-light-gray rounded-xl py-2 px-2.5 h-fit">
                                    <p className="text-xs text-[#4D4D4D]">Blood Group: </p>
                                    <p className="text-xs font-semibold text-[#4D4D4D]">
                                        O+ve
                                    </p>
                                </div>

                                {/* Gender Card */}
                                <div className="flex items-center gap-x-1.5 bg-light-gray rounded-xl py-2 px-2.5 h-fit">
                                    <p className="text-xs text-[#4D4D4D]">Gender: </p>
                                    <p className="text-xs font-semibold text-[#4D4D4D]">
                                        Male
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
