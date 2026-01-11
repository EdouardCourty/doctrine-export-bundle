<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Tests\Fixtures\Enum;

enum UserStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';
    case PENDING = 'pending';
}
