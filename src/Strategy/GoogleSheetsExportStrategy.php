<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Strategy;

use Ecourty\DoctrineExportBundle\Contract\DoctrineExporterInterface;
use Ecourty\DoctrineExportBundle\Contract\ExportStrategyInterface;
use Ecourty\DoctrineExportBundle\Enum\ExportFormat;
use Ecourty\DoctrineExportBundle\Enum\GoogleSheetsOperation;
use Ecourty\DoctrineExportBundle\Exception\GoogleSheetsException;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\Permission;
use Google\Service\Sheets;
use Google\Service\Sheets\BatchUpdateSpreadsheetRequest;
use Google\Service\Sheets\CellData;
use Google\Service\Sheets\DimensionRange;
use Google\Service\Sheets\ExtendedValue;
use Google\Service\Sheets\GridRange;
use Google\Service\Sheets\InsertDimensionRequest;
use Google\Service\Sheets\Request;
use Google\Service\Sheets\RowData;
use Google\Service\Sheets\Spreadsheet;
use Google\Service\Sheets\UpdateCellsRequest;

class GoogleSheetsExportStrategy implements ExportStrategyInterface
{
    use ValueStringifierTrait;

    private const array SCOPES = [
        Sheets::SPREADSHEETS,
        Drive::DRIVE_FILE,
    ];

    private ?Sheets $sheetsService = null;
    private ?Drive $driveService = null;
    private ?string $spreadsheetId = null;
    private ?string $spreadsheetUrl = null;

    /** @var array<int, array<int, string>> */
    private array $rowBuffer = [];

    /** @var array<int, string> */
    private array $headerFields = [];

    /** @var array<string, mixed> */
    private array $exportOptions = [];

    private int $nextRowIndex = 1; // Start at row 1 (0 is header)

    public function __construct(
        private readonly string $credentialsPath,
        private readonly int $batchSize = 10000,
    ) {
        $this->validateDependencies();
    }

    public function getFormat(): ExportFormat
    {
        return ExportFormat::GOOGLE_SHEETS;
    }

    public function prepare(array $fields, array $options): void
    {
        $this->headerFields = $fields;
        $this->exportOptions = $options;
        $this->initializeGoogleClient($options);
        $this->createSpreadsheet($options);
        $this->writeHeader();
    }

    /**
     * @param array<int, string> $fields
     */
    public function generateHeader(array $fields): ?string
    {
        // Headers are written in prepare(), return empty string
        return '';
    }

    /**
     * @param array<string, mixed> $data
     */
    public function formatRow(array $data): string
    {
        // Convert data to row values (maintain field order)
        $rowValues = [];
        foreach ($this->headerFields as $field) {
            $rowValues[] = $this->valueToString($data[$field] ?? null);
        }

        $this->rowBuffer[] = $rowValues;

        // Flush buffer if batch size reached
        if (\count($this->rowBuffer) >= $this->batchSize) {
            $this->flushBuffer();
        }

        // Return empty string (actual writing happens in batch)
        return '';
    }

    public function generateFooter(): ?string
    {
        // Footer is handled in finalize()
        return '';
    }

    public function finalize(): void
    {
        // Flush remaining buffer
        if (!empty($this->rowBuffer)) {
            $this->flushBuffer();
        }

        // Share with email from options if provided
        $shareEmail = $this->exportOptions[DoctrineExporterInterface::OPTION_GOOGLE_SHEETS_SHARE_EMAIL] ?? null;
        if (\is_string($shareEmail)) {
            $this->shareWithEmail($shareEmail);
        }
    }

    public function getExportUrl(): ?string
    {
        return $this->spreadsheetUrl;
    }

    public function supportsFileExport(): bool
    {
        return false;
    }

    private function validateDependencies(): void
    {
        if (!class_exists(Client::class)) {
            throw GoogleSheetsException::missingDependency();
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    private function initializeGoogleClient(array $options): void
    {
        $credentials = $this->resolveCredentials();

        // Subject email is required for domain-wide delegation
        $subjectEmail = $options[DoctrineExporterInterface::OPTION_GOOGLE_SHEETS_SUBJECT_EMAIL] ?? null;
        if (!\is_string($subjectEmail) || empty($subjectEmail)) {
            throw new \InvalidArgumentException(
                'Subject email is required for Google Sheets export with service account. ' .
                'Set the option: DoctrineExporterInterface::OPTION_GOOGLE_SHEETS_SUBJECT_EMAIL'
            );
        }

        try {
            $client = new Client();
            $client->setAuthConfig($credentials);
            $client->addScope(self::SCOPES);
            $client->setSubject($subjectEmail);

            $this->sheetsService = new Sheets($client);
            $this->driveService = new Drive($client);
        } catch (\Exception $e) {
            throw GoogleSheetsException::apiError(GoogleSheetsOperation::CLIENT_INITIALIZATION, $e);
        }
    }

    /**
     * @return string|array<string, mixed>
     */
    private function resolveCredentials(): string|array
    {
        // Try as file path first
        if (file_exists($this->credentialsPath)) {
            return $this->credentialsPath;
        }

        // Try as JSON string
        try {
            $decoded = json_decode($this->credentialsPath, true, 512, JSON_THROW_ON_ERROR);
            // Ensure it's an associative array (not a list)
            if (\is_array($decoded) && !empty($decoded) && !array_is_list($decoded)) {
                return $decoded; // @phpstan-ignore-line return.type
            }
        } catch (\JsonException) {
            // Not valid JSON, continue to error
        }

        throw GoogleSheetsException::invalidCredentials($this->credentialsPath);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function createSpreadsheet(array $options): void
    {
        try {
            $title = $options[DoctrineExporterInterface::OPTION_SPREADSHEET_TITLE] ?? 'Export ' . date('Y-m-d H:i:s');

            $spreadsheet = new Spreadsheet([
                'properties' => [
                    'title' => $title,
                ],
            ]);

            assert(null !== $this->sheetsService);
            $spreadsheet = $this->sheetsService->spreadsheets->create($spreadsheet, [
                'fields' => 'spreadsheetId,spreadsheetUrl',
            ]);

            $this->spreadsheetId = $spreadsheet->getSpreadsheetId();
            $this->spreadsheetUrl = $spreadsheet->getSpreadsheetUrl();
        } catch (\Exception $e) {
            throw GoogleSheetsException::apiError(GoogleSheetsOperation::SPREADSHEET_CREATION, $e);
        }
    }

    private function writeHeader(): void
    {
        try {
            $headerCells = [];
            foreach ($this->headerFields as $header) {
                $cellData = new CellData();
                $cellValue = new ExtendedValue();
                $cellValue->setStringValue($header);
                $cellData->setUserEnteredValue($cellValue);
                $headerCells[] = $cellData;
            }

            $headerRow = new RowData();
            $headerRow->setValues($headerCells);

            $this->writeRowsToSpreadsheet(0, [$headerRow], \count($this->headerFields), true);
        } catch (\Exception $e) {
            throw GoogleSheetsException::apiError(GoogleSheetsOperation::HEADER_WRITING, $e);
        }
    }

    private function flushBuffer(): void
    {
        if (empty($this->rowBuffer)) {
            return;
        }

        try {
            $rowsData = [];
            foreach ($this->rowBuffer as $row) {
                $cellsData = [];
                foreach ($row as $cellValue) {
                    $cellData = new CellData();
                    $extendedValue = new ExtendedValue();
                    $extendedValue->setStringValue($cellValue);
                    $cellData->setUserEnteredValue($extendedValue);
                    $cellsData[] = $cellData;
                }

                $rowData = new RowData();
                $rowData->setValues($cellsData);
                $rowsData[] = $rowData;
            }

            $this->writeRowsToSpreadsheet($this->nextRowIndex, $rowsData, \count($this->headerFields));

            $this->nextRowIndex += \count($this->rowBuffer);
            $this->rowBuffer = [];
        } catch (\Exception $e) {
            throw GoogleSheetsException::apiError(GoogleSheetsOperation::BATCH_WRITE, $e);
        }
    }

    private function shareWithEmail(string $email): void
    {
        try {
            $permission = new Permission([
                'type' => 'user',
                'role' => 'writer',
                'emailAddress' => $email,
            ]);

            assert(null !== $this->driveService);
            $this->driveService->permissions->create(
                $this->spreadsheetId,
                $permission,
                ['fields' => 'id']
            );
        } catch (\Exception $e) {
            throw GoogleSheetsException::apiError(GoogleSheetsOperation::PERMISSION_SHARING, $e);
        }
    }

    /**
     * @param array<int, RowData> $rows
     */
    private function writeRowsToSpreadsheet(int $startIndex, array $rows, int $columnCount, bool $isHeader = false): void
    {
        $endIndex = $startIndex + \count($rows);
        $requests = [];

        // Expand columns if needed for header (default sheet has 26 columns)
        if ($isHeader && $columnCount > 26) {
            $insertDimension = new InsertDimensionRequest();
            $dimensionRange = new DimensionRange();
            $dimensionRange->setDimension('COLUMNS');
            $dimensionRange->setStartIndex(0);
            $dimensionRange->setEndIndex($columnCount);
            $insertDimension->setRange($dimensionRange);

            $insertColumnsRequest = new Request();
            $insertColumnsRequest->setInsertDimension($insertDimension);
            $requests[] = $insertColumnsRequest;
        }

        // Insert rows
        $insertDimension = new InsertDimensionRequest();
        $dimensionRange = new DimensionRange();
        $dimensionRange->setDimension('ROWS');
        $dimensionRange->setStartIndex($startIndex);
        $dimensionRange->setEndIndex($endIndex);
        $insertDimension->setRange($dimensionRange);

        $insertRowsRequest = new Request();
        $insertRowsRequest->setInsertDimension($insertDimension);
        $requests[] = $insertRowsRequest;

        // Update cells with data
        $updateCells = new UpdateCellsRequest();
        $gridRange = new GridRange();
        $gridRange->setStartRowIndex($startIndex);
        $gridRange->setEndRowIndex($endIndex);
        $gridRange->setStartColumnIndex(0);
        $gridRange->setEndColumnIndex($columnCount);
        $updateCells->setRange($gridRange);
        $updateCells->setFields('userEnteredValue');
        $updateCells->setRows($rows);

        $updateCellsRequest = new Request();
        $updateCellsRequest->setUpdateCells($updateCells);
        $requests[] = $updateCellsRequest;

        // Execute batch update
        $batchUpdateRequest = new BatchUpdateSpreadsheetRequest();
        $batchUpdateRequest->setRequests($requests);

        assert(null !== $this->sheetsService);
        $this->sheetsService->spreadsheets->batchUpdate($this->spreadsheetId, $batchUpdateRequest);
    }
}
