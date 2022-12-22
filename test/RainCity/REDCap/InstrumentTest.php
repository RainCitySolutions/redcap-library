<?php
namespace RainCity\REDCap;

use Psr\Log\LoggerInterface;
use RainCity\TestHelper\ReflectionHelper;


/**
 *
 * @covers \RainCity\REDCap\Instrument
 *
 */
class InstrumentTest extends REDCapTestCase
{
    private const MISSED_INPUT_FIELD = 'Missed input field';

    const TEST_FORM_NAME = 'testName';
    const TEST_FORM_LABEL = 'testLabel';

    /** @var Instrument Instance used for testing */
    private $testObj;

    private function createMockField(string $type, bool $isRequired, bool $isCAT = false): Field {
        $mockField = $this->createMock(Field::class);
        $mockField->method('getName')->willReturn('testField');
        $mockField->method('getType')->willReturn($type);
        $mockField->method('isRequired')->willReturn($isRequired);
        $mockField->method('isCAT')->willReturn($isCAT);

        return $mockField;
    }

    protected function setUp(): void {
        parent::setUp();

        $this->testObj = $this->createDummyInstrument('test');
    }

    public function testCtor_invalidArg() {
        $this->expectException('TypeError');
        new Instrument((object)[], 'label', array(), array());  // NOSONAR - ignore useless object instantiation

        $this->expectException('TypeError');
        new Instrument('test', 4.9, array(), array());  // NOSONAR - ignore useless object instantiation

        $this->expectException('TypeError');
        new Instrument('test', 'label', array(), array());  // NOSONAR - ignore useless object instantiation
    }

    public function testCtor_withoutEvents() {
        $obj = $this->createInstrument(static::DEMOGRAPHICS_FORM, false);

        $name = ReflectionHelper::getObjectProperty(Instrument::class, 'instrumentName', $obj);
        $label = ReflectionHelper::getObjectProperty(Instrument::class, 'instrumentLabel', $obj);
        $reqFields = ReflectionHelper::getObjectProperty(Instrument::class, 'requiredFields', $obj);
        $optFields = ReflectionHelper::getObjectProperty(Instrument::class, 'optionalFields', $obj);
        $events = ReflectionHelper::getObjectProperty(Instrument::class, 'events', $obj);
        $logger = ReflectionHelper::getObjectProperty(Instrument::class, 'logger', $obj);

        $this->assertEquals(static::DEMOGRAPHICS_FORM, $name, 'Instrument name not stored properly');
        $this->assertEquals(static::DEMOGRAPHICS_LABEL, $label, 'Label not stored properly');
        $this->assertEmpty($events);
        $this->assertInstanceOf(LoggerInterface::class, $logger);


        $fieldNames = self::exportedFieldsForInstrument(
            static::DEMOGRAPHICS_FORM,
            $this->stubRedcapProj->exportFieldNames(),
            $this->stubRedcapProj->exportMetadata());

        $loadedFields = array_merge(array_keys($reqFields), array_keys($optFields));
        $this->assertCount(count($fieldNames), $loadedFields);
        $this->assertEquals($fieldNames, $loadedFields);
    }


    public function testCtor_withEvents() {
        $obj = $this->createInstrument(static::CONSENT_FORM);

        $loadedEvents = ReflectionHelper::getObjectProperty(Instrument::class, 'events', $obj);

        $redcapEvents = array();
        $eventMappings = $this->stubRedcapProj->exportInstrumentEventMappings();
        foreach ($eventMappings as $entry) {
            if ($entry['form'] === static::CONSENT_FORM) {
                $redcapEvents[] = $entry['unique_event_name'];
            }
        }

        $this->assertCount(count($redcapEvents), $loadedEvents);
        $this->assertEquals($redcapEvents, $loadedEvents);
    }


    public function testCtor_singularCheckbox() {
        $checkboxFieldname = static::SINGULAR_CHECKBOX_FIELD.'___1';
        $obj = $this->createInstrument(static::SINGULAR_CHECKBOX_FORM);

        $reqFields = ReflectionHelper::getObjectProperty(Instrument::class, 'requiredFields', $obj);
        $optFields = ReflectionHelper::getObjectProperty(Instrument::class, 'optionalFields', $obj);

        $this->assertCount(0, $reqFields, 'Required fields should have been empty');
        $this->assertCount(1, $optFields, 'Optional fields should contain the singluar checkbox');
        $this->assertArrayHasKey($checkboxFieldname, $optFields, 'Singular checkbox missing from optional fields');

        $field = array_pop($optFields);

        $this->assertEquals($checkboxFieldname, $field->getName(), 'Optional fields missing singular checkbox');
    }


    public function testGetName() {
        $name = $this->testObj->getName();

        $this->assertEquals(self::TEST_FORM_NAME, $name);
    }

    public function testIsCat_withoutNext() {
        ReflectionHelper::setObjectProperty(Instrument::class, 'isCAT', true, $this->testObj);

        $isCAT = $this->testObj->isCAT(false);
        $this->assertTrue($isCAT);

        ReflectionHelper::setObjectProperty(Instrument::class, 'isCAT', false, $this->testObj);

        $isCAT = $this->testObj->isCAT(false);
        $this->assertFalse($isCAT);
    }

    public function testIsCAT_withNext() {
        $obj = $this->createDummyInstrument('next');
        ReflectionHelper::setObjectProperty(Instrument::class, 'isCAT', true, $obj);
        $this->testObj->setNextInstrument($obj);

        $isCat = $this->testObj->isCAT(true);

        $this->assertTrue($isCat, 'If any next instruments are CAT, result should be TRUE');
    }

    public function testGetRequiredRecordFieldNames() {
        $fields = array(
            'field1' => 'value1',
            'field2' => 'value2',
            'field3' => 'value3'
            );

        ReflectionHelper::setObjectProperty(Instrument::class, 'requiredFields', $fields, $this->testObj);

        $keys = $this->testObj->getRequiredRecordFieldNames();

        $this->assertEquals(array_keys($fields), $keys);
    }

    public function testGetRequiredFields() {
        $testFields = array(
            'field1' => 'value1',
            'field2' => 'value2',
            'field3' => 'value3'
        );

        ReflectionHelper::setObjectProperty(Instrument::class, 'requiredFields', $testFields, $this->testObj);

        $fields = $this->testObj->getRequiredFields();

        $this->assertEquals($testFields, $fields);
    }

    public function testGetOptionalRecordFieldNames() {
        $fields = array(
            'field1' => 'value1',
            'field2' => 'value2',
            'field3' => 'value3'
        );

        ReflectionHelper::setObjectProperty(Instrument::class, 'optionalFields', $fields, $this->testObj);

        $keys = $this->testObj->getOptionalRecordFieldNames();

        $this->assertEquals(array_keys($fields), $keys);
    }

    public function testGetOptionalFields() {
        $testFields = array(
            'field1' => 'value1',
            'field2' => 'value2',
            'field3' => 'value3'
        );

        ReflectionHelper::setObjectProperty(Instrument::class, 'optionalFields', $testFields, $this->testObj);

        $fields = $this->testObj->getOptionalFields();

        $this->assertEquals($testFields, $fields);
    }

    public function testGetAllFieldNames() {
        $reqFields = array(
            'field9' => 'value9',
            'field8' => 'value8',
            'field7' => 'value7'
        );
        $optFields = array(
            'fieldA' => 'valueA',
            'fieldB' => 'valueB',
            'fieldC' => 'valueC'
        );

        ReflectionHelper::setObjectProperty(Instrument::class, 'requiredFields', $reqFields, $this->testObj);
        ReflectionHelper::setObjectProperty(Instrument::class, 'optionalFields', $optFields, $this->testObj);

        $keys = $this->testObj->getAllFieldNames();

        $this->assertEquals(array_merge(array_keys($reqFields), array_keys($optFields), array(static::TEST_FORM_NAME.'_complete', static::TEST_FORM_NAME.'_timestamp')), $keys);
    }

    public function testGetAllFields() {
        $reqFields = array(
            'field9' => 'value9',
            'field8' => 'value8',
            'field7' => 'value7'
        );
        $optFields = array(
            'fieldA' => 'valueA',
            'fieldB' => 'valueB',
            'fieldC' => 'valueC'
        );

        ReflectionHelper::setObjectProperty(Instrument::class, 'requiredFields', $reqFields, $this->testObj);
        ReflectionHelper::setObjectProperty(Instrument::class, 'optionalFields', $optFields, $this->testObj);

        $keys = $this->testObj->getAllFields();

        $this->assertEquals(array_merge($reqFields, $optFields), $keys);
    }

    public function testHasInputs() {
        ReflectionHelper::setObjectProperty(Instrument::class, 'hasInputFields', true, $this->testObj);

        $hasInputFields = $this->testObj->hasInputs();

        $this->assertTrue($hasInputFields, 'hasInputs() should have returned true');

        ReflectionHelper::setObjectProperty(Instrument::class, 'hasInputFields', false, $this->testObj);

        $hasInputFields = $this->testObj->hasInputs();

        $this->assertFalse($hasInputFields, 'hasInputs() should have returned false');
    }

    public function testAddNextInstrument() {
        $nextInst = ReflectionHelper::getObjectProperty(Instrument::class, 'nextInstrument', $this->testObj);

        $this->assertEmpty($nextInst, 'Test object should not have a next instrument yet');

        $nextInst = $this->createDummyInstrument('next');
        $this->testObj->setNextInstrument($nextInst);

        $nextProp = ReflectionHelper::getObjectProperty(Instrument::class, 'nextInstrument', $this->testObj);
        $this->assertEquals($nextInst, $nextProp);
    }

    public function testHasNextInstrument() {
        $this->assertFalse($this->testObj->hasNextInstrument());

        ReflectionHelper::setObjectProperty(Instrument::class, 'nextInstrument', $this->createDummyInstrument('next'), $this->testObj);

        $this->assertTrue($this->testObj->hasNextInstrument());
    }

    public function testGetNextInstrument() {
        $testInst = $this->createDummyInstrument('next');

        ReflectionHelper::setObjectProperty(Instrument::class, 'nextInstrument', $testInst, $this->testObj);

        $nextInst = $this->testObj->getNextInstrument();

        $this->assertEquals($testInst, $nextInst);
    }

    public function testAddField_requiredTypeRequiredField() {
        $mockField = $this->createMockField('yesno', true);

        ReflectionHelper::invokeObjectMethod(Instrument::class, $this->testObj, 'addField', $mockField);

        $reqFields = ReflectionHelper::getObjectProperty(Instrument::class,'requiredFields', $this->testObj);
        $optFields = ReflectionHelper::getObjectProperty(Instrument::class,'optionalFields', $this->testObj);
        $hasInput = ReflectionHelper::getObjectProperty(Instrument::class,'hasInputFields', $this->testObj);
        $isCAT = ReflectionHelper::getObjectProperty(Instrument::class,'isCAT', $this->testObj);

        $this->assertArrayHasKey($mockField->getName(), $reqFields);
        $this->assertArrayNotHasKey($mockField->getName(), $optFields);
        $this->assertTrue($hasInput, self::MISSED_INPUT_FIELD);
        $this->assertFalse($isCAT, 'CAT flag should be false');
    }

    public function testAddField_requiredTypeNotRequiredField() {
        $mockField = $this->createMockField('truefalse', false);

        ReflectionHelper::invokeObjectMethod(Instrument::class, $this->testObj, 'addField', $mockField);

        $reqFields = ReflectionHelper::getObjectProperty(Instrument::class,'requiredFields', $this->testObj);
        $optFields = ReflectionHelper::getObjectProperty(Instrument::class,'optionalFields', $this->testObj);
        $hasInput = ReflectionHelper::getObjectProperty(Instrument::class,'hasInputFields', $this->testObj);
        $isCAT = ReflectionHelper::getObjectProperty(Instrument::class,'isCAT', $this->testObj);

        $this->assertArrayNotHasKey($mockField->getName(), $reqFields);
        $this->assertArrayHasKey($mockField->getName(), $optFields);
        $this->assertTrue($hasInput, self::MISSED_INPUT_FIELD);
        $this->assertFalse($isCAT, 'CAT flag should be false');
    }

    public function testAddField_requiredCATField() {
        $mockField = $this->createMockField('radio', false, true);

        ReflectionHelper::invokeObjectMethod(Instrument::class, $this->testObj, 'addField', $mockField);


        $reqFields = ReflectionHelper::getObjectProperty(Instrument::class,'requiredFields', $this->testObj);
        $optFields = ReflectionHelper::getObjectProperty(Instrument::class,'optionalFields', $this->testObj);
        $hasInput = ReflectionHelper::getObjectProperty(Instrument::class,'hasInputFields', $this->testObj);
        $isCAT = ReflectionHelper::getObjectProperty(Instrument::class,'isCAT', $this->testObj);

        $this->assertArrayNotHasKey($mockField->getName(), $reqFields);
        $this->assertArrayHasKey($mockField->getName(), $optFields);
        $this->assertTrue($hasInput, self::MISSED_INPUT_FIELD);
        $this->assertTrue($isCAT, 'CAT flag should be true');
    }

    public function testAddField_optionalTypeInputField() {
        $mockField = $this->createMockField('notes', false);

        ReflectionHelper::invokeObjectMethod(Instrument::class, $this->testObj, 'addField', $mockField);

        $reqFields = ReflectionHelper::getObjectProperty(Instrument::class,'requiredFields', $this->testObj);
        $optFields = ReflectionHelper::getObjectProperty(Instrument::class,'optionalFields', $this->testObj);
        $hasInput = ReflectionHelper::getObjectProperty(Instrument::class,'hasInputFields', $this->testObj);

        $this->assertArrayNotHasKey($mockField->getName(), $reqFields);
        $this->assertArrayHasKey($mockField->getName(), $optFields);
        $this->assertTrue($hasInput, self::MISSED_INPUT_FIELD);
    }

    public function testAddField_optionalTypeNonInputField() {
        $mockField = $this->createMockField('calc', false);

        ReflectionHelper::invokeObjectMethod(Instrument::class, $this->testObj, 'addField', $mockField);

        $reqFields = ReflectionHelper::getObjectProperty(Instrument::class,'requiredFields', $this->testObj);
        $optFields = ReflectionHelper::getObjectProperty(Instrument::class,'optionalFields', $this->testObj);
        $hasInput = ReflectionHelper::getObjectProperty(Instrument::class,'hasInputFields', $this->testObj);

        $this->assertArrayNotHasKey($mockField->getName(), $reqFields);
        $this->assertArrayHasKey($mockField->getName(), $optFields);
        $this->assertFalse($hasInput, 'calc field type does not have input');
    }

    public function testAddField_optionalUnknownTypeField() {
        $fieldType = 'custom';
        $mockField = $this->createMockField($fieldType, false);

        ReflectionHelper::invokeObjectMethod(Instrument::class, $this->testObj, 'addField', $mockField);

        $reqFields = ReflectionHelper::getObjectProperty(Instrument::class,'requiredFields', $this->testObj);
        $optFields = ReflectionHelper::getObjectProperty(Instrument::class,'optionalFields', $this->testObj);
        $skippedTypes = ReflectionHelper::getObjectProperty(Instrument::class,'skippedTypes', $this->testObj);
        $hasInput = ReflectionHelper::getObjectProperty(Instrument::class,'hasInputFields', $this->testObj);

        $this->assertArrayNotHasKey($mockField->getName(), $reqFields);
        $this->assertArrayHasKey($mockField->getName(), $optFields);
        $this->assertContains($fieldType, $skippedTypes, 'Unknown field type should have been remembered');
        $this->assertTrue($hasInput, 'Unknown field type should assumed to allow input');
    }

    public function testGetEvents() {
        $testEvents = array(
            'testEvent1',
            'testEvent2'
            );

        $this->assertEmpty($this->testObj->getEvents(), 'Event list should default to empty list');

        ReflectionHelper::setObjectProperty(Instrument::class, 'events', $testEvents, $this->testObj);

        $events = $this->testObj->getEvents();

        $this->assertIsArray($events, 'return value from getEvents() should be an array');
        $this->assertEquals($testEvents, $events);
    }

    const ARRAY_SIZE_REGEX      = '/a:8:{.+}/';

    public function testSerialize() {
        $testFormName = 'fullObjTest';
        $testEvent = 'testEvent';
        $reqFieldName = 'reqFieldName';
        $reqFieldType = 'yesno';
        $optFieldName = 'optFieldName';
        $optFieldType = 'text';

        $reqField = $this->createMock(Field::class);
        $reqField->method('getFormName')->willReturn($testFormName);
        $reqField->method('isCAT')->willReturn(false);
        $reqField->method('getName')->willReturn($reqFieldName);
        $reqField->method('getType')->willReturn($reqFieldType);
        $reqField->method('isRequired')->willReturn(true);

        $optField = $this->createMock(Field::class);
        $optField->method('getFormName')->willReturn($testFormName);
        $optField->method('isCAT')->willReturn(false);
        $optField->method('getName')->willReturn($optFieldName);
        $optField->method('getType')->willReturn($optFieldType);
        $optField->method('isRequired')->willReturn(false);

        ReflectionHelper::setObjectProperty(Instrument::class, 'isCAT', true, $this->testObj);
        ReflectionHelper::setObjectProperty(Instrument::class, 'events', array($testEvent), $this->testObj);

        ReflectionHelper::invokeObjectMethod(Instrument::class, $this->testObj, 'addField', $reqField);
        ReflectionHelper::invokeObjectMethod(Instrument::class, $this->testObj, 'addField', $optField);

        $serializedObj = $this->testObj->serialize();

        $this->assertMatchesRegularExpression(self::ARRAY_SIZE_REGEX, $serializedObj); // should be an array of 8 elements

        /*
         a:8:{
         s:14:"instrumentName";s:8:"testName";
         s:15:"instrumentLabel";s:9:"testLabel";
         s:5:"isCAT";b:1;
         s:14:"hasInputFields";b:1;
         s:14:"requiredFields";a:1:{
         s:12:"reqFieldName";N;
         }
         s:14:"optionalFields";a:1:{
         s:12:"optFieldName";N;
         }
         s:13:"impliedFields";a:2:{
         i:0;s:17:"testName_complete";
         i:1;s:18:"testName_timestamp";
         }
         s:6:"events";a:1:{
         i:0;s:9:"testEvent";
         }
         }
         */

        $this->assertStringContainsString(self::TEST_FORM_NAME, $serializedObj);
        $this->assertStringContainsString(self::TEST_FORM_LABEL, $serializedObj);
        $this->assertStringContainsString($reqFieldName, $serializedObj);
        $this->assertStringContainsString($optFieldName, $serializedObj);
        $this->assertStringContainsString($testEvent, $serializedObj);

        $this->assertStringContainsString(self::TEST_FORM_NAME.'_complete', $serializedObj);
        $this->assertStringContainsString(self::TEST_FORM_NAME.'_timestamp', $serializedObj);

        return $serializedObj;
    }

    /**
     * @depends testSerialize
     */
    public function testUnserialize($serializedObj) // $serialObj is passed from testSerialize
    {
        $reqFieldName = 'reqFieldName';
        $optFieldName = 'optFieldName';

        /** @var Instrument */
        $form = (new \ReflectionClass(Instrument::class))->newInstanceWithoutConstructor();

        $form->unserialize($serializedObj);

        $this->assertEquals(self::TEST_FORM_NAME, $form->getName());
        $this->assertEquals(true, $form->isCAT());

        $this->assertContains($reqFieldName, $form->getRequiredRecordFieldNames());
        $this->assertContains($optFieldName, $form->getOptionalRecordFieldNames());
    }

    public static function exportedFieldsForInstrument (string $instrumentName, array $fieldNames, array $metadata) {
        $exportFieldNames = array();
        $formFields = array();

        foreach($metadata as $field) {
            if ($instrumentName === $field['form_name']) {
                $formFields[] = $field['field_name'];
            }
        }

        foreach ($fieldNames as $entry) {
            if (in_array($entry['original_field_name'], $formFields)) {
                $exportFieldNames[] = $entry['export_field_name'];
            }
        }

        return $exportFieldNames;
    }

}
