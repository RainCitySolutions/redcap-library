<?php
declare(strict_types = 1);
namespace RainCity\REDCap;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;

#[CoversClass('\RainCity\REDCap\Field')]
class FieldTest extends TestCase
{
    const TEST_INSTRUMENT_NAME = 'test_instrument';
    const IS_REQUIRED_NOT_AS_EXPECTED = 'isRequired() not as expected';
    const IS_OPTIONAL_NOT_AS_EXPECTED = 'isOptional() not as expected';

    protected function getTestField(): array {
        return array (
            "field_name" => "gender",
            "form_name" => self::TEST_INSTRUMENT_NAME,
            "section_header" => "",
            "field_type" => "radio",
            "field_label" => "Gender ",
            "select_choices_or_calculations" => "1, Female | 2, Male | 3, Transgender Female | 4, Transgender Male | 5, Gender Variant/Non-Conforming | 6, Prefer not to answer",
            "field_note" => "",
            "text_validation_type_or_show_slider_number" => "",
            "text_validation_min" => "",
            "text_validation_max" => "",
            "branching_logic" => "",
            "required_field" => "",
            "field_annotation" => ""
        );
    }

    protected function getSerializeTestField(): array {
        $field = $this->getTestField();
        $field['field_note'] = 'A field note';
        $field['branching_logic'] = 'Some Branching Logic';

        return $field;
    }


    public function testConstructor_noArg()
    {
        $this->expectException("ArgumentCountError");
        new Field();    // NOSONAR
    }

    public function testConstructor_invalidArray()
    {
        $this->expectException("InvalidArgumentException");
        new Field(array()); // NOSONAR
    }

    public function testConstructor()
    {
        $testField = $this->getTestField();

        $field = new Field($testField);

        $this->assertEquals($testField['form_name'], $field->getFormName());
        $this->assertEquals($testField['field_name'], $field->getName());
        $this->assertEquals($testField['field_type'], $field->getType());
        $this->assertEquals($testField['branching_logic'], $field->getBranching());
        $this->assertEquals($testField['field_note'], $field->getNote());

        $this->assertTrue($field->isRequired(), self::IS_REQUIRED_NOT_AS_EXPECTED );
        $this->assertFalse($field->isOptional(), self::IS_OPTIONAL_NOT_AS_EXPECTED);
        $this->assertFalse($field->isCAT(), 'isCAT() not as expected');
        $this->assertFalse($field->hasBranching(), 'hasBranching() not as expected');
    }

    public function testExplicitRequired()
    {
        $localField = $this->getTestField();
        $localField['required_field'] = 'y';

        $field = new Field($localField);

        $this->assertTrue($field->isRequired(), self::IS_REQUIRED_NOT_AS_EXPECTED);
        $this->assertFalse($field->isOptional(), self::IS_OPTIONAL_NOT_AS_EXPECTED);
    }

    public function testImplicitOptional_branchingLogic()
    {
        $localField = $this->getTestField();
        $localField['branching_logic'] = 'someLogic';

        $field = new Field($localField);

        $this->assertFalse($field->isRequired(), self::IS_REQUIRED_NOT_AS_EXPECTED);
        $this->assertTrue($field->isOptional(), self::IS_OPTIONAL_NOT_AS_EXPECTED);
        $this->assertTrue($field->hasBranching(), 'hasBranching() not as expected');
    }

    public function testImplicitOptional_optionalField()
    {
        $localField = $this->getTestField();
        $localField['field_note'] = 'Optional';

        $field = new Field($localField);

        $this->assertFalse($field->isRequired(), self::IS_REQUIRED_NOT_AS_EXPECTED);
        $this->assertTrue($field->isOptional(), self::IS_OPTIONAL_NOT_AS_EXPECTED);
    }

    public function testCAT()
    {
        $localField = $this->getTestField();
        $localField['field_name'] = 'field_name_tscore';

        $field = new Field($localField);

        $this->assertTrue($field->isCAT(), 'isCAT() not as expected');
    }

    public function testSetFormName() {
        $localField = $this->getTestField();
        $localField['field_name'] = 'field_name_tscore';

        $field = new Field($localField);
        $this->assertEquals(self::TEST_INSTRUMENT_NAME, $field->getFormName());

        $testFormName = 'a_different_instrument';

        $field->setFormName($testFormName);
        $this->assertEquals($testFormName, $field->getFormName());
    }

    public function testSerialize()
    {
        $localField = $this->getSerializeTestField();

        $event = new Field($localField);
        $serializedObj = $event->serialize();

        $this->assertStringStartsWith('a:8', $serializedObj);   // check that its an array of the expected length

        $fldStr = sprintf('s:%d:"%s"', strlen(self::TEST_INSTRUMENT_NAME), self::TEST_INSTRUMENT_NAME);
        $this->assertStringContainsString($fldStr, $serializedObj, "Instrument name not serialized properly");

        foreach (array ('field_name', 'field_type', 'field_note', 'branching_logic') as $fieldName) {
            $fldStr = sprintf('s:%d:"%s"', strlen($localField[$fieldName]), $localField[$fieldName]);
            $this->assertStringContainsString($fldStr, $serializedObj, "Field '{$fieldName}' not serialized properly");
        }

        // No good way to validate the boolean fields short of know the order they are serialized

        return $serializedObj;
    }

    #[Depends('testSerialize')]
    public function testUnserialize($serializedObj) // $serialObj is passed from testSerialize
    {
        $localField = $this->getSerializeTestField();   // Assumption: that testSerialize used getSerializeTestField()

        /** @var Field */
        $field = (new \ReflectionClass(Field::class))->newInstanceWithoutConstructor();

        $field->unserialize($serializedObj);

        $this->assertEquals(self::TEST_INSTRUMENT_NAME, $field->getFormName());

        $this->assertEquals($localField['field_name'], $field->getName());
        $this->assertEquals($localField['field_type'], $field->getType());
        $this->assertEquals($localField['branching_logic'], $field->getBranching());
        $this->assertEquals($localField['field_note'], $field->getNote());
    }

    public function testGetCheckboxFieldName_notCheckbox()
    {
        $localField = $this->getTestField();

        $field = new Field($localField);

        $this->assertNull($field->getCheckboxFieldName(), 'getCheckboxFieldName() should return null');
    }

    public function testGetCheckboxFieldName_isCheckbox()
    {
        $testFieldName = 'a_checkbox_field';

        $localField = $this->getTestField();
        $localField['field_type'] = "checkbox";
        $localField['field_name'] = $testFieldName . '___3';

        $field = new Field($localField);

        $result = $field->getCheckboxFieldName();
        $this->assertNotNull($result, 'getCheckboxFieldName() should not return null');
        $this->assertEquals($testFieldName, $result, 'getCheckboxFieldName() returned incorrect value');
    }
}
