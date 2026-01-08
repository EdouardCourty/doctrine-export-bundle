<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Enum;

enum GoogleSheetsOperation: string
{
    case CLIENT_INITIALIZATION = 'client initialization';
    case SPREADSHEET_CREATION = 'spreadsheet creation';
    case HEADER_WRITING = 'header writing';
    case BATCH_WRITE = 'batch write';
    case PERMISSION_SHARING = 'permission sharing';
}
