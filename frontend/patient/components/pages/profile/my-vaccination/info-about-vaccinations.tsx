import {  ChevronDown,  MessageCircleQuestion } from "lucide-react"
import { useState } from "react";
import { usePatientVaccinations } from "@/queries/usePatientVaccinations";

const InfoAboutVaccinations = () => {

    const [openFaq, setOpenFaq] = useState<number | null>(0);

    const { data, isLoading, error } = usePatientVaccinations();

    const vaccinationFaqs = data?.data?.faqs || [];

    return (
        <section className="space-y-8 mt-10">

            <h2 className="font-display text-2xl font-bold text-on-surface flex items-center gap-4">
                <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary shadow-sm">
                    <MessageCircleQuestion className="w-5 h-5" />
                </div>
                FAQs?
            </h2>
            
            {/* FAQ Section */}
            <div className="bg-white rounded-md border-light-gray overflow-hidden shadow-sm">
                <div className="p-5 border-b border-outline-variant bg-[#F5F6F8]">
                    <h3 className="text-lg font-semibold text-[#1F1E1E]">
                        General Vaccination FAQs
                    </h3>
                </div>

                <div className="divide-y divide-outline-variant/30">
                    {vaccinationFaqs.map((faq: any, index: number) => {
                        const isOpen = openFaq === index;

                        return (
                            <div key={faq.id || index}>
                                <button
                                    type="button"
                                    onClick={() =>
                                        setOpenFaq(isOpen ? null : index)
                                    }
                                    className="w-full p-5 flex items-center justify-between gap-4 hover:bg-surface-container-lowest transition-colors text-left"
                                >
                                    <span className="font-semibold text-[#1F1E1E] text-sm">
                                        {faq.question}
                                    </span>

                                    <ChevronDown
                                        className={`w-6 h-6  shrink-0 transition-transform duration-300 ${isOpen ? "rotate-180" : ""
                                            }`}
                                    />
                                </button>

                                <div
                                    className={`grid transition-all duration-300 ease-in-out ${isOpen
                                            ? "grid-rows-[1fr]"
                                            : "grid-rows-[0fr]"
                                        }`}
                                >
                                    <div className="overflow-hidden">
                                        <div className="px-5 pb-5">
                                            <p className="text-sm text-[#4D4D4D] leading-relaxed">
                                                {faq.answer}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>
        </section>
    )
}

export default InfoAboutVaccinations