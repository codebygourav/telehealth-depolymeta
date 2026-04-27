"use client";

import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { ArrowRight, FileText, Pill } from "lucide-react";
import Link from "next/link";

interface QuickLinksProps {
    reportSummary?: string;
    prescriptionSummary?: string;
    onViewReports?: () => void;
    onManageRefills?: () => void;
}

export default function QuickLinks({
    reportSummary,
    prescriptionSummary,
    onViewReports,
    onManageRefills,
}: QuickLinksProps) {
    // const handleViewReports = () => {
    //     if (onViewReports) {
    //         onViewReports();
    //     } else {
    //         window.location.href = "/reviews";
    //     }
    // };

    // const handleManageRefills = () => {
    //     if (onManageRefills) {
    //         onManageRefills();
    //     } else {
    //         window.location.href = "/transactions";
    //     }
    // };

    const cards = [
        {
            title: "Medications",
            heading: "3 Active Medications",
            subtext: "Next dose in 2 hours",
            active: "280",
            inactive: "03",
            progress: "73%",
            iconBg: "bg-[#fde8ea]",
            iconColor: "text-red-500",
            progressColor: "bg-red-500",
            href: "/my-medicines/",
        },
        {
            title: "Test Results",
            heading: "2 New Test Results",
            subtext: "Blood work from 05/12",
            active: "12",
            inactive: "05",
            progress: "74%",
            iconBg: "bg-[#f8efdf]",
            iconColor: "text-amber-500",
            progressColor: "bg-amber-400",
            href: "/medical-records",
        },
    ];

    return (
        // <div className="grid grid-cols-1 sm:grid-cols-2 gap-6 h-full">
        //     {/* Reports Card */}
        //     <Card className="rounded-3xl border border-border/50 shadow-sm flex-1 p-0">
        //         <CardContent className="flex flex-col h-full p-5 justify-between">
        //             <div>
        //                 <div className="flex justify-between items-start mb-4">
        //                     <div className="p-3 bg-[#d1e4d7] rounded-xl">
        //                         <FileText className="w-6 h-6 text-primary" />
        //                     </div>
        //                 </div>
        //                 <h4 className="text-xl font-bold text-foreground mb-1 font-headline">
        //                     Reviews
        //                 </h4>
        //             </div>
        //             <Button
        //                 variant="ghost"
        //                 onClick={handleViewReports}
        //                 className="mt-4 w-fit p-0 h-auto text-primary font-semibold text-sm flex items-center gap-2 hover:gap-3 transition-all hover:bg-transparent"
        //             >
        //                 View all reports
        //                 <ArrowRight className="w-4 h-4" />
        //             </Button>
        //         </CardContent>
        //     </Card>

        //     {/* Prescriptions Card */}
        //     <Card className="rounded-3xl border border-border/50 shadow-sm flex-1">
        //         <CardContent className="p-6 flex flex-col h-full justify-between">
        //             <div>
        //                 <div className="flex justify-between items-start mb-4">
        //                     <div className="p-3 bg-[#ffdad9] rounded-xl">
        //                         <Pill className="w-6 h-6 text-primary" />
        //                     </div>
        //                 </div>
        //                 <h4 className="text-xl font-bold text-foreground mb-1 font-headline">
        //                     Transactions
        //                 </h4>
        //             </div>
        //             <Button
        //                 variant="ghost"
        //                 onClick={handleManageRefills}
        //                 className="mt-4 w-fit p-0 h-auto text-primary font-semibold text-sm flex items-center gap-2 hover:gap-3 transition-all hover:bg-transparent"
        //             >
        //                 Manage refills
        //                 <ArrowRight className="w-4 h-4" />
        //             </Button>
        //         </CardContent>
        //     </Card>
        // </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 h-full items-stretch">

            {cards.map((item, index) => (
                <Card
                    key={index}
                    className="rounded-[5px] shadow-card-lg h-full flex flex-col"
                >
                    <Link href={item.href}>
                    <CardContent className="py-3.75 px-5 h-full flex flex-col">
                        <div className="flex items-start justify-between">
                            <div>
                                <p className="text-sm font-normal text-[#3a3a3a]">
                                    {item.title}
                                </p>
                            </div>

                            <div
                                className={`flex h-13.75 w-14.5 shrink-0 items-center justify-center rounded-[6px] ${item.iconBg}`}
                            >
                                <FileText className={`h-6.5 w-6.5 ${item.iconColor}`} />
                            </div>
                        </div>

                        <div>
                            <h3 className="sm:text-[18px] font-bold leading-tight text-[#222]">
                                {item.heading}
                            </h3>

                            <p className="mt-2 text-sm leading-[1.4] text-[#4a4a4a]">
                                {item.subtext}
                            </p>
                        </div>

                        <div className="mt-auto pt-5">
                            <div className="h-1 w-full overflow-hidden rounded-full bg-[#d9dde3]">
                                <div
                                    className={`h-full ${item.progressColor}`}
                                    style={{ width: item.progress }}
                                />
                            </div>

                            <div className="mt-3 flex items-center justify-between text-[14px] text-[#2f2f2f]">
                                <p className="font-normal">
                                    Active : <span className="font-medium">{item.active}</span>
                                </p>
                                <p className="font-normal">
                                    Inactive : <span className="font-medium">{item.inactive}</span>
                                </p>
                            </div>
                        </div>
                    </CardContent>
                    </Link>
                </Card>
                
            ))}
            {/* Medications Card */}
            {/* <Card className="rounded-xl border border-gray-200 bg-[#f7f7f7] shadow-sm" >
                <CardContent>
                    <div className="flex items-start justify-between">
                        <p className="text-sm font-normal text-[#3a3a3a]">Medications</p>

                        <div className="flex h-13.75 w-14.5 items-center justify-center rounded-xl bg-[#fde8ea]">
                            <FileText className="h-6.5 w-6.5 text-red-500" />
                        </div>
                    </div>

                    <div className="">
                        <h3 className="sm:text-[18px] font-semibold text-[#222]">
                            3 Active Medications
                        </h3>
                        <p className="mt-1 text-sm text-[#4a4a4a]">Next dose in 2 hours</p>
                    </div>

                    <div className="mt-10">
                        <div className="h-1 w-full overflow-hidden rounded-full bg-[#d9dde3]">
                            <div className="h-full w-[73%] bg-red-500" />
                        </div>

                        <div className="mt-5 flex items-center justify-between text-[14px] text-[#2f2f2f]">
                            <p className="font-normal">
                                Active : <span className="font-medium">280</span>
                            </p>
                            <p className="font-normal">
                                Inctive : <span className="font-medium">03</span>
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card> */}


            {/* <Card className="rounded-xl border border-gray-200 bg-[#f7f7f7] shadow-sm">
                <CardContent className="p-6 sm:p-7">
                    <div className="flex items-start justify-between">
                        <p className="text-sm font-normal text-[#3a3a3a]">Test Results</p>

                        <div className="flex h-[68px] w-[68px] items-center justify-center rounded-xl bg-[#f8efdf]">
                            <Pill className="h-8 w-8 text-amber-500" />
                        </div>
                    </div>

                    <div className="mt-8">
                        <h3 className="text-[24px] sm:text-[26px] font-bold text-[#222]">
                            2 New Test Results
                        </h3>
                        <p className="mt-1 text-[18px] text-[#4a4a4a]">Blood work from 05/12</p>
                    </div>

                    <div className="mt-10">
                        <div className="h-[4px] w-full overflow-hidden rounded-full bg-[#d9dde3]">
                            <div className="h-full w-[74%] bg-amber-400" />
                        </div>

                        <div className="mt-5 flex items-center justify-between text-[18px] text-[#2f2f2f]">
                            <p>
                                Active : <span className="font-medium">12</span>
                            </p>
                            <p>
                                Inctive : <span className="font-medium">05</span>
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card> */}
        </div >
    );
}



{/* <div className="flex items-start justify-between">
                            <div>
                                <p className="text-sm font-normal text-[#3a3a3a]">{item.title}</p>
                                <div className="">
                                    <h3 className="sm:text-[18px]  font-bold text-[#222]">
                                        {item.heading}
                                    </h3>
                                    <p className="mt-0 text-sm text-[#4a4a4a]">{item.subtext}</p>
                                </div>
                            </div>
                            <div
                                className={`flex h-13.75 w-14.5 items-center justify-center rounded-[6px] ${item.iconBg}`}
                            >
                                <FileText className={`h-6.5 w-6.5 ${item.iconColor}`} />
                            </div>
                        </div> */}