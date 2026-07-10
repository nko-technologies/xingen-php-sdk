<?php

declare(strict_types=1);

namespace Xingen\Sdk\Models;

enum ValidationLayer: string
{
    case CORE = 'CORE'; // EN16931
    case NATIONAL = 'NATIONAL'; // XRechnung
    case NETWORK = 'NETWORK'; // Peppol
}
