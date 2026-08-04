<?php

declare(strict_types=1);

namespace App\Shared\DTO;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProblemResponse',
    description: 'RFC 9457 Problem+JSON error response',
    type: 'object',
)]
final readonly class ProblemResponse
{
    public function __construct(
        #[OA\Property(property: 'status', type: 'integer', example: 404)]
        public int $status,
        #[OA\Property(property: 'title', type: 'string', example: 'Not Found')]
        public string $title,
        #[OA\Property(property: 'detail', type: 'string', example: 'Resource not found.')]
        public string $detail,
        #[OA\Property(property: 'extra', type: 'object', nullable: true, description: 'Additional fields: trace_id, errors, retry_after')]
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
