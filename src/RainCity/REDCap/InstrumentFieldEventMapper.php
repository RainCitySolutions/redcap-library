<?php
declare(strict_types = 1);
namespace RainCity\REDCap;

use Psr\SimpleCache\CacheInterface;
use RainCity\DataCache;
use RainCity\Singleton;

class InstrumentFieldEventMapper
    extends Singleton
{
    /** @var array<string, array<string>> */
    private array $mapEventToFields = array();

    const INSTRUMENT_FIELD_EVENT_MAP = 'REDCapInstFldEvtMap';

    /**
     *
     * @param array<mixed> $args
     */
    protected function __construct(array $args)
    {
        if (!empty($args)) {
            $proj = $args[0];

            /** @var CacheInterface */
            $dataCache = DataCache::instance();

            $cacheInst = $dataCache->get(self::INSTRUMENT_FIELD_EVENT_MAP);
            if (isset($cacheInst)) {
                $this->mapEventToFields = $cacheInst->mapEventToFields;
            }
            else {
                $mapFieldToExportFields = array();
                $mapFormToFields = array();
                $mapEventToInstruments = array();

                $exportFieldNames = $proj->exportFieldNames();
                if (is_array($exportFieldNames)) {
                    $mapFieldToExportFields = $this->loadFieldToExportFieldMap($exportFieldNames);
                }

                $exportMetadata = $proj->exportMetadata();
                if (is_array($exportMetadata)) {
                    $mapFormToFields = $this->loadFormToFieldsMap($exportMetadata);
                }

                $exportInstrumentEventMappings = $proj->exportInstrumentEventMappings();
                if (is_array($exportInstrumentEventMappings)) {
                    $mapEventToInstruments = $this->loadEventToInstrumentMap($exportInstrumentEventMappings);
                }

                array_walk($mapEventToInstruments, function ($forms, $event) use ($mapFormToFields, $mapFieldToExportFields) {
                    if (!array_key_exists($event, $this->mapEventToFields)) {
                        $this->mapEventToFields[$event] = array();
                    }
                    foreach ($forms as $form) {
                        if (isset($mapFormToFields[$form])) {
                            $fieldNames = $mapFormToFields[$form];
                            foreach ($fieldNames as $field) {
                                $this->mapEventToFields[$event] = array_merge($this->mapEventToFields[$event], $mapFieldToExportFields[$field]);
                            }
                        }
                    }
                });

                $dataCache->set(self::INSTRUMENT_FIELD_EVENT_MAP, $this);
            }
        }
    }

    /**
     *
     * @param array<array<string, string>> $exportFieldNames
     *
     * @return array<array<string>>
     */
    private function loadFieldToExportFieldMap(array $exportFieldNames): array
    {
        $mapFieldToExportFields = array();

            // walk throughthe array adding each export field name to its form name entry
        array_walk($exportFieldNames, function ($entry, $ndx) use (&$mapFieldToExportFields) { // NOSONAR - ignore $ndx
            if (!array_key_exists($entry['original_field_name'], $mapFieldToExportFields)) {
                $mapFieldToExportFields[$entry['original_field_name']] = array();
            }
            $mapFieldToExportFields[$entry['original_field_name']][] = $entry['export_field_name'];
        });

        return $mapFieldToExportFields;
    }

    /**
     *
     * @param array<array<string, string>> $exportMetadata
     *
     * @return array<array<string>>
     */
    private function loadFormToFieldsMap(array $exportMetadata): array
    {
        $mapFormToFields = array();

        array_walk($exportMetadata, function ($entry, $ndx) use (&$mapFormToFields) { // NOSONAR - ignore $ndx
            if (!array_key_exists($entry['form_name'], $mapFormToFields)) {
                $mapFormToFields[$entry['form_name']] = array();
            }
            $mapFormToFields[$entry['form_name']][] = $entry['field_name'];
        });

        return $mapFormToFields;
    }

    /**
     *
     * @param array<array<string, string>> $exportInstrumentEventMappings
     *
     * @return array<string, array<string>>
     */
    private function loadEventToInstrumentMap(array $exportInstrumentEventMappings): array
    {
        $mapEventToInstruments = array();

        // walk through the array adding each form to its event entry
        array_walk($exportInstrumentEventMappings, function ($entry, $ndx) use (&$mapEventToInstruments) { // NOSONAR - ignore $ndx
            if (!array_key_exists($entry['unique_event_name'], $mapEventToInstruments)) {
                $mapEventToInstruments[$entry['unique_event_name']] = array();
            }
            $mapEventToInstruments[$entry['unique_event_name']][] = $entry['form'];
        });

        return $mapEventToInstruments;
    }

    public function isValidField(string $field): bool
    {
        $result = false;

        foreach($this->mapEventToFields as $eventFieldList) {
            foreach($eventFieldList as $eventField) {
                if ($eventField == $field) {
                    $result = true;
                    break;
                }
            }
        }

        return $result;
    }

}

