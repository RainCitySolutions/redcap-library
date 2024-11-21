<?php
declare(strict_types = 1);
namespace RainCity\REDCap;


use PHPUnit\Framework\Attributes\CoversClass;
use RainCity\TestHelper\ReflectionHelper;

#[CoversClass('\RainCity\REDCap\Record')]
class RecordTest extends REDCapTestCase
{
    private const SHOULD_BE_NO_FIELD_DATA = 'There should not be any field data';
    //***********************************************************************
    //***********************************************************************
    //* Constructor tests
    //***********************************************************************
    //***********************************************************************

    /**
     *
     */
    public function testCtor_noArg()
    {
        $this->expectException("ArgumentCountError");
        new Record();   // NOSONAR - ignore useless object instantiation
    }

    /**
     *
     */
    public function testCtor_invalidArg()
    {
        $this->expectException("TypeError");
        new Record((object)[], 'fields');   // NOSONAR - ignore useless object instantiation
    }

    /**
     *
     */
    public function testCtor_onlyProj()
    {
        $obj = new Record($this->stubRedcapProj);

        $proj = ReflectionHelper::getObjectProperty(Record::class, 'redcapProj', $obj);
        $fieldNames = ReflectionHelper::getObjectProperty(Record::class, 'redcapFields', $obj);
        $fieldArray = ReflectionHelper::getObjectProperty(Record::class, 'fieldArray', $obj);
        $dirtyFlags = ReflectionHelper::getObjectProperty(Record::class, 'dirtyFieldArray', $obj);

        $this->assertEquals($this->stubRedcapProj, $proj);
        $this->assertEmpty($fieldNames, 'Field name list should be empty');
        $this->assertEmpty($fieldArray, 'Field list should be empty');
        $this->assertEmpty($dirtyFlags, 'Dirty flags list should be empty');

        $redcapFields = $this->stubRedcapProj->exportFieldNames();
        $validFields = ReflectionHelper::getObjectProperty(Record::class, 'validFields', $obj);

        foreach ($redcapFields as $field) {
            $this->assertContains($field['export_field_name'], $validFields);
        }
    }

    /**
     *
     */
    public function testCtor_projAndFields()
    {
        $testFields = array(static::EMAIL_FIELD);
        $obj = new Record($this->stubRedcapProj, $testFields);

        $fields = ReflectionHelper::getObjectProperty(Record::class, 'redcapFields', $obj);

        $this->assertEquals($testFields, $fields);
    }

    /**
     *
     */
    public function testCtor_existingClassicRecord()
    {
        $this->useClassicProject();

        $obj = new Record($this->stubRedcapProj, [], static::TEST_RCD_0_ID);

        $recordId = ReflectionHelper::getObjectProperty(Record::class, 'recordId', $obj);
        $fieldNames = ReflectionHelper::getObjectProperty(Record::class, 'redcapFields', $obj);
        $fieldArray = ReflectionHelper::getObjectProperty(Record::class, 'fieldArray', $obj);
        $dirtyFlags = ReflectionHelper::getObjectProperty(Record::class, 'dirtyFieldArray', $obj);

        $this->assertEquals(static::TEST_RCD_0_ID, $recordId);
        $this->assertEmpty($fieldNames);
        $this->assertEmpty($fieldArray);
        $this->assertEmpty($dirtyFlags, 'Dirty flags list should be empty');
    }



    //***********************************************************************
    //***********************************************************************
    //* IsValidEvent tests
    //***********************************************************************
    //***********************************************************************

    /**
     */
    public function testIsValidEvent_noParam() {
        $this->expectException('ArgumentCountError');

        $obj = new Record($this->stubRedcapProj);

        ReflectionHelper::invokeObjectMethod(Record::class, $obj, 'isValidEvent');
    }

    /**
     */
    public function testIsValidEvent_classic_null() {
        $this->useClassicProject();

        $obj = new Record($this->stubRedcapProj);

        $this->assertTrue(ReflectionHelper::invokeObjectMethod(Record::class, $obj, 'isValidEvent', null));
    }

    /**
     */
    public function testIsValidEvent_classic_anyEvent() {
        $this->useClassicProject();

        $obj = new Record($this->stubRedcapProj);

        $this->assertTrue(ReflectionHelper::invokeObjectMethod(Record::class, $obj, 'isValidEvent', 'anyEvent'));
    }

    /**
     */
    public function testIsValidEvent_events_null() {
        $obj = new Record($this->stubRedcapProj);

        $this->assertTrue(ReflectionHelper::invokeObjectMethod(Record::class, $obj, 'isValidEvent', null));
    }

    /**
     */
    public function testIsValidEvent_events_validEvent() {
        $obj = new Record($this->stubRedcapProj);

        $this->assertTrue(ReflectionHelper::invokeObjectMethod(Record::class, $obj, 'isValidEvent', static::TEST_EVENT_C));
    }

    /**
     */
    public function testIsValidEvent_events_invalidEvent() {
        $obj = new Record($this->stubRedcapProj);

        $this->assertFalse(ReflectionHelper::invokeObjectMethod(Record::class, $obj, 'isValidEvent', 'anyEvent'));
    }

    //***********************************************************************
    //***********************************************************************
    //* ValidateRecord tests
    //***********************************************************************
    //***********************************************************************

    /**
     * Validate behavior when input contains multiple records
     */
    public function testValidateRecord_emptyRecord() {
        $this->useClassicProject();

        $obj = new Record($this->stubRedcapProj);

        $testRcd = array();

        $result = ReflectionHelper::invokeObjectMethod(Record::class, $obj, 'validateRecord', $testRcd);
        $this->assertTrue($result);
    }

    /**
     * Validate behavior when input contains multiple records
     */
    public function testValidateRecord_classic_multipleRecords() {
        $this->useClassicProject();

        $obj = new Record($this->stubRedcapProj);

        $testRcd = array(
            array (static::RCD_ID_FIELD => static::TEST_RCD_1_ID),
            array (static::RCD_ID_FIELD => static::TEST_RCD_2_ID)
        );

        $result = ReflectionHelper::invokeObjectMethod(Record::class, $obj, 'validateRecord', $testRcd);

        $this->assertFalse($result);
    }

    /**
     * Validate behavior when input doesn't contain REDCap data
     */
    public function testValidateRecord_classic_notRedcapData() {
        $this->useClassicProject();

        $obj = new Record($this->stubRedcapProj);

        $testRcd = array(1);

        $result = ReflectionHelper::invokeObjectMethod(Record::class, $obj, 'validateRecord', $testRcd);

        $this->assertFalse($result);
    }

    /**
     * Validate behavior when input already has a record id
     */
    public function testValidateRecord_classic_rcdIdExists() {
        $this->useClassicProject();

        $obj = new Record($this->stubRedcapProj);

        $testRcd = $this->generateRedcapRecord(array(self::TEST_RCD_2_ID));

        $result = ReflectionHelper::invokeObjectMethod(Record::class, $obj, 'validateRecord', $testRcd);

        $this->assertTrue($result);
    }

    /**
     * Validate behavior when input is missing the record id
     */
    public function testValidateRecord_classic_missingRcdId() {
        $this->useClassicProject();

        $obj = new Record($this->stubRedcapProj, [], static::TEST_RCD_3_ID);

        $testRcd = $this->generateRedcapRecord(array(self::TEST_RCD_3_ID), null, array(self::CONSENT_FORM, self::HISTORY_FORM));

        $result = ReflectionHelper::invokeObjectMethod(Record::class, $obj, 'validateRecord', $testRcd);

        $this->assertTrue($result);
    }


    /**
     * Validate behavior when neither the project or record have defined the record id
     */
    public function testValidateRecord_classic_recordIdNotSet() {
        $this->useClassicProject();

        $obj = new Record($this->stubRedcapProj);

        $testRcd = array(array());

        $result = ReflectionHelper::invokeObjectMethod(Record::class, $obj, 'validateRecord', $testRcd);

        $this->assertFalse($result);
    }

    /**
     * Validate behavior when input not REDCap data
     */
    public function testValidateRecord_events_notRedcapData() {
        $obj = new Record($this->stubRedcapProj);

        $testRcd = array(1);

        $result = ReflectionHelper::invokeObjectMethod(Record::class, $obj, 'validateRecord', $testRcd);

        $this->assertFalse($result);
    }

    /**
     * Validate behavior when input not REDCap data
     */
    public function testValidateRecord_events_notRedcapData2() {
        $obj = new Record($this->stubRedcapProj);

        $testRcd = array(array(), 2);

        $result = ReflectionHelper::invokeObjectMethod(Record::class, $obj, 'validateRecord', $testRcd);

        $this->assertFalse($result);
    }

    /**
     * Validate behavior when input has more events than project
     */
/*
    public function testValidateRecord_events_moreRecordsThanEvents() {
        $obj = new Record($this->stubRedcapProj);

        // Record has 2 entries but there are only 1 events
        ReflectionHelper::setObjectProperty(Record::class, 'redcapEvents', array(static::TEST_EVENT_B), $obj);

        $testRcd = array(
            array (),
            array ()
        );

        $result = ReflectionHelper::invokeObjectMethod(Record::class, $obj, 'validateRecord', $testRcd);

        $this->assertTrue($result);
    }
*/
    /**
     * Validate behavior when input has more events than project
     */
/*
    public function testValidateRecord_events_moreEventsThanRecords() {
        $obj = new Record($this->stubRedcapProj);

        // Record has 2 entries but there are only 3 events
        $testRcd = array(
            array (),
            array ()
        );

        $result = ReflectionHelper::invokeObjectMethod(Record::class, $obj, 'validateRecord', $testRcd);

        $this->assertFalse($result);
    }
*/
    /**
     * Validate behavior when input already has a record id
     */
    public function testValidateRecord_events_rcdIdAndEventExists() {
        $obj = new Record($this->stubRedcapProj);

        ReflectionHelper::setObjectProperty(Record::class, 'redcapEvents', array(static::TEST_EVENT_A, static::TEST_EVENT_B), $obj);
        $testRcd = array(
            array(
                self::RCD_ID_FIELD => self::TEST_RCD_4_ID,
                \RainCity\REDCap\Record::REDCAP_EVENT_NAME => self::TEST_EVENT_A
            ),
            array(
                self::RCD_ID_FIELD => self::TEST_RCD_4_ID,
                \RainCity\REDCap\Record::REDCAP_EVENT_NAME => self::TEST_EVENT_B
            )
        );

        $result = ReflectionHelper::invokeObjectMethod(Record::class, $obj, 'validateRecord', $testRcd);

        $this->assertTrue($result);
    }

    /**
     * Validate behavior when input is missing the record id
     */
    public function testValidateRecord_events_missingRcdId() {
        $obj = new Record($this->stubRedcapProj);

        ReflectionHelper::setObjectProperty(Record::class, 'recordId', static::TEST_RCD_3_ID, $obj);
        ReflectionHelper::setObjectProperty(Record::class, 'redcapEvents', array(static::TEST_EVENT_B, static::TEST_EVENT_C), $obj);
        $testRcd = array(
            array(
                \RainCity\REDCap\Record::REDCAP_EVENT_NAME => self::TEST_EVENT_B
            ),
            array(
                \RainCity\REDCap\Record::REDCAP_EVENT_NAME => self::TEST_EVENT_C
            )
        );

        $result = ReflectionHelper::invokeObjectMethod(Record::class, $obj, 'validateRecord', $testRcd);

        $this->assertFalse($result);
    }

    /**
     * Validate behavior when input is missing the event names
     */
    public function testValidateRecord_events_missingEventName() {
        $obj = new Record($this->stubRedcapProj);

        ReflectionHelper::setObjectProperty(Record::class, 'redcapEvents', array(static::TEST_EVENT_A, static::TEST_EVENT_C), $obj);
        $testRcd = array(
            array(
                static::RCD_ID_FIELD => static::TEST_RCD_4_ID
            ),
            array(
                static::RCD_ID_FIELD => static::TEST_RCD_4_ID
            )
        );

        $result = ReflectionHelper::invokeObjectMethod(Record::class, $obj, 'validateRecord', $testRcd);

        $this->assertFalse($result);
    }

    /**
     * Validate behavior when input has is _complete, _timestamp and survey identifier fields
     */
    public function testValidateRecord_classic_completedTimestampFields() {
        $this->useClassicProject();

        $obj = new Record($this->stubRedcapProj);

        $testRcd = array(
            array(
                static::RCD_ID_FIELD => static::TEST_RCD_4_ID,
                static::CONSENT_FIELD => 'y',
                static::CONSENT_FORM . '_complete' => 2,
                static::CONSENT_FORM . '_timestamp' => '',
                Record::REDCAP_SURVEY_IDENTIFIER => ''
            )
        );

        $result = ReflectionHelper::invokeObjectMethod(Record::class, $obj, 'validateRecord', $testRcd);

        $this->assertTrue($result);
    }


    //***********************************************************************
    //***********************************************************************
    //* GetFields tests
    //***********************************************************************
    //***********************************************************************

    /**
     * Validate behavior when input is missing the event names
     */
    public function testGetFields() {
        $obj = new Record($this->stubRedcapProj);

        $testFields = array(
            static::EMAIL_FIELD,
            static::PHONE_FIELD,
            static::CONSENT_FIELD
            );

        ReflectionHelper::setObjectProperty(Record::class, 'recordIdField', static::RCD_ID_FIELD, $obj);
        ReflectionHelper::setObjectProperty(Record::class, 'redcapFields', $testFields, $obj);

        $resultFields = $obj->getFields();

        $this->assertEquals(count($testFields) + 1, count($resultFields));
        $this->assertContains(static::RCD_ID_FIELD, $resultFields);

        foreach($testFields as $field) {
            $this->assertContains($field, $resultFields);
        }
    }



    //***********************************************************************
    //***********************************************************************
    //* GetRecordIdFieldName tests
    //***********************************************************************
    //***********************************************************************

    /**
     */
    public function testGetRecordIdFieldName() {
        $obj = new Record($this->stubRedcapProj);

        $rcdIdField = ReflectionHelper::invokeObjectMethod(Record::class, $obj, 'getRecordIdFieldName');

        $this->assertEquals(static::RCD_ID_FIELD, $rcdIdField);
    }


    //***********************************************************************
    //***********************************************************************
    //* LoadRecord tests
    //***********************************************************************
    //***********************************************************************

    /**
     * Validate behavior when loadRecord is called with an empty array
     *
     */
    public function testLoadRecord_emptyArray()
    {
        $obj = new Record($this->stubRedcapProj);

        $result = $obj->loadRecord(array());

        $this->assertFalse($result);

        $fields = ReflectionHelper::getObjectProperty(Record::class, 'fieldArray', $obj);

        $this->assertEmpty($fields, self::SHOULD_BE_NO_FIELD_DATA);
    }

    /**
     *
     */
    public function testLoadRecord_multipleRecords () {
        $this->useClassicProject();

        $testRcds = $this->generateRedcapRecord(array(self::TEST_RCD_1_ID, self::TEST_RCD_2_ID, self::TEST_RCD_3_ID));

        // Multiple records (record ids) should fail. A Record represents a single record in REDCap.
        $obj = new Record($this->stubRedcapProj);
        $result = $obj->loadRecord($testRcds);

        $this->assertFalse($result);
    }

    /**
     * Validate behavior when loadRecord is called with a plain array (non-associative)
     *
     */
    public function testLoadRecord_plainArray()
    {
        $this->useClassicProject();

        $obj = new Record($this->stubRedcapProj);

        $result = $obj->loadRecord(array(array('foo@bar.net')));

        $this->assertFalse($result);

        $fields = ReflectionHelper::getObjectProperty(Record::class, 'fieldArray', $obj);

        $this->assertEmpty($fields, self::SHOULD_BE_NO_FIELD_DATA);
    }

    /**
     * Validate behavior when loadRecord is called with a null value
     *
     */
    public function testLoadRecord_nullValue()
    {
        $this->useClassicProject();

        $obj = new Record($this->stubRedcapProj);

        $result = $obj->loadRecord(array(array(static::EMAIL_FIELD => null)));

        $this->assertFalse($result);

        $fields = ReflectionHelper::getObjectProperty(Record::class, 'fieldArray', $obj);

        $this->assertEmpty($fields, self::SHOULD_BE_NO_FIELD_DATA);
    }

    /**
     * Validate behavior when loadRecord is called with a bad value type
     *
     */
    public function testLoadRecord_badValueType()
    {
        $this->useClassicProject();

        $obj = new Record($this->stubRedcapProj);

        $result = $obj->loadRecord(array(array(static::PHONE_FIELD => new \stdClass())));

        $this->assertFalse($result);

        $fields = ReflectionHelper::getObjectProperty(Record::class, 'fieldArray', $obj);

        $this->assertEmpty($fields, self::SHOULD_BE_NO_FIELD_DATA);
    }

    /**
     * Validate behavior when loadRecord is called with a bad event
     *
     */
    public function testLoadRecord_invalidEvent()
    {
        $obj = new Record($this->stubRedcapProj);

        $result = $obj->loadRecord(array(array(Record::REDCAP_EVENT_NAME => 'bad_event', 'some_field' => 'some_value')));

        $this->assertFalse($result);

        $fields = ReflectionHelper::getObjectProperty(Record::class, 'fieldArray', $obj);

        $this->assertEmpty($fields, self::SHOULD_BE_NO_FIELD_DATA);
    }

    /**
     * Validate behavior when loadRecord is called with a bad event
     *
     */
    public function testLoadRecord_eventOnNonEventProject()
    {
        // be sure to generate the record before useClassicProject() so its and event record
        $testRecord = $this->generateRedcapRecord(array(static::TEST_RCD_4_ID));

        $this->useClassicProject();

        $obj = new Record($this->stubRedcapProj);

        $result = $obj->loadRecord($testRecord);

        $this->assertFalse($result);

        $fields = ReflectionHelper::getObjectProperty(Record::class, 'fieldArray', $obj);

        $this->assertEmpty($fields, self::SHOULD_BE_NO_FIELD_DATA);
    }

    /**
     * Validate behavior when loadRecord is called with an integer value
     *
     */
    public function testLoadRecord_intValue()
    {
        $this->useClassicProject();

        $testArray = array(array(static::PHONE_FIELD => 2));

        $obj = new Record($this->stubRedcapProj);

        $result = $obj->loadRecord($testArray);

        $this->assertFalse($result);

        $fields = ReflectionHelper::getObjectProperty(Record::class, 'fieldArray', $obj);

        $this->assertEmpty($fields, self::SHOULD_BE_NO_FIELD_DATA);
    }

    /**
     * Validate behavior when loadRecord is called with a string value
     *
     */
    public function testLoadRecord_stringValue()
    {
        $this->useClassicProject();

        $testArray = array(array(static::EMAIL_FIELD => 'bar@foo.edu'));

        $obj = new Record($this->stubRedcapProj);

        $result = $obj->loadRecord($testArray);

        $this->assertFalse($result);

        $fields = ReflectionHelper::getObjectProperty(Record::class, 'fieldArray', $obj);

        $this->assertEmpty($fields, self::SHOULD_BE_NO_FIELD_DATA);
    }

    /**
     * Validate behavior when loadRecord is called with invalid fields
     *
     */
    public function testLoadRecord_invalidFields()
    {
        $this->useClassicProject();

        $testArray = array(array('invalidField' => 'bar@foo.edu'));

        $obj = new Record($this->stubRedcapProj);
        ReflectionHelper::setObjectProperty(Record::class, 'recordId', static::TEST_RCD_1_ID, $obj);

        $result = $obj->loadRecord($testArray);

        $this->assertFalse($result);

        $fields = ReflectionHelper::getObjectProperty(Record::class, 'fieldArray', $obj);

        $this->assertEmpty($fields, self::SHOULD_BE_NO_FIELD_DATA);
    }

    /**
     * Validate behavior when loadRecord is called with a full record
     *
     */
    public function testLoadRecord_classic_fullRecord()
    {
        $this->useClassicProject();

        $obj = new Record($this->stubRedcapProj);

        $testRcd = $this->generateRedcapRecord(array(self::TEST_RCD_1_ID));

        $result = $obj->loadRecord($testRcd);

        $this->assertTrue($result);

        $recordId = ReflectionHelper::getObjectProperty(Record::class, 'recordId', $obj);
        $fieldArray = ReflectionHelper::getObjectProperty(Record::class, 'fieldArray', $obj);

        $this->assertEquals(static::TEST_RCD_1_ID, $recordId, 'Record Id not loaded propertly');
        $this->assertEquals(count($testRcd[0]) - 1, count($fieldArray), 'Fields not loaded properly, fieldArray wrong size');

        // add back in record id field
        $fieldArray = array_merge($fieldArray, array(static::RCD_ID_FIELD => static::TEST_RCD_1_ID));

        $this->assertEquals($testRcd[0], $fieldArray, 'Wrong field data');

        $redcapEvents = ReflectionHelper::getObjectProperty(Record::class, 'redcapEvents', $obj);
        $this->assertEmpty($redcapEvents);
    }

    /**
     * Validate behavior when loadRecord is called with a full record
     *
     */
    public function testLoadRecord_event_fullRecord()
    {
        $testRcd = $this->generateRedcapRecord(array(static::TEST_RCD_4_ID));

        $obj = new Record($this->stubRedcapProj);

        $result = $obj->loadRecord($testRcd);

        $this->assertTrue($result);

        $fieldArray = ReflectionHelper::getObjectProperty(Record::class, 'fieldArray', $obj);
        $redcapEvents = ReflectionHelper::getObjectProperty(Record::class, 'redcapEvents', $obj);

        $this->assertIsArray($fieldArray);
        $this->assertCount(count(static::ALL_VALID_EVENTS), $fieldArray, 'Events not loaded properly, fieldArray wrong size');
        $this->assertCount(count(static::ALL_VALID_EVENTS), $redcapEvents, 'Events not loaded properly, fieldArray wrong size');

        foreach (static::ALL_VALID_EVENTS as $ndx => $event) {
            $this->assertArrayHasKey($event, $fieldArray);

            // add back in record id and event fields
            $rcd = array_merge($fieldArray[$event], array(static::RCD_ID_FIELD => static::TEST_RCD_4_ID, Record::REDCAP_EVENT_NAME => $event));

            $this->assertCount(count($testRcd[$ndx]), $rcd, 'Wrong number of fields');
            $this->assertEquals($testRcd[$ndx], $rcd, 'Wrong field data');

            $this->assertContains($event, $redcapEvents);
        }
    }

    /**
     * Validate behavior when loadRecord is called for all event data
     *
     */
/*
    public function testLoadRecord_multiEventRecord()
    {
        $testRcd = $this->generateRedcapRecord(array(static::TEST_RCD_5_ID), null, array(static::CONSENT_FORM, static::HISTORY_FORM));

        $obj = new Record($this->stubRedcapProj);
        ReflectionHelper::setObjectProperty(Record::class, 'recordId', static::TEST_RCD_5_ID, $obj);

        $result = $obj->loadRecord($testRcd);

        $this->assertTrue ($result);

        $fieldArray = ReflectionHelper::getObjectProperty(Record::class, 'fieldArray', $obj);
        $redcapEvents = ReflectionHelper::getObjectProperty(Record::class, 'redcapEvents', $obj);

        $this->assertCount(count(static::ALL_VALID_EVENTS), $fieldArray);
        $this->assertCount(count(static::ALL_VALID_EVENTS), $redcapEvents);

        foreach(static::ALL_VALID_EVENTS as $ndx => $eventName) {
            $this->assertArrayHasKey($eventName, $fieldArray, 'fieldArray missing expected event element');
            // subtract 2 for the record id and event fields being added
            $this->assertEquals($testRcd[$ndx] - 2, $fieldArray[$eventName], 'Wrong number of fields for event');

            $testEntry = $testRcd[$ndx];
            $actualEntry = $fieldArray[$eventName];
            foreach ($testEntry as $testField => $testValue) {
                $this->assertArrayHasKey($testField, $actualEntry, 'Event record missing field');
                $this->assertEquals($testValue, $actualEntry[$testField], 'Event fields has incorrect value');
            }

            $this->assertContains($eventName, $redcapEvents);
        }
    }
*/
    /**
     * Validate behavior when loadRecord is called for selected event data
     *
     */
    public function testLoadRecord_selectedMultiEventRecord()
    {
        $testEvents = array(static::TEST_EVENT_A, static::TEST_EVENT_B);
        $testRcd = $this->generateRedcapRecord(array(static::TEST_RCD_4_ID), null, null, $testEvents);

        $obj = new Record($this->stubRedcapProj);

        ReflectionHelper::setObjectProperty(Record::class, 'redcapEvents', $testEvents, $obj);

        $result = $obj->loadRecord($testRcd);

        $this->assertTrue($result);

        $fieldArray = ReflectionHelper::getObjectProperty(Record::class, 'fieldArray', $obj);
        $redcapEvents = ReflectionHelper::getObjectProperty(Record::class, 'redcapEvents', $obj);

        $this->assertCount(count($testRcd), $fieldArray, 'Fields not loaded properly, fieldArray wrong size');

        foreach ($testRcd as $testEntry) {
            $this->assertArrayHasKey($testEntry[Record::REDCAP_EVENT_NAME], $fieldArray, 'fieldArray missing expected event element');

            $actualEntry = $fieldArray[$testEntry[Record::REDCAP_EVENT_NAME]];
            // record id and event fields would have been removed thus - 2
            $this->assertEquals(count($testEntry) - 2, count($actualEntry), 'Wrong number of fields for event');

            foreach ($testEntry as $testField => $testValue) {
                if (static::RCD_ID_FIELD !== $testField && Record::REDCAP_EVENT_NAME != $testField) {
                    $this->assertArrayHasKey($testField, $actualEntry, 'Event record missing field');
                    $this->assertEquals($testValue, $actualEntry[$testField], 'Event fields has incorrect value');
                }
            }
        }

        $this->assertCount(count($testEvents), $redcapEvents);
        foreach ($testEvents as $event) {
            $this->assertContains($event, $redcapEvents);
        }
    }


    /**
     *
     */
    public function testLoadRecord_classicBadRecord() {
        $this->useClassicProject();

        $obj = new Record($this->stubRedcapProj);

        $result = $obj->loadRecord(array(1));

        $this->assertFalse($result);

        $fields = ReflectionHelper::getObjectProperty(Record::class, 'fieldArray', $obj);

        $this->assertEmpty($fields, self::SHOULD_BE_NO_FIELD_DATA);
    }

    /**
     *
     */
    public function testLoadRecord_classic() {
        $this->useClassicProject();

        $obj = new Record($this->stubRedcapProj);

        $testRcd = $this->generateRedcapRecord(array(self::TEST_RCD_3_ID));

        $result = $obj->loadRecord($testRcd);

        $this->assertTrue($result);

        $fieldArray = ReflectionHelper::getObjectProperty(Record::class, 'fieldArray', $obj);

        // add back in record id field
        $fieldArray = array_merge($fieldArray, array(static::RCD_ID_FIELD => static::TEST_RCD_3_ID));

        $this->assertCount(count($testRcd[0]), $fieldArray);

        $this->assertEquals($testRcd[0], $fieldArray);

        foreach ($testRcd[0] as $field => $value) {
            $this->assertEquals($value, $fieldArray[$field]);
        }
    }

    /**
     *
     */
    public function testLoadRecord_specificEvent_noEventField() {
        $testEvents = array(self::TEST_EVENT_C);

        // generate record which doesn't include a record id or event name (i.e. don't include the first form)
        $testRcd = $this->generateRedcapRecord(array(static::TEST_RCD_5_ID), null, array(static::CONSENT_FORM, static::HISTORY_FORM), $testEvents);

        $obj = new Record($this->stubRedcapProj);
        ReflectionHelper::setObjectProperty(Record::class, 'redcapEvents', $testEvents, $obj);

        $result = $obj->loadRecord($testRcd);

        $this->assertTrue($result);

        $fieldArray = ReflectionHelper::getObjectProperty(Record::class, 'fieldArray', $obj);
        $this->assertIsArray($fieldArray);
        $this->assertCount(count($testEvents), $fieldArray);
        foreach ($testEvents as $event) {
            $this->assertArrayHasKey($event, $fieldArray, 'fieldArray missing expected element');
        }
    }

    /**
     *
     */
    public function testLoadRecord_specificEvents_noEventField() {
        $testEvents = array(self::TEST_EVENT_A, self::TEST_EVENT_C);

        $obj = new Record($this->stubRedcapProj);
        $obj->setEvents($testEvents);

        $testRcd = $this->generateRedcapRecord(array(static::TEST_RCD_5_ID), null, array(self::CONSENT_FORM), $testEvents);
        $result = $obj->loadRecord($testRcd);

        $this->assertTrue($result);

        $fieldArray = ReflectionHelper::getObjectProperty(Record::class, 'fieldArray', $obj);
        $this->assertIsArray($fieldArray);
        $this->assertCount(2, $fieldArray);
        $this->assertArrayHasKey(self::TEST_EVENT_A, $fieldArray, 'fieldArray missing expected element');
        $this->assertArrayHasKey(self::TEST_EVENT_C, $fieldArray, 'fieldArray missing expected element');
    }

    /**
     *
     */
    public function testLoadRecord_specificEvent_withEventField() {
        $testEvents = array(static::TEST_EVENT_B);
        $testRcd = $this->generateRedcapRecord(array(static::TEST_RCD_4_ID), null, null, $testEvents);

        $obj = new Record($this->stubRedcapProj);
        ReflectionHelper::setObjectProperty(Record::class, 'redcapEvents', $testEvents, $obj);

        $result = $obj->loadRecord($testRcd);

        $this->assertTrue($result);

        $fieldArray = ReflectionHelper::getObjectProperty(Record::class, 'fieldArray', $obj);
        $this->assertIsArray($fieldArray);
        $this->assertCount(count($testEvents), $fieldArray);
        foreach ($testEvents as $event) {
            $this->assertArrayHasKey($event, $fieldArray, 'fieldArray missing expected element');
        }
    }

    /**
     *
     */
    public function testLoadRecord_specificEvents_withEventField() {
        $testEvents = array(self::TEST_EVENT_B, self::TEST_EVENT_C);
        $testRcd = $this->generateRedcapRecord(array(static::TEST_RCD_6_ID), null, null, $testEvents);

        $obj = new Record($this->stubRedcapProj);
        ReflectionHelper::setObjectProperty(Record::class, 'redcapEvents', $testEvents, $obj);

        $result = $obj->loadRecord($testRcd);

        $this->assertTrue($result);

        $fieldArray = ReflectionHelper::getObjectProperty(Record::class, 'fieldArray', $obj);
        $this->assertIsArray($fieldArray);
        $this->assertCount(count($testEvents), $fieldArray);
        foreach ($testEvents as $event) {
            $this->assertArrayHasKey($event, $fieldArray, 'fieldArray missing expected element');
        }
    }



    //***********************************************************************
    //***********************************************************************
    //* LoadRecordById tests
    //***********************************************************************
    //***********************************************************************

    /**
     * Validate behavior when loadRecordById is called with an invalid record id
     *
     */
    public function testLoadRecordById_invalidRecord()
    {
        $obj = new Record($this->stubRedcapProj);

        $result = $obj->loadRecordById(static::INVALID_TEST_RCD_ID);

        $this->assertFalse($result);

        $fields = ReflectionHelper::getObjectProperty(Record::class, 'fieldArray', $obj);
        $flags = ReflectionHelper::getObjectProperty(Record::class, 'dirtyFieldArray', $obj);

        $this->assertEmpty($fields, 'fieldArray should be empty');
        $this->assertEmpty($flags, 'dirty flags should be empty');
    }

    /**
     * Validate behavior when loadRecordById with new object
     *
     */
    public function testLoadRecordById_classic_newObject()
    {
        $this->useClassicProject();

        $obj = new Record($this->stubRedcapProj);

        $result = $obj->loadRecordById(static::TEST_RCD_2_ID);

        $this->assertTrue($result);

        $recordId = ReflectionHelper::getObjectProperty(Record::class, 'recordId', $obj);
        $fieldArray = ReflectionHelper::getObjectProperty(Record::class, 'fieldArray', $obj);

        $this->assertEquals(static::TEST_RCD_2_ID, $recordId);
        $this->assertGreaterThan(0, count($fieldArray));
    }


    /**
     * Validate behavior when loadRecordById with new object
     *
     */
    public function testLoadRecordById_events_newObject()
    {
        $obj = new Record($this->stubRedcapProj);

        $result = $obj->loadRecordById(static::TEST_RCD_5_ID);

        $this->assertTrue($result);

        $fieldArray = ReflectionHelper::getObjectProperty(Record::class, 'fieldArray', $obj);

        $this->assertEquals(3, count($fieldArray), 'Fields not loaded properly, fieldArray wrong size');
        $this->assertArrayHasKey(static::TEST_EVENT_A, $fieldArray, 'fieldArray missing expected element');
        $this->assertArrayHasKey(static::TEST_EVENT_B, $fieldArray, 'fieldArray missing expected element');
        $this->assertArrayHasKey(static::TEST_EVENT_C, $fieldArray, 'fieldArray missing expected element');
    }

    /**
     *
     */
    public function testLoadRecordById_withEventNames() {
        $testEvents = array(static::TEST_EVENT_A, static::TEST_EVENT_B);
        $testRcd = $this->generateRedcapRecord(array(static::TEST_RCD_4_ID), null, null, $testEvents);

        $this->setCallback('exportRecords', function() use ($testRcd) {
            return $testRcd;
        } );

        $obj = new Record($this->stubRedcapProj);

        ReflectionHelper::setObjectProperty(Record::class, 'redcapEvents', $testEvents, $obj);

        $result = $obj->loadRecordById(static::TEST_RCD_4_ID);

        $this->assertTrue($result);

        $fieldArray = ReflectionHelper::getObjectProperty(Record::class, 'fieldArray', $obj);

        $this->assertCount(count($testRcd), $fieldArray, 'Fields not loaded properly, fieldArray wrong size');

        foreach($testRcd as $testEntry) {
            $this->assertArrayHasKey($testEntry[Record::REDCAP_EVENT_NAME], $fieldArray, 'fieldArray missing expected event element');

            $actualEntry = $fieldArray[$testEntry[Record::REDCAP_EVENT_NAME]];
            // Subtract 2 to account for record id and event name fields
            $this->assertEquals(count($testEntry) - 2, count($actualEntry), 'Wrong number of fields for event');

            foreach ($testEntry as $testField => $testValue) {
                if (static::RCD_ID_FIELD !== $testField && Record::REDCAP_EVENT_NAME != $testField) {
                    $this->assertArrayHasKey($testField, $actualEntry, 'Event record missing field');
                    $this->assertEquals($testValue, $actualEntry[$testField], 'Event fields has incorrect value');
                }
            }
        }
    }

    /**
     *
     */
/*
    public function testLoadRecordById_withoutEventNames() {
        // generate record which doesn't include a record id or event name (i.e. don't include the first form)
        $testRcd = $this->generateRedcapRecord(array(static::TEST_RCD_5_ID), null, array(static::CONSENT_FORM, static::HISTORY_FORM));

        $obj = new Record($this->stubRedcapProj);
        $this->setCallback('exportRecords', function() use ($testRcd) {
            return $testRcd;
        } );

        $result = $obj->loadRecordById(static::TEST_RCD_5_ID);

        $this->assertTrue($result);

        $fieldArray = ReflectionHelper::getObjectProperty(Record::class, 'fieldArray', $obj);

        $this->assertCount(count($testRcd), $fieldArray, 'Fields not loaded properly, fieldArray wrong size');

        foreach($testRcd as $ndx => $testEntry) {
            $this->assertArrayHasKey(static::ALL_VALID_EVENTS[$ndx], $fieldArray, 'fieldArray missing expected event element');

            $actualEntry = $fieldArray[static::ALL_VALID_EVENTS[$ndx]];
            $this->assertCount(count($testEntry), $actualEntry, 'Wrong number of fields for event');

            foreach ($testEntry as $testField => $testValue) {
                $this->assertArrayHasKey($testField, $actualEntry, 'Event record missing field');
                $this->assertEquals($testValue, $actualEntry[$testField], 'Event fields has incorrect value');
            }
        }
    }
*/
    /**
     * Validate behavior when loadRecordById with existing data and invalid record id
     *
     */
    public function testLoadRecordById_existingData_invalidRcdId()
    {
        $oldValue = 'someaddress@emailserver.com';

        $this->setCallback('exportRecords', function() { return [];} );

        $obj = new Record($this->stubRedcapProj);

        // add field data which will also set a dirty flag
        $obj->setFieldValue(static::EMAIL_FIELD, $oldValue);

        $result = $obj->loadRecordById(static::INVALID_TEST_RCD_ID);

        $this->assertFalse($result);

        $fieldArray = ReflectionHelper::getObjectProperty(Record::class, 'fieldArray', $obj);
        $dirtyFieldArray = ReflectionHelper::getObjectProperty(Record::class, 'dirtyFieldArray', $obj);

        $this->assertEmpty($fieldArray, 'fieldArray should be empty');
        $this->assertEmpty($dirtyFieldArray, 'dirty flags should be empty');
    }

    /**
     * Validate behavior when loadRecordById with existing data
     *
     */
    public function testLoadRecordById_classic_existingData()
    {
        $this->useClassicProject();

        $oldValue = 'someaddress@emailserver.com';

        $obj = new Record($this->stubRedcapProj);

        // add field data which will also set a dirty flag
        $obj->setFieldValue(static::EMAIL_FIELD, $oldValue);

        // Note: if a set of fields has been specified during construction,
        //  via setFields or setFieldValue (as in this test) the
        //  loadRecordById() will only fetch the fields specified.
        $result = $obj->loadRecordById(static::TEST_RCD_1_ID);

        $this->assertTrue($result);

        $fieldArray = ReflectionHelper::getObjectProperty(Record::class, 'fieldArray', $obj);
        $dirtyFieldArray = ReflectionHelper::getObjectProperty(Record::class, 'dirtyFieldArray', $obj);

        // Should be 1 as one field has been set prior to loadRecordById
        $this->assertCount(1, $fieldArray, 'Fields not loaded properly, fieldArray wrong size');
        $this->assertArrayHasKey(static::EMAIL_FIELD, $fieldArray);
        $this->assertNotEquals($oldValue, $fieldArray[static::EMAIL_FIELD],'Record not loaded properly');

        $this->assertEmpty($dirtyFieldArray, 'dirty flags should be empty');
    }



    //***********************************************************************
    //***********************************************************************
    //* SetFields tests
    //***********************************************************************
    //***********************************************************************

    /**
     * Validate behavior when setFields is called on a new object
     *
     */
    public function testSetFields_newObject()
    {
        $obj = new Record($this->stubRedcapProj);

        $obj->setFields(array(static::EMAIL_FIELD));

        $fieldNames = ReflectionHelper::getObjectProperty(Record::class, 'redcapFields', $obj);

        $this->assertNotEmpty($fieldNames, 'Field name list should not be empty');
        $this->assertEquals(1, count($fieldNames), 'Unexpected number of fields');
        $this->assertEquals(static::EMAIL_FIELD, $fieldNames[0], 'Missing expected field');
    }

    /**
     * Validate behavior when setFields is called a second time
     *
     */
    public function testSetFields_duplicateField()
    {
        $obj = new Record($this->stubRedcapProj);

        $fieldArray = array(static::EMAIL_FIELD, static::PHONE_FIELD);

        ReflectionHelper::setObjectProperty(Record::class, 'redcapFields', $fieldArray, $obj);

        $obj->setFields($fieldArray);

        $fieldNames = ReflectionHelper::getObjectProperty(Record::class, 'redcapFields', $obj);

        $this->assertNotEmpty($fieldNames, 'Field name list should not be empty');
        $this->assertEquals(2, count($fieldNames), 'Unexpected number of fields');
        $this->assertTrue(in_array($fieldArray[0], $fieldNames), 'Missing expected field');
        $this->assertTrue(in_array($fieldArray[1], $fieldNames), 'Missing expected field');
    }

    /**
     * Validate behavior when new field set is different than previous set
     *
     */
    public function testSetFields_changeFields()
    {
        $obj = new Record($this->stubRedcapProj);

        $oldFieldArray = array(static::EMAIL_FIELD, static::PHONE_FIELD);
        $newFieldArray = array(static::ADDRESS_FIELD);

        ReflectionHelper::setObjectProperty(Record::class, 'redcapFields', $oldFieldArray, $obj);

        $obj->setFields($newFieldArray);

        $fieldNames = ReflectionHelper::getObjectProperty(Record::class, 'redcapFields', $obj);

        $this->assertNotEmpty($fieldNames, 'Field name list should not be empty');
        $this->assertEquals(1, count($fieldNames), 'Unexpected number of fields');
        $this->assertTrue(in_array($newFieldArray[0], $fieldNames), 'Missing expected field');
    }

    /**
     * Validate behavior when field set is removed
     *
     */
    public function testSetFields_removeFields()
    {
        $obj = new Record($this->stubRedcapProj);

        $fieldArray = array(static::EMAIL_FIELD, static::PHONE_FIELD);

        ReflectionHelper::setObjectProperty(Record::class, 'redcapFields', $fieldArray, $obj);

        $obj->setFields(array());

        $fieldNames = ReflectionHelper::getObjectProperty(Record::class, 'redcapFields', $obj);

        $this->assertEmpty($fieldNames, 'Field name list should be empty');
    }


    /**
     * Validate behavior when invalid fields are provided
     *
     */
    public function testSetFields_invalidFields()
    {
        $obj = new Record($this->stubRedcapProj);

        $obj->setFields(array('foo', 'bar'));

        $fieldNames = ReflectionHelper::getObjectProperty(Record::class, 'redcapFields', $obj);

        $this->assertEmpty($fieldNames, 'Field name list should be empty');
    }



    //***********************************************************************
    //***********************************************************************
    //* GetFields tests
    //***********************************************************************
    //***********************************************************************

    /**
     * Validate behavior when object is new
     *
     */
    public function testGetFields_newObject()
    {
        $obj = new Record($this->stubRedcapProj);

        $fieldNames = $obj->getFields();

        $this->assertEquals(1, count($fieldNames), 'Field name list not expected size');
        $this->assertEquals(static::RCD_ID_FIELD, $fieldNames[0], 'Unexpected field in list');
    }

    /**
     * Validate behavior when object has fields
     *
     */
    public function testGetFields_withFields()
    {
        $obj = new Record($this->stubRedcapProj);

        ReflectionHelper::setObjectProperty(Record::class, 'redcapFields', array(static::PHONE_FIELD), $obj);

        $fieldNames = $obj->getFields();

        $this->assertEquals(2, count($fieldNames), 'Field name list not expected size');
        $this->assertTrue(in_array(static::RCD_ID_FIELD, $fieldNames), 'Missing expected field in list');
        $this->assertTrue(in_array(static::PHONE_FIELD, $fieldNames), 'Missing expected field in list');
    }


    /**
     * Validate behavior when object is new
     *
     */
    public function testAddFields_newObject()
    {
        $obj = new Record($this->stubRedcapProj);

        $obj->addFields(array(static::PHONE_FIELD, static::ADDRESS_FIELD));

        $fieldNames = ReflectionHelper::getObjectProperty(Record::class, 'redcapFields', $obj);

        $this->assertEquals(2, count($fieldNames), 'Field name list not expected size');
        $this->assertTrue(in_array(static::PHONE_FIELD, $fieldNames), 'Missing expected field in list');
        $this->assertTrue(in_array(static::ADDRESS_FIELD, $fieldNames), 'Missing expected field in list');
    }



    //***********************************************************************
    //***********************************************************************
    //* AddFields tests
    //***********************************************************************
    //***********************************************************************

    /**
     * Validate behavior when object has fields
     *
     */
    public function testAddFields_existingFields()
    {
        $obj = new Record($this->stubRedcapProj);

        ReflectionHelper::setObjectProperty(Record::class, 'redcapFields', array(static::EMAIL_FIELD), $obj);

        $obj->addFields(array(static::ADDRESS_FIELD));

        $fieldNames = ReflectionHelper::getObjectProperty(Record::class, 'redcapFields', $obj);

        $this->assertEquals(2, count($fieldNames), 'Field name list not expected size');
        $this->assertTrue(in_array(static::EMAIL_FIELD, $fieldNames), 'Missing expected field in list');
        $this->assertTrue(in_array(static::ADDRESS_FIELD, $fieldNames), 'Missing expected field in list');
    }

    /**
     * Validate behavior when invalid fields are added
     *
     */
    public function testAddFields_invalidFields()
    {
        $obj = new Record($this->stubRedcapProj);

        $obj->addFields(array('cow', 'horse'));

        $fieldNames = ReflectionHelper::getObjectProperty(Record::class, 'redcapFields', $obj);

        $this->assertEmpty($fieldNames, 'Field name list should be empty');
    }



    //***********************************************************************
    //***********************************************************************
    //* RemoveFields tests
    //***********************************************************************
    //***********************************************************************

    /**
     * Validate behavior when existing fields are removed
     *
     */
    public function testRemoveFields_existingFields()
    {
        $obj = new Record($this->stubRedcapProj);

        ReflectionHelper::setObjectProperty(Record::class, 'redcapFields', array(static::EMAIL_FIELD, static::ADDRESS_FIELD), $obj);

        $obj->removeFields(array(static::EMAIL_FIELD));

        $fieldNames = ReflectionHelper::getObjectProperty(Record::class, 'redcapFields', $obj);

        $this->assertEquals(1, count($fieldNames), 'Field name list incorrect size');
        $this->assertTrue(in_array(static::ADDRESS_FIELD, $fieldNames), 'Missing expected field in list');
    }

    /**
     * Validate behavior when invalid fields are removed
     *
     */
    public function testRemoveFields_invalidFields()
    {
        $obj = new Record($this->stubRedcapProj);

        ReflectionHelper::setObjectProperty(Record::class, 'redcapFields', array(static::EMAIL_FIELD), $obj);

        $obj->removeFields(array('cow', 'horse'));

        $fieldNames = ReflectionHelper::getObjectProperty(Record::class, 'redcapFields', $obj);

        $this->assertEquals(1, count($fieldNames), 'Field name list incorrect size');
    }



    //***********************************************************************
    //***********************************************************************
    //* SetRecordId tests
    //***********************************************************************
    //***********************************************************************

    /**
     * Validate behavior when object is new
     *
     */
    public function testSetRecordId_newObject()
    {
        $testRcdId = 'newObjRcdId';

        $obj = new Record($this->stubRedcapProj);

        $obj->setRecordId($testRcdId);

        $fieldArray = ReflectionHelper::getObjectProperty(Record::class, 'fieldArray', $obj);

        $this->assertEmpty($fieldArray);

        $rcdId = ReflectionHelper::getObjectProperty(Record::class, 'recordId', $obj);

        $this->assertEquals($testRcdId, $rcdId, 'Record id field not set correctly');
    }

    /**
     * Validate behavior when record has a record id
     *
     */
    public function testSetRecordId_existingId()
    {
        $testRcdId = 'existingRcdId';

        $obj = new Record($this->stubRedcapProj);

        ReflectionHelper::setObjectProperty(Record::class, 'recordId', 'someRcdId', $obj);

        $obj->setRecordId($testRcdId);

        $rcdId = ReflectionHelper::getObjectProperty(Record::class, 'recordId', $obj);

        $this->assertEquals($testRcdId, $rcdId, 'Record id field not set correctly');
    }



    //***********************************************************************
    //***********************************************************************
    //* GetRecordId tests
    //***********************************************************************
    //***********************************************************************

    /**
     * Validate behavior when object is new
     *
     */
    public function testGetRecordId_newObject()
    {
        $obj = new Record($this->stubRedcapProj);

        $rcdId = $obj->getRecordId();

        $this->assertNull($rcdId, 'There should not be a record id yet');
    }

    /**
     * Validate behavior when object has fields
     *
     */
    public function testGetRecordId_withId()
    {
        $testRcdId = 'testRcdId';

        $obj = new Record($this->stubRedcapProj);

        ReflectionHelper::setObjectProperty(Record::class, 'recordId', $testRcdId, $obj);

        $rcdId = $obj->getRecordId();

        $this->assertEquals($testRcdId, $rcdId, 'Record Id not as expected');
    }



    //***********************************************************************
    //***********************************************************************
    //* SetFieldValue tests
    //***********************************************************************
    //***********************************************************************

    /**
     * Validate behavior when field is invalid
     *
     */
    public function testSetFieldValue_invalidField()
    {
        $obj = new Record($this->stubRedcapProj);

        $obj->setFieldValue('foo', 'bar');

        $values = ReflectionHelper::getObjectProperty(Record::class, 'fieldArray', $obj);
        $dirtyFlags = ReflectionHelper::getObjectProperty(Record::class, 'dirtyFieldArray', $obj);

        $this->assertEmpty($values, 'Field should not have been saved');
        $this->assertArrayNotHasKey('foo', $dirtyFlags, 'Dirty flag should not be set for invalid field');
    }

    /**
     * Validate behavior when value is set
     *
     */
    public function testSetFieldValue_setValue()
    {
        $testEmail = 'bob@foobar.cc';

        $obj = new Record($this->stubRedcapProj);

        $obj->setFieldValue(static::EMAIL_FIELD, $testEmail);

        $fieldArray = ReflectionHelper::getObjectProperty(Record::class, 'fieldArray', $obj);
        $dirtyFlags = ReflectionHelper::getObjectProperty(Record::class, 'dirtyFieldArray', $obj);

        $this->assertEquals(1, count($fieldArray), 'Fields not loaded properly, fieldArray wrong size');
        $this->assertArrayHasKey(static::TEST_EVENT_A, $fieldArray, 'fieldArray missing expected element');

        $rcd = $fieldArray[static::TEST_EVENT_A];

        $this->assertEquals(1, count($rcd), 'Field count is incorrect');
        $this->assertContains(static::EMAIL_FIELD, array_keys($rcd), 'Missing expected field');
        $this->assertEquals($testEmail, $rcd[static::EMAIL_FIELD], 'Value stored incorrectly');

        $this->assertEquals(1, count($dirtyFlags), 'Fields not loaded properly, fieldArray wrong size');
        $this->assertArrayHasKey(static::TEST_EVENT_A, $dirtyFlags, 'fieldArray missing expected element');

        $flags = $dirtyFlags[static::TEST_EVENT_A];

        $this->assertArrayHasKey(static::EMAIL_FIELD, $flags, 'Dirty flag should be set for field');
    }

    /**
     * Validate behavior when value is set
     *
     */
    public function testSetFieldValue_replaceValue()
    {
        $testEmail = 'bob@foobar.cc';

        $obj = new Record($this->stubRedcapProj);

        ReflectionHelper::setObjectProperty(Record::class, 'fieldArray', array(static::TEST_EVENT_A => array(static::EMAIL_FIELD => 'before@setfield.co')), $obj);

        $obj->setFieldValue(static::EMAIL_FIELD, $testEmail);

        $fieldArray = ReflectionHelper::getObjectProperty(Record::class, 'fieldArray', $obj);
        $dirtyFlags = ReflectionHelper::getObjectProperty(Record::class, 'dirtyFieldArray', $obj);

        $this->assertEquals(1, count($fieldArray), 'Fields not loaded properly, fieldArray wrong size');
        $this->assertArrayHasKey(static::TEST_EVENT_A, $fieldArray, 'fieldArray missing expected element');

        $rcd = $fieldArray[static::TEST_EVENT_A];

        $this->assertEquals(1, count($rcd), 'Field count is incorrect');
        $this->assertContains(static::EMAIL_FIELD, array_keys($rcd), 'Missing expected field');
        $this->assertEquals($testEmail, $rcd[static::EMAIL_FIELD], 'Value stored incorrectly');

        $this->assertEquals(1, count($dirtyFlags), 'Fields not loaded properly, fieldArray wrong size');
        $this->assertArrayHasKey(static::TEST_EVENT_A, $dirtyFlags, 'fieldArray missing expected element');

        $flags = $dirtyFlags[static::TEST_EVENT_A];
        $this->assertArrayHasKey(static::EMAIL_FIELD, $flags, 'Dirty flag should be set for field');
    }



    //***********************************************************************
    //***********************************************************************
    //* SetEvents tests
    //***********************************************************************
    //***********************************************************************

    /**
     *
     */
    public function testSetEvents()
    {
        $obj = new Record($this->stubRedcapProj);

        $prop = ReflectionHelper::getObjectProperty(Record::class, 'redcapEvents', $obj);

        $this->assertIsArray($prop, 'REDCap event list should be an array');
        $this->assertEmpty($prop, 'REDCap event list should default to empty');

        $obj->setEvents(array(static::TEST_EVENTS[1]['unique_event_name']));

        $prop = ReflectionHelper::getObjectProperty(Record::class, 'redcapEvents', $obj);

        $this->assertIsArray($prop, 'REDCap event list should be an array');
        $this->assertCount(1, $prop, 'Event list should have a single entry');
        $this->assertContains(static::TEST_EVENTS[1]['unique_event_name'], $prop, 'REDCap event list missing expected value');
    }



    //***********************************************************************
    //***********************************************************************
    //* SetInstruments tests
    //***********************************************************************
    //***********************************************************************

    /**
     *
     */
    public function testSetInstruments_invalidInstrument()
    {
        $obj = new Record($this->stubRedcapProj);

        $prop = ReflectionHelper::getObjectProperty(Record::class, 'redcapInstruments', $obj);

        $this->assertIsArray($prop, 'REDCap event list should be an array');
        $this->assertEmpty($prop, 'REDCap event list should default to empty');

        $obj->setInstruments(array('invalid_instrument'));

        $prop = ReflectionHelper::getObjectProperty(Record::class, 'redcapInstruments', $obj);

        $this->assertIsArray($prop, 'REDCap event list should be an array');
        $this->assertEmpty($prop, 'Event list should still be empty');
    }

    /**
     *
     */
    public function testSetInstruments_validInstruments()
    {
        $obj = new Record($this->stubRedcapProj);

        $prop = ReflectionHelper::getObjectProperty(Record::class, 'redcapInstruments', $obj);

        $this->assertIsArray($prop, 'REDCap event list should be an array');
        $this->assertEmpty($prop, 'REDCap event list should default to empty');

        $obj->setInstruments(array(self::DEMOGRAPHICS_FORM, self::CONSENT_FORM));

        $prop = ReflectionHelper::getObjectProperty(Record::class, 'redcapInstruments', $obj);

        $this->assertIsArray($prop, 'REDCap event list should be an array');
        $this->assertCount(2, $prop);
        $this->assertContains(self::CONSENT_FORM, $prop);
        $this->assertContains(self::DEMOGRAPHICS_FORM, $prop);
    }



    //***********************************************************************
    //***********************************************************************
    //* CollapeCheckboxFields tests
    //***********************************************************************
    //***********************************************************************

    /**
     *
     */
    public function testCollapeCheckboxFields_classic_noCheckboxes() {
        $this->useClassicProject();

        $testFields = array(static::RELATIONSHIP_FIELD, static::FULLNAME_FIELD);
        $testRcd = $this->generateRedcapRecord(array(static::TEST_RCD_1_ID), $testFields);

        $this->setCallback('exportRecords', function() use ($testRcd) { return $testRcd; } );

        $obj = new Record($this->stubRedcapProj, array(), static::TEST_RCD_1_ID);

        $result = ReflectionHelper::invokeObjectMethod(Record::class, $obj, 'collapeCheckboxFields', array_keys($testRcd[0]));

        $this->assertEquals($testRcd[0], $result);
    }

    /**
     *
     */
    public function testCollapeCheckboxFields_classic_withCheckboxes() {
        $this->useClassicProject();

        $testFields = array(static::RELATIONSHIP_FIELD, static::FULLNAME_FIELD, static::RACE_FIELD);
        $testRcd = $this->generateRedcapRecord(array(static::TEST_RCD_3_ID), $testFields);
        $expecedValue = 0;

        array_walk($testRcd[0], function ($value, $key) use (&$expecedValue) {
            if (0 === strpos($key, static::RACE_FIELD)) {
                $expecedValue += $value;
            }
        });

        $this->setCallback('exportRecords', function() use ($testRcd) { return $testRcd; } );

        $obj = new Record($this->stubRedcapProj, array(), static::TEST_RCD_3_ID);

        $result = ReflectionHelper::invokeObjectMethod(Record::class, $obj, 'collapeCheckboxFields', array(static::RACE_FIELD));

        $this->assertArrayHasKey(static::RACE_FIELD, $result);
        $this->assertEquals($expecedValue, $result[static::RACE_FIELD]);
    }

    /**
     *
     */
    public function testCollapeCheckboxFields_events_noCheckboxesNullEvent() {
        $testFields = array(static::FULLNAME_FIELD, static::CONSENT_FIELD);
        $testRcd = $this->generateRedcapRecord(array(static::TEST_RCD_2_ID), $testFields);

        $this->setCallback('exportRecords', function() use ($testRcd) { return $testRcd; } );

        $obj = new Record($this->stubRedcapProj, array(), static::TEST_RCD_2_ID);

        $this->expectException('InvalidArgumentException');
        ReflectionHelper::invokeObjectMethod(Record::class, $obj, 'collapeCheckboxFields', array_keys($testRcd[0]));
    }

    /**
     *
     */
    public function testCollapeCheckboxFields_events_noCheckboxesWithEvent() {
        $testFields = array(static::ADDRESS_FIELD, static::EMAIL_FIELD, static::CONSENT_FIELD);
        $testRcd = $this->generateRedcapRecord(array(static::TEST_RCD_4_ID), $testFields);

        $this->setCallback('exportRecords', function() use ($testRcd) { return $testRcd; } );

        $obj = new Record($this->stubRedcapProj, array(), static::TEST_RCD_4_ID);

        foreach($testRcd as &$entry) {
            // remove the record id and event fields from the test record
            unset ($entry[static::RCD_ID_FIELD]);
            unset ($entry[Record::REDCAP_EVENT_NAME]);
        }

        foreach ($obj->getEvents() as $ndx => $event) {
            $result = ReflectionHelper::invokeObjectMethod(Record::class, $obj, 'collapeCheckboxFields', array_keys($testRcd[$ndx]), $event);
            $this->assertEquals($testRcd[$ndx], $result);
        }
    }

    /**
     *
     */
    public function testCollapeCheckboxFields_events_withCheckboxesNullEvent() {
        $testFields = array(static::FULLNAME_FIELD, static::RACE_FIELD, static::PHONE_FIELD);
        $testRcd = $this->generateRedcapRecord(array(static::TEST_RCD_6_ID), $testFields);

        $this->setCallback('exportRecords', function() use ($testRcd) { return $testRcd; } );

        $obj = new Record($this->stubRedcapProj, array(), static::TEST_RCD_6_ID);

        $this->expectException('InvalidArgumentException');
        ReflectionHelper::invokeObjectMethod(Record::class, $obj, 'collapeCheckboxFields', array(static::RACE_FIELD));
    }

    /**
     *
     */
    public function testCollapeCheckboxFields_events_withCheckboxesWithEvent() {
        $testFields = array(static::FULLNAME_FIELD, static::RACE_FIELD, static::PHONE_FIELD);
        $testRcd = $this->generateRedcapRecord(array(static::TEST_RCD_6_ID), $testFields);
        $expecedValue = 0;

        array_walk($testRcd[2], function ($value, $key) use (&$expecedValue) {
            if (0 === strpos($key, static::RACE_FIELD)) {
                $expecedValue += $value;
            }
        });

        $this->setCallback('exportRecords', function() use ($testRcd) { return $testRcd; } );

        $obj = new Record($this->stubRedcapProj, array(), static::TEST_RCD_6_ID);

        $result = ReflectionHelper::invokeObjectMethod(Record::class, $obj, 'collapeCheckboxFields', array(static::RACE_FIELD), static::TEST_EVENT_C);

        $this->assertArrayHasKey(static::RACE_FIELD, $result);
        $this->assertEquals($expecedValue, $result[static::RACE_FIELD]);
    }


    /**
     *
     */
    public function testCollapeCheckboxFields_events_withCheckboxesNotInterested() {
        $testFields = array(static::FULLNAME_FIELD, static::RACE_FIELD, static::PHONE_FIELD);
        $testRcd = $this->generateRedcapRecord(array(static::TEST_RCD_6_ID), $testFields);

        $this->setCallback('exportRecords', function() use ($testRcd) { return $testRcd; } );

        $obj = new Record($this->stubRedcapProj, array(), static::TEST_RCD_6_ID);

        $result = ReflectionHelper::invokeObjectMethod(Record::class, $obj, 'collapeCheckboxFields', array(static::FULLNAME_FIELD, static::PHONE_FIELD), static::TEST_EVENT_C);

        $this->assertArrayNotHasKey(static::RACE_FIELD, $result);
    }


    //***********************************************************************
    //***********************************************************************
    //* Save tests
    //***********************************************************************
    //***********************************************************************

    /**
     * Validate behavior when saving new object
     *
     */
    public function testSave_newObject()
    {
        $obj = new Record($this->stubRedcapProj);

        $this->stubRedcapProj
            ->expects($this->once())
            ->method('importRecords')
            ->with($this->callback(function($rcds) {
                $isOk = false;

                if (is_array($rcds) && count($rcds) === 1) {
                    $rcd = $rcds[0];

                    if (array_key_exists(self::RCD_ID_FIELD, $rcd) &&
                        static::getCurrentRcdId() === $rcd[self::RCD_ID_FIELD]) {
                        $isOk = true;
                    }
                }
                return $isOk;
            }));

        $obj->save();

        $dirtyFlags = ReflectionHelper::getObjectProperty(Record::class, 'dirtyFieldArray', $obj);

        $this->assertEmpty($dirtyFlags, 'No dirty flags should be set after save');
    }

    /**
     * Validate behavior when saving new object
     *
     */
    public function testSave_classic_newValues()
    {
        $this->useClassicProject();
        $obj = new Record($this->stubRedcapProj);

        $testRcd = $this->generateRedcapRecord(array(self::TEST_RCD_1_ID));
        $obj->loadRecord($testRcd);

        // clear out the record id so one gets generated on save()
        ReflectionHelper::setObjectProperty(Record::class, 'recordId', null, $obj);

        $this->stubRedcapProj
            ->expects($this->once())
            ->method('importRecords')
            ->with($this->callback(function($saveRcd) use ($testRcd) {
                $testRcd[0][self::RCD_ID_FIELD] = static::getCurrentRcdId();

                return $testRcd === $saveRcd;
            }) );

        $obj->save();
    }


    /**
     * Validate behavior when saving new object
     *
     */
    public function testSave_classic_existingRecord()
    {
        $this->useClassicProject();

        $testRcd = $this->generateRedcapRecord(array(static::TEST_RCD_2_ID));

        $obj = new Record($this->stubRedcapProj);
        $obj->loadRecord($testRcd);

        $this->stubRedcapProj
        ->expects($this->once())
        ->method('importRecords')
        ->with($testRcd);

        $obj->save();
    }

    /**
     * Validate behavior when saving new object
     *
     */
    public function testSave_events_newValues()
    {
        $obj = new Record($this->stubRedcapProj);
        $testRcd = $this->generateRedcapRecord(array(self::TEST_RCD_1_ID));
        $obj->loadRecord($testRcd);

        // clear out the record id so one gets generated on save()
        ReflectionHelper::setObjectProperty(Record::class, 'recordId', null, $obj);

        $this->stubRedcapProj
        ->expects($this->once())
        ->method('importRecords')
        ->with($this->callback(function($saveRcd) use ($testRcd) {
            foreach (array_keys($testRcd) as $key) {
                $testRcd[$key][self::RCD_ID_FIELD] = static::getCurrentRcdId();
            }

            return $testRcd === $saveRcd;
        }) );

        $obj->save();
    }

    /**
     * Validate behavior when saving new object
     *
     */
    public function testSave_events_multiEventRecord()
    {
        $testEvents = array(static::TEST_EVENT_A, static::TEST_EVENT_B);
        $testRcd = $this->generateRedcapRecord(array(static::TEST_RCD_4_ID), null, null, $testEvents);

        $obj = new Record($this->stubRedcapProj);
        ReflectionHelper::setObjectProperty(Record::class, 'redcapEvents', $testEvents, $obj);

        $obj->loadRecord($testRcd);

        $this->stubRedcapProj
            ->expects($this->once())
            ->method('importRecords')
            ->with($testRcd);

        $obj->save();
    }



    //***********************************************************************
    //***********************************************************************
    //* GetREDCapArray tests
    //***********************************************************************
    //***********************************************************************

    /**
     *
     */
    public function testGetREDCapArray_invalidEvent()
    {
        $obj = new Record($this->stubRedcapProj);

        ReflectionHelper::setObjectProperty(Record::class, 'recordId', self::TEST_RCD_2_ID, $obj);
        ReflectionHelper::setObjectProperty(Record::class, 'fieldArray', array (self::EMAIL_FIELD => 'get@redcaparray.co'), $obj);

        $redcapArray = $obj->getREDCapArray('an_invalid_event');

        $this->assertIsArray($redcapArray);
        $this->assertEmpty($redcapArray);
    }

    /**
     *
     */
    public function testGetREDCapArray_noEvents()
    {
        $this->useClassicProject();

        $obj = new Record($this->stubRedcapProj);

        $testRcd = array(
            self::EMAIL_FIELD => 'before@setfield.co',
            self::CONSENT_FIELD => 'y'
        );

        ReflectionHelper::setObjectProperty(Record::class, 'recordId', self::TEST_RCD_1_ID, $obj);
        ReflectionHelper::setObjectProperty(Record::class, 'fieldArray', $testRcd, $obj);

        $redcapArray = $obj->getREDCapArray();

        $this->assertIsArray($redcapArray);
        $this->assertCount(1, $redcapArray);

        $redcapRcd = array_shift($redcapArray);

        // Add record id field for comparison. It should be in the REDCap array returned.
        $testRcd[self::RCD_ID_FIELD] = self::TEST_RCD_1_ID;

        $this->assertIsArray($redcapRcd);
        $this->assertCount(count($testRcd), $redcapRcd);

        foreach($testRcd as $field => $value) {
            $this->assertArrayHasKey($field, $redcapRcd);
            $this->assertEquals($value, $redcapRcd[$field]);
        }
    }

    /**
     *
     */
    public function testGetREDCapArray_withAllEvents()
    {
        $obj = new Record($this->stubRedcapProj);

        $testRcd = array (
            self::TEST_EVENT_A => array (
                self::SHORTNAME_FIELD => 'Mary',
                self::PHONE_FIELD => '(212) 555-1212',
                self::CONSENT_FIELD => 'n',
                self::FULLNAME_FIELD => 'Mary Contrary'
            ),
            self::TEST_EVENT_C => array (
                self::SHORTNAME_FIELD => 'Susan',
                self::EMAIL_FIELD => 'before@setfield.co',
                self::FULLNAME_FIELD => 'Susan Sunshine'
            ),
        );

        ReflectionHelper::setObjectProperty(Record::class, 'recordId', self::TEST_RCD_3_ID, $obj);
        ReflectionHelper::setObjectProperty(Record::class, 'fieldArray', $testRcd, $obj);

        $redcapArray = $obj->getREDCapArray();

        $this->assertIsArray($redcapArray);
        $this->assertCount(count($testRcd), $redcapArray);    // should be two events

        // Add additional expected fields.
        $testRcd[self::TEST_EVENT_A][self::RCD_ID_FIELD] = self::TEST_RCD_3_ID;
        $testRcd[self::TEST_EVENT_A][Record::REDCAP_EVENT_NAME] = self::TEST_EVENT_A;
        $testRcd[self::TEST_EVENT_C][self::RCD_ID_FIELD] = self::TEST_RCD_3_ID;
        $testRcd[self::TEST_EVENT_C][Record::REDCAP_EVENT_NAME] = self::TEST_EVENT_C;

        $eventRcd = array_shift($redcapArray);

        $this->assertIsArray($eventRcd);
        $this->assertCount(count($testRcd[self::TEST_EVENT_A]), $eventRcd);

        foreach($testRcd[self::TEST_EVENT_A] as $field => $value) {
            $this->assertArrayHasKey($field, $eventRcd);
            $this->assertEquals($value, $eventRcd[$field]);
        }

        $eventRcd = array_shift($redcapArray);

        $this->assertIsArray($eventRcd);
        $this->assertCount(count($testRcd[self::TEST_EVENT_C]), $eventRcd);

        foreach($testRcd[self::TEST_EVENT_C] as $field => $value) {
            $this->assertArrayHasKey($field, $eventRcd);
            $this->assertEquals($value, $eventRcd[$field]);
        }
    }

    /**
     *
     */
    public function testGetREDCapArray_withSpecificEvent()
    {
        $obj = new Record($this->stubRedcapProj);

        $testRcd = array (
            self::TEST_EVENT_B => array (
                self::SHORTNAME_FIELD => 'Mary',
                self::PHONE_FIELD => '(212) 555-1212',
                self::CONSENT_FIELD => 'n',
                self::FULLNAME_FIELD => 'Mary Contrary'
            ),
            self::TEST_EVENT_C => array (
                self::SHORTNAME_FIELD => 'Susan',
                self::EMAIL_FIELD => 'before@setfield.co',
                self::CONSENT_FIELD => 'y',
                self::FULLNAME_FIELD => 'Susan Sunshine'
            ),
        );

        ReflectionHelper::setObjectProperty(Record::class, 'recordId', self::TEST_RCD_2_ID, $obj);
        ReflectionHelper::setObjectProperty(Record::class, 'fieldArray', $testRcd, $obj);

        $redcapArray = $obj->getREDCapArray(self::TEST_EVENT_C);

        $this->assertIsArray($redcapArray);
        $this->assertCount(1, $redcapArray);    // should be one event

        // Add additional expected fields.
        $testEventRcd = $testRcd[self::TEST_EVENT_C];
        $testEventRcd[self::RCD_ID_FIELD] = self::TEST_RCD_2_ID;
        $testEventRcd[Record::REDCAP_EVENT_NAME] = self::TEST_EVENT_C;

        $eventRcd = array_shift($redcapArray);

        $this->assertIsArray($eventRcd);
        $this->assertCount(count($testEventRcd), $eventRcd);

        foreach($testEventRcd as $field => $value) {
            $this->assertArrayHasKey($field, $eventRcd);
            $this->assertEquals($value, $eventRcd[$field]);
        }
    }



    //***********************************************************************
    //***********************************************************************
    //* GetFieldValue tests
    //***********************************************************************
    //***********************************************************************

    /**
     *
     */
    public function testGetFieldValue_withEvents_noEvent() {
        $obj = new Record($this->stubRedcapProj);

        $testRcd = array (
            self::TEST_EVENT_B => array (
                self::SHORTNAME_FIELD => 'Mary',
                self::PHONE_FIELD => '(212) 555-1212',
                self::CONSENT_FIELD => 'n',
                self::FULLNAME_FIELD => 'Mary Contrary'
            ),
            self::TEST_EVENT_C => array (
                self::SHORTNAME_FIELD => 'Susan',
                self::EMAIL_FIELD => 'before@setfield.co',
                self::FULLNAME_FIELD => 'Susan Sunshine'
            ),
        );

        ReflectionHelper::setObjectProperty(Record::class, 'recordId', self::TEST_RCD_2_ID, $obj);
        ReflectionHelper::setObjectProperty(Record::class, 'fieldArray', $testRcd, $obj);
        ReflectionHelper::setObjectProperty(Record::class, 'redcapEvents', array(self::TEST_EVENT_B, self::TEST_EVENT_C), $obj);

        $this->assertEquals($testRcd[self::TEST_EVENT_B][self::SHORTNAME_FIELD], $obj->getFieldValue(self::SHORTNAME_FIELD));
        $this->assertEquals($testRcd[self::TEST_EVENT_B][self::PHONE_FIELD], $obj->getFieldValue(self::PHONE_FIELD));
        $this->assertEquals($testRcd[self::TEST_EVENT_B][self::CONSENT_FIELD], $obj->getFieldValue(self::CONSENT_FIELD));
        $this->assertEquals($testRcd[self::TEST_EVENT_B][self::FULLNAME_FIELD], $obj->getFieldValue(self::FULLNAME_FIELD));
    }

    /**
     *
     */
    public function testGetFieldValue_withEvents_specificEvent() {
        $obj = new Record($this->stubRedcapProj);

        $testRcd = array (
            self::TEST_EVENT_A => array (
                self::SHORTNAME_FIELD => 'Mary',
                self::PHONE_FIELD => '(212) 555-1212',
                self::FULLNAME_FIELD => 'Mary Contrary'
            ),
            self::TEST_EVENT_C => array (
                self::SHORTNAME_FIELD => 'Susan',
                self::EMAIL_FIELD => 'before@setfield.co',
                self::CONSENT_FIELD => 'y',
                self::FULLNAME_FIELD => 'Susan Sunshine'
            ),
        );

        ReflectionHelper::setObjectProperty(Record::class, 'recordId', self::TEST_RCD_3_ID, $obj);
        ReflectionHelper::setObjectProperty(Record::class, 'fieldArray', $testRcd, $obj);
        ReflectionHelper::setObjectProperty(Record::class, 'redcapEvents', array(self::TEST_EVENT_A, self::TEST_EVENT_C), $obj);

        $this->assertEquals($testRcd[self::TEST_EVENT_C][self::SHORTNAME_FIELD], $obj->getFieldValue(self::SHORTNAME_FIELD, self::TEST_EVENT_C));
        $this->assertEquals($testRcd[self::TEST_EVENT_C][self::EMAIL_FIELD], $obj->getFieldValue(self::EMAIL_FIELD, self::TEST_EVENT_C));
        $this->assertEquals($testRcd[self::TEST_EVENT_C][self::CONSENT_FIELD], $obj->getFieldValue(self::CONSENT_FIELD, self::TEST_EVENT_C));
        $this->assertEquals($testRcd[self::TEST_EVENT_C][self::FULLNAME_FIELD], $obj->getFieldValue(self::FULLNAME_FIELD, self::TEST_EVENT_C));
    }

    /**
     *
     */
    public function testGetEvents_explicitEvents() {
        $testEvents = array(static::TEST_EVENT_A, static::TEST_EVENT_C);

        $obj = new Record($this->stubRedcapProj);

        ReflectionHelper::setObjectProperty(Record::class, 'redcapEvents', $testEvents, $obj);

        $actualEvents = $obj->getEvents();

        $this->assertCount(count($testEvents), $actualEvents);
        $this->assertEquals($testEvents, $actualEvents);
    }

    /**
     *
     */
    public function testGetEvents_impliedEvents() {
//        $testRcd = $this->generateRedcapRecord(array(static::TEST_RCD_4_ID));

//        $this->setCallback('exportRecords', function() use ($testRcd) { return $testRcd; } );

        $obj = new Record($this->stubRedcapProj, array(), static::TEST_RCD_4_ID);

//        $obj = new Record($this->stubRedcapProj);

        $actualEvents = $obj->getEvents();

        $this->assertCount(count(static::ALL_VALID_EVENTS), $actualEvents);
        $this->assertEquals(static::ALL_VALID_EVENTS, $actualEvents);
    }

    //***********************************************************************
    //***********************************************************************
    //* GetMostRecentFieldValue tests
    //***********************************************************************
    //***********************************************************************

    /**
     *
     */
    public function testGetMostRecentFieldValue_classic_noValue() {
        $this->useClassicProject();

        $obj = new Record($this->stubRedcapProj);

        $testRcd = array (
            // Leave out for test self::SHORTNAME_FIELD => 'Mary',
            self::PHONE_FIELD => '(212) 555-1212',
            self::CONSENT_FIELD => 'n',
            self::FULLNAME_FIELD => 'Mary Contrary'
        );

        ReflectionHelper::setObjectProperty(Record::class, 'recordId', self::TEST_RCD_2_ID, $obj);
        ReflectionHelper::setObjectProperty(Record::class, 'fieldArray', $testRcd, $obj);

        $this->assertNull($obj->getMostRecentFieldValue(self::SHORTNAME_FIELD));
    }

    /**
     *
     */
    public function testGetMostRecentFieldValue_classic_blankValue() {
        $this->useClassicProject();

        $obj = new Record($this->stubRedcapProj);

        $testRcd = array (
            self::SHORTNAME_FIELD => '',
            self::PHONE_FIELD => '(212) 555-1212',
            self::CONSENT_FIELD => 'n',
            self::FULLNAME_FIELD => 'Mary Contrary'
        );

        ReflectionHelper::setObjectProperty(Record::class, 'recordId', self::TEST_RCD_2_ID, $obj);
        ReflectionHelper::setObjectProperty(Record::class, 'fieldArray', $testRcd, $obj);

        $this->assertNull($obj->getMostRecentFieldValue(self::SHORTNAME_FIELD));
    }

    /**
     *
     */
    public function testGetMostRecentFieldValue_classic_withValue() {
        $this->useClassicProject();

        $obj = new Record($this->stubRedcapProj);

        $testRcd = array (
            self::SHORTNAME_FIELD => 'Larry',
            self::PHONE_FIELD => '(212) 555-1212',
            self::CONSENT_FIELD => 'n',
            self::FULLNAME_FIELD => 'Mary Contrary'
        );

        ReflectionHelper::setObjectProperty(Record::class, 'recordId', self::TEST_RCD_2_ID, $obj);
        ReflectionHelper::setObjectProperty(Record::class, 'fieldArray', $testRcd, $obj);

        $this->assertEquals($testRcd[self::SHORTNAME_FIELD], $obj->getMostRecentFieldValue(self::SHORTNAME_FIELD));
    }

    /**
     *
     */
    public function testGetMostRecentFieldValue_withEvents_noValues() {
        $obj = new Record($this->stubRedcapProj);

        $testRcd = array (
            self::TEST_EVENT_B => array (
                // Leave out for test self::SHORTNAME_FIELD => 'Mary',
                self::PHONE_FIELD => '(212) 555-1212',
                self::CONSENT_FIELD => 'n',
                self::FULLNAME_FIELD => 'Mary Contrary'
            ),
            self::TEST_EVENT_C => array (
                // Leave out for test self::SHORTNAME_FIELD => 'Susan',
                self::EMAIL_FIELD => 'before@setfield.co',
                self::FULLNAME_FIELD => 'Susan Sunshine'
            ),
        );

        ReflectionHelper::setObjectProperty(Record::class, 'recordId', self::TEST_RCD_2_ID, $obj);
        ReflectionHelper::setObjectProperty(Record::class, 'fieldArray', $testRcd, $obj);
        ReflectionHelper::setObjectProperty(Record::class, 'redcapEvents', array(self::TEST_EVENT_B, self::TEST_EVENT_C), $obj);

        $this->assertNull($obj->getMostRecentFieldValue(self::SHORTNAME_FIELD));
    }

    /**
     *
     */
    public function testGetMostRecentFieldValue_withEvents_blankValues() {
        $obj = new Record($this->stubRedcapProj);

        $testRcd = array (
            self::TEST_EVENT_B => array (
                self::SHORTNAME_FIELD => '',
                self::PHONE_FIELD => '(212) 555-1212',
                self::CONSENT_FIELD => 'n',
                self::FULLNAME_FIELD => 'Mary Contrary'
            ),
            self::TEST_EVENT_C => array (
                self::SHORTNAME_FIELD => '',
                self::EMAIL_FIELD => 'before@setfield.co',
                self::FULLNAME_FIELD => 'Susan Sunshine'
            ),
        );

        ReflectionHelper::setObjectProperty(Record::class, 'recordId', self::TEST_RCD_2_ID, $obj);
        ReflectionHelper::setObjectProperty(Record::class, 'fieldArray', $testRcd, $obj);
        ReflectionHelper::setObjectProperty(Record::class, 'redcapEvents', array(self::TEST_EVENT_B, self::TEST_EVENT_C), $obj);

        $this->assertNull($obj->getMostRecentFieldValue(self::SHORTNAME_FIELD));
    }

    /**
     *
     */
    public function testGetMostRecentFieldValue_withEvents_firstValue() {
        $obj = new Record($this->stubRedcapProj);

        $testRcd = array (
            self::TEST_EVENT_B => array (
                self::SHORTNAME_FIELD => 'Mary',
                self::PHONE_FIELD => '(212) 555-1212',
                self::CONSENT_FIELD => 'n',
                self::FULLNAME_FIELD => 'Mary Contrary'
            ),
            self::TEST_EVENT_C => array (
                self::SHORTNAME_FIELD => '',
                self::EMAIL_FIELD => 'before@setfield.co',
                self::FULLNAME_FIELD => 'Susan Sunshine'
            ),
        );

        ReflectionHelper::setObjectProperty(Record::class, 'recordId', self::TEST_RCD_2_ID, $obj);
        ReflectionHelper::setObjectProperty(Record::class, 'fieldArray', $testRcd, $obj);
        ReflectionHelper::setObjectProperty(Record::class, 'redcapEvents', array(self::TEST_EVENT_B, self::TEST_EVENT_C), $obj);

        $this->assertEquals($testRcd[self::TEST_EVENT_B][self::SHORTNAME_FIELD], $obj->getMostRecentFieldValue(self::SHORTNAME_FIELD));
    }

    /**
     *
     */
    public function testGetMostRecentFieldValue_withEvents_lastValue() {
        $obj = new Record($this->stubRedcapProj);

        $testRcd = array (
            self::TEST_EVENT_B => array (
                self::SHORTNAME_FIELD => 'Mary',
                self::PHONE_FIELD => '(212) 555-1212',
                self::CONSENT_FIELD => 'n',
                self::FULLNAME_FIELD => 'Mary Contrary'
            ),
            self::TEST_EVENT_C => array (
                self::SHORTNAME_FIELD => 'Susan',
                self::EMAIL_FIELD => 'before@setfield.co',
                self::FULLNAME_FIELD => 'Susan Sunshine'
            ),
        );

        ReflectionHelper::setObjectProperty(Record::class, 'recordId', self::TEST_RCD_2_ID, $obj);
        ReflectionHelper::setObjectProperty(Record::class, 'fieldArray', $testRcd, $obj);
        ReflectionHelper::setObjectProperty(Record::class, 'redcapEvents', array(self::TEST_EVENT_B, self::TEST_EVENT_C), $obj);

        $this->assertEquals($testRcd[self::TEST_EVENT_C][self::SHORTNAME_FIELD], $obj->getMostRecentFieldValue(self::SHORTNAME_FIELD));
    }

/*
 *  isValidRedcapEntry() currently commented out
 *
    public function testIsValidRedcapEntry_emptyArray() {
        $testObj = new Record($this->stubRedcapProj);
        $testEntry = array();

        $result = ReflectionHelper::invokeObjectMethod(Record::class, $testObj, 'isValidRedcapEntry', $testEntry);

        $this->assertTrue($result);
    }

    public function testIsValidRedcapEntry_invalidKey() {
        $testObj = new Record($this->stubRedcapProj);
        $testEntry = array('aValue'); // implied integer key of 0

        $result = ReflectionHelper::invokeObjectMethod(Record::class, $testObj, 'isValidRedcapEntry', $testEntry);

        $this->assertFalse($result);
    }

    public function testIsValidRedcapEntry_invalidValue() {
        $testObj = new Record($this->stubRedcapProj);
        $testEntry = array('aField' => array('aValue'));

        $result = ReflectionHelper::invokeObjectMethod(Record::class, $testObj, 'isValidRedcapEntry', $testEntry);

        $this->assertFalse($result);
    }

    public function testIsValidRedcapEntry_validStringValue() {
        $testObj = new Record($this->stubRedcapProj);
        $testEntry = array('aField' => 'aValue');

        $result = ReflectionHelper::invokeObjectMethod(Record::class, $testObj, 'isValidRedcapEntry', $testEntry);

        $this->assertTrue($result);
    }

    public function testIsValidRedcapEntry_validIntValue() {
        $testObj = new Record($this->stubRedcapProj);
        $testEntry = array('aField' => 1415);

        $result = ReflectionHelper::invokeObjectMethod(Record::class, $testObj, 'isValidRedcapEntry', $testEntry);

        $this->assertTrue($result);
    }

    public function testIsValidRedcapEntry_validBlankValue() {
        $testObj = new Record($this->stubRedcapProj);
        $testEntry = array('aField' => '');

        $result = ReflectionHelper::invokeObjectMethod(Record::class, $testObj, 'isValidRedcapEntry', $testEntry);

        $this->assertTrue($result);
    }
*/

/*
 *  isValidRedcapStructure() currently commented out
 *
    public function testIsValidRedcapStructure_emptyArray() {
        $testObj = new Record($this->stubRedcapProj);
        $testEntry = array();

        $result = ReflectionHelper::invokeObjectMethod(Record::class, $testObj, 'isValidRedcapStructure', $testEntry);

        $this->assertTrue($result);
    }

    public function testIsValidRedcapStructure_plainArray() {
        $testObj = new Record($this->stubRedcapProj);
        $testEntry = array('a', 'b');

        $result = ReflectionHelper::invokeObjectMethod(Record::class, $testObj, 'isValidRedcapStructure', $testEntry);

        $this->assertFalse($result);
    }

    public function testIsValidRedcapStructure_stringKey() {
        $testObj = new Record($this->stubRedcapProj);
        $testEntry = array('aKey' => 'aValue');

        $result = ReflectionHelper::invokeObjectMethod(Record::class, $testObj, 'isValidRedcapStructure', $testEntry);

        $this->assertFalse($result);
    }

    public function testIsValidRedcapStructure_invalidEntry() {
        $testObj = new Record($this->stubRedcapProj);
        $testEntry = array(array('aValue'));

        $result = ReflectionHelper::invokeObjectMethod(Record::class, $testObj, 'isValidRedcapStructure', $testEntry);

        $this->assertFalse($result);
    }

    public function testIsValidRedcapStructure_validEntry() {
        $testObj = new Record($this->stubRedcapProj);
        $testEntry = array(array('someField' => 'someValue'));

        $result = ReflectionHelper::invokeObjectMethod(Record::class, $testObj, 'isValidRedcapStructure', $testEntry);

        $this->assertTrue($result);
    }
*/
}
