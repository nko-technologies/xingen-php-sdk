<?php

declare(strict_types=1);

namespace Xingen\Sdk\Invoices;

/** Payment means (BG-16). */
final class PaymentMeansInput
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

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'typeCode' => $this->typeCode,
            'paymentMeansText' => $this->paymentMeansText,
            'remittanceInformation' => $this->remittanceInformation,
            'creditTransferAccountId' => $this->creditTransferAccountId,
            'accountName' => $this->accountName,
            'serviceProviderId' => $this->serviceProviderId,
            'mandateReferenceId' => $this->mandateReferenceId,
            'cardAccountNumber' => $this->cardAccountNumber,
            'cardHolderName' => $this->cardHolderName,
            'creditorId' => $this->creditorId,
            'debitedAccountId' => $this->debitedAccountId,
        ];
    }
}
