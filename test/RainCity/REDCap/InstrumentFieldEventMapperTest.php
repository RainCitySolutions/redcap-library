<?php
declare(strict_types = 1);
namespace RainCity\REDCap;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(InstrumentFieldEventMapper::class)]
class InstrumentFieldEventMapperTest extends REDCapTestCase
{
    /** Export field names for every field on the history form. */
    private const HISTORY_EXPORT_FIELDS = [
        self::PAST_ILLNESS_FIELD,
        self::SHUNT_FIELD,
        self::CSF_DIVERSION . '___1',
        self::CSF_DIVERSION . '___2',
        self::CSF_DIVERSION . '___3',
        self::CSF_DIVERSION . '___4',
    ];

    /** Export field names for every field on the consent form. */
    private const CONSENT_EXPORT_FIELDS = [
        self::FULLNAME_FIELD,
        self::CONSENT_FIELD,
    ];

    /** Export field names for every field on the demographics form. */
    private const DEMOGRAPHICS_EXPORT_FIELDS = [
        self::RCD_ID_FIELD,
        self::EMAIL_FIELD,
        self::ADDRESS_FIELD,
        self::PHONE_FIELD,
        self::RELATIONSHIP_FIELD,
        self::SHORTNAME_FIELD,
        self::DATEOFBIRTH_FIELD,
        self::RACE_FIELD . '___1',
        self::RACE_FIELD . '___2',
        self::RACE_FIELD . '___3',
        self::RACE_FIELD . '___4',
        self::RACE_FIELD . '___5',
        self::RACE_FIELD . '___777',
        self::RACE_FIELD . '___888',
        self::RACE_FIELD . '___999',
        self::MISSING_DATA_FIELD,
    ];

    private function createMapper(): InstrumentFieldEventMapper
    {
        return new InstrumentFieldEventMapper($this->mockRedcapProj, $this->mockCache);
    }

    /**
     * Read the private event-to-fields map.
     *
     * @return array<string,array<string>>
     */
    private function getEventMap(InstrumentFieldEventMapper $mapper): array
    {
        $prop = new \ReflectionProperty(InstrumentFieldEventMapper::class, 'mapEventToFields');
        $prop->setAccessible(true);

        return $prop->getValue($mapper);
    }

    /**
     * Build a mapper as if it had been unserialized from the cache, i.e.
     * without running the constructor.
     *
     * @param array<string,array<string>> $map
     */
    private function createCachedMapper(array $map): InstrumentFieldEventMapper
    {
        $ref = new \ReflectionClass(InstrumentFieldEventMapper::class);
        $mapper = $ref->newInstanceWithoutConstructor();

        $prop = $ref->getProperty('mapEventToFields');
        $prop->setAccessible(true);
        $prop->setValue($mapper, $map);

        return $mapper;
    }

    // ---------------------------------------------------------------------
    // Cache miss
    // ---------------------------------------------------------------------

    public function testCacheMissLooksUpCacheUsingExpectedKey(): void
    {
        $this->mockCache->expects($this->once())
            ->method('get')
            ->with(InstrumentFieldEventMapper::INSTRUMENT_FIELD_EVENT_MAP)
            ->willReturn(null);

        $this->createMapper();
    }

    public function testCacheMissExportsDataFromRedcapOnce(): void
    {
        $this->mockCache->method('get')->willReturn(null);

        $this->mockRedcapProj->expects($this->once())->method('exportFieldNames');
        $this->mockRedcapProj->expects($this->once())->method('exportMetadata');
        $this->mockRedcapProj->expects($this->once())->method('exportInstrumentEventMappings');

        $this->createMapper();
    }

    public function testCacheMissStoresMapperInCache(): void
    {
        $this->mockCache->method('get')->willReturn(null);

        $this->mockCache->expects($this->once())
            ->method('set')
            ->with(
                InstrumentFieldEventMapper::INSTRUMENT_FIELD_EVENT_MAP,
                $this->isInstanceOf(InstrumentFieldEventMapper::class)
                );

        $this->createMapper();
    }

    public function testCacheMissBuildsAMapEntryForEachEvent(): void
    {
        $this->mockCache->method('get')->willReturn(null);

        $map = $this->getEventMap($this->createMapper());

        $this->assertEqualsCanonicalizing(self::ALL_VALID_EVENTS, array_keys($map));
    }

    public function testCacheMissMapsEventToFieldsOfItsInstruments(): void
    {
        $this->mockCache->method('get')->willReturn(null);

        $map = $this->getEventMap($this->createMapper());

        // Initial visit: demographics + consent + history
        $this->assertEqualsCanonicalizing(
            array_merge(
                self::DEMOGRAPHICS_EXPORT_FIELDS,
                self::CONSENT_EXPORT_FIELDS,
                self::HISTORY_EXPORT_FIELDS
                ),
            $map[self::TEST_EVENT_A]
            );

        // Annual visits: history only
        $this->assertEqualsCanonicalizing(self::HISTORY_EXPORT_FIELDS, $map[self::TEST_EVENT_B]);
        $this->assertEqualsCanonicalizing(self::HISTORY_EXPORT_FIELDS, $map[self::TEST_EVENT_C]);
    }

    public function testCacheMissExpandsCheckboxFieldsToTheirExportFieldNames(): void
    {
        $this->mockCache->method('get')->willReturn(null);

        $map = $this->getEventMap($this->createMapper());

        $this->assertContains(self::RACE_FIELD . '___1', $map[self::TEST_EVENT_A]);
        $this->assertContains(self::RACE_FIELD . '___999', $map[self::TEST_EVENT_A]);
        $this->assertNotContains(self::RACE_FIELD, $map[self::TEST_EVENT_A]);
    }

    public function testCacheMissEventWithUnknownFormHasEmptyFieldList(): void
    {
        $this->mockCache->method('get')->willReturn(null);

        $this->setCallback('exportInstrumentEventMappings', fn() => [
            ['arm_num' => 1, 'unique_event_name' => self::TEST_EVENT_A, 'form' => 'no_such_form'],
        ]);

        $map = $this->getEventMap($this->createMapper());

        $this->assertSame([self::TEST_EVENT_A => []], $map);
    }

    public function testCacheMissWithNoEventMappingsProducesEmptyMap(): void
    {
        $this->mockCache->method('get')->willReturn(null);

        $this->setCallback('exportInstrumentEventMappings', fn() => []);

        $mapper = $this->createMapper();

        $this->assertSame([], $this->getEventMap($mapper));
        $this->assertFalse($mapper->isValidField(self::EMAIL_FIELD));
    }

    // ---------------------------------------------------------------------
    // Cache hit
    // ---------------------------------------------------------------------

    public function testCacheHitUsesCachedMapWithoutQueryingRedcap(): void
    {
        $cached = $this->createCachedMapper([
            self::TEST_EVENT_A => ['cached_field_1', 'cached_field_2'],
        ]);

        $this->mockCache->expects($this->once())
            ->method('get')
            ->with(InstrumentFieldEventMapper::INSTRUMENT_FIELD_EVENT_MAP)
            ->willReturn($cached);

        $this->mockRedcapProj->expects($this->never())->method('exportFieldNames');
        $this->mockRedcapProj->expects($this->never())->method('exportMetadata');
        $this->mockRedcapProj->expects($this->never())->method('exportInstrumentEventMappings');
        $this->mockCache->expects($this->never())->method('set');

        $mapper = $this->createMapper();

        $this->assertTrue($mapper->isValidField('cached_field_1'));
        $this->assertTrue($mapper->isValidField('cached_field_2'));
        // a field that exists in the REDCap test data but not in the cached map
        $this->assertFalse($mapper->isValidField(self::EMAIL_FIELD));
    }

    // ---------------------------------------------------------------------
    // isValidField
    // ---------------------------------------------------------------------

    /**
     * @return array<string, array{string, bool}>
     */
    public static function isValidFieldProvider(): array
    {
        return [
            'record id'                       => [self::RCD_ID_FIELD, true],
            'text field'                      => [self::EMAIL_FIELD, true],
            'field on consent form'           => [self::CONSENT_FIELD, true],
            'field on history form'           => [self::PAST_ILLNESS_FIELD, true],
            'checkbox export field'           => [self::RACE_FIELD . '___1', true],
            'checkbox export field (999)'     => [self::RACE_FIELD . '___999', true],
            'checkbox export field (history)' => [self::CSF_DIVERSION . '___4', true],

            // The checkbox's original name is not an export field name
            'checkbox original name'          => [self::RACE_FIELD, false],
            // Form is not mapped to any event in the test data
            'field on unmapped form'          => [self::SINGULAR_CHECKBOX_FIELD . '___1', false],
            'unmapped form original name'     => [self::SINGULAR_CHECKBOX_FIELD, false],
            'unknown field'                   => ['no_such_field', false],
            'empty string'                    => ['', false],
        ];
    }

    #[DataProvider('isValidFieldProvider')]
    public function testIsValidField(string $field, bool $expected): void
    {
        $this->mockCache->method('get')->willReturn(null);

        $this->assertSame($expected, $this->createMapper()->isValidField($field));
    }

    public function testIsValidFieldIsTrueWhenFieldIsOnlyValidForOneEvent(): void
    {
        $cached = $this->createCachedMapper([
            self::TEST_EVENT_A => ['field_a'],
            self::TEST_EVENT_B => ['field_b'],
        ]);
        $this->mockCache->method('get')->willReturn($cached);

        $mapper = $this->createMapper();

        $this->assertTrue($mapper->isValidField('field_a'));
        $this->assertTrue($mapper->isValidField('field_b'));
        $this->assertFalse($mapper->isValidField('field_c'));
    }

    public function testIsValidFieldIsFalseForEmptyMap(): void
    {
        $this->mockCache->method('get')->willReturn($this->createCachedMapper([]));

        $this->assertFalse($this->createMapper()->isValidField(self::RCD_ID_FIELD));
    }
}
