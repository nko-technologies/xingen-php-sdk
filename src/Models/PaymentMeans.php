<?php

declare(strict_types=1);

namespace Xingen\Sdk\Models;

use Xingen\Sdk\Internal\Wire;

final class PaymentMeans
{
    public function __construct(
        public readonly ?string $typeCode = null,
        public readonly ?string $paymentMeansText = null,
        public readonly ?string $remittanceInformation = null,
        public readonly ?string $creditTransferAccountId = null,
        public readonly ?string $accountName = null,
        public readonly ?string $serviceProviderId = null,
        public readonly ?string $mandateReferenceId = null,
        public readonly ?string $cardAccountNumber = null,
        public readonly ?string $cardHolderName = null,
        public readonly ?string $creditorId = null,
        public readonly ?string $debitedAccountId = null,
    ) {
    }

    public static function fromWire(mixed $raw): self
    {
        $w = Wire::asWire($raw);

        return new self(
            typeCode: Wire::str($w, 'typeCode'),
            paymentMeansText: Wire::str($w, 'paymentMeansText'),
            remittanceInformation: Wire::str($w, 'remittanceInformation'),
            creditTransferAccountId: Wire::str($w, 'creditTransferAccountId'),
            accountName: Wire::str($w, 'accountName'),
            serviceProviderId: Wire::str($w, 'serviceProviderId'),
            mandateReferenceId: Wire::str($w, 'mandateReferenceId'),
            cardAccountNumber: Wire::str($w, 'cardAccountNumber'),
            cardHolderName: Wire::str($w, 'cardHolderName'),
            creditorId: Wire::str($w, 'creditorId'),
            debitedAccountId: Wire::str($w, 'debitedAccountId'),
        );
    }
}
