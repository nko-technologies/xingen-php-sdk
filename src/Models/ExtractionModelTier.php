<?php

declare(strict_types=1);

namespace Xingen\Sdk\Models;

/**
 * Extraction quality/cost tier for AI-based PDF invoice extraction:
 *
 * - FAST -- lower-cost model, good for clean/text-based PDFs (available to all tiers)
 * - ACCURATE -- highest-accuracy model, recommended for scanned/low-quality PDFs (Pro only)
 */
enum ExtractionModelTier: string
{
    case FAST = 'FAST';
    case ACCURATE = 'ACCURATE';
}
