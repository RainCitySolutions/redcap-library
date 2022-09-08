<?php
namespace RainCity\REDCap;

use PHPUnit\Framework\TestCase;

/**
 *
 * @covers \RainCity\REDCap\Event
 *
 */
class EventTest extends TestCase
{
    private $testEvent = array (
        "event_name" => "initial_visit",
        "arm_num" => 1,
        "day_offset" => 2,
        "offset_min" => 0,
        "offset_max" => 0,
        "unique_event_name" => "initial_visit_arm_1",
        "custom_event_label" => null
    );

    public function testConstructor_noArg()
    {
        $this->expectException("ArgumentCountError");
        new Event();
    }

    public function testConstructor_emptyArray()
    {
        $this->expectException("InvalidArgumentException");
        new Event(array());
    }

    public function testConstructor()
    {
        $event = new Event($this->testEvent);
        $this->assertEquals($this->testEvent['unique_event_name'], $event->getName());
    }

    public function testSerialize()
    {
        $event = new Event($this->testEvent);
        $serialObj = $event->serialize();

        $this->assertStringStartsWith('a:1', $serialObj);   // check that its an array of the expected length

        $fldStr = sprintf('s:%d:"%s"', strlen($this->testEvent['unique_event_name']), $this->testEvent['unique_event_name']);
        $this->assertStringContainsString($fldStr, $serialObj, 'Event not serialized properly');

        return $serialObj;
    }

    /**
     * @depends testSerialize
     */
    public function testUnserialize(string $serialObj)
    {
        $reflection = new \ReflectionClass(Event::class);
        $event = $reflection->newInstanceWithoutConstructor();
        $event->unserialize($serialObj);
        $this->assertEquals($this->testEvent['unique_event_name'], $event->getName());
    }
}
