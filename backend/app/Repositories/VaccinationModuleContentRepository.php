<?php

namespace App\Repositories;

use App\Models\VaccinationClinicalInsight;
use App\Models\VaccinationGeneralFaq;
use Illuminate\Support\Collection;

class VaccinationModuleContentRepository
{
    public function getActiveGeneralFaqs(): Collection
    {
        return VaccinationGeneralFaq::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get();
    }

    public function getActiveClinicalInsight(): VaccinationClinicalInsight
    {
        return VaccinationClinicalInsight::query()
            ->active()
            ->latest()
            ->first()
            ?? $this->defaultClinicalInsight();
    }

    /**
     * @return array{faqs: Collection<int, VaccinationGeneralFaq>, clinical_insight: VaccinationClinicalInsight}
     */
    public function getOverviewSupplementaryContent(): array
    {
        return [
            'faqs' => $this->getActiveGeneralFaqs(),
            'clinical_insight' => $this->getActiveClinicalInsight(),
        ];
    }

    protected function defaultClinicalInsight(): VaccinationClinicalInsight
    {
        return new VaccinationClinicalInsight([
            'title' => 'Clinical Insight',
            'message' => 'Vaccination schedules are based on international pediatric standards. If you miss a dose, please contact your pediatrician immediately to reschedule. You can add personal notes to each log for tracking side effects or allergic reactions.',
            'is_active' => true,
        ]);
    }
}
