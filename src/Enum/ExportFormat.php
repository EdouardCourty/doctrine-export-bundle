<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Enum;

enum ExportFormat: string
{
    case CSV = 'csv';
    case JSON = 'json';
    case XML = 'xml';

    public function getExtension(): string
    {
        return match ($this) {
            self::CSV => 'csv',
            self::JSON => 'json',
            self::XML => 'xml',
        };
    }

    public function getMimeType(): string
    {
        return match ($this) {
            self::CSV => 'text/csv',
            self::JSON => 'application/json',
            self::XML => 'application/xml',
        };
    }

    public static function fromString(string $format): self
    {
        return self::from(strtolower($format));
    }
}
