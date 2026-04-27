"use client";

import { CustomAvatar } from "@/components/custom/custom-avatar";
import { SectionHeader } from "@/components/custom/SectionHeader";
import { DashboardCarousel } from "@/components/pages/Dashboard/dashboard-carousel";
import { Avatar, AvatarImage, Card, CardContent } from "@/components/ui";
import { ExternalLink } from "lucide-react";
import Image from "next/image";
import { useState } from "react";

export interface Advertisement {
    id: string;
    title: string;
    description: string;
    image: string;
    link?: string;
}

interface AdvertisementsProps {
    ads: Advertisement[];
}

export function Advertisements({ ads }: AdvertisementsProps) {
    if (!ads || ads.length === 0) {
        return null;
    }

    // Component inside to use hooks per item
    const AdvertisementCard = ({ ad }: { ad: Advertisement }) => {
        const [isExpanded, setIsExpanded] = useState(false);

        // Strip HTML tags for character count
        const stripHtml = (html: string) => {
            const temp = document.createElement('div');
            temp.innerHTML = html;
            return temp.textContent || temp.innerText || '';
        };

        const plainText = stripHtml(ad.description);
        const needsTruncation = plainText.length > 120;

        const getDisplayText = () => {
            if (isExpanded) return ad.description;
            if (!needsTruncation) return ad.description;

            let truncated = plainText.slice(0, 120);
            const lastSpace = truncated.lastIndexOf(' ');
            if (lastSpace > 0 && lastSpace > 100) {
                truncated = truncated.slice(0, lastSpace);
            }
            return truncated;
        };

        // Function to clean and format description
        const formatDescription = (text: string) => {
            // Preserve bullet points and line breaks
            return text
                .replace(/\n/g, '<br/>')
                .replace(/•/g, '•')
                .replace(/- /g, '• ');
        };

        return (
            <Card className=" rounded-[5px]">
                <CardContent className="flex-1">
                    <div className="w-full rounded-lg overflow-hidden mb-2 sm:mb-3">
                        <img
                            src={ad.image}
                            alt={ad.title}
                            className="w-[450px] h-[300px]"
                        />
                    </div>

                    {/* <Image
                        src={ad.image}
                        alt={ad.title}
                        width={100}
                        height={10}
                        className="rounded-lg"
                    /> */}
                    {/* <h4 className="font-semibold text-sm sm:text-base md:text-lg text-gray-900 mb-1.5 sm:mb-2">
                        {ad.title}
                    </h4> */}
                    {/* <div>
                        {!isExpanded && needsTruncation ? (
                            <>
                                <div
                                    className="mb-2 text-sm text-gray-700 leading-relaxed whitespace-pre-line"
                                    dangerouslySetInnerHTML={{
                                        __html: formatDescription(getDisplayText()) + '...'
                                    }}
                                />
                                <button
                                    onClick={() => setIsExpanded(true)}
                                    className="text-emerald-600 text-xs font-semibold mt-1 hover:text-emerald-700 hover:underline focus:outline-none transition-colors"
                                >
                                    Read more
                                </button>
                            </>
                        ) : (
                            <>
                                <div
                                    className="mb-2 text-sm text-gray-700 leading-relaxed whitespace-pre-line"
                                    dangerouslySetInnerHTML={{
                                        __html: formatDescription(ad.description)
                                    }}
                                />
                                {isExpanded && (
                                    <button
                                        onClick={() => setIsExpanded(false)}
                                        className="text-emerald-600 text-xs font-semibold mt-1 hover:text-emerald-700 hover:underline focus:outline-none transition-colors"
                                    >
                                        Read less
                                    </button>
                                )}
                            </>
                        )}
                    </div> */}

                    <div className="flex w-full items-end justify-between gap-2 sm:gap-3 mt-auto">
                        {ad.link ? (
                            <a
                                href={ad.link}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="bg-white border flex items-center gap-1.5 sm:gap-2 border-gray-300 text-[#103228] px-3 sm:px-4 py-1.5 md:text-xs text-sm rounded-[5px] font-medium hover:bg-gray-100 transition-colors duration-100 shrink-0"
                            >
                                <ExternalLink className="w-3 h-3 sm:w-3.5 sm:h-3.5" />
                                View
                            </a>
                        ) : (
                            <span className="bg-gray-300 text-gray-700 px-3 sm:px-4 py-1.5 text-xs sm:text-sm rounded-md font-medium opacity-60 cursor-default shrink-0">
                                Book Appointment
                            </span>
                        )}
                        {/* <CustomAvatar
                        src={ad.image}
                        radius="lg"
                        className="w-12 h-12 sm:w-[60px] sm:h-[60px] md:w-[76px] md:h-[76px] shrink-0"
                    /> */}
                    </div>
                </CardContent>
            </Card>
        );
    };

    return (
        <section>

            <SectionHeader title="Safe & Advanced Surgical Care" />

            <Card className="rounded-[5px] shadow-card-lg">
                <CardContent>
                    <DashboardCarousel
                        items={ads}
                        contentClassName="-ml-3 sm:-ml-4 md:-ml-6 py-3 sm:py-4 px-1"
                        basisClassName="pl-3 sm:pl-4 md:pl-6 basis-full sm:basis-1/2 lg:basis-1/3"
                        dotClassName="bg-gray-300 opacity-100 hover:bg-gray-400 h-1 sm:h-1.5"
                        activeDotClassName="bg-primary opacity-100 h-1 sm:h-1.5"
                        renderItem={(ad) => <AdvertisementCard key={ad.id} ad={ad} />}
                    />
                </CardContent>
            </Card>
        </section>
    );
}