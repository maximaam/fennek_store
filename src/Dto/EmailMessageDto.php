<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Mime\Address;

final readonly class EmailMessageDto
{
    /**
     * @param array<string, mixed> $context
     * @param Address[]            $cc
     * @param Address[]            $bcc
     */
    public function __construct(
        public Address $to,
        public string $subject,
        public string $template,
        public array $context = [],
        public array $cc = [],
        public array $bcc = [],
        public ?Address $from = null,
    ) {
    }
}
