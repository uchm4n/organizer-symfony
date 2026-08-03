<?php

declare(strict_types=1);

namespace App\Item\Enum;

enum ItemType: int
{
    case Note        = 1;
    case Todo        = 2;
    case Spreadsheet = 3;
    case TaxFiling   = 4;
    case Event       = 5;
    case Document    = 6;
    case Custom      = 99;

    public function label(): string
    {
        return match ($this) {
            self::Note        => 'Note',
            self::Todo        => 'Todo',
            self::Spreadsheet => 'Spreadsheet',
            self::TaxFiling   => 'TaxFiling',
            self::Event       => 'Event',
            self::Document    => 'Document',
            self::Custom      => 'Custom',
        };
    }
}
