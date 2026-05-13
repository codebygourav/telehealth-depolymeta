import { CheckCircle2, GraduationCap } from "lucide-react"
import { Card, CardContent, CardHeader } from '@/components/ui';

const InfoAboutVaccinations = () => {
    return (
        <section className="space-y-8 mt-10">

            <h2 className="font-display text-2xl font-bold text-on-surface flex items-center gap-4">
                <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary shadow-sm">
                    <GraduationCap className="w-5 h-5" />
                </div>
                Information About Vaccinations
            </h2>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                {[
                    { name: 'BCG Vaccine', desc: 'Protects against Tuberculosis. Usually given once at birth.', sideEffects: ['Small scar at site', 'Mild fever'] },
                    { name: 'Polio (OPV/IPV)', desc: 'Essential for preventing poliomyelitis and paralysis.', sideEffects: ['Soreness at site', 'Fussiness'] },
                    { name: 'Hepatitis B', desc: 'Prevents liver infection. Requires a series of 3-4 doses.', sideEffects: ['Mild local pain', 'Low-grade fever'] },
                ].map((v) => (
                    <Card key={v.name} className="rounded-lg p-4 sm:p-5 md:p-6">
                        <CardHeader className="px-0" >
                            <div className="flex items-center gap-3 mb-2">
                                <CheckCircle2 className="w-6 h-6 text-primary/60" />
                                <h3 className="font-semibold text-[#1F1E1E] text-lg">{v.name}</h3>
                            </div>
                            <p className="text-sm text-[#4D4D4D]">{v.desc}</p>
                        </CardHeader>
                        <CardContent className="space-y-3 px-0 mt-2">
                            <p className="text-[10px] font-black text-on-surface-variant uppercase tracking-widest">Side Effects:</p>
                            <ul className="text-xs font-semibold text-secondary space-y-1">
                                {v.sideEffects.map((eff) => (
                                    <li key={eff} className="flex items-center gap-2 text-[#4D4D4D]">
                                        <div className="w-1.5 h-1.5 rounded-full bg-primary/30" />
                                        {eff}
                                    </li>
                                ))}
                            </ul>
                        </CardContent>
                    </Card>
                ))}
            </div>

            {/* FAQ Section */}
            <div className="bg-white rounded-md border-light-gray overflow-hidden shadow-sm">
                <div className="p-5 border-b border-outline-variant bg-[#F5F6F8]">
                    <h3 className="text-lg font-semibold text-[#1F1E1E]">General Vaccination FAQs</h3>
                </div>
                <div className="divide-y divide-outline-variant/30">
                    {[
                        { q: 'Why are multiple doses needed?', a: 'Some vaccines require multiple doses to build complete immunity and ensure long-term protection.' },
                        { q: 'What if my baby has a slight cold?', a: "Minor illnesses like a cold usually aren't reasons to delay vaccination, but consult your pediatrician first." },
                        { q: 'Are vaccines safe?', a: 'Yes, vaccines undergo rigorous safety testing and monitoring by global health authorities.' },
                    ].map((faq) => (
                        <div key={faq.q} className="p-5 hover:bg-surface-container-lowest transition-colors">
                            <p className="font-semibold text-[#1F1E1E] text-sm mb-2">{faq.q}</p>
                            <p className="text-sm text-[#4D4D4D]">{faq.a}</p>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    )
}

export default InfoAboutVaccinations