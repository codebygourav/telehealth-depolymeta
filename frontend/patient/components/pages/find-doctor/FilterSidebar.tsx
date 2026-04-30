import { Filter } from "lucide-react";
import type { ConsultationType } from "@/types/browse-doctors";
import type { SpecialtyOption } from "@/types/departments";
import { RadioField } from "@/components/custom/RadioField";
import SelectField from "@/components/custom/SelectField";

interface FilterSidebarProps {
    specialty: string;
    consultationType: ConsultationType;
    specialtyOptions: SpecialtyOption[];
    onSpecialtyChange: (value: string) => void;
    onConsultationTypeChange: (value: ConsultationType) => void;
    onClearFilters: () => void;
}

const consultationTypeOptions = [
    { value: "all", label: "All Types" },
    { value: "video", label: "Video Consultation" },
    { value: "in-person", label: "In-person Consultation" },
];

const FilterSidebar = ({
    specialty,
    consultationType,
    specialtyOptions,
    onSpecialtyChange,
    onConsultationTypeChange,
    onClearFilters,
}: FilterSidebarProps) => {
    return (
        <aside className="w-full lg:w-72 flex-shrink-0">
            <div className="sticky top-28 space-y-8">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Filter className="w-5 h-5 text-on-surface-variant" />
                        <h2 className="font-headline font-bold text-xl tracking-tight">
                            Refine Search
                        </h2>
                    </div>

                    <button
                        onClick={onClearFilters}
                        className="text-sm text-surface-tint font-semibold hover:underline transition-colors"
                    >
                        Clear all
                    </button>
                </div>

                <div className="space-y-6">
                    <div className="space-y-3">
                        <label className="block text-xs uppercase tracking-widest font-bold text-on-surface-variant/70">
                            Specialty
                        </label>

                        <SelectField
                            name="specialty"
                            value={specialty}
                            onChange={onSpecialtyChange}
                            options={specialtyOptions}
                            placeholder="Select specialty"
                            className="w-full!"
                            triggerClassName="w-full !h-auto bg-transparent border border-light-gray rounded-md px-5 py-3.5 text-sm font-medium"
                        />
                    </div>

                    <RadioField
                        label="Consultation Type"
                        value={consultationType}
                        onChange={(value) =>
                            onConsultationTypeChange(value as ConsultationType)
                        }
                        options={consultationTypeOptions}
                        direction="column"
                        labelClass="text-xs uppercase tracking-widest font-bold text-on-surface-variant/70"
                    />
                </div>
            </div>
        </aside>
    );
};

export default FilterSidebar;