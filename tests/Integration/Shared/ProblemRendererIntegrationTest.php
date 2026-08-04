<?php

declare(strict_types=1);

namespace App\Tests\Integration\Shared;

use App\Shared\HttpKernel\ProblemRenderer;
use App\Tests\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class ProblemRendererIntegrationTest extends IntegrationTestCase
{
    private const STATUS_404 = 404;
    private const TITLE_NOT_FOUND = 'Not Found';
    private const DETAIL_MISSING = 'Resource missing';

    private const STATUS_400 = 400;
    private const TITLE_BAD_REQUEST = 'Bad Request';
    private const DETAIL_INVALID = 'Invalid input';

    private const STATUS_422 = 422;
    private const TITLE_UNPROCESSABLE = 'Unprocessable Entity';
    private const DETAIL_VALIDATION = 'Validation failed';
    private const EXTRA_ERRORS = ['errors' => ['email' => 'required']];

    private const STATUS_500 = 500;
    private const TITLE_SERVER_ERROR = 'Internal Server Error';
    private const DETAIL_BROKE = 'Something broke';

    public function testResponseReturnsJsonResponse(): void
    {
        $response = ProblemRenderer::response(
            self::STATUS_404,
            self::TITLE_NOT_FOUND,
            self::DETAIL_MISSING,
        );

        self::assertInstanceOf(\Symfony\Component\HttpFoundation\JsonResponse::class, $response);
        self::assertSame(self::STATUS_404, $response->getStatusCode());
        self::assertSame('application/problem+json', $response->headers->get('Content-Type'));
    }

    public function testResponseContainsProblemFields(): void
    {
        $response = ProblemRenderer::response(
            self::STATUS_400,
            self::TITLE_BAD_REQUEST,
            self::DETAIL_INVALID,
        );

        $data = json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('title', $data);
        self::assertArrayHasKey('status', $data);
        self::assertArrayHasKey('detail', $data);
        self::assertSame(self::TITLE_BAD_REQUEST, $data['title']);
        self::assertSame(self::STATUS_400, $data['status']);
        self::assertSame(self::DETAIL_INVALID, $data['detail']);
    }

    public function testResponseIncludesExtraFields(): void
    {
        $response = ProblemRenderer::response(
            self::STATUS_422,
            self::TITLE_UNPROCESSABLE,
            self::DETAIL_VALIDATION,
            self::EXTRA_ERRORS,
        );

        $data = json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('errors', $data);
        self::assertSame(['email' => 'required'], $data['errors']);
    }

    #[DataProvider('titleProvider')]
    public function testTitleForStatusReturnsCorrectTitles(int $status, string $expected): void
    {
        self::assertSame($expected, ProblemRenderer::titleForStatus($status));
    }

    public static function titleProvider(): array
    {
        return [
            [400, 'Bad Request'],
            [401, 'Unauthorized'],
            [403, 'Forbidden'],
            [404, 'Not Found'],
            [405, 'Method Not Allowed'],
            [409, 'Conflict'],
            [422, 'Unprocessable Entity'],
            [429, 'Too Many Requests'],
            [500, 'Internal Server Error'],
        ];
    }

    public function testTitleForStatusReturnsDefaultForUnknown(): void
    {
        self::assertSame('Error', ProblemRenderer::titleForStatus(599));
    }

    public function testResponseWithEmptyExtra(): void
    {
        $response = ProblemRenderer::response(
            self::STATUS_500,
            self::TITLE_SERVER_ERROR,
            self::DETAIL_BROKE,
            [],
        );

        $data = json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertSame(array_keys($data), ['title', 'status', 'detail']);
    }
}
