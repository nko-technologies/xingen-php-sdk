<?php

declare(strict_types=1);

namespace Xingen\Sdk\Models;

enum Severity: string
{
    case ERROR = 'ERROR';
    case WARNING = 'WARNING';
    case INFO = 'INFO';
}
