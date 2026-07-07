<?php

namespace Tests\Feature;

use App\Filament\Resources\Doctors\Schemas\DoctorForm;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Test mutateFormDataBeforeSave handles structured repeater mode correctly.
     */
    public function test_mutates_structured_repeater_mode_correctly(): void
    {
        $input = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'education_info_mode' => 'repeater',
            'education_info_repeater' => [
                [
                    'degree' => 'MBBS',
                    'institution' => 'CMC',
                    'completion_year' => '2015',
                ]
            ],
            'education_info_editor' => '<p>ignored html</p>',
        ];

        $output = DoctorForm::mutateFormDataBeforeSave($input);

        $this->assertArrayNotHasKey('education_info_mode', $output);
        $this->assertArrayNotHasKey('education_info_repeater', $output);
        $this->assertArrayNotHasKey('education_info_editor', $output);
        
        $this->assertArrayHasKey('education_info', $output);
        $this->assertCount(1, $output['education_info']);
        $this->assertEquals('MBBS', $output['education_info'][0]['degree']);
    }

    /**
     * Test mutateFormDataBeforeSave handles free-text editor mode correctly.
     */
    public function test_mutates_free_text_editor_mode_correctly(): void
    {
        $input = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'education_info_mode' => 'editor',
            'education_info_repeater' => [
                [
                    'degree' => 'MBBS',
                    'institution' => 'CMC',
                ]
            ],
            'education_info_editor' => '<p>custom html content</p>',
        ];

        $output = DoctorForm::mutateFormDataBeforeSave($input);

        $this->assertArrayNotHasKey('education_info_mode', $output);
        $this->assertArrayNotHasKey('education_info_repeater', $output);
        $this->assertArrayNotHasKey('education_info_editor', $output);
        
        $this->assertArrayHasKey('education_info', $output);
        $this->assertCount(1, $output['education_info']);
        $this->assertTrue($output['education_info'][0]['is_free_text']);
        $this->assertEquals('<p>custom html content</p>', $output['education_info'][0]['html']);
    }

    /**
     * Test mutateFormDataBeforeFill handles structured repeater mode correctly.
     */
    public function test_mutates_structured_repeater_mode_before_fill(): void
    {
        $input = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'education_info' => [
                [
                    'degree' => 'MBBS',
                    'institution' => 'CMC',
                    'completion_year' => '2015',
                ]
            ],
        ];

        $output = DoctorForm::mutateFormDataBeforeFill($input);

        $this->assertEquals('repeater', $output['education_info_mode']);
        $this->assertCount(1, $output['education_info_repeater']);
        $this->assertEquals('MBBS', $output['education_info_repeater'][0]['degree']);
        $this->assertEquals('', $output['education_info_editor']);
    }

    /**
     * Test mutateFormDataBeforeFill handles free-text editor mode correctly.
     */
    public function test_mutates_free_text_editor_mode_before_fill(): void
    {
        $input = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'education_info' => [
                [
                    'is_free_text' => true,
                    'html' => '<p>custom html content</p>',
                ]
            ],
        ];

        $output = DoctorForm::mutateFormDataBeforeFill($input);

        $this->assertEquals('editor', $output['education_info_mode']);
        $this->assertEquals('<p>custom html content</p>', $output['education_info_editor']);
        $this->assertEquals([], $output['education_info_repeater']);
    }
}
