<?php

namespace Tests\Unit;

use App\Support\PrescriptionSpeech;
use PHPUnit\Framework\TestCase;

class PrescriptionSpeechTest extends TestCase
{
    public function test_default_template_keeps_dynamic_placeholders(): void
    {
        $template = PrescriptionSpeech::defaultTemplate();

        $this->assertStringContainsString('{item_number}', $template);
        $this->assertStringContainsString('{medicine_name}', $template);
        $this->assertStringContainsString('{schedule_sentence}', $template);
    }

    public function test_placeholders_match_supported_template_tokens(): void
    {
        $this->assertNotContains('{medicine_label}', PrescriptionSpeech::placeholders());
        $this->assertContains('{medicine_type}', PrescriptionSpeech::placeholders());
        $this->assertContains('{max_doses_sentence}', PrescriptionSpeech::placeholders());
    }

    public function test_placeholders_help_text_matches_single_template_copy(): void
    {
        $this->assertSame(
            'Available placeholders: {item_number}, {medicine_name}, {medicine_type}, {dosage}, {dosage_sentence}, {use_type_label}, {frequency_label}, {schedule_sentence}, {timing_list}, {timing_sentence}, {meal_timing_label}, {meal_timing_sentence}, {duration_label}, {duration_sentence}, {instructions}, {instructions_sentence}, {take_when}, {reason_sentence}, {min_gap}, {min_gap_sentence}, {max_doses_per_day}, {max_doses_sentence}. Free-text medicine names and instructions are spoken exactly as saved.',
            PrescriptionSpeech::placeholdersHelpText()
        );
    }
}
