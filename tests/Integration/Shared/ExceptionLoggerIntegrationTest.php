<?php

declare(strict_types=1);

namespace App\Tests\Integration\Shared;

use App\Shared\Logging\ExceptionLogger;
use App\Tests\Integration\IntegrationTestCase;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

final class ExceptionLoggerIntegrationTest extends IntegrationTestCase
{
    private const STATUS_500 = 500;
    private const STATUS_401 = 401;
    private const STATUS_404 = 404;
    private const STATUS_403 = 403;
    private const STATUS_400 = 400;

    private TestHandler $logHandler;
    private ExceptionLogger $exceptionLogger;
    private RequestStack $requestStack;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logHandler = new TestHandler();
        $logger = new Logger('test', [$this->logHandler]);
        /** @var RequestStack $requestStack */
        $this->requestStack = self::getContainer()->get(RequestStack::class);
        $this->requestStack->push(new Request());
        $this->exceptionLogger = new ExceptionLogger($logger, $this->requestStack);
    }

    public function testLogServerErrorLogsAsError(): void
    {
        $this->exceptionLogger->log(new \RuntimeException('test'), self::STATUS_500);

        self::assertTrue($this->logHandler->hasRecords(Logger::ERROR));
    }

    public function testLogClientNoiseLogsAsInfo(): void
    {
        $this->exceptionLogger->log(new AuthenticationException(), self::STATUS_401);

        self::assertTrue($this->logHandler->hasRecords(Logger::INFO));
    }

    public function testLogNotFoundLogsAsInfo(): void
    {
        $this->exceptionLogger->log(new NotFoundHttpException('not found'), self::STATUS_404);

        self::assertTrue($this->logHandler->hasRecords(Logger::INFO));
    }

    public function testLogWarningStatusLogsAsWarning(): void
    {
        $this->exceptionLogger->log(new \RuntimeException('forbidden'), self::STATUS_403);

        self::assertTrue($this->logHandler->hasRecords(Logger::WARNING));
    }

    public function testLogIncludesStatusInMessage(): void
    {
        $this->exceptionLogger->log(new \RuntimeException('fail'), self::STATUS_500);

        $records = $this->logHandler->getRecords();
        self::assertNotEmpty($records);

        $message = $records[0]['message'];
        self::assertStringContainsString((string) self::STATUS_500, $message);
    }

    public function testLogWithEmptyMessageUsesNoMessage(): void
    {
        $this->exceptionLogger->log(new \RuntimeException(''), self::STATUS_400);

        $records = $this->logHandler->getRecords();
        self::assertNotEmpty($records);

        $message = $records[0]['message'];
        self::assertStringContainsString('no message', $message);
    }
}
