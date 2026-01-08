<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Exception;

use Ecourty\DoctrineExportBundle\Enum\GoogleSheetsOperation;

/**
 * Exception thrown when Google Sheets API operations fail.
 */
class GoogleSheetsException extends ExportException
{
    public static function missingDependency(): self
    {
        return new self(
            'Google API Client library is not installed. ' .
            'Run: composer require google/apiclient'
        );
    }

    public static function invalidCredentials(string $path): self
    {
        return new self(
            sprintf('Google API credentials file not found or invalid: %s', $path)
        );
    }

    public static function apiError(GoogleSheetsOperation $operation, \Throwable $previous): self
    {
        return new self(
            sprintf('Google Sheets API error during %s: %s', $operation->value, $previous->getMessage()),
            0,
            $previous
        );
    }
}
