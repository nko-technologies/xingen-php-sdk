<?php

declare(strict_types=1);

namespace Xingen\Sdk\Models;

/**
 * Validation profile requested for an invoice.
 *
 * - EN16931 -- European standard EN 16931 (free)
 * - PEPPOL -- PEPPOL BIS Billing 3.0 (free)
 * - XRECHNUNG -- German XRechnung standard (Pro)
 * - FRANCE -- French Factur-X standard (Pro) -- not yet implemented server-side
 * - ITALY -- Italian FatturaPA standard (Pro) -- not yet implemented server-side
 */
enum ValidationProfile: string
{
    case EN16931 = 'EN16931';
    case PEPPOL = 'PEPPOL';
    case XRECHNUNG = 'XRECHNUNG';
    case FRANCE = 'FRANCE';
    case ITALY = 'ITALY';
}
