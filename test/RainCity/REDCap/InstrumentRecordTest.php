<?php
namespace RainCity\REDCap;

use RainCity\TestHelper\ReflectionHelper;

/**
 * InstrumentRecod test case.
 *
 * @covers \RainCity\REDCap\InstrumentRecord
 *
 * @covers RainCity\REDCap\CompletedFieldCount::__construct
 * @covers RainCity\REDCap\CompletedFieldCount::getCompletedCount
 * @covers RainCity\REDCap\CompletedFieldCount::getFirstIncompleteField
 * @covers RainCity\REDCap\CompletedFieldCount::getFirstIncompleteInstrument
 * @covers RainCity\REDCap\CompletedFieldCount::getRequiredCount
 * @covers RainCity\REDCap\CompletedFieldCount::merge
 * @covers RainCity\REDCap\Field::__construct
 * @covers RainCity\REDCap\Field::getCheckboxFieldName
 * @covers RainCity\REDCap\Field::getName
 * @covers RainCity\REDCap\Field::getType
 * @covers RainCity\REDCap\Field::isCAT
 * @covers RainCity\REDCap\Field::isRequired
 * @covers RainCity\REDCap\Field::getBranching
 * @covers RainCity\REDCap\Field::hasBranching
 * @covers RainCity\REDCap\Instrument::__construct
 * @covers RainCity\REDCap\Instrument::addField
 * @covers RainCity\REDCap\Instrument::collapseFieldnames
 * @covers RainCity\REDCap\Instrument::getAllFieldNames
 * @covers RainCity\REDCap\Instrument::getEvents
 * @covers RainCity\REDCap\Instrument::getName
 * @covers RainCity\REDCap\Instrument::getNextInstrument
 * @covers RainCity\REDCap\Instrument::getOptionalFields
 * @covers RainCity\REDCap\Instrument::getOptionalRecordFieldNames
 * @covers RainCity\REDCap\Instrument::getRequiredFormFieldNames
 * @covers RainCity\REDCap\Instrument::getRequiredRecordFieldNames
 * @covers RainCity\REDCap\Instrument::hasNextInstrument
 * @covers RainCity\REDCap\Instrument::initLogger
 * @covers RainCity\REDCap\Instrument::isCAT
 * @covers RainCity\REDCap\Instrument::makeSingularCheckboxesOptional
 * @covers RainCity\REDCap\Instrument::readExportFieldNames
 * @covers RainCity\REDCap\Instrument::readMetadata
 * @covers RainCity\REDCap\Instrument::setNextInstrument
 * @covers RainCity\REDCap\Record::__construct
 * @covers RainCity\REDCap\Record::collapeCheckboxFields
 * @covers RainCity\REDCap\Record::fetchField
 * @covers RainCity\REDCap\Record::getFieldValue
 * @covers RainCity\REDCap\Record::getRecordIdFieldName
 * @covers RainCity\REDCap\Record::getREDCapArray
 * @covers RainCity\REDCap\Record::isLoaded
 * @covers RainCity\REDCap\Record::isValidEvent
 * @covers RainCity\REDCap\Record::isValidField
 * @covers RainCity\REDCap\Record::loadEventRecord
 * @covers RainCity\REDCap\Record::loadRecord
 * @covers RainCity\REDCap\Record::loadRecordById
 * @covers RainCity\REDCap\Record::privLoadRecord
 * @covers RainCity\REDCap\Record::projectUsesEvents
 * @covers RainCity\REDCap\Record::setEvents
 * @covers RainCity\REDCap\Record::setFieldValue
 * @covers RainCity\REDCap\Record::setFields
 * @covers RainCity\REDCap\Record::setInstruments
 * @covers RainCity\REDCap\Record::storeField
 * @covers RainCity\REDCap\Record::validateEvent
 * @covers RainCity\REDCap\Record::validateRecord
 * @covers RainCity\REDCap\SurveyStatus::__construct
 * @covers RainCity\REDCap\SurveyStatus::isRedcapIncomplete
 * @covers RainCity\REDCap\SurveyStatus::isRedcapSurveyIncomplete
 * @covers RainCity\REDCap\SurveyStatus::setRedcapStatus
 */
class InstrumentRecordTest extends REDCapTestCase
{
    private const UNEXPECTED_COMPLETED_COUNT = 'Unexpected completed count';
    private const UNEXPECTED_REQUIRED_COUNT = 'Unexpected required count';
    private const BOB_EMAIL_ADDRESS = 'bob@myco.com';
    private const JANE_FULL_NAME = 'Jane Doe';
    private const TIMESTAMP_SUFFIX = '_timestamp';

    /** @var InstrumentRecord instance used for testing */
    private $testObj;

    /** @var Instrument instance used for testing */
    private $testInstrument;

    private function createMockStatus(string $method, $retValue): SurveyStatus {
        $mock = $this->createMock(SurveyStatus::class);
        $mock->method($method)->willReturn($retValue);

        return $mock;
    }

    protected function setUp(): void {
        parent::setUp();

        $this->testInstrument = $this->createInstrument(static::DEMOGRAPHICS_FORM);

        // Load a random record
        $this->testObj = new InstrumentRecord(
            $this->stubRedcapProj,
            $this->testInstrument,
            static::ALL_VALID_RCD_IDS[rand(0, count(static::ALL_VALID_RCD_IDS) - 1)]
            );
    }

    public function testCtor_withNextInstrument() {
        $testInst = $this->createInstrument(static::DEMOGRAPHICS_FORM);
        $nextInst = $this->createInstrument(static::CONSENT_FORM);

        $testInst->setNextInstrument($nextInst);

        $instRcd = new InstrumentRecord($this->stubRedcapProj, $testInst);

        $prop = ReflectionHelper::getObjectProperty(InstrumentRecord::class, 'nextInstrumentRcd', $instRcd);

        $this->assertNotNull($prop);
        $this->assertInstanceOf(InstrumentRecord::class, $prop);

        $inst = $prop->getInstrument();
        $this->assertEquals($nextInst, $inst);
    }

    public function testGetStatus_classic() {
        $this->useClassicProject();

        $instRcd = new InstrumentRecord($this->stubRedcapProj, $this->testInstrument);

        $status = $instRcd->getStatus();

        $this->assertNotNull($status);
        $this->assertInstanceOf(SurveyStatus::class, $status);
    }

    public function testGetStatus_events() {
        $instRcd = new InstrumentRecord($this->stubRedcapProj, $this->testInstrument);

        $instrumentEvents = $this->testInstrument->getEvents();

        foreach ($instrumentEvents as $event) {
            $status = $instRcd->getStatus($event);

            $this->assertNotNull($status);
            $this->assertInstanceOf(SurveyStatus::class, $status);
        }
    }

    public function testCanEdit_classic_noNextInstrument() {
        ReflectionHelper::setObjectProperty(InstrumentRecord::class, 'status', array(static::TEST_EVENT_A => $this->createMockStatus('canEdit', false)), $this->testObj);

        $canEdit = $this->testObj->canEdit(static::TEST_EVENT_A);

        $this->assertFalse($canEdit, 'canEdit() should have retured FALSE');

        ReflectionHelper::setObjectProperty(InstrumentRecord::class, 'status', array(static::TEST_EVENT_A => $this->createMockStatus('canEdit', true)), $this->testObj);

        $canEdit = $this->testObj->canEdit(static::TEST_EVENT_A);

        $this->assertTrue($canEdit, 'canEdit() should have retured TRUE');
    }

    public function testCanEdit_withNextInstrument() {
        ReflectionHelper::setObjectProperty(InstrumentRecord::class, 'status', array(static::TEST_EVENT_A => $this->createMockStatus('canEdit', false)), $this->testObj);

        $nextInst = new InstrumentRecord($this->stubRedcapProj, $this->createInstrument(static::CONSENT_FORM));

        ReflectionHelper::setObjectProperty(InstrumentRecord::class, 'status', array(static::TEST_EVENT_A => $this->createMockStatus('canEdit', true)), $nextInst);
        ReflectionHelper::setObjectProperty(InstrumentRecord::class, 'nextInstrumentRcd', $nextInst, $this->testObj);

        $canEdit = $this->testObj->canEdit(static::TEST_EVENT_A);

        $this->assertTrue($canEdit, 'If a next instrument is editable the parent should be editable');

        ReflectionHelper::setObjectProperty(InstrumentRecord::class, 'status', array(static::TEST_EVENT_A => $this->createMockStatus('canEdit', false)), $nextInst);

        $canEdit = $this->testObj->canEdit(static::TEST_EVENT_A);

        $this->assertFalse($canEdit, 'canEdit() should have returned FASLE');
    }

    public function testNotStarted_noNextInstrument() {
        ReflectionHelper::setObjectProperty(InstrumentRecord::class, 'status', array(static::TEST_EVENT_A => $this->createMockStatus('notStarted', true)), $this->testObj);

        $notStarted = $this->testObj->notStarted(true, static::TEST_EVENT_A);

        $this->assertTrue($notStarted, 'notStarted() should have returned TRUE');

        ReflectionHelper::setObjectProperty(InstrumentRecord::class, 'status', array(static::TEST_EVENT_A => $this->createMockStatus('notStarted', false)), $this->testObj);

        $notStarted = $this->testObj->notStarted(true, static::TEST_EVENT_A);

        $this->assertFalse($notStarted, 'notStarted() should have returned FALSE');
    }

    public function testNotStarted_withNextInstrument() {
        ReflectionHelper::setObjectProperty(InstrumentRecord::class, 'status', array(static::TEST_EVENT_A => $this->createMockStatus('notStarted', true)), $this->testObj);

        $nextInst = new InstrumentRecord($this->stubRedcapProj, $this->createInstrument(static::CONSENT_FORM));

        ReflectionHelper::setObjectProperty(InstrumentRecord::class, 'status', array(static::TEST_EVENT_A => $this->createMockStatus('notStarted', false)), $nextInst);
        ReflectionHelper::setObjectProperty(InstrumentRecord::class, 'nextInstrumentRcd', $nextInst, $this->testObj);

        $notStarted = $this->testObj->notStarted(false, static::TEST_EVENT_A); // don't check next instrument

        $this->assertTrue($notStarted, 'notStarted should have returned TRUE');

        $notStarted = $this->testObj->notStarted(true, static::TEST_EVENT_A); // check next instrument

        $this->assertFalse($notStarted, 'notStarted should have returned FALSE');

        ReflectionHelper::setObjectProperty(InstrumentRecord::class, 'status', array(static::TEST_EVENT_A => $this->createMockStatus('notStarted', true)), $nextInst);

        $notStarted = $this->testObj->notStarted(true, static::TEST_EVENT_A); // check next instrument

        $this->assertTrue($notStarted, 'notStarted should have returned TRUE');
    }

    public function testIsComplete_noNextInstrument() {
        ReflectionHelper::setObjectProperty(InstrumentRecord::class, 'status', array(static::TEST_EVENT_A => $this->createMockStatus('isComplete', true)), $this->testObj);

        $notStarted = $this->testObj->isComplete(true, static::TEST_EVENT_A);

        $this->assertTrue($notStarted, 'isComplete() should have returned TRUE');

        ReflectionHelper::setObjectProperty(InstrumentRecord::class, 'status', array(static::TEST_EVENT_A => $this->createMockStatus('isComplete', false)), $this->testObj);

        $notStarted = $this->testObj->isComplete(true, static::TEST_EVENT_A);

        $this->assertFalse($notStarted, 'isComplete() should have returned FALSE');
    }

    public function testIsComplete_withNextInstrument() {
        ReflectionHelper::setObjectProperty(InstrumentRecord::class, 'status', array(static::TEST_EVENT_A => $this->createMockStatus('isComplete', true)), $this->testObj);

        $nextInst = new InstrumentRecord($this->stubRedcapProj, $this->createInstrument(static::CONSENT_FORM));

        ReflectionHelper::setObjectProperty(InstrumentRecord::class, 'status', array(static::TEST_EVENT_A => $this->createMockStatus('isComplete', false)), $nextInst);
        ReflectionHelper::setObjectProperty(InstrumentRecord::class, 'nextInstrumentRcd', $nextInst, $this->testObj);

        $notStarted = $this->testObj->isComplete(false, static::TEST_EVENT_A); // don't check next instrument

        $this->assertTrue($notStarted, 'isComplete should have returned TRUE');

        $notStarted = $this->testObj->isComplete(true, static::TEST_EVENT_A); // check next instrument

        $this->assertFalse($notStarted, 'isComplete should have returned FALSE');

        ReflectionHelper::setObjectProperty(InstrumentRecord::class, 'status', array(static::TEST_EVENT_A => $this->createMockStatus('isComplete', true)), $nextInst);

        $notStarted = $this->testObj->isComplete(true, static::TEST_EVENT_A); // check next instrument

        $this->assertTrue($notStarted, 'isComplete should have returned TRUE');
    }

    public function testGetCompletedFieldCounts_isCatNotCompleteNoNext() {
        ReflectionHelper::setObjectProperty(Instrument::class, 'isCAT', true, $this->testInstrument);
        ReflectionHelper::setObjectProperty(InstrumentRecord::class, 'status', array(static::TEST_EVENT_A => $this->createMockStatus('isComplete', false)), $this->testObj);

        $result = $this->testObj->getCompletedFieldCounts(static::TEST_EVENT_A);

        $this->assertEquals(0, $result->getCompletedCount(), 'Expected completed count to be 0');
        $this->assertEquals(1, $result->getRequiredCount(), 'Expected required count to be 1');
    }

    public function testGetCompletedFieldCounts_isCatCompleteNoNext() {
        ReflectionHelper::setObjectProperty(Instrument::class, 'isCAT', true, $this->testInstrument);
        ReflectionHelper::setObjectProperty(InstrumentRecord::class, 'status', array(static::TEST_EVENT_A => $this->createMockStatus('isComplete', true)), $this->testObj);

        $result = $this->testObj->getCompletedFieldCounts(static::TEST_EVENT_A);

        $this->assertEquals(1, $result->getCompletedCount(), 'Expected completed count to be 1');
        $this->assertEquals(1, $result->getRequiredCount(), 'Expected required count to be 1');
    }

    public function testGetCompletedFieldCounts_notCatNoNext() {
        $knownDataFields = array(static::EMAIL_FIELD, static::ADDRESS_FIELD);
        $testRcd = $this->generateRedcapRecord(
            array(static::TEST_RCD_4_ID),
            null,
            array(static::DEMOGRAPHICS_FORM),
            array(static::TEST_EVENT_A),
            $knownDataFields
            );

        $this->setCallback('exportRecords', function() use ($testRcd) { return $testRcd; } );

        $testInst = $this->createInstrument(static::DEMOGRAPHICS_FORM);
        $instRcd = new InstrumentRecord($this->stubRedcapProj, $testInst, static::TEST_RCD_4_ID);

        $completedCnt = count($knownDataFields);
        $requiredCnt = count($testInst->getRequiredFormFieldNames()) - 1; // don't include the record id field

        ReflectionHelper::setObjectProperty(Instrument::class, 'isCAT', false, $this->testInstrument);

        $result = $instRcd->getCompletedFieldCounts(static::TEST_EVENT_A);

        $this->assertGreaterThanOrEqual($completedCnt, $result->getCompletedCount(), self::UNEXPECTED_COMPLETED_COUNT);
        $this->assertEquals($requiredCnt, $result->getRequiredCount(), self::UNEXPECTED_REQUIRED_COUNT);
    }

    public function testGetCompletedFieldCounts_notCatWithNext() {
        $knownDataFields = array(static::EMAIL_FIELD, static::ADDRESS_FIELD, static::PHONE_FIELD);
        $testRcd = $this->generateRedcapRecord(
            array(static::TEST_RCD_1_ID),
            null,
            array(static::DEMOGRAPHICS_FORM, static::CONSENT_FORM),
            array(static::TEST_EVENT_A),
            $knownDataFields
            );

        $this->setCallback('exportRecords', function() use ($testRcd) { return $testRcd; } );

        $topInst = $this->createInstrument(static::DEMOGRAPHICS_FORM);
        $nextInst = $this->createInstrument(static::CONSENT_FORM);

        $completedCnt = count($knownDataFields);
        $requiredCnt = count($topInst->getRequiredFormFieldNames()) - 1; // don't count record id field

        $topInst->setNextInstrument($nextInst);

        $topTestRcd = new InstrumentRecord($this->stubRedcapProj, $topInst, static::TEST_RCD_1_ID);

        $result = $topTestRcd->getCompletedFieldCounts(static::TEST_EVENT_A);

        $this->assertGreaterThanOrEqual($completedCnt, $result->getCompletedCount(), self::UNEXPECTED_COMPLETED_COUNT);
        $this->assertEquals($requiredCnt, $result->getRequiredCount(), self::UNEXPECTED_REQUIRED_COUNT);

        $nextTestRcd = $topTestRcd->getNextInstrumentRecord();

        $this->assertNotNull($nextTestRcd);

        $completedCnt = 0;
        $requiredCnt = count($nextInst->getRequiredFormFieldNames());

        $result = $nextTestRcd->getCompletedFieldCounts(static::TEST_EVENT_A);

        $this->assertGreaterThanOrEqual($completedCnt, $result->getCompletedCount(), self::UNEXPECTED_COMPLETED_COUNT);
        $this->assertEquals($requiredCnt, $result->getRequiredCount(), self::UNEXPECTED_REQUIRED_COUNT);
    }

    public function testGetCumulativeFieldCounts_notCatWithNext() {
        $knownDataFields = array(static::EMAIL_FIELD, static::ADDRESS_FIELD, static::PHONE_FIELD, static::CONSENT_FIELD, static::PAST_ILLNESS_FIELD);
        $testRcd = $this->generateRedcapRecord(
            array(static::TEST_RCD_1_ID),
            null,
            array(static::DEMOGRAPHICS_FORM, static::CONSENT_FORM, static::HISTORY_FORM),
            null,
            $knownDataFields
            );

        $this->setCallback('exportRecords', function() use ($testRcd) { return $testRcd; } );

        $demoInst = $this->createInstrument(static::DEMOGRAPHICS_FORM);
        $consentInst = $this->createInstrument(static::CONSENT_FORM);
        $historyInst = $this->createInstrument(static::HISTORY_FORM);

        $completedCnt = count($knownDataFields);
        $requiredCnt = count($demoInst->getRequiredFormFieldNames()) + count($consentInst->getRequiredFormFieldNames()) + count($historyInst->getRequiredFormFieldNames()) - 1; // don't count record id field

        $demoInst->setNextInstrument($consentInst);
        $consentInst->setNextInstrument($historyInst);

        $localTestObj = new InstrumentRecord($this->stubRedcapProj, $demoInst, static::TEST_RCD_1_ID);

        $result = $localTestObj->getCumulativeFieldCounts(static::TEST_EVENT_A);

        $this->assertGreaterThanOrEqual($completedCnt, $result->getCompletedCount(), self::UNEXPECTED_COMPLETED_COUNT);
        $this->assertEquals($requiredCnt, $result->getRequiredCount(), self::UNEXPECTED_REQUIRED_COUNT);
    }

    public function testInitRequiredFieldCounts_classic_noRequiredFields() {
        $this->useClassicProject();
        $localTestInstrument = $this->createDummyInstrument('test');

        $localTestObj = new InstrumentRecord($this->stubRedcapProj,  $localTestInstrument, static::TEST_RCD_1_ID);

        $fieldCounts = $localTestObj->getCompletedFieldCounts();

        $this->assertEquals(0, $fieldCounts->getCompletedCount(), 'Completed field count should default to 0');
        $this->assertNull($fieldCounts->getFirstIncompleteField(), 'First incomplete field should be null');
    }

    public function testInitRequiredFieldCounts_events_noRecord() {
        $localTestInstrument = $this->createInstrument(static::DEMOGRAPHICS_FORM);

        $localTestObj = new InstrumentRecord($this->stubRedcapProj,  $localTestInstrument, static::INVALID_TEST_RCD_ID);

        $fieldCounts = $localTestObj->getCompletedFieldCounts();

        $this->assertEquals(0, $fieldCounts->getCompletedCount(), 'Completed field count should default to 0');
        $this->assertNull($fieldCounts->getFirstIncompleteField(), 'First incomplete field should be null');
    }

/*
    public function testInitRequiredFieldCounts_events_withRequiredFields() {
        $testRcd = $this->generateRedcapRecord(
            array(static::TEST_RCD_1_ID),
            array(static::EMAIL_FIELD, static::PHONE_FIELD),
            null,
            array(static::TEST_EVENT_A),
            array(static::EMAIL_FIELD, static::PHONE_FIELD),
            );

        $this->setCallback('exportRecords', function() use ($testRcd) { return $testRcd; } );

        $localTestInstrument = $this->createInstrument(static::DEMOGRAPHICS_FORM);

//        $rcd = $this->generateRedcapRecord(array(static::TEST_RCD_1_ID), null, array(static::DEMOGRAPHICS_FORM), array(static::TEST_EVENT_A));

        $localTestObj = new InstrumentRecord($this->stubRedcapProj, $localTestInstrument, static::TEST_RCD_1_ID);

        $fieldCounts = $localTestObj->getCompletedFieldCounts(static::TEST_EVENT_A);

        // subtract 1 for the record id field
        $this->assertEquals(count(static::TEST_RCD_1) - 1, $fieldCounts->getCompletedCount(), 'Completed field count incorrect');
        $this->assertNull($fieldCounts->getFirstIncompleteField(), 'First incomplete field should be null');
    }

    public function testInitRequiredFieldCounts_events_withIncompleteField() {
        $this->setCallback('exportRecords', function($format = 'php', $type = 'flat', $recordIds = null) {
            return array(array_merge(static::TEST_RCD_1, array(static::MISSING_DATA_FIELD => '')));
        } );

        $localTestInstrument = $this->createInstrument(static::DEMOGRAPHICS_FORM);

        $localTestObj = new InstrumentRecord($this->stubRedcapProj,  $localTestInstrument, static::TEST_RCD_1_ID);

        $fieldCounts = $localTestObj->getCompletedFieldCounts();

        // subtract 1 for the record id field
        $this->assertEquals(count(static::TEST_RCD_1) - 1, $fieldCounts->getCompletedCount(), 'Completed field count incorrect');
        $this->assertEquals(static::MISSING_DATA_FIELD, $fieldCounts->getFirstIncompleteField(), 'First incomplete field is not correct');
    }

    public function testInitRequiredFieldCounts_events_withTwoIncompleteField2() {
        $this->setCallback('exportRecords', function($format = 'php', $type = 'flat', $recordIds = null) {
            return array(array_merge(static::TEST_RCD_1, array(
                static::SHORTNAME_FIELD => '',
                static::MISSING_DATA_FIELD => ''
            )));
        } );

        $localTestInstrument = $this->createInstrument(static::DEMOGRAPHICS_FORM);

        $localTestObj = new InstrumentRecord($this->stubRedcapProj,  $localTestInstrument, static::TEST_RCD_1_ID);

        $fieldCounts = $localTestObj->getCompletedFieldCounts();

        // subtract 1 for the record id field
        $this->assertEquals(count(static::TEST_RCD_1) - 1, $fieldCounts->getCompletedCount(), 'Completed field count incorrect');
        $this->assertEquals(static::SHORTNAME_FIELD, $fieldCounts->getFirstIncompleteField(), 'First incomplete field is not correct');
    }
*/
/*
    public function testAppendNextFieldToUrl_noIncompleteFields() {
        $url = $this->testObj->appendNextFieldToUrl('');

        $this->assertEquals('', $url);
    }

    public function testAppendNextFieldToUrl_inParent() {
        $testField = 'testField';

        ReflectionHelper::setObjectProperty(InstrumentRecord::class, 'firstIncompleteField', $testField, $this->testObj);

        $url = $this->testObj->appendNextFieldToUrl('');

        $this->assertEquals('#'.$testField.'-tr', $url);
    }

    public function testAppendNextFieldToUrl_inNext() {
        $this->setCallback('exportRecords', function($format = 'php', $type = 'flat', $recordIds = null) {
            return array(array_merge(static::TEST_RCD_1, array(
                static::FULLNAME_FIELD => ''
            )));
        } );

        $testInstrument = $this->createInstrument(static::DEMOGRAPHICS_FORM);
        $nextInstrument = $this->createInstrument(static::CONSENT_FORM);

        $testInstrument->setNextInstrument($nextInstrument);

        $testObj = new InstrumentRecord($this->stubRedcapProj, $testInstrument, static::TEST_RCD_1_ID);

        $url = $testObj->appendNextFieldToUrl('');

        $this->assertEquals('#'.static::FULLNAME_FIELD.'-tr', $url);
    }

    public function testGetNextIntrumentRecord_withoutNextInstrument() {
        $testInstrument = $this->createInstrument(static::DEMOGRAPHICS_FORM);

        $testObj = new InstrumentRecord($this->stubRedcapProj, $testInstrument);

        $nextObj = $testObj->getNextInstrumentRecord();

        $this->assertNull($nextObj);
    }

    public function testGetNextIntrumentRecord_withNextInstrument() {
        $testInstrument = $this->createInstrument(static::DEMOGRAPHICS_FORM);
        $nextInstrument = $this->createInstrument(static::CONSENT_FORM);

        $testInstrument->setNextInstrument($nextInstrument);

        $testObj = new InstrumentRecord($this->stubRedcapProj, $testInstrument);

        $nextObj = $testObj->getNextInstrumentRecord();

        $this->assertNotNull($nextObj);
        $this->assertNotEquals($testObj, $nextObj);
        $this->assertEquals($nextInstrument, $nextObj->getInstrument());
    }
*/
/*
    public function testSetFieldValue_withNextInstrument() {
        $this->useClassicProject();

        $testInstrument = $this->createInstrument(static::DEMOGRAPHICS_FORM);
        $nextInstrument = $this->createInstrument(static::CONSENT_FORM);

        $testInstrument->setNextInstrument($nextInstrument);

        $testObj = new InstrumentRecord($this->stubRedcapProj, $testInstrument);
        $nextObj = $testObj->getNextInstrumentRecord();

        $testRcd = $this->createInstrument(static::DEMOGRAPHICS_FORM);

        $testRcd = array_merge(self::TEST_RCD_1, array (self::CONSENT_FIELD => 'y', self::FULLNAME_FIELD => 'Robert Smith'));
        $testObj->loadRecord(array($testRcd));

        $textFields = ReflectionHelper::getObjectProperty(Record::class, 'fieldArray', $testObj);
        $nextFields = ReflectionHelper::getObjectProperty(Record::class, 'fieldArray', $nextObj);

        $this->assertIsArray($textFields);
        $this->assertIsArray($nextFields);

        $this->assertCount(1, $textFields);
        $this->assertCount(1, $nextFields);

        $textFields = array_shift($textFields);
        $nextFields = array_shift($nextFields);

        $this->assertCount(2, $textFields);
        $this->assertCount(2, $nextFields);

        $this->assertEquals($testRcd[self::CONSENT_FIELD], $nextFields[self::CONSENT_FIELD]);
        $this->assertEquals($testRcd[self::FULLNAME_FIELD], $nextFields[self::FULLNAME_FIELD]);
    }
*/
    public function testGetFieldValue_withNextInstrument() {
        $testInst = $this->createInstrument(static::DEMOGRAPHICS_FORM);
        $nextInst = $this->createInstrument(static::CONSENT_FORM);

        $testInst->setNextInstrument($nextInst);

        $instRcd = new InstrumentRecord($this->stubRedcapProj, $testInst);
        $nextObj = $instRcd->getNextInstrumentRecord();

        ReflectionHelper::setObjectProperty(Record::class, 'fieldArray', array( self::TEST_EVENT_A => array(self::SHORTNAME_FIELD => 'Bob', self::EMAIL_FIELD => self::BOB_EMAIL_ADDRESS)), $instRcd);
        ReflectionHelper::setObjectProperty(Record::class, 'fieldArray', array( self::TEST_EVENT_A => array(self::CONSENT_FIELD => 'n', self::FULLNAME_FIELD => self::JANE_FULL_NAME)), $nextObj);

        $this->assertEquals('Bob', $instRcd->getFieldValue(self::SHORTNAME_FIELD));
        $this->assertEquals(self::BOB_EMAIL_ADDRESS, $instRcd->getFieldValue(self::EMAIL_FIELD));
        $this->assertEquals('n', $instRcd->getFieldValue(self::CONSENT_FIELD));
        $this->assertEquals(self::JANE_FULL_NAME, $instRcd->getFieldValue(self::FULLNAME_FIELD));
    }

    public function testGetREDCapArray_withNextInstrument() {
        $testInst = $this->createInstrument(static::DEMOGRAPHICS_FORM);
        $nextInst = $this->createInstrument(static::CONSENT_FORM);

        $testInst->setNextInstrument($nextInst);

        $instRcd = new InstrumentRecord($this->stubRedcapProj, $testInst);
        $nextObj = $instRcd->getNextInstrumentRecord();

        ReflectionHelper::setObjectProperty(Record::class, 'recordId', self::TEST_RCD_2_ID, $instRcd);
        ReflectionHelper::setObjectProperty(Record::class, 'recordId', self::TEST_RCD_2_ID, $nextObj);
        ReflectionHelper::setObjectProperty(Record::class, 'fieldArray', array( self::TEST_EVENT_A => array(self::SHORTNAME_FIELD => 'Bob', self::EMAIL_FIELD => self::BOB_EMAIL_ADDRESS)), $instRcd);
        ReflectionHelper::setObjectProperty(Record::class, 'fieldArray', array( self::TEST_EVENT_A => array(self::CONSENT_FIELD => 'n', self::FULLNAME_FIELD => self::JANE_FULL_NAME)), $nextObj);

        $redcapArray = $instRcd->getREDCapArray();

        $this->assertIsArray($redcapArray);
        $this->assertCount(1, $redcapArray);

        $redcapRcd = array_shift($redcapArray);
        $this->assertIsArray($redcapRcd);

        $this->assertArrayHasKey(self::RCD_ID_FIELD, $redcapRcd);
        $this->assertArrayHasKey(Record::REDCAP_EVENT_NAME, $redcapRcd);
        $this->assertArrayHasKey(self::SHORTNAME_FIELD, $redcapRcd);
        $this->assertArrayHasKey(self::EMAIL_FIELD, $redcapRcd);
        $this->assertArrayHasKey(self::CONSENT_FIELD, $redcapRcd);
        $this->assertArrayHasKey(self::FULLNAME_FIELD, $redcapRcd);

        $this->assertEquals(self::TEST_RCD_2_ID, $redcapRcd[self::RCD_ID_FIELD]);
        $this->assertEquals(self::TEST_EVENT_A, $redcapRcd[Record::REDCAP_EVENT_NAME]);
        $this->assertEquals('Bob', $redcapRcd[self::SHORTNAME_FIELD]);
        $this->assertEquals(self::BOB_EMAIL_ADDRESS, $redcapRcd[self::EMAIL_FIELD]);
        $this->assertEquals('n', $redcapRcd[self::CONSENT_FIELD]);
        $this->assertEquals(self::JANE_FULL_NAME, $redcapRcd[self::FULLNAME_FIELD]);
    }

    public function testGetREDCapArray_withClassicProject() {
        $this->setCallback('exportEvents', function() { return null; } );

        $testInst = $this->createInstrument(static::DEMOGRAPHICS_FORM);
        $nextInst = $this->createInstrument(static::CONSENT_FORM);

        $testInst->setNextInstrument($nextInst);

        $instRcd = new InstrumentRecord($this->stubRedcapProj, $testInst);
        $nextObj = $instRcd->getNextInstrumentRecord();

        ReflectionHelper::setObjectProperty(Record::class, 'recordId', self::TEST_RCD_3_ID, $instRcd);
        ReflectionHelper::setObjectProperty(Record::class, 'recordId', self::TEST_RCD_3_ID, $nextObj);
        ReflectionHelper::setObjectProperty(Record::class, 'fieldArray', array(self::SHORTNAME_FIELD => 'Bob', self::EMAIL_FIELD => self::BOB_EMAIL_ADDRESS), $instRcd);
        ReflectionHelper::setObjectProperty(Record::class, 'fieldArray', array(self::CONSENT_FIELD => 'n', self::FULLNAME_FIELD => self::JANE_FULL_NAME), $nextObj);

        $redcapArray = $instRcd->getREDCapArray();

        $this->assertIsArray($redcapArray);
        $this->assertCount(1, $redcapArray);

        $redcapRcd = array_shift($redcapArray);
        $this->assertIsArray($redcapRcd);

        $this->assertArrayHasKey(self::RCD_ID_FIELD, $redcapRcd);
        $this->assertArrayHasKey(self::SHORTNAME_FIELD, $redcapRcd);
        $this->assertArrayHasKey(self::EMAIL_FIELD, $redcapRcd);
        $this->assertArrayHasKey(self::CONSENT_FIELD, $redcapRcd);
        $this->assertArrayHasKey(self::FULLNAME_FIELD, $redcapRcd);
        $this->assertArrayNotHasKey(Record::REDCAP_EVENT_NAME, $redcapRcd);

        $this->assertEquals(self::TEST_RCD_3_ID, $redcapRcd[self::RCD_ID_FIELD]);
        $this->assertEquals('Bob', $redcapRcd[self::SHORTNAME_FIELD]);
        $this->assertEquals(self::BOB_EMAIL_ADDRESS, $redcapRcd[self::EMAIL_FIELD]);
        $this->assertEquals('n', $redcapRcd[self::CONSENT_FIELD]);
        $this->assertEquals(self::JANE_FULL_NAME, $redcapRcd[self::FULLNAME_FIELD]);
    }


    public function testGetTimestamp_classic_noTimestamp() {
        $this->useClassicProject();

        $testRcd = $this->generateRedcapRecord(array(static::TEST_RCD_2_ID), null, array(static::CONSENT_FORM));

        $this->setCallback('exportRecords', function() use ($testRcd) { return $testRcd; } );

        $instRcd = new InstrumentRecord(
            $this->stubRedcapProj,
            $this->createInstrument(static::CONSENT_FORM),
            static::TEST_RCD_2_ID);

        $timestamp = $instRcd->getTimestamp();

        $this->assertNull($timestamp);
    }

    public function testGetTimestamp_classic_blankTimestmap() {
        $this->useClassicProject();

        $testRcd = $this->generateRedcapRecord(array(static::TEST_RCD_4_ID), null, array(static::CONSENT_FORM));
        $testRcd[0][static::CONSENT_FORM.self::TIMESTAMP_SUFFIX] = '';

        $this->setCallback('exportRecords', function() use ($testRcd) { return $testRcd; } );

        $instRcd = new InstrumentRecord(
            $this->stubRedcapProj,
            $this->createInstrument(static::CONSENT_FORM),
            static::TEST_RCD_4_ID);

        $timestamp = $instRcd->getTimestamp();

        $this->assertNull($timestamp);
    }

    public function testGetTimestamp_classic_withTimestmap() {
        $this->useClassicProject();

        $testDateTime = $this->generateRandomDateTime();

        $testRcd = $this->generateRedcapRecord(array(static::TEST_RCD_4_ID), null, array(static::CONSENT_FORM));
        $testRcd[0][static::CONSENT_FORM.self::TIMESTAMP_SUFFIX] = $testDateTime->format(Record::REDCAP_TIMESTAMP_FORMAT); // '2019-06-15 17:45:07';

        $this->setCallback('exportRecords', function() use ($testRcd) { return $testRcd; } );

        $instRcd = new InstrumentRecord(
            $this->stubRedcapProj,
            $this->createInstrument(static::CONSENT_FORM),
            static::TEST_RCD_4_ID);

        $timestamp = $instRcd->getTimestamp();

        $this->assertNotNull($timestamp);
        $this->assertEquals($testDateTime, $timestamp);
    }


    public function testGetTimestamp_events_withTimestmap() {
        $testDateTimeArray = array(
            static::TEST_EVENT_A => $this->generateRandomDateTime(),
            static::TEST_EVENT_B => $this->generateRandomDateTime(),
            static::TEST_EVENT_C => $this->generateRandomDateTime()
            );

        $testRcd = $this->generateRedcapRecord(array(static::TEST_RCD_3_ID), null, array(static::HISTORY_FORM));
        foreach ($testDateTimeArray as $event => $timestamp) {
            $testRcd[array_search($event, array_keys($testDateTimeArray))][static::HISTORY_FORM.self::TIMESTAMP_SUFFIX] = $timestamp->format(Record::REDCAP_TIMESTAMP_FORMAT);
        }

        $this->setCallback('exportRecords', function() use ($testRcd) { return $testRcd; } );

        $instRcd = new InstrumentRecord(
            $this->stubRedcapProj,
            $this->createInstrument(static::HISTORY_FORM),
            static::TEST_RCD_3_ID);

        foreach ($testDateTimeArray as $event => $testTimestamp) {
            $timestamp = $instRcd->getTimestamp($event);

            $this->assertNotNull($timestamp);
            $this->assertEquals($testTimestamp, $timestamp);
        }
    }

}
