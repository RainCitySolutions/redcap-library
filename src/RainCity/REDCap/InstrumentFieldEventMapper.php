<?php
declare(strict_types = 1);
namespace RainCity\REDCap;

use Psr\SimpleCache\CacheInterface;

/**
 * Constructs a mapping of REDCap events to fields names that are valid for
 * that event.
 * 
 * TODO: Incomplete, only provides a method to check if a field is valid for
 * at least one of the events.
 */
class InstrumentFieldEventMapper
{
    /** @var array<string,array<string>> */
    private array $mapEventToFields = [];

    const INSTRUMENT_FIELD_EVENT_MAP = 'REDCapInstFldEvtMap';

    /**
     * 
     * @param RedCapProject $project 
     * @param CacheInterface $cache 
     */
    public function __construct(
        RedCapProject $project,
        CacheInterface $cache
        )
    {
        $cacheInst = $cache->get(self::INSTRUMENT_FIELD_EVENT_MAP);
        if (isset($cacheInst)) {
            $this->mapEventToFields = $cacheInst->mapEventToFields;
        }
        else {
            $mapFieldToExportFields = [];
            $mapFormToFields = [];
            $mapEventToInstruments = [];

            $exportFieldNames = $project->exportFieldNames();
            if (is_array($exportFieldNames)) {
                $mapFieldToExportFields = $this->loadFieldToExportFieldMap($exportFieldNames);
            }

            $exportMetadata = $project->exportMetadata();
            if (is_array($exportMetadata)) {
                $mapFormToFields = $this->loadFormToFieldsMap($exportMetadata);
            }

            $exportInstrumentEventMappings = $project->exportInstrumentEventMappings();
            if (is_array($exportInstrumentEventMappings)) {
                $mapEventToInstruments = $this->loadEventToInstrumentMap($exportInstrumentEventMappings);
            }

            array_walk($mapEventToInstruments, function ($forms, $event) use ($mapFormToFields, $mapFieldToExportFields) {
                if (!array_key_exists($event, $this->mapEventToFields)) {
                    $this->mapEventToFields[$event] = [];
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

            $cache->set(self::INSTRUMENT_FIELD_EVENT_MAP, $this);
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
        $mapFieldToExportFields = [];

            // walk throughthe array adding each export field name to its form name entry
        array_walk($exportFieldNames, function ($entry, $ndx) use (&$mapFieldToExportFields) { // NOSONAR - ignore $ndx
            if (!array_key_exists($entry['original_field_name'], $mapFieldToExportFields)) {
                $mapFieldToExportFields[$entry['original_field_name']] = [];
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
        $mapFormToFields = [];

        array_walk($exportMetadata, function ($entry, $ndx) use (&$mapFormToFields) { // NOSONAR - ignore $ndx
            if (!array_key_exists($entry['form_name'], $mapFormToFields)) {
                $mapFormToFields[$entry['form_name']] = [];
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
        $mapEventToInstruments = [];

        // walk through the array adding each form to its event entry
        array_walk($exportInstrumentEventMappings, function ($entry, $ndx) use (&$mapEventToInstruments) { // NOSONAR - ignore $ndx
            if (!array_key_exists($entry['unique_event_name'], $mapEventToInstruments)) {
                $mapEventToInstruments[$entry['unique_event_name']] = [];
            }
            $mapEventToInstruments[$entry['unique_event_name']][] = $entry['form'];
        });

        return $mapEventToInstruments;
    }

    /**
     * Check if the field is valid for at least one of the events.
     * 
     * @param string $field A field name
     * 
     * @return bool Returns true if the field is valid for one of the events,
     *      otherwise returns false.
     */
    public function isValidField(string $field): bool
    {
        $result = false;

        foreach($this->mapEventToFields as $eventFieldList) {
            if (in_array($field, $eventFieldList)) {
                $result = true;
                break;
            }
        }

        return $result;
    }

}
