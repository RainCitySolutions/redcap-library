<?php
namespace RainCity\REDCap;

use IU\PHPCap\PhpCapException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RainCity\TestHelper\ReflectionHelper;


/**
 *
 * @covers \RainCity\REDCap\RedCapErrorHandler
 *
 */
class RedCapErrorHandlerTest extends TestCase
{
    public function testCtor() {
        $obj = new RedCapErrorHandler();

        $log = ReflectionHelper::getObjectProperty(RedCapErrorHandler::class, 'logger', $obj);

        $this->assertInstanceOf(LoggerInterface::class, $log);
    }

    /**
     * @expectedException PhpCapException
     */
    public function testThrowException() {
        $stubLogger = $this->createMock(LoggerInterface::class);

        $stubLogger
        ->expects($this->exactly(2))
        ->method('error');

        $obj = new RedCapErrorHandler();
        ReflectionHelper::setObjectProperty(RedCapErrorHandler::class, 'logger', $stubLogger, $obj);

        $this->expectException(PhpCapException::class);

        $obj->throwException('Test Exception', 987);
    }
}
