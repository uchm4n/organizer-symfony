<?php

declare(strict_types=1);

namespace App\Shared\DTO;

final readonly class ProblemResponse
{
    public function __construct(
        public int $status,
        public string $title,
        public string $detail,
        public ?array $extra = null,
    ) {}

    public function toArray(): array
    {
        $data = [
            'title'  => $this->title,
            'status' => $this->status,
            'detail' => $this->detail,
        ];

        if ($this->extra !== null) {
            $data = array_merge($data, $this->extra);
        }

        return $data;
    }
}
