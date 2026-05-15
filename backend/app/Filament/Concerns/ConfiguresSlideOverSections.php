<?php

namespace App\Filament\Concerns;

use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;

trait ConfiguresSlideOverSections
{
    /**
     * @param  array<int, Component>  $components
     * @return array<int, Group>
     */
    protected static function wrapSlideOverForm(array $components): array
    {
        return [
            Group::make($components)
                ->columnSpanFull()
                ->extraAttributes(['class' => 'vaccination-accordion-form']),
        ];
    }

    /**
     * @param  array<int, Component>  $schema
     */
    protected static function slideOverSection(
        string $label,
        array $schema,
        ?string $description = null,
        bool $collapsedByDefault = true,
        ?string $icon = null,
    ): Section {
        $section = Section::make($label)
            ->schema(static::slideOverFields($schema))
            ->columns(1)
            ->columnSpanFull()
            ->collapsible()
            ->compact()
            ->extraAttributes([
                'class' => 'vaccination-accordion-section',
            ]);

        if ($icon) {
            $section->icon($icon);
        } else {
            $section->icon('heroicon-o-rectangle-stack');
        }

        if ($collapsedByDefault) {
            $section->collapsed();
        }

        if ($description !== null) {
            $section->description($description);
        }

        return $section;
    }

    /**
     * @param  array<int, Component>  $schema
     * @return array<int, Component>
     */
    protected static function slideOverFields(array $schema): array
    {
        return array_map(function (Component $field): Component {
            if (method_exists($field, 'columnSpanFull')) {
                $field->columnSpanFull();
            }

            return $field;
        }, $schema);
    }
}
