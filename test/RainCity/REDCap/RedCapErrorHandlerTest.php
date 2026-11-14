<?php
declare(strict_types = 1);
namespace RainCity\REDCap;

use IU\PHPCap\PhpCapException;
use Override;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\LoggerInterface;
use RCS\Util\ReflectionHelper;

#[CoversClass('\RainCity\REDCap\RedCapErrorHandler')]
class RedCapErrorHandlerTest extends TestCase
{
    /**
     * @expectedException PhpCapException
     */
    public function testThrowException() {
        $stubLogger = $this->createMock(LoggerInterface::class);

        $stubLogger
        ->expects($this->exactly(2))
        ->method('error');

        $obj = new RedCapErrorHandler($stubLogger);
        ReflectionHelper::setObjectProperty(RedCapErrorHandler::class, 'logger', $stubLogger, $obj);

        $this->expectException(PhpCapException::class);

        $obj->throwException('Test Exception', 987);
    }
}
