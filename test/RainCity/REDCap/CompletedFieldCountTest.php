<?php
namespace RainCity\REDCap;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use RainCity\TestHelper\ReflectionHelper;

#[CoversClass('\RainCity\REDCap\CompletedFieldCount')]
class CompletedFieldCountTest extends TestCase
{
    public function testCtor_noArgs() {
        $testObj = new CompletedFieldCount();

        $this->assertEquals(0, ReflectionHelper::getObjectProperty(CompletedFieldCount::class, 'completedCnt', $testObj));
        $this->assertEquals(0, ReflectionHelper::getObjectProperty(CompletedFieldCount::class, 'requiredCnt', $testObj));

        $this->assertNull(ReflectionHelper::getObjectProperty(CompletedFieldCount::class, 'firstIncompleteInstrument', $testObj));
        $this->assertNull(ReflectionHelper::getObjectProperty(CompletedFieldCount::class, 'firstIncompleteField', $testObj));
    }

    public function testCtor_withCounts() {
        $testCompletedCnt = 85;
        $testRequiredCnt = 24;

        $testObj = new CompletedFieldCount($testCompletedCnt, $testRequiredCnt);

        $this->assertEquals($testCompletedCnt, ReflectionHelper::getObjectProperty(CompletedFieldCount::class, 'completedCnt', $testObj));
        $this->assertEquals($testRequiredCnt, ReflectionHelper::getObjectProperty(CompletedFieldCount::class, 'requiredCnt', $testObj));

        $this->assertNull(ReflectionHelper::getObjectProperty(CompletedFieldCount::class, 'firstIncompleteInstrument', $testObj));
        $this->assertNull(ReflectionHelper::getObjectProperty(CompletedFieldCount::class, 'firstIncompleteField', $testObj));
    }

    public function testCtor_withInstruments() {
        $testInstrument = 'testInstrument';
        $testField = 'testField';

        $testObj = new CompletedFieldCount(0, 0, $testInstrument, $testField);

        $this->assertEquals($testInstrument, ReflectionHelper::getObjectProperty(CompletedFieldCount::class, 'firstIncompleteInstrument', $testObj));
        $this->assertEquals($testField, ReflectionHelper::getObjectProperty(CompletedFieldCount::class, 'firstIncompleteField', $testObj));
    }

    public function testGetters() {
        $testCompletedCnt = 21;
        $testRequiredCnt = 6;
        $testInstrument = 'gettersTestInstrument';
        $testField = 'gettersTestField';

        $testObj = new CompletedFieldCount();

        ReflectionHelper::setObjectProperty(CompletedFieldCount::class, 'completedCnt', $testCompletedCnt, $testObj);
        ReflectionHelper::setObjectProperty(CompletedFieldCount::class, 'requiredCnt', $testRequiredCnt, $testObj);
        ReflectionHelper::setObjectProperty(CompletedFieldCount::class, 'firstIncompleteInstrument', $testInstrument, $testObj);
        ReflectionHelper::setObjectProperty(CompletedFieldCount::class, 'firstIncompleteField', $testField, $testObj);

        $this->assertEquals($testCompletedCnt, $testObj->getCompletedCount());
        $this->assertEquals($testRequiredCnt, $testObj->getRequiredCount());
        $this->assertEquals($testInstrument, $testObj->getFirstIncompleteInstrument());
        $this->assertEquals($testField, $testObj->getFirstIncompleteField());
    }

    public function testSetCounts_noneSet() {
        $testCompletedCnt = 75;
        $testRequiredCnt = 91;

        $testObj = new CompletedFieldCount();

        $testObj->setCounts($testCompletedCnt, $testRequiredCnt);

        $this->assertEquals($testCompletedCnt, ReflectionHelper::getObjectProperty(CompletedFieldCount::class, 'completedCnt', $testObj));
        $this->assertEquals($testRequiredCnt, ReflectionHelper::getObjectProperty(CompletedFieldCount::class, 'requiredCnt', $testObj));
    }

    public function testSetCounts_alreadySet() {
        $testCompletedCntA = 41;
        $testRequiredCntA = 42;
        $testCompletedCntB = 79;
        $testRequiredCntB = 85;

        $testObj = new CompletedFieldCount();

        $testObj->setCounts($testCompletedCntA, $testRequiredCntA);
        $testObj->setCounts($testCompletedCntB, $testRequiredCntB);

        $this->assertEquals($testCompletedCntB, ReflectionHelper::getObjectProperty(CompletedFieldCount::class, 'completedCnt', $testObj));
        $this->assertEquals($testRequiredCntB, ReflectionHelper::getObjectProperty(CompletedFieldCount::class, 'requiredCnt', $testObj));
    }

    public function testSetFirstIncomplete_noneSet() {
        $testInstrument = 'incompleteInstrument';
        $testField = 'incompleteField';

        $testObj = new CompletedFieldCount();

        $testObj->setFirstIncomplete($testInstrument, $testField);

        $this->assertEquals($testInstrument, ReflectionHelper::getObjectProperty(CompletedFieldCount::class, 'firstIncompleteInstrument', $testObj));
        $this->assertEquals($testField, ReflectionHelper::getObjectProperty(CompletedFieldCount::class, 'firstIncompleteField', $testObj));
    }

    public function testSetFirstIncomplete_alreadySet() {
        $testInstrumentA = 'incompleteInstrumentA';
        $testFieldA = 'incompleteFieldA';
        $testFieldB = 'incompleteFieldB';

        $testObj = new CompletedFieldCount();

        $testObj->setFirstIncomplete($testInstrumentA, $testFieldA);

        $this->assertEquals($testInstrumentA, ReflectionHelper::getObjectProperty(CompletedFieldCount::class, 'firstIncompleteInstrument', $testObj));
        $this->assertEquals($testFieldA, ReflectionHelper::getObjectProperty(CompletedFieldCount::class, 'firstIncompleteField', $testObj));

        $testObj->setFirstIncomplete($testInstrumentA, $testFieldB);

        $this->assertEquals($testInstrumentA, ReflectionHelper::getObjectProperty(CompletedFieldCount::class, 'firstIncompleteInstrument', $testObj));
        $this->assertEquals($testFieldA, ReflectionHelper::getObjectProperty(CompletedFieldCount::class, 'firstIncompleteField', $testObj));
    }

    public function testMerge_noneSet() {
        $testCompletedCnt = 3;
        $testRequiredCnt = 4;
        $testInstrument = 'incompleteInstrumentA';
        $testField = 'incompleteFieldA';

        $testObj = new CompletedFieldCount();

        // Ensure assumed state
        $this->assertEquals(0, ReflectionHelper::getObjectProperty(CompletedFieldCount::class, 'completedCnt', $testObj));
        $this->assertEquals(0, ReflectionHelper::getObjectProperty(CompletedFieldCount::class, 'requiredCnt', $testObj));
        $this->assertNull(ReflectionHelper::getObjectProperty(CompletedFieldCount::class, 'firstIncompleteInstrument', $testObj));
        $this->assertNull(ReflectionHelper::getObjectProperty(CompletedFieldCount::class, 'firstIncompleteField', $testObj));

        $testObj->merge(new CompletedFieldCount($testCompletedCnt, $testRequiredCnt, $testInstrument, $testField));

        // Test expectations
        $this->assertEquals($testCompletedCnt, ReflectionHelper::getObjectProperty(CompletedFieldCount::class, 'completedCnt', $testObj));
        $this->assertEquals($testRequiredCnt, ReflectionHelper::getObjectProperty(CompletedFieldCount::class, 'requiredCnt', $testObj));
        $this->assertEquals($testInstrument, ReflectionHelper::getObjectProperty(CompletedFieldCount::class, 'firstIncompleteInstrument', $testObj));
        $this->assertEquals($testField, ReflectionHelper::getObjectProperty(CompletedFieldCount::class, 'firstIncompleteField', $testObj));
    }

    public function testMerge_accumulation() {
        $testCompletedCntA = 3;
        $testRequiredCntA = 4;
        $testCompletedCntB = 7;
        $testRequiredCntB = 22;

        $testObj = new CompletedFieldCount($testCompletedCntA, $testRequiredCntA);

        // Ensure assumed state
        $this->assertEquals($testCompletedCntA, ReflectionHelper::getObjectProperty(CompletedFieldCount::class, 'completedCnt', $testObj));
        $this->assertEquals($testRequiredCntA, ReflectionHelper::getObjectProperty(CompletedFieldCount::class, 'requiredCnt', $testObj));

        $testObj->merge(new CompletedFieldCount($testCompletedCntB, $testRequiredCntB));

        // Test expectations
        $this->assertEquals($testCompletedCntA + $testCompletedCntB, ReflectionHelper::getObjectProperty(CompletedFieldCount::class, 'completedCnt', $testObj));
        $this->assertEquals($testRequiredCntA + $testRequiredCntB, ReflectionHelper::getObjectProperty(CompletedFieldCount::class, 'requiredCnt', $testObj));
    }

    public function testMerge_incompleteAlreadySet() {
        $testInstrumentA = 'incompleteInstrumentA';
        $testFieldA = 'incompleteFieldA';
        $testInstrumentB = 'incompleteInstrumentB';
        $testFieldB = 'incompleteFieldB';

        $testObj = new CompletedFieldCount(0, 0, $testInstrumentA, $testFieldA);

        // Ensure assumed state
        $this->assertEquals($testInstrumentA, ReflectionHelper::getObjectProperty(CompletedFieldCount::class, 'firstIncompleteInstrument', $testObj));
        $this->assertEquals($testFieldA, ReflectionHelper::getObjectProperty(CompletedFieldCount::class, 'firstIncompleteField', $testObj));

        $testObj->merge(new CompletedFieldCount(0, 0, $testInstrumentB, $testFieldB));

        // Test expectations
        $this->assertEquals($testInstrumentA, ReflectionHelper::getObjectProperty(CompletedFieldCount::class, 'firstIncompleteInstrument', $testObj));
        $this->assertEquals($testFieldA, ReflectionHelper::getObjectProperty(CompletedFieldCount::class, 'firstIncompleteField', $testObj));
    }


    public function testHasIncompleteField() {
        $testObj = new CompletedFieldCount();

        $this->assertFalse($testObj->hasIncompleteField());

        ReflectionHelper::setObjectProperty(CompletedFieldCount::class, 'firstIncompleteField', 'testField', $testObj);

        $this->assertTrue($testObj->hasIncompleteField());
    }
}
