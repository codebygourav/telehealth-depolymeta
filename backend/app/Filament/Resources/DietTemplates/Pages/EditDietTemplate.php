<?php

namespace App\Filament\Resources\DietTemplates\Pages;

use App\Filament\Resources\DietTemplates\DietTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Alignment;

class EditDietTemplate extends EditRecord
{
    protected static string $resource = DietTemplateResource::class;

    protected array $dietChartDays = [];
    protected array $syncPatientPlanIds = [];

    public function getFormActionsAlignment(): Alignment|string
    {
        return Alignment::End;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['diet_chart_payload'] = json_encode(DietTemplateResource::dietChartDataForForm($this->record));

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->dietChartDays = DietTemplateResource::decodeDietChartPayload($data['diet_chart_payload'] ?? $this->data['diet_chart_payload'] ?? null);
        $this->syncPatientPlanIds = $data['sync_patient_plans'] ?? [];
        unset($data['days'], $data['diet_chart_payload'], $data['sync_patient_plans']);

        return $data;
    }

    protected function afterSave(): void
    {
        // First sync the template's own days and meals
        DietTemplateResource::syncDietChart($this->record, $this->dietChartDays);

        // Next, if there are patient plans selected to sync:
        if (!empty($this->syncPatientPlanIds)) {
            $template = $this->record;
            $newDaysData = $this->dietChartDays;

            // Fetch selected plans
            $plans = \App\Models\PatientDietPlan::whereIn('id', $this->syncPatientPlanIds)->get();

            foreach ($plans as $patientDietPlan) {
                // Update overall metadata
                $durationDays = (int) ($template->duration_days ?? 7);
                $startDate = \Carbon\Carbon::parse($patientDietPlan->start_date);
                $endDate = $startDate->copy()->addDays(max(0, $durationDays - 1));

                $patientDietPlan->update([
                    'template_name' => $template->name,
                    'template_description' => $template->description,
                    'diet_category' => $template->diet_category,
                    'patient_type' => $template->patient_type,
                    'daily_calories' => $template->daily_calories,
                    'protein_target' => $template->protein_target,
                    'carbs_limit' => $template->carbs_limit,
                    'salt_limit' => $template->salt_limit,
                    'doctor_remark' => $template->doctor_remark,
                    'allowed_food_notes' => $template->allowed_food_notes,
                    'hydration_advice' => $template->hydration_advice,
                    'exercise_advice' => $template->exercise_advice,
                    'features' => $template->features,
                    'duration_days' => $durationDays,
                    'end_date' => $endDate->toDateString(),
                ]);

                // Sync days and meals
                foreach ($newDaysData as $dayData) {
                    $dayNumber = $dayData['day_number'];
                    $weekDay = $dayData['week_day'];

                    $planDay = \App\Models\PatientDietPlanDay::updateOrCreate(
                        [
                            'patient_diet_plan_id' => $patientDietPlan->id,
                            'day_number' => $dayNumber,
                        ],
                        [
                            'week_day' => $weekDay,
                            'date' => $patientDietPlan->start_date 
                                ? \Carbon\Carbon::parse($patientDietPlan->start_date)->addDays($dayNumber - 1)->toDateString()
                                : null,
                        ]
                    );

                    // Fetch existing meals for this day
                    $existingMeals = \App\Models\PatientDietPlanMeal::where('patient_diet_plan_day_id', $planDay->id)
                        ->orderBy('sort_order')
                        ->get();

                    $newMeals = $dayData['meals'];
                    $maxCount = max(count($existingMeals), count($newMeals));

                    for ($i = 0; $i < $maxCount; $i++) {
                        if (isset($newMeals[$i])) {
                            $mealData = $newMeals[$i];
                            $dbData = [
                                'meal_type' => $mealData['meal_type'],
                                'meal_name' => $mealData['meal_name'],
                                'instructions' => $mealData['instructions'] ?? null,
                                'meal_image' => $mealData['meal_image'] ?? null,
                                'helpful_links' => $mealData['helpful_links'] ?? null,
                                'calories' => $mealData['calories'] ?? null,
                                'protein_grams' => $mealData['protein_grams'] ?? null,
                                'carbs_grams' => $mealData['carbs_grams'] ?? null,
                                'fat_grams' => $mealData['fat_grams'] ?? null,
                                'meal_time' => $mealData['start_time'] ?? null,
                                'sort_order' => $mealData['sort_order'],
                            ];

                            if (isset($existingMeals[$i])) {
                                // Update existing meal
                                $existingMeals[$i]->update($dbData);
                            } else {
                                // Create new meal
                                $dbData['patient_diet_plan_day_id'] = $planDay->id;
                                $dbData['status'] = 'pending';
                                \App\Models\PatientDietPlanMeal::create($dbData);
                            }
                        } else {
                            // Delete excess meal
                            if (isset($existingMeals[$i])) {
                                $existingMeals[$i]->delete();
                            }
                        }
                    }
                }

                // Delete any days that are not in the new template
                $newDayNumbers = collect($newDaysData)->pluck('day_number')->all();
                \App\Models\PatientDietPlanDay::where('patient_diet_plan_id', $patientDietPlan->id)
                    ->whereNotIn('day_number', $newDayNumbers)
                    ->delete();
            }
        }
    }
}
