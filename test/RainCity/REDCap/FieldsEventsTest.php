<?php
declare(strict_types=1);
namespace RainCity\REDCap;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(FieldsEvents::class)]
final class FieldsEventsTest extends TestCase
{
    #[Test]
    public function constructor_sets_event_and_field_names(): void
    {
        $fe = new FieldsEvents(
            ['event1', 'event2'],
            ['field1', 'field2']
            );

        self::assertSame(['event1', 'event2'], $fe->eventNames);
        self::assertSame(['field1', 'field2'], $fe->fieldNames);
    }

    #[Test]
    public function constructor_defaults_to_empty_arrays(): void
    {
        $fe = new FieldsEvents();

        self::assertSame([], $fe->eventNames);
        self::assertSame([], $fe->fieldNames);
    }

    #[Test]
    public function add_merges_events_and_fields(): void
    {
        $a = new FieldsEvents(
            ['event1'],
            ['field1']
            );

        $b = new FieldsEvents(
            ['event2'],
            ['field2']
            );

        $a->add($b);

        self::assertEqualsCanonicalizing(
            ['event1', 'event2'],
            $a->eventNames
            );

        self::assertEqualsCanonicalizing(
            ['field1', 'field2'],
            $a->fieldNames
            );
    }

    #[Test]
    public function add_removes_duplicates(): void
    {
        $a = new FieldsEvents(
            ['event1'],
            ['field1']
            );

        $b = new FieldsEvents(
            ['event1', 'event2'],
            ['field1', 'field2']
            );

        $a->add($b);

        self::assertEqualsCanonicalizing(
            ['event1', 'event2'],
            $a->eventNames
            );

        self::assertEqualsCanonicalizing(
            ['field1', 'field2'],
            $a->fieldNames
            );
    }

    #[Test]
    public function add_preserves_existing_values(): void
    {
        $a = new FieldsEvents(
            ['eventA'],
            ['fieldA']
            );

        $b = new FieldsEvents(
            ['eventB'],
            ['fieldB']
            );

        $a->add($b);

        self::assertContains('eventA', $a->eventNames);
        self::assertContains('eventB', $a->eventNames);

        self::assertContains('fieldA', $a->fieldNames);
        self::assertContains('fieldB', $a->fieldNames);
    }
}
