<?php

namespace Database\Seeders;

use App\Models\DietTemplate;
use App\Models\Doctor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DietTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $doctor = Doctor::query()->first();

        if (! $doctor) {
            $this->command?->warn('DietTemplateSeeder skipped: no doctor found. Run DoctorSeeder first.');

            return;
        }

        foreach ($this->templates() as $templateData) {
            DB::transaction(function () use ($doctor, $templateData): void {
                $template = DietTemplate::updateOrCreate(
                    [
                        'doctor_id' => $doctor->id,
                        'name' => $templateData['name'],
                    ],
                    [
                        'description' => $templateData['description'],
                        'duration_days' => $templateData['duration_days'],
                        'restrictions' => $templateData['restrictions'],
                        'notes' => $templateData['notes'],
                        'is_active' => true,
                    ]
                );

                $template->days()->delete();

                foreach ($templateData['days'] as $dayData) {
                    $day = $template->days()->create([
                        'day_number' => $dayData['day_number'],
                        'week_day' => $dayData['week_day'],
                    ]);

                    foreach ($dayData['meals'] as $sortOrder => $mealData) {
                        $day->meals()->create(array_merge($mealData, [
                            'sort_order' => $sortOrder + 1,
                        ]));
                    }
                }
            });
        }
    }

    private function templates(): array
    {
        return [
            [
                'name' => 'Balanced Recovery Diet - 7 Days',
                'description' => 'General balanced meal chart for recovery and daily wellness.',
                'duration_days' => 7,
                'restrictions' => 'Avoid fried foods, sugary beverages, and heavy late-night meals.',
                'notes' => 'Adjust portions based on patient age, weight, diagnosis, and allergies.',
                'days' => $this->weeklyDays([
                    ['meal_type' => 'MORNING', 'meal_name' => 'Warm water with soaked almonds', 'instructions' => 'Serve 4 soaked almonds with lukewarm water.', 'calories' => 90, 'protein_grams' => 3, 'carbs_grams' => 4, 'fat_grams' => 7, 'start_time' => '07:00'],
                    ['meal_type' => 'BREAKFAST', 'meal_name' => 'Vegetable oats with curd', 'instructions' => 'Use seasonal vegetables and plain curd.', 'calories' => 320, 'protein_grams' => 14, 'carbs_grams' => 48, 'fat_grams' => 8, 'start_time' => '08:30'],
                    ['meal_type' => 'LUNCH', 'meal_name' => 'Dal, rice, roti, salad, and vegetables', 'instructions' => 'Use less oil and include one bowl of salad.', 'calories' => 560, 'protein_grams' => 22, 'carbs_grams' => 82, 'fat_grams' => 14, 'start_time' => '13:00'],
                    ['meal_type' => 'EVENING_SNACK', 'meal_name' => 'Fruit bowl with roasted chana', 'instructions' => 'Prefer apple, papaya, guava, or seasonal fruit.', 'calories' => 210, 'protein_grams' => 8, 'carbs_grams' => 36, 'fat_grams' => 4, 'start_time' => '17:00'],
                    ['meal_type' => 'DINNER', 'meal_name' => 'Khichdi with vegetable soup', 'instructions' => 'Keep dinner light and low spice.', 'calories' => 430, 'protein_grams' => 17, 'carbs_grams' => 66, 'fat_grams' => 10, 'start_time' => '20:00'],
                ]),
            ],
            [
                'name' => 'Diabetes Friendly Diet - 7 Days',
                'description' => 'Low glycemic meal chart with steady carbohydrate distribution.',
                'duration_days' => 7,
                'restrictions' => 'Avoid sugar, fruit juices, refined flour, sweets, and sweetened tea or coffee.',
                'notes' => 'Monitor blood glucose and adjust carbohydrates per doctor guidance.',
                'days' => $this->weeklyDays([
                    ['meal_type' => 'MORNING', 'meal_name' => 'Methi water and walnuts', 'instructions' => 'Use unsweetened methi water with 2 walnuts.', 'calories' => 110, 'protein_grams' => 3, 'carbs_grams' => 5, 'fat_grams' => 9, 'start_time' => '07:00'],
                    ['meal_type' => 'BREAKFAST', 'meal_name' => 'Besan chilla with mint curd', 'instructions' => 'Prepare with minimal oil and no sweet chutney.', 'calories' => 300, 'protein_grams' => 18, 'carbs_grams' => 34, 'fat_grams' => 10, 'start_time' => '08:30'],
                    ['meal_type' => 'MID_MEAL', 'meal_name' => 'Cucumber and sprouts salad', 'instructions' => 'Add lemon and roasted cumin.', 'calories' => 120, 'protein_grams' => 8, 'carbs_grams' => 17, 'fat_grams' => 2, 'start_time' => '11:00'],
                    ['meal_type' => 'LUNCH', 'meal_name' => 'Multigrain roti, dal, paneer, and salad', 'instructions' => 'Limit rice and keep plate half vegetables or salad.', 'calories' => 520, 'protein_grams' => 28, 'carbs_grams' => 58, 'fat_grams' => 18, 'start_time' => '13:00'],
                    ['meal_type' => 'DINNER', 'meal_name' => 'Grilled paneer with vegetable soup', 'instructions' => 'Prefer early dinner and avoid dessert.', 'calories' => 390, 'protein_grams' => 25, 'carbs_grams' => 28, 'fat_grams' => 20, 'start_time' => '19:30'],
                ]),
            ],
            [
                'name' => 'Pregnancy Nutrition Diet - 7 Days',
                'description' => 'Nutrient-dense meal chart for pregnancy support.',
                'duration_days' => 7,
                'restrictions' => 'Avoid unpasteurized dairy, raw sprouts, excess caffeine, and high-mercury fish.',
                'notes' => 'Confirm trimester-specific changes, supplements, and medical restrictions with the doctor.',
                'days' => $this->weeklyDays([
                    ['meal_type' => 'MORNING', 'meal_name' => 'Milk with dates and soaked nuts', 'instructions' => 'Use pasteurized milk and 1-2 dates.', 'calories' => 240, 'protein_grams' => 9, 'carbs_grams' => 28, 'fat_grams' => 11, 'start_time' => '07:00'],
                    ['meal_type' => 'BREAKFAST', 'meal_name' => 'Vegetable poha with curd', 'instructions' => 'Add peanuts and vegetables for protein and fiber.', 'calories' => 380, 'protein_grams' => 13, 'carbs_grams' => 58, 'fat_grams' => 12, 'start_time' => '08:30'],
                    ['meal_type' => 'MID_MEAL', 'meal_name' => 'Seasonal fruit and coconut water', 'instructions' => 'Avoid fruit juices and use whole fruit.', 'calories' => 190, 'protein_grams' => 3, 'carbs_grams' => 42, 'fat_grams' => 1, 'start_time' => '11:00'],
                    ['meal_type' => 'LUNCH', 'meal_name' => 'Roti, dal, rice, leafy vegetables, and salad', 'instructions' => 'Include leafy greens and one protein source.', 'calories' => 650, 'protein_grams' => 27, 'carbs_grams' => 92, 'fat_grams' => 18, 'start_time' => '13:00'],
                    ['meal_type' => 'DINNER', 'meal_name' => 'Paneer vegetable pulao with raita', 'instructions' => 'Keep spices moderate and use fresh curd.', 'calories' => 540, 'protein_grams' => 24, 'carbs_grams' => 68, 'fat_grams' => 19, 'start_time' => '20:00'],
                ]),
            ],
        ];
    }

    private function weeklyDays(array $meals): array
    {
        $weekDays = [
            'MONDAY',
            'TUESDAY',
            'WEDNESDAY',
            'THURSDAY',
            'FRIDAY',
            'SATURDAY',
            'SUNDAY',
        ];

        return array_map(
            fn (string $weekDay, int $index): array => [
                'day_number' => $index + 1,
                'week_day' => $weekDay,
                'meals' => $this->rotateMeals($meals, $index),
            ],
            $weekDays,
            array_keys($weekDays)
        );
    }

    private function rotateMeals(array $meals, int $offset): array
    {
        $rotated = $meals;

        foreach ($rotated as $index => $meal) {
            if (in_array($meal['meal_type'], ['LUNCH', 'DINNER'], true)) {
                $rotated[$index]['meal_name'] = $offset % 2 === 0
                    ? $meal['meal_name']
                    : $this->alternateMealName($meal['meal_type'], $meal['meal_name']);
            }
        }

        return $rotated;
    }

    private function alternateMealName(string $mealType, string $default): string
    {
        return match ($mealType) {
            'LUNCH' => 'Roti, dal, mixed vegetables, curd, and salad',
            'DINNER' => 'Vegetable daliya with clear soup',
            default => $default,
        };
    }
}
