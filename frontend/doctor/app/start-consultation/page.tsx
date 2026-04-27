// "use client";

// import { useEffect, useState, Suspense } from "react";
// import { useSearchParams, useRouter } from "next/navigation";
// import { Pill, FileUser, Loader2 } from "lucide-react";


// const ConsultationContent = () => {

//     const searchParams = useSearchParams();
//     const router = useRouter();
//     const roomUrl = searchParams.get("room_url");
//     const appointmentId = searchParams.get("appointment_id");

//     const [joined, setJoined] = useState(false);
//     const [chatOpen, setChatOpen] = useState(false);

//     useEffect(() => {
//         const handleMessage = (event: MessageEvent) => {
//             // Whereby sends postMessage events from the iframe
//             if (event.data?.type === "join") {
//                 setJoined(true);
//             }
//             if (event.data?.type === "leave") {
//                 setJoined(false);
//                 setChatOpen(false);
//             }
//             // Fires when chat or people panel opens/closes
//             if (event.data?.type === "chat_toggle" || event.data?.type === "people_toggle") {
//                 setChatOpen(event.data?.open ?? false);
//             }
//         };

//         window.addEventListener("message", handleMessage);
//         return () => window.removeEventListener("message", handleMessage);
//     }, []);

//     if (!roomUrl) {
//         return (
//             <div className="flex h-screen w-full items-center justify-center p-4 text-sm text-destructive">
//                 No Room URL provided.
//             </div>
//         );
//     }

//     return (
//         <div className="relative w-full h-screen">

//             {/* Whereby iframe — full default Whereby UI */}
//             <iframe
//                 src={roomUrl}
//                 allow="camera; microphone; fullscreen; speaker; display-capture"
//                 className="w-full h-full border-none"
//             />

//             {/* Custom buttons — shown only after joining */}
//             {joined && (
//                 <div className={`absolute bottom-1 left-1/2  flex gap-3 z-50 transition-all duration-300 ${chatOpen ? "-translate-x-[150%]" : "-translate-x-[285%]"}`}>

//                     <button className="flex flex-col items-center gap-1.5"
//                         onClick={() => {
//                             window.open(`/appointments/${appointmentId}`, '_blank')
//                         }}
//                     >
//                         <div className="w-12 h-12 bg-[#0000008f] rounded-xl flex items-center justify-center hover:bg-[#000000af] transition-colors">
//                             <FileUser color="#fff" />
//                         </div>
//                         <span className="font-inter font-bold text-xs text-white">Patient</span>
//                     </button>

//                     <button
//                         onClick={() => window.open(`/appointments/${appointmentId}`, "_blank")}
//                         className="flex flex-col items-center gap-1.5"
//                     >
//                         <div className="w-12 h-12 bg-[#0000008f] rounded-xl flex items-center justify-center hover:bg-[#000000af] transition-colors">
//                             <Pill color="#fff" />
//                         </div>
//                         <span className="font-inter font-bold text-xs text-white">Prescribe</span>
//                     </button>

//                 </div>
//             )}
//         </div>
//     );
// };

// export default function StartConsultation() {
//     return (
//         <Suspense
//             fallback={
//                 <div className="flex h-screen w-full items-center justify-center">
//                     <Loader2 className="h-8 w-8 animate-spin text-primary" />
//                 </div>
//             }
//         >
//             <ConsultationContent />
//         </Suspense>
//     );
// }

"use client";

import { useEffect, useState, Suspense } from "react";
import { useSearchParams, useRouter } from "next/navigation";
import { Pill, FileUser, Loader2 } from "lucide-react";



const ConsultationContent = () => {

    const searchParams = useSearchParams();
    const router = useRouter();
    const roomUrl = searchParams.get("room_url");
    const appointmentId = searchParams.get("appointment_id");

    const [joined, setJoined] = useState(false);
    const [chatOpen, setChatOpen] = useState(false);

    useEffect(() => {
        const handleMessage = (event: MessageEvent) => {
            // Whereby sends postMessage events from the iframe
            if (event.data?.type === "join") {
                setJoined(true);
            }
            if (event.data?.type === "leave") {
                setJoined(false);
                setChatOpen(false);
            }
            // Fires when chat or people panel opens/closes
            if (event.data?.type === "chat_toggle" || event.data?.type === "people_toggle") {
                setChatOpen(event.data?.open ?? false);
            }
        };

        window.addEventListener("message", handleMessage);
        return () => window.removeEventListener("message", handleMessage);
    }, []);

    if (!roomUrl) {
        return (
            <div className="flex h-screen w-full items-center justify-center p-4 text-sm text-destructive">
                No Room URL provided.
            </div>
        );
    }

    return (
        <div className="relative w-full h-screen">

            {/* Whereby iframe — full default Whereby UI */}
            <iframe
                src={roomUrl}
                allow="camera; microphone; fullscreen; speaker; display-capture"
                className="w-full h-full border-none"
            />

            {/* Custom buttons — shown only after joining */}
            {joined && (
                <div className={`absolute bottom-1 left-1/2  flex gap-3 z-50 transition-all duration-300 ${chatOpen ? "-translate-x-[150%]" : "-translate-x-[285%]"}`}>

                    <button className="flex flex-col items-center gap-1.5"
                        onClick={() => {
                            window.open(`/appointments/${appointmentId}`, '_blank')
                        }}
                    >
                        <div className="w-12 h-12 bg-[#0000008f] rounded-xl flex items-center justify-center hover:bg-[#000000af] transition-colors">
                            <FileUser color="#fff" />
                        </div>
                        <span className="font-inter font-bold text-xs text-white">Patient</span>
                    </button>

                    <button
                        onClick={() => window.open(`/appointments/${appointmentId}?tab=prescription`, "_blank")}
                        className="flex flex-col items-center gap-1.5"
                    >
                        <div className="w-12 h-12 bg-[#0000008f] rounded-xl flex items-center justify-center hover:bg-[#000000af] transition-colors">
                            <Pill color="#fff" />
                        </div>
                        <span className="font-inter font-bold text-xs text-white">Prescribe</span>
                    </button>

                </div>
            )}
        </div>
    );
};

export default function StartConsultation() {
    return (
        <Suspense
            fallback={
                <div className="flex h-screen w-full items-center justify-center">
                    <Loader2 className="h-8 w-8 animate-spin text-primary" />
                </div>
            }
        >
            <ConsultationContent />
        </Suspense>
    );
}