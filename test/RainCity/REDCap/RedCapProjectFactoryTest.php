<?php
declare(strict_types = 1);
namespace RainCity\REDCap;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use RainCity\TestHelper\ReflectionHelper;

#[CoversClass('\RainCity\REDCap\RedcapProjectFactory')]
final class RedCapProjectFactoryTest extends TestCase
{
    private const TEST_URL = 'http://some.redcap.server.org/api';
    private const TEST_TOKEN = 'A1B2C3D4E5F6A1B2C3D4E5F6A1B2C3D4';

    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUp()
     */
    protected function setUp(): void
    {
        ReflectionHelper::setClassProperty(RedCapProjectFactory::class, 'singletonFactory', null);
    }

    public function testCtor() {
        $testObj = new RedCapProjectFactory(self::TEST_URL, self::TEST_TOKEN);

        $this->assertNotNull($testObj);
    }

    public function testInstance_notInitialized() {
        $this->expectException('\RainCity\Exception\InvalidStateException');

        RedCapProjectFactory::instance();
    }

    public function testInitSingleton_alreadyInitialized() {
        $this->expectException('\RainCity\Exception\InvalidStateException');

        $testObj = new RedCapProjectFactory(self::TEST_URL, self::TEST_TOKEN);

        ReflectionHelper::setClassProperty(RedCapProjectFactory::class, 'singletonFactory', $this->createStub(RedCapProjectFactory::class));

        $testObj->initSingleton();
    }

    public function testInitSingleton_instance() {
        $testObj = new RedCapProjectFactory(self::TEST_URL, self::TEST_TOKEN);

        $singleton = $testObj->initSingleton();

        $this->assertNotNull($singleton);
        $this->assertInstanceOf(RedCapProjectFactory::class, $singleton);

        $instance = RedCapProjectFactory::instance();

        $this->assertNotNull($instance);
        $this->assertSame($singleton, $instance);
    }

    public function testGetProject_existingProject() {
        $testObj = new RedCapProjectFactory(self::TEST_URL, self::TEST_TOKEN);

        $stubProject = $this->createStub(RedCapProject::class);

        ReflectionHelper::setObjectProperty(RedCapProjectFactory::class, 'project', $stubProject, $testObj);

        $project = $testObj->getProject();

        $this->assertNotNull($project);
        $this->assertSame($stubProject, $project);
    }

    public function testGetProject_noProject() {
//        $testObj = new RedCapProjectFactory(self::TEST_URL, self::TEST_TOKEN);

        $mockProject = $this->createStub(RedCapProject::class);

        $mock = $this->createPartialMock(RedCapProjectFactory::class, ['createProject']);
        $mock->expects($this->once())->method('createProject')->willReturn($mockProject);

        $project = $mock->getProject();

        $this->assertNotNull($project);
        $this->assertSame($mockProject, $project);
    }
}
