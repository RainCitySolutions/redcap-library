<?php
namespace RainCity\REDCap;


/**
 * @covers \RainCity\REDCap\SurveyStatus
 *
 * @covers RainCity\REDCap\CompletedFieldCount::__construct
 * @covers RainCity\REDCap\CompletedFieldCount::getCompletedCount
 * @covers RainCity\REDCap\CompletedFieldCount::getFirstIncompleteField
 * @covers RainCity\REDCap\CompletedFieldCount::getFirstIncompleteInstrument
 * @covers RainCity\REDCap\CompletedFieldCount::getRequiredCount
 */
class SurveyStatusTest extends REDCapTestCase
{
    /** @var Instrument */
    private $mockInst;

    /** @var InstrumentRecord */
    private $mockInstRcd;

    protected function setUp(): void {
        parent::setUp();

        $this->mockInst = $this->createMock(Instrument::class);
        $this->mockInst->method('getName')->willReturn(static::DEMOGRAPHICS_FORM);

        $this->mockInstRcd = $this->createMock(InstrumentRecord::class);
        $this->mockInstRcd->method('getInstrument')->willReturn($this->mockInst);
    }

    public function testCtor_instrumentNotLoaded()
    {
        $this->mockInst->method('isCAT')->willReturn(false);

        $this->mockInstRcd->method('isLoaded')->willReturn(false);

//        $this->mockInstRcd->method('getFieldValue')->willReturn(null);
//        $this->mockInstRcd->method('getCompletedFieldCounts')->willReturn(new CompletedFieldCount(0, 10));

        $status = new SurveyStatus($this->mockInstRcd);

        $this->assertTrue($status->canEdit());

        $this->assertTrue($status->isRedcapIncomplete());
        $this->assertFalse($status->isRedcapSurveyIncomplete());
        $this->assertFalse($status->isRedcapComplete());

        $this->assertFalse($status->notStarted());
        $this->assertFalse($status->isComplete());

        $this->assertEquals('Can Edit', (string)$status);
    }

    public function testCtor()
    {
        $this->mockInst->method('isCAT')->willReturn(false);

        $this->mockInstRcd->method('isLoaded')->willReturn(true);
        $this->mockInstRcd->method('getFieldValue')->willReturn(null);
        $this->mockInstRcd->method('getCompletedFieldCounts')->willReturn(new CompletedFieldCount(0, 10));

        $status = new SurveyStatus($this->mockInstRcd);

        $this->assertTrue($status->canEdit());

        $this->assertTrue($status->isRedcapIncomplete());
        $this->assertFalse($status->isRedcapSurveyIncomplete());
        $this->assertFalse($status->isRedcapComplete());

        $this->assertTrue($status->notStarted());
        $this->assertFalse($status->isComplete());

        $this->assertEquals('Not Started - Can Edit', (string)$status);
    }

    public function testCtor_CAT()
    {
        $this->mockInst->method('isCAT')->willReturn(true);

        $this->mockInstRcd->method('isLoaded')->willReturn(true);
        $this->mockInstRcd->method('getFieldValue')->will($this->returnCallback(function($field) {
            $result = null;

            switch($field) {
                case static::DEMOGRAPHICS_FORM.'_complete':
                    $result = "2";  // REDCap says it's complete
                    break;
                case static::DEMOGRAPHICS_FORM.'_timestamp':
                    $result = '';
                    break;
            }
            return $result;
        } ) );
        $this->mockInstRcd->method('getCompletedFieldCounts')->willReturn(new CompletedFieldCount(0, 10));

        $status = new SurveyStatus($this->mockInstRcd);

        $this->assertTrue($status->canEdit());

        $this->assertFalse($status->isRedcapIncomplete());
        $this->assertFalse($status->isRedcapSurveyIncomplete());
        $this->assertTrue($status->isRedcapComplete());

        $this->assertFalse($status->notStarted());
        $this->assertTrue($status->isComplete());

        $this->assertEquals('Complete - Can Edit', (string)$status);
    }

    public function testCtor_noRequiredFields()
    {
        $this->mockInst->method('isCAT')->willReturn(false);

        $this->mockInstRcd->method('isLoaded')->willReturn(true);
        $this->mockInstRcd->method('getFieldValue')->willReturn(null);
        $this->mockInstRcd->method('getCompletedFieldCounts')->willReturn(new CompletedFieldCount(0, 0));

        $status = new SurveyStatus($this->mockInstRcd);

        $this->assertFalse($status->canEdit());

        $this->assertTrue($status->isRedcapIncomplete());
        $this->assertFalse($status->isRedcapSurveyIncomplete());
        $this->assertFalse($status->isRedcapComplete());

        $this->assertTrue($status->notStarted());
        $this->assertTrue($status->isComplete());

        $this->assertEquals('Not Started - Complete', (string)$status);
    }

    public function testCtor_surveyIncomplete()
    {
        $this->mockInst->method('isCAT')->willReturn(false);

        $this->mockInstRcd->method('isLoaded')->willReturn(true);
        $this->mockInstRcd->method('getFieldValue')->will($this->returnCallback(function($field) {
            $result = null;

            switch($field) {
                case static::DEMOGRAPHICS_FORM.'_complete':
                    $result = "0";  // REDCap says it's complete
                    break;
                case static::DEMOGRAPHICS_FORM.'_timestamp':
                    $result = '[not completed]';
                    break;
            }
            return $result;
        } ) );

        $this->mockInstRcd->method('getCompletedFieldCounts')->willReturn(new CompletedFieldCount(0, 10));

        $status = new SurveyStatus($this->mockInstRcd);

        $this->assertTrue($status->canEdit());

        $this->assertTrue($status->isRedcapIncomplete());
        $this->assertFalse($status->isRedcapSurveyIncomplete());
        $this->assertFalse($status->isRedcapComplete());

        $this->assertTrue($status->notStarted());
        $this->assertFalse($status->isComplete());

        $this->assertEquals('Not Started - Can Edit', (string)$status);
    }

    public function testCtor_surveyComplete()
    {
        $this->mockInst->method('isCAT')->willReturn(false);

        $this->mockInstRcd->method('isLoaded')->willReturn(true);
        $this->mockInstRcd->method('getFieldValue')->will($this->returnCallback(function($field) {
            $result = null;

            switch($field) {
                case static::DEMOGRAPHICS_FORM.'_complete':
                    $result = "2";  // REDCap says it's complete
                    break;
                case static::DEMOGRAPHICS_FORM.'_timestamp':
                    $result = '[not completed]';
                    break;
            }
            return $result;
        } ) );

        $this->mockInstRcd->method('getCompletedFieldCounts')->willReturn(new CompletedFieldCount(7, 7));

        $status = new SurveyStatus($this->mockInstRcd);

        $this->assertTrue($status->canEdit());

        $this->assertFalse($status->isRedcapIncomplete());
        $this->assertFalse($status->isRedcapSurveyIncomplete());
        $this->assertTrue($status->isRedcapComplete());

        $this->assertFalse($status->notStarted());
        $this->assertTrue($status->isComplete());

        $this->assertEquals('Complete - Can Edit', (string)$status);
    }

    public function testCtor_surveyUnverified()
    {
        $this->mockInst->method('isCAT')->willReturn(false);

        $this->mockInstRcd->method('isLoaded')->willReturn(true);
        $this->mockInstRcd->method('getFieldValue')->will($this->returnCallback(function($field) {
            $result = null;

            switch($field) {
                case static::DEMOGRAPHICS_FORM.'_complete':
                    $result = "1";  // REDCap says it's complete
                    break;
                case static::DEMOGRAPHICS_FORM.'_timestamp':
                    $result = '[not completed]';
                    break;
            }
            return $result;
        } ) );

        $this->mockInstRcd->method('getCompletedFieldCounts')->willReturn(new CompletedFieldCount(0, 10));

        $status = new SurveyStatus($this->mockInstRcd);

        $this->assertTrue($status->canEdit());

        $this->assertTrue($status->isRedcapIncomplete());
        $this->assertFalse($status->isRedcapSurveyIncomplete());
        $this->assertFalse($status->isRedcapComplete());

        $this->assertTrue($status->notStarted());
        $this->assertFalse($status->isComplete());

        $this->assertEquals('Not Started - Can Edit', (string)$status);
    }

    public function testCtor_UnknownRedcapState()
    {
        $this->mockInst->method('isCAT')->willReturn(false);

        $this->mockInstRcd->method('isLoaded')->willReturn(true);
        $this->mockInstRcd->method('getFieldValue')->will($this->returnCallback(function($field) {
            $result = null;

            switch($field) {
                case static::DEMOGRAPHICS_FORM.'_complete':
                    $result = "99";  // REDCap says it's complete
                    break;
                case static::DEMOGRAPHICS_FORM.'_timestamp':
                    $result = '[not completed]';
                    break;
            }
            return $result;
        } ) );

        $this->mockInstRcd->method('getCompletedFieldCounts')->willReturn(new CompletedFieldCount(0, 10));

        $status = new SurveyStatus($this->mockInstRcd);

        $this->assertTrue($status->canEdit());

        $this->assertTrue($status->isRedcapIncomplete());
        $this->assertFalse($status->isRedcapSurveyIncomplete());
        $this->assertFalse($status->isRedcapComplete());

        $this->assertTrue($status->notStarted());
        $this->assertFalse($status->isComplete());

        $this->assertEquals('Not Started - Can Edit', (string)$status);
    }
}
