<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class EmailMessageDto
{
    public function __construct(
        public string $to,
        public string $subject,
        public string $template,
        public array $context = [],
        public ?string $from = null,
        public array $cc = [],
        public array $bcc = [],
    ) {
    }
}
