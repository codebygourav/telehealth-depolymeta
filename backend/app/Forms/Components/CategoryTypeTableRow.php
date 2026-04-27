<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;

class CategoryTypeTableRow extends Field
{
    protected string $view = 'filament.components.category-type-table-row';

    protected function setUp(): void
    {
        parent::setUp();

        $this->dehydrated(false);
    }
}
