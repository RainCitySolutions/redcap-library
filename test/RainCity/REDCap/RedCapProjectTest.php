<?php
declare(strict_types = 1);
namespace RainCity\REDCap;

use IU\PHPCap\PhpCapException;
use IU\PHPCap\RedCapApiConnectionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\MockObject\MockObject;
use RCS\Util\ReflectionHelper;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;

#[CoversClass('\RainCity\REDCap\RedcapProject')]
#[CoversMethod('RainCity\REDCap\RedCapErrorHandler', '__construct')]
final class RedCapProjectTest extends REDCapTestCase
{
    const TEST_URL = 'https://redcap.somesite.co/redcap/api';
    const TEST_TOKEN = 'FCDEBA1234567890ABCDEF1234567890';

    const TEST_EXCEPTION = 'Test Exception';

    private RedCapProject $proj;
    private LockFactory $lockFactory;

    private RedCapConfigIntf|MockObject $mockConfig;
    private RedCapApiConnectionInterface|MockObject $mockConn;


    protected function setUp(): void
    {
        parent::setUp();

        $this->lockFactory = new LockFactory(new InMemoryStore());

        $this->mockConn = $this->createMock(RedCapApiConnectionInterface::class);
        $this->mockConn->method('getUrl')->willReturn(self::TEST_URL);

        $this->mockConfig = $this->createMock(RedCapConfigIntf::class);
        $this->mockConfig->method('getApiUrl')->willReturn(self::TEST_URL);
        $this->mockConfig->method('getApiToken')->willReturn(self::TEST_TOKEN);
        $this->mockConfig->method('getConnection')->willReturn($this->mockConn);

        $this->proj = new RedCapProject($this->mockConfig, $this->mockCache, $this->lockFactory, $this->stubLogger);

        // Ensure the cache is empty before running each of our tests
        $this->mockCache->clear();
    }


    public function testCtor() {
        $proj = new RedCapProject($this->mockConfig, $this->mockCache, $this->lockFactory, $this->stubLogger);

        $token = ReflectionHelper::getObjectProperty(RedCapProject::class, 'apiToken', $proj);

        $this->assertEquals(self::TEST_TOKEN, $token);

        $conn = $proj->getConnection();
        $url = $conn->getUrl();

        $this->assertEquals(self::TEST_URL, $url);
    }

    public function testExportRedcapVersion_withException() {
        $this->mockConn->method('callWithArray')->will($this->throwException(new PhpCapException(self::TEST_EXCEPTION, 934)));

        $result = $this->proj->exportRedcapVersion();

        $this->assertNull($result);
    }

    public function testExportInstruments_exceptionPhpFormat()
    {
        $this->mockConn->method('callWithArray')->will($this->throwException(new PhpCapException(self::TEST_EXCEPTION, 734)));

        $result = $this->proj->exportInstruments('php');

        $this->assertEquals(array(), $result);
    }

    public function testExportInstruments_exceptionJsonFormat()
    {
        $this->mockConn->method('callWithArray')->will($this->throwException(new PhpCapException(self::TEST_EXCEPTION, 734)));

        $result = $this->proj->exportInstruments('json');

        $this->assertEquals('{}', $result);
    }

    public function testExportInstrumentEventMappings_exceptionPhpFormat()
    {
        $this->mockConn->method('callWithArray')->will($this->throwException(new PhpCapException(self::TEST_EXCEPTION, 734)));

        $result = $this->proj->exportInstrumentEventMappings();

        $this->assertEquals(array(), $result);
    }

    public function testExportInstrumentEventMappings_exceptionJsonFormat()
    {
        $this->mockConn->method('callWithArray')->will($this->throwException(new PhpCapException(self::TEST_EXCEPTION, 734)));

        $result = $this->proj->exportInstrumentEventMappings('json');

        $this->assertEquals('{}', $result);
    }

    public function testExportMetadata_withException()
    {
        $this->mockConn->method('callWithArray')->will($this->throwException(new PhpCapException(self::TEST_EXCEPTION, 734)));

        $result = $this->proj->exportMetadata();

        $this->assertEquals(array(), $result);
    }

    public function testExportPdfFileOfInstruments_withException()
    {
        $this->mockConn->method('callWithArray')->will($this->throwException(new PhpCapException(self::TEST_EXCEPTION, 734)));

        $result = $this->proj->exportPdfFileOfInstruments();

        $this->assertEquals('', $result);
    }

    public function testExportProjectInfo_exceptionPhpFormat()
    {
        $this->mockConn->method('callWithArray')->will($this->throwException(new PhpCapException(self::TEST_EXCEPTION, 734)));

        $result = $this->proj->exportProjectInfo('php');

        $this->assertEquals(array(), $result);
    }

    public function testExportProjectInfo_exceptionJsonFormat()
    {
        $this->mockConn->method('callWithArray')->will($this->throwException(new PhpCapException(self::TEST_EXCEPTION, 734)));

        $result = $this->proj->exportProjectInfo('json');

        $this->assertEquals('{}', $result);
    }

    public function testExportRecords_exceptionPhpFormat() {
        $this->mockConn->method('callWithArray')->will($this->throwException(new PhpCapException(self::TEST_EXCEPTION, 434)));

        $result = $this->proj->exportRecords('php');

        $this->assertEquals(array(), $result);
    }

    public function testExportRecords_exceptionJsonFormat() {
        $this->mockConn->method('callWithArray')->will($this->throwException(new PhpCapException(self::TEST_EXCEPTION, 434)));

        $result = $this->proj->exportRecords('json');

        $this->assertEquals('{}', $result);
    }

    public function testExportSurveyLink_withException()
    {
        $rcdId = 'Rcd987';
        $form = 'testInst';

        $this->mockConn->method('callWithArray')->will($this->throwException(new PhpCapException(self::TEST_EXCEPTION, 343)));

        $result = $this->proj->exportSurveyLink($rcdId, $form);

        $this->assertEquals('', $result);
    }

    public function testExportSurveyReturnCode_withException()
    {
        $recordId = 'rcdId1';
        $form = 'testInstrument';

        $this->mockConn->method('callWithArray')->will($this->throwException(new PhpCapException(self::TEST_EXCEPTION, 343)));

        $result = $this->proj->exportSurveyReturnCode($recordId, $form);

        $this->assertEquals('', $result);
    }

    public function testGetRecordIdFieldName_withException()
    {
        $this->mockConn->method('callWithArray')->will($this->throwException(new PhpCapException(self::TEST_EXCEPTION, 543)));

        $result = $this->proj->getRecordIdFieldName();

        $this->assertEquals('', $result);
    }

    public function testExportEvents_exceptionPhpFormat()
    {
        $this->mockConn->method('callWithArray')->will($this->throwException(new PhpCapException(self::TEST_EXCEPTION, 543)));

        $result = $this->proj->exportEvents();

        $this->assertEquals(array(), $result);
    }

    public function testExportEvents_exceptionJsonFormat()
    {
        $this->mockConn->method('callWithArray')->will($this->throwException(new PhpCapException(self::TEST_EXCEPTION, 543)));

        $result = $this->proj->exportEvents('json');

        $this->assertEquals('{}', $result);
    }

    public function testCacheResponse() {
        $testVersion = '9.5.2';

        $this->mockConn->expects($this->once())->method('callWithArray')->willReturn($testVersion);

        $result = $this->proj->exportRedcapVersion();

        $this->assertEquals($testVersion, $result);

        $this->mockCache->expects($this->once())->method('get')->willReturn($testVersion);
        $this->mockConn->expects($this->never())->method('callWithArray')->willReturn($testVersion);

        $result = $this->proj->exportRedcapVersion();

        $this->assertEquals($testVersion, $result);
    }

    public function testExportFieldNames_exceptionPhpFormat()
    {
        $this->mockConn->method('callWithArray')->will($this->throwException(new PhpCapException(self::TEST_EXCEPTION, 543)));

        $result = $this->proj->exportFieldNames('php');

        $this->assertEquals(array(), $result);
    }

    public function testExportFieldNames_exceptionJsonFormat()
    {
        $this->mockConn->method('callWithArray')->will($this->throwException(new PhpCapException(self::TEST_EXCEPTION, 543)));

        $result = $this->proj->exportFieldNames('json');

        $this->assertEquals('{}', $result);
    }
}
