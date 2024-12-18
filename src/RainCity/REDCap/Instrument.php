<?php
declare(strict_types = 1);
namespace RainCity\REDCap;

use RainCity\SerializeAsArrayTrait;
use Serializable;

/**
 * Represents an instrument/form in REDCap.
 *
 */
class Instrument implements Serializable
{
    use SerializeAsArrayTrait {
        __serialize as protected traitSerialize;
    }

    /*
     * The following fields are serialized, for storage in the instrument
     * cache as they are generic to all records/users.
     */
    private string $instrumentName;
    private string $instrumentLabel;
    private bool $isCAT = false;
    private bool $hasInputFields = false;
    /** @var Field[] Associative array where the key is the field name */
    private array $requiredFields = array();
    /** @var Field[] Associative array where the key is the field name */
    private array $optionalFields = array();
    /** @var string[] Array of implied field names */
    private array $impliedFields = array();
    /** @var string[] Array of event names */
    private array $events = array();

    /** @var Instrument Next instrument if this instrument is setup for Auto-Continue, otherwise null */
    private ?Instrument $nextInstrument = null;
    /** @var string[] */
    private array $skippedTypes = array();

    /**
     * Construct an Instrument instance using data retreived from REDCap.
     *
     * @param string $instrumentName  The name of the form (from exportInstruments)
     * @param string $instrumentLabel The label for the from (from exportInstruments)
     * @param array<string, array<string, string>> $fieldNames       The field names (from exportFieldNames)
     * @param array<array<string, mixed>> $metadata         The field data (from exportMetadata)
     * @param array<array<string, string>> $eventMappings    The event data (from exportInstrumentEventMapping)
     *      if the instrument is part of a multi-event project.
     */
    public function __construct(
        string $instrumentName,
        string $instrumentLabel,
        array $fieldNames = [],
        array $metadata = [],
        array $eventMappings = []
        )
    {
        $this->instrumentName = $instrumentName;
        $this->instrumentLabel = $instrumentLabel;

        $exportFieldNames = $this->readExportFieldNames($fieldNames);

        $this->readMetadata($metadata, $instrumentName, $exportFieldNames);

        $this->makeSingularCheckboxesOptional();

        $this->impliedFields[] = $instrumentName.'_complete';
        $this->impliedFields[] = $instrumentName.'_timestamp';

        foreach($eventMappings as $entry) {
            if ($instrumentName === $entry['form']) {
                $this->events[] = $entry['unique_event_name'];
            }
        }
    }

    /**
     *
     * @param array<string, array<string, string>> $fieldNames
     *
     * @return array<string, array<string>>
     */
    private function readExportFieldNames(array $fieldNames): array
    {
        $exportFieldNames = array();

        foreach ($fieldNames as $entry) {
            if (!array_key_exists($entry['original_field_name'], $exportFieldNames)) {
                $exportFieldNames[$entry['original_field_name']] = array();
            }
            $exportFieldNames[$entry['original_field_name']][] = $entry['export_field_name'];
        }

        return $exportFieldNames;
    }

    /**
     *
     * @param array<array<string, mixed>> $metadata
     * @param string $instrumentName
     * @param array<string, array<string>> $exportFieldNames
     */
    private function readMetadata(array $metadata, string $instrumentName, array $exportFieldNames): void
    {
        foreach($metadata as $field) {
            if ($instrumentName === $field['form_name'] &&
                // Some fields, such as descriptive, aren't in the exported
                // field list but they are in the metadata
                isset($exportFieldNames[$field['field_name']]) )
            {
                foreach ($exportFieldNames[$field['field_name']] as $exportField) {
                    $field['field_name'] = $exportField;
                    $this->addField(new Field($field));
                }
            }
        }
    }


    /**
     * Look for any singular checkbox fields and make them optional.
     *
     * A singular checkbox is basically a toggle. It is either set or not.
     */
    private function makeSingularCheckboxesOptional(): void
    {
        /** @var array<string, array<Field>> */
        $checkboxFields = array();

        foreach($this->requiredFields as $field) {
            if ("checkbox" == $field->getType()) {
                $fieldname = $field->getCheckboxFieldName();

                if (!isset($checkboxFields[$fieldname])) {
                    $checkboxFields[$fieldname] = [];
                }
                $checkboxFields[$fieldname][] = $field;
            }
        }

        foreach($checkboxFields as $fieldList) {
            if (1 === count($fieldList)) {
                $field = array_pop($fieldList);
                unset ($this->requiredFields[$field->getName()]);
                $this->optionalFields[$field->getName()] = $field;
            }
        }
    }

    public function getName(): string
    {
        return $this->instrumentName;
    }

    public function getLabel(): string
    {
        return $this->instrumentLabel;
    }

    public function isCAT(bool $checkNextInstrument = true): bool
    {
        $isCat = $this->isCAT;

        if (!$isCat && $checkNextInstrument && !is_null($this->nextInstrument)) {
            $isCat = $this->nextInstrument->isCAT($checkNextInstrument);
        }

        return $isCat;
    }

    /**
     * Fetch the list of required fields for the instrument based on the
     * fields allowed for a record.
     *
     * This differs from getRequiredFormFieldNames() in that checkbox fields
     * are seperate fields for each checkbox value, e.g. "race___1",
     * "race___2" whereas for a form there would only be a "race" field.
     *
     * @return string[]
     */
    public function getRequiredRecordFieldNames(): array
    {
        return array_keys($this->requiredFields);
    }

    /**
     * Fetch the list of required fields for the instrument based on the
     * fields that would appear on the form/instrument.
     *
     * This list is potentially a subset of the names returned by
     * getRequiredRecordFieldNames() as checkbox fields are represented by a
     * single fieldname, not a field name for each checkbox option.
     * <p>
     * Additionally, only input fields are returned. Description fields, for
     * example are not included.
     *
     * @return string[]
     */
    public function getRequiredFormFieldNames(): array
    {
        return $this->collapseFieldnames(array_keys($this->requiredFields));
    }

    /**
     *
     * @return Field[]
     */
    public function getRequiredFields(): array
    {
        return $this->requiredFields;
    }

    /**
     * Fetch the list of optional fields for the instrument based on the
     * fields allowed for a record.
     *
     * This differs from getOptionalFormFieldNames() in that checkbox fields
     * are seperate fields for each checkbox value, e.g. "race___1",
     * "race___2" whereas for a form there would only be a "race" field.
     *
     * @return string[]
     */
    public function getOptionalRecordFieldNames(): array
    {
        return array_keys($this->optionalFields);
    }

    /**
     * Fetch the list of optional fields for the instrument based on the
     * fields that would appear on the form/instrument.
     *
     * This list is potentially a subset of the names returned by
     * getOptionalFieldNames() as checkbox fields are represented by a single
     * fieldname, not a field name for each checkbox option.
     * <p>
     * Additionally, only input fields are returned. Description fields, for
     * example are not included.
     *
     * @return string[]
     */
    public function getOptionalFormFieldNames(): array
    {
        return $this->collapseFieldnames(array_keys($this->optionalFields));
    }

    /**
     *
     * @return Field[]
     */
    public function getOptionalFields(): array
    {
        return $this->optionalFields;
    }

    /**
     * @return string[]
     */
    public function getAllFieldNames(): array
    {
        return array_merge($this->getRequiredRecordFieldNames(), $this->getOptionalRecordFieldNames(), $this->impliedFields);
    }

	/**
     *
     * @return Field[]
     */
    public function getAllFields(): array
    {
        return array_merge($this->getRequiredFields(), $this->getOptionalFields());
    }

    public function hasInputs(): bool
    {
        return $this->hasInputFields;
    }

    public function setNextInstrument(Instrument $nextInstrument): void
    {
        $this->nextInstrument = $nextInstrument;
    }

    /**
     * Is there an Auto-Continue instrument?
     *
     * @return boolean Returns true if the instrument has a next instrument, otherwise returns false.
     */
    public function hasNextInstrument(): bool
    {
        return !is_null($this->nextInstrument);
    }

    /**
     * Fetch the next Instrument.
     *
     * @return ?Instrument The next instrument in the Auto-Continue sequence if there is one.
     *      Otherwise returns null.
     */
    public function getNextInstrument(): ?Instrument
    {
        return $this->nextInstrument;
    }

    public function addField(Field $field): void
    {
        if ($field->isCAT()) {
            $this->isCAT = true;
        }

        $fieldName = $field->getName();
        $fieldType = $field->getType();

        // Only keep fields of a certain type, that don't have branching logic and aren't 'Optional'
        if (in_array($fieldType, Field::REQUIRED_FIELD_TYPES) ) {
            if ($field->isRequired())
            {
                $this->requiredFields[$fieldName] = $field;
            }
            else {
                $this->optionalFields[$fieldName] = $field;
            }
            $this->hasInputFields = true;
        }
        else {
            $this->optionalFields[$fieldName] = $field;

            if (!in_array($fieldType, Field::NONINPUT_FIELD_TYPES)) {
                $this->hasInputFields = true;
            }

            if (!in_array($fieldType, Field::IGNORED_FIELD_TYPES) &&
                !in_array($fieldType, $this->skippedTypes) )
            {
                $this->skippedTypes[] = $fieldType;
            }
        }
    }

    /**
     *
     * @return string[]
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     *
     * @param string[] $fieldnames
     *
     * @return string[]
     */
    private function collapseFieldnames(array $fieldnames): array
    {
        $result = array();

        foreach ($fieldnames as $fieldname) {
            $matches = array();
            // is this a checkbox field?
            if (preg_match ('/^(.*)___\d+$/', $fieldname, $matches)) {
                $fieldname = $matches[1];

                if (!in_array($fieldname, $result)) {
                    $result[] = $fieldname;
                }
            }
            else {
                $result[] = $fieldname;
            }
        }

        return $result;
    }

    /**
     *
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        $vars = $this->traitSerialize();

        // Don't persist these vars
        unset($vars['nextInstrument']);
        unset($vars['skippedTypes']);

        return $vars;
    }
}
