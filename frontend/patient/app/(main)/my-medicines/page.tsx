"use client";

import { useState } from "react";
import { MedicineDetailView } from "@/components/pages/my-medicines/MedicineDetailView";
import { MedicineListView } from "@/components/pages/my-medicines/MedicineListView";

const MyMedicines = () => {
    const [selectedMedicineId, setSelectedMedicineId] = useState<string | null>(null);

    if (selectedMedicineId) {
        return (
            <MedicineDetailView
                prescriptionId={selectedMedicineId}
                onBack={() => setSelectedMedicineId(null)}
            />
        );
    }

    return (
        <MedicineListView onViewDetail={(id) => setSelectedMedicineId(id)} />
    );
};

export default MyMedicines;
