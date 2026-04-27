<?php

namespace App\Filament\Resources\Payments\Schemas;

use Filament\Schemas\Schema;
use Filament\Infolists\Components\ViewEntry;

class PaymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                ViewEntry::make('payment_details')
                    ->view('filament.payments.payment-view')
                    ->state(fn($record) => $record)
                    ->columnSpanFull(),
            ]);
    }
}
