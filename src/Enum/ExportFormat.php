<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Enum;

enum ExportFormat: string
{
    case CSV = 'csv';
    case JSON = 'json';
    case XML = 'xml';
    case GOOGLE_SHEETS = 'google_sheets';

    public function getExtension(): string
    {
        return match ($this) {
            self::CSV => 'csv',
            self::JSON => 'json',
            self::XML => 'xml',
            self::GOOGLE_SHEETS => 'gsheet',
        };
    }

    public function getMimeType(): string
    {
        return match ($this) {
            self::CSV => 'text/csv',
            self::JSON => 'application/json',
            self::XML => 'application/xml',
            self::GOOGLE_SHEETS => 'application/vnd.google-apps.spreadsheet',
        };
    }

    public static function fromString(string $format): self
    {
        return self::from(strtolower($format));
    }
}
