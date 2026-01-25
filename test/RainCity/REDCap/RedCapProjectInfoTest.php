<?php
declare(strict_types=1);
namespace RainCity\REDCap;

use IU\PHPCap\ErrorHandlerInterface;
use IU\PHPCap\RedCapApiConnectionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RedCapProjectInfo::class)]
final class RedCapProjectInfoTest extends TestCase
{
    #[Test]
    public function constructor_sets_all_properties(): void
    {
        $errorHandler = $this->createStub(ErrorHandlerInterface::class);
        $connection   = $this->createStub(RedCapApiConnectionInterface::class);

        $info = new RedCapProjectInfo(
            'id123',
            'https://example.com',
            'TOKEN',
            false,
            '/ca.pem',
            $errorHandler,
            $connection
        );

        self::assertSame('id123', $info->getId());
        self::assertSame('https://example.com', $info->getApiUrl());
        self::assertSame('TOKEN', $info->getApiToken());
        self::assertFalse($info->getSslVerify());
        self::assertSame('/ca.pem', $info->getCaCertificateFile());
        self::assertSame($errorHandler, $info->getErrorHandler());
        self::assertSame($connection, $info->getConnection());
    }

    #[Test]
    public function constructor_uses_default_values(): void
    {
        $info = new RedCapProjectInfo(
            'id',
            'https://example.com',
            'TOKEN'
        );

        self::assertTrue($info->getSslVerify());
        self::assertNull($info->getCaCertificateFile());
        self::assertNull($info->getErrorHandler());
        self::assertNull($info->getConnection());
    }

    #[Test]
    public function project_is_null_by_default(): void
    {
        $info = new RedCapProjectInfo(
            'id',
            'url',
            'token'
        );

        self::assertNull($info->getProject());
    }

    #[Test]
    public function set_project_stores_project_instance(): void
    {
        $info = new RedCapProjectInfo(
            'id',
            'url',
            'token'
        );

        $project = $this->createStub(RedCapProject::class);

        $info->setProject($project);

        self::assertSame($project, $info->getProject());
    }
}
