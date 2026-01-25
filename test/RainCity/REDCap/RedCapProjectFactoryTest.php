<?php
declare(strict_types=1);
namespace RainCity\REDCap;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(RedCapProjectFactory::class)]
#[UsesClass(RedCapProjectInfo::class)]
final class RedCapProjectFactoryTest extends TestCase
{
    private TestableRedCapProjectFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new TestableRedCapProjectFactory();
    }

    private function createFactoryMock(): RedCapProjectFactory
    {
        return $this->getMockBuilder(RedCapProjectFactory::class)
        ->onlyMethods(['createProject'])
        ->getMock();
    }

    #[Test]
    public function instance_returns_same_factory(): void
    {
        $a = RedCapProjectFactory::instance();
        $b = RedCapProjectFactory::instance();

        self::assertSame($a, $b);
    }

    #[Test]
    public function same_url_and_token_returns_same_project_id(): void
    {
        $id1 = $this->factory->initializeProject(
            'https://example.com',
            'TOKEN123'
            );

        $id2 = $this->factory->initializeProject(
            'https://example.com',
            'TOKEN123'
            );

        self::assertSame($id1, $id2);
    }

    #[Test]
    public function different_url_or_token_returns_different_ids(): void
    {
        $id1 = $this->factory->initializeProject(
            'https://a.com',
            'TOKEN'
            );

        $id2 = $this->factory->initializeProject(
            'https://b.com',
            'TOKEN'
            );

        self::assertNotSame($id1, $id2);
    }

    #[Test]
    public function get_project_creates_project_lazily(): void
    {
        $factory = $this->createFactoryMock();

        $project = $this->createStub(RedCapProject::class);

        $factory
            ->expects(self::once())
            ->method('createProject')
            ->with(self::isInstanceOf(RedCapProjectInfo::class))
            ->willReturn($project);

        $id = $factory->initializeProject(
            'https://example.com',
            'TOKEN'
            );

        $result = $factory->getProject($id);

        self::assertSame($project, $result);
    }

    #[Test]
    public function get_project_returns_same_instance_on_subsequent_calls(): void
    {
        $factory = $this->createFactoryMock();

        $project = $this->createStub(RedCapProject::class);

        $factory
            ->expects(self::once())
            ->method('createProject')
            ->willReturn($project);

        $id = $factory->initializeProject(
            'https://example.com',
            'TOKEN'
            );

        $p1 = $factory->getProject($id);
        $p2 = $factory->getProject($id);

        self::assertSame($p1, $p2);
    }

    #[Test]
    public function get_project_returns_null_for_unknown_id(): void
    {
        $project = $this->factory->getProject('does-not-exist');

        self::assertNull($project);
        self::assertSame(0, $this->factory->createCalls);
    }
}

/**
 * Testable factory that exposes creation count.
 */
final class TestableRedCapProjectFactory extends RedCapProjectFactory
{
    public int $createCalls = 0;

    protected function createProject(RedCapProjectInfo $info): RedCapProject
    {
        $this->createCalls++;

        // We don't care about real behavior here
        return $this->createStub(RedCapProject::class);
    }
}

