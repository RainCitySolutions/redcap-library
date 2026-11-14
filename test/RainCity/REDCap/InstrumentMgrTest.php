<?php
declare(strict_types = 1);
namespace RainCity\REDCap;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(InstrumentMgr::class)]
#[UsesClass(Field::class)]
#[UsesClass(Instrument::class)]
class InstrumentMgrTest extends REDCapTestCase
{
    private string $cacheKey;

    protected function setUp(): void
    {
        parent::setUp();

        // The base class stubs the connection URL; build the key the same way InstrumentMgr does.
        $this->cacheKey = 'RedcapInstruments-' . $this->mockRedcapProj->getConnection()->getUrl();
    }

    protected function tearDown(): void
    {
        unset($this->cacheKey);

        parent::tearDown();
    }

    private function createManager(): InstrumentMgr
    {
        return new InstrumentMgr($this->mockRedcapProj, $this->mockCache, $this->stubLogger);
    }

    /**
     * Number of metadata fields the base class defines for a form.
     */
    private function expectedFieldCount(string $formName): int
    {
        return count(array_filter(
            self::getTestMetadata(),
            fn(array $field) => $field['form_name'] === $formName
            ));
    }

    public function testCacheMissExportsInstrumentsAndCachesThem(): void
    {
        $this->mockCache->expects($this->once())
            ->method('get')
            ->with($this->cacheKey, [])
            ->willReturn([]);

        $this->mockRedcapProj->expects($this->once())->method('exportInstruments');
        $this->mockRedcapProj->expects($this->once())->method('exportMetadata');
        $this->mockRedcapProj->expects($this->once())->method('exportInstrumentEventMappings');

        $this->mockCache->expects($this->once())
            ->method('set')
            ->with(
                $this->cacheKey,
                $this->callback(fn($value) =>
                    is_array($value)
                    && count($value) === count(self::TEST_INSTRUMENTS)
                    && array_reduce($value, fn($ok, $i) => $ok && $i instanceof Instrument, true))
            );

        $this->createManager();
    }

    public function testCacheMissCreatesAnInstrumentForEachRedcapInstrument(): void
    {
        $this->mockCache->method('get')->willReturn([]);

        $instruments = $this->createManager()->getInstruments();

        $expectedNames = array_column(self::TEST_INSTRUMENTS, 'instrument_name');

        $this->assertSame($expectedNames, array_keys($instruments));
        $this->assertContainsOnlyInstancesOf(Instrument::class, $instruments);
    }

    public function testCacheMissAssignsFieldsToTheirInstruments(): void
    {
        $this->mockCache->method('get')->willReturn([]);

        $instruments = $this->createManager()->getInstruments();

        foreach ([
            self::DEMOGRAPHICS_FORM,
            self::CONSENT_FORM,
            self::HISTORY_FORM,
            self::SINGULAR_CHECKBOX_FORM,
        ] as $formName) {
            $instrument = $instruments[$formName];
            $actual = count($instrument->getRequiredFields()) + count($instrument->getOptionalFields());

            $this->assertSame(
                $this->expectedFieldCount($formName),
                $actual,
                "Unexpected field count for form '$formName'"
                );
        }
    }

    public function testCacheMissSeparatesRequiredFromOptionalFields(): void
    {
        $this->mockCache->method('get')->willReturn([]);

        // Mark one consent field as required
        $metadata = self::getTestMetadata();
        foreach ($metadata as &$field) {
            if ($field['field_name'] === self::CONSENT_FIELD) {
                $field['required_field'] = 'y';
            }
        }
        unset($field);

        $this->setCallback('exportMetadata', fn() => $metadata);

        $consent = $this->createManager()->getInstruments()[self::CONSENT_FORM];

        $this->assertCount(2, $consent->getRequiredFields());
        $this->assertCount(0, $consent->getOptionalFields());
    }

    public function testCacheMissWithNoInstrumentsCachesEmptyArray(): void
    {
        $this->mockCache->method('get')->willReturn([]);

        $this->setCallback('exportInstruments', fn() => []);
        $this->setCallback('exportMetadata', fn() => []);
        $this->setCallback('exportInstrumentEventMappings', fn() => []);

        $this->mockCache->expects($this->once())
            ->method('set')
            ->with($this->cacheKey, []);

        $this->assertSame([], $this->createManager()->getInstruments());
    }

    public function testCacheHitSkipsRedcapExportsAndDoesNotRewriteCache(): void
    {
        $field = $this->createMock(Field::class);
        $field->expects($this->once())->method('setFormName')->with(self::DEMOGRAPHICS_FORM);

        $instrument = $this->createMock(Instrument::class);
        $instrument->method('getName')->willReturn(self::DEMOGRAPHICS_FORM);
        $instrument->method('getRequiredFields')->willReturn([$field]);
        $instrument->method('getOptionalFields')->willReturn([]);

        $this->mockCache->expects($this->once())
            ->method('get')
            ->with($this->cacheKey, [])
            ->willReturn([self::DEMOGRAPHICS_FORM => $instrument]);

        $this->mockRedcapProj->expects($this->never())->method('exportInstruments');
        $this->mockRedcapProj->expects($this->never())->method('exportMetadata');
        $this->mockRedcapProj->expects($this->never())->method('exportInstrumentEventMappings');
        $this->mockCache->expects($this->never())->method('set');

        $this->assertSame(
            [self::DEMOGRAPHICS_FORM => $instrument],
            $this->createManager()->getInstruments()
            );
    }

    public function testCacheHitReinitializesFormNameOnRequiredAndOptionalFields(): void
    {
        $required = $this->createMock(Field::class);
        $required->expects($this->once())->method('setFormName')->with(self::HISTORY_FORM);

        $optionalOne = $this->createMock(Field::class);
        $optionalOne->expects($this->once())->method('setFormName')->with(self::HISTORY_FORM);

        $optionalTwo = $this->createMock(Field::class);
        $optionalTwo->expects($this->once())->method('setFormName')->with(self::HISTORY_FORM);

        $instrument = $this->createMock(Instrument::class);
        $instrument->method('getName')->willReturn(self::HISTORY_FORM);
        $instrument->method('getRequiredFields')->willReturn([$required]);
        $instrument->method('getOptionalFields')->willReturn([$optionalOne, $optionalTwo]);

        $this->mockCache->method('get')->willReturn([self::HISTORY_FORM => $instrument]);

        $this->createManager();
    }

    public function testGetInstrumentsReturnsSameResultOnRepeatedCalls(): void
    {
        $this->mockCache->method('get')->willReturn([]);

        $mgr = $this->createManager();

        $this->assertSame($mgr->getInstruments(), $mgr->getInstruments());
    }
}
