<?php
declare(strict_types = 1);
namespace RainCity\REDCap;

use IU\PHPCap\RedCapProject;
use Psr\Log\LoggerInterface;

/**
 * Represents a record in the REDCap system for a single participant.
 *
 * The record may have data for multiple events, each within its own set of
 * field values.
 */
class Record
{
    const REDCAP_EVENT_NAME = 'redcap_event_name';  // name of the record array field to indicate the event for a record
    const REDCAP_SURVEY_IDENTIFIER = 'redcap_survey_identifier';
    const REDCAP_TIMESTAMP_FORMAT = 'Y-m-d H:i:s';

    /** @var bool Is the project longitudinal */
    private bool $isLongitudinal = false;

    /** @var string[] List of valid fields within REDCap */
    private array $validFields = array();

    /** @var array<mixed> List of valid instruments within REDCap */
    private array $validInstruments = array();

    /** @var string[] List of valid events within REDCap */
    private array $validEvents = array();

    /** @var array<string, true> Lookup set of the valid field names (name => true) */
    private array $validFieldSet = array();

    /** @var array<string, true> Lookup set of the valid event names (name => true) */
    private array $validEventSet = array();

    /** @var bool Cached result of projectUsesEvents(); constant after construction */
    private bool $usesEvents = false;

    /** @var string[] List of REDCap fields to be tracked by the record */
    private array $redcapFields = array();

    /** @var string[] List of REDCap instruments to specify when loading the record */
    private array $redcapInstruments = array();

    /** @var string[] List of REDCap events referenced by the record */
    private array $redcapEvents = array();

    /** @var string The name of the Record ID field */
    private string $recordIdField;

    /** @var string The identifier for the record */
    private ?string $recordId = null;

    /** @var array<string, mixed>|array<string, array<string, mixed>>
     *      If the project uses events then this is a multi-dimensional
     *      associative array of REDCap events to array of REDCap field names
     *      to values.
     *      Otherwise, this is an associative array of REDCap field names to values.
     */
    private array $fieldArray = array();

    /** @var array<string, bool>|array<string, array<string, bool>>
     *      A multi-dimensional associative array of REDCap events to array of
     *      REDCap fields that have been modified since the last save. If the
     *      project does not use events this is a single-dimentional array.
     *      <p>
     *      The key is the field name, the value should be set to true.
     */
    private array $dirtyFieldArray = array();

    /**
     * Constructs a REDCap Record object.
     *
     * If a record id is provided the record will be loaded from REDCap.
     *
     * @param RedCapProject $project A RedCapProject object to use with this record.
     * @param LoggerInterface $logger The logger to use
     * @param string[] $fields A list of field names to be retrieved/stored for the record.
     *      If specified as an empty array, all of the fields will be used.
     * @param string|int|null $recordId The ID of a record to load. (optional)
     * @param string[] $instruments An array of instrument names used in retrieving the record.
     * @param string[] $events An array of event names to use in retrieving/storing the record.
     *
     * @throws \TypeError Thrown if an entry in the fields array is not a string.
     */
    public function __construct(
        protected readonly RedCapProject $project,
        protected readonly LoggerInterface $logger,
        array $fields = array(),
        string|int|null $recordId = null,
        array $instruments = array(),
        array $events = array()
        )
    {
        $this->recordIdField = $project->getRecordIdFieldName();

        $projInfo = $project->exportProjectInfo();
        $this->isLongitudinal = boolval($projInfo['is_longitudinal'] ?? false);

        $exportFieldNames = $project->exportFieldNames();
        if (is_array($exportFieldNames)) {
            foreach($exportFieldNames as $entry) {
                $this->validFields[] = $entry['export_field_name'];
            }
        }
        // Include the event name field in the valid fields as REDCap will not return it as a field name.
        $this->validFields[] = self::REDCAP_EVENT_NAME;

        // Add the timestamp field for each instrument, which isn't returned by exportFieldNames
        $exportInstruments = $project->exportInstruments();
        if (is_array($exportInstruments)) {
            foreach(array_keys($exportInstruments) as $name) {
                $this->validInstruments[] = $name;
                $this->validFields[] = $name.'_timestamp';
            }
        }

        $exportEvents = $project->exportEvents();

        foreach ($exportEvents as $event) {
            $this->validEvents[] = $event['unique_event_name'];
        }

        // Hash lookups (isset) replace repeated in_array() scans on the hot load path.
        $this->validFieldSet = array_fill_keys($this->validFields, true);
        $this->validEventSet = array_fill_keys($this->validEvents, true);
        $this->usesEvents = $this->isLongitudinal && !empty($this->validEvents);

        $this->setFields($fields);
        $this->setInstruments($instruments);
        $this->setEvents($events);

        if (isset($recordId)) {
            $this->loadRecordById($recordId);
        }
    }

    /**
     * Load a specific record from REDCap into the object.
     *
     * @param string|int $recordId A record ID within REDCap.
     *
     * @return bool Returns true if the record was found in REDCap, otherwise
     *      returns false.
     */
    public function loadRecordById (string|int $recordId): bool
    {
        $result = false;

        $this->recordId = strval($recordId);
        $this->fieldArray = array();

        /** @var string[] */
        $fields = $this->redcapFields;

        /*
         * If the record id field isn't already in the list of fields, add it
         * so that the record id and event name are always included in the
         * record returned.
         */
        if (!in_array($this->recordIdField, $fields)) {
            $fields[] = $this->recordIdField;
        }

        $rcd = $this->project->exportRecords(
            'php',
            'flat',
            array($recordId),
            $fields,
            empty($this->redcapInstruments) ? null : $this->redcapInstruments,
            (!$this->projectUsesEvents() || empty($this->redcapEvents)) ? null : $this->redcapEvents,
            null,       // filterLogic
            'raw',      // rawOrLabel;
            'raw',      // rawOrLabelHeaders
            false,      // exportCheckboxLabel
            true        // exportSurveyFields   - Always include survey fields
            );
        if (!empty($rcd)) {
            $this->loadRecord($rcd);
            $result = true;
        }

        // Clear the dirty flags at the end.
        // Whether we loaded a record or not everything is in a clean state.
        $this->dirtyFieldArray = array();

        return $result;
    }

    /**
     * Load data from REDCap into the object.
     *
     * <p>
     * If the record is for a classic (non-event) project, it is expected to
     * be an array containing a single associative array of field names to
     * values.
     * <p>
     * If the record is for a multi-event project, it is expected to be an
     * array containing multiple associative arrays, one for each event in
     * the project or events set on the object if only a subset of events
     * is being manipulated.
     * <p>
     * If the value is empty, or not a string or integer, the element will
     * be ignored.
     * <p>
     * If the project uses events but an invalid event is specified the first
     * event in the project will be used.
     *
     * @param array<string, mixed> $rcd An array of associative arrays of
     *      strings to values. The format is expected to be that returned by
     *      REDCap on a exportRecords request and contain data for a single
     *      REDCap record.
     *
     * @return bool Returns true if the record was loaded, otherwise returns false.
     */
    public function loadRecord (array $rcd): bool
    {
        if (empty($rcd) || !$this->validateRecord($rcd)) {
            return false;
        }

        $this->importValidatedRecord($rcd);

        return true;
    }

    /**
     * Load a record that has already passed validateRecord().
     *
     * Split from loadRecord() so that subclasses which chain records (see
     * InstrumentRecord) can validate the incoming data once and not once per
     * link in the chain.
     *
     * @param array<string, mixed>|array<string, array<string, mixed>> $rcd A validated REDCap record.
     *
     * @throws \InvalidArgumentException Thrown if there is an issue loading the record.
     */
    protected function importValidatedRecord (array $rcd): void
    {
        if (!$this->usesEvents) {
            $this->privLoadRecord($rcd[array_key_first($rcd)]);
            return;
        }

        foreach ($rcd as $eventRcd) {
            $event = (string)$eventRcd[self::REDCAP_EVENT_NAME];

            if (!isset($this->validEventSet[$event])) {
                throw new \InvalidArgumentException ('Record references an event that is not valid');
            }

            $this->privLoadRecord($eventRcd, $event);
        }
    }

    /**
     * Hook allowing subclasses to restrict which fields get stored while loading.
     *
     * The field is already known to be valid for the project and is neither
     * the record id nor the event name.
     */
    protected function isLoadableField (string $field): bool
    {
        return true;
    }

    public function isLoaded(): bool
    {
        return !empty($this->fieldArray);
    }

    /**
     * Validate the struture and content of the record.
     *
     * Records are expected to be an array of arrays where each inner array
     * is an associative array of strings to strings or integers.
     * <p>
     * Further, we expect that each inner entry will include the record id
     * and event name fields.
     *
     * @param array<string, mixed>|array<string, array<string, mixed>> $rcd The record to validate.
     *
     * @return bool Returns true if the record is valid, otherwise returns false.
     */
    private function validateRecord (array $rcd): bool
    {
        // If the record is empty, don't do anything.
        if (empty($rcd)) {
            return true;
        }

        if ($this->usesEvents ? count($this->validEvents) < count($rcd) : 1 !== count($rcd)) {
            $this->logger->warning('Found issue with REDCap record. ' .
                ($this->usesEvents ? 'More entries in record than valid events' : 'More than one entry in record'));
            return false;
        }

        $rcdId = null;

        foreach ($rcd as $ndx => $entry) {
            // Each top level entry must be an array with a numeric index.
            if (!is_numeric($ndx) || !is_array($entry)) {
                $this->logger->warning('Found issue with REDCap record. Expected an array of arrays with numeric indecies.');
                return false;
            }

            // Keys must be field names, values strings or numbers, and any
            // event name must be valid for the project.
            foreach ($entry as $field => $value) {
                if (!is_string($field) || !(is_string($value) || is_numeric($value))) {
                    $this->logger->warning('Found issue with REDCap record. An inner array isn\'t and associatve entry if field name to either a string or numeric value.');
                    return false;
                }

                if (self::REDCAP_EVENT_NAME === $field && !(is_string($value) && isset($this->validEventSet[$value]))) {
                    $this->logger->warning('Found invalid event in REDCap record: {event}', array ('event' => $value));
                    return false;
                }
            }

            // Every entry must carry the record id (and the event name for
            // event projects), and all entries must be for the same record.
            if (!array_key_exists($this->recordIdField, $entry)) {
                $this->logger->warning('Found issue with REDCap record. Record ID field not found in one of the entries.');
                return false;
            }

            if ($rcdId === null) {
                $rcdId = $entry[$this->recordIdField];
            }
            elseif ($entry[$this->recordIdField] !== $rcdId) {
                $this->logger->warning('Found issue with REDCap record. More than one record present.');
                return false;
            }

            if ($this->usesEvents && !array_key_exists(self::REDCAP_EVENT_NAME, $entry)) {
                $this->logger->warning('Found issue with REDCap record. Event name not found in one of the entries.');
                return false;
            }
        }

        return true;
    }

    /**
     * Loads an associative array into the object.
     *
     * Assumes the $rcd is a single record object to be loaded for the
     * specified event.
     *
     * @param array<string, mixed> $rcd An associative array of strings to values.
     * @param string|null $event A valid event name or null
     */
    private function privLoadRecord (array $rcd, ?string $event = null): void
    {
        if ($this->usesEvents) {
            if ($event === null || !isset($this->validEventSet[$event])) {
                $event = $this->validEvents[0];
            }

            // If its not already in the list, add the event
            if (!in_array($event, $this->redcapEvents)) {
                $this->redcapEvents[] = $event;
            }
        }

        // One O(n) set build per load replaces an in_array() scan per field.
        $trackedFields = array_flip($this->redcapFields);
        $loaded = array();

        foreach ($rcd as $field => $value) {
            if (!isset($this->validFieldSet[$field]) || !(is_string($value) || is_int($value))) {
                continue;
            }

            if ($field === $this->recordIdField) {
                if (isset($this->recordId)) {
                    if ($value != $this->recordId) {
                        throw new \InvalidArgumentException("Attempt to load more than one record into Record instance");
                    }
                }
                else {
                    $this->recordId = strval($value);
                }
                continue;
            }

            // The event name field is only used to select the event.
            if ($field === self::REDCAP_EVENT_NAME || !$this->isLoadableField($field)) {
                continue;
            }

            $loaded[$field] = (string)$value;

            if (!isset($trackedFields[$field])) {
                $trackedFields[$field] = true;
                $this->redcapFields[] = $field;
            }
        }

        if (empty($loaded)) {
            return;
        }

        // Store everything in one pass, marking the fields dirty as
        // setFieldValue()/storeField() always did.
        $dirty = array_fill_keys(array_keys($loaded), true);

        if ($this->usesEvents) {
            $this->fieldArray[$event] = array_replace($this->fieldArray[$event] ?? array(), $loaded);
            $this->dirtyFieldArray[$event] = array_replace($this->dirtyFieldArray[$event] ?? array(), $dirty);
        }
        else {
            $this->fieldArray = array_replace($this->fieldArray, $loaded);
            $this->dirtyFieldArray = array_replace($this->dirtyFieldArray, $dirty);
        }
    }

    /**
     * Fetch the fields being used by the object.
     * If the set of fields has not previously been set then only the record
     * id field is returned.
     *
     * @return string[] An array of field names.
     */
    public function getFields(): array
    {
        return array_merge(array ($this->recordIdField), $this->redcapFields);
    }

    /**
     * Set the list of fields to be retrieved/stored for the record.
     *
     * @param string[] $fields A list of field names. If specified as an empty
     *      array, all of the fields will be used.
     */
    public function setFields(array $fields): void
    {
        $this->redcapFields = array();

        foreach ($fields as $field) {
            if (in_array($field, $this->validFields)) {
                $this->redcapFields[] = $field;
            }
        }
    }

    /**
     * Set the list of instrument fields to be retrieved for the record.
     *
     * In an instrument is specified that is not value for the project it
     * will be ignored.
     *
     * @param string[] $instruments A list of instrument names.
     */
    public function setInstruments(array $instruments): void
    {
        $this->redcapInstruments = array();

        foreach ($instruments as $instrument) {
            if (in_array($instrument, $this->validInstruments)) {
                $this->redcapInstruments[] = $instrument;
            }
        }
    }

    /**
     * Set the list of events to be used in retrieving the record.
     *
     * If an event is specifed that is not valid for the project it will be
     * ignored.
     *
     * @param string[] $events A list of fully qualified event names. Fully
     *      qualified is the event name including the arm name.
     */
    public function setEvents(array $events): void
    {
        $this->redcapEvents = array();

        foreach ($events as $event) {
            if (in_array($event, $this->validEvents)) {
                $this->redcapEvents[] = $event;
            }
        }
    }


    /**
     * Gets the list of events being used by the record.
     *
     * @return string[] An array of event names.
     */
    public function getEvents(): array
    {
        return $this->redcapEvents;
    }

    /**
     * Add fields to the list of fields to be retreived/stored for the record.
     *
     * @param string[] $newFields A list of field names. If specified as an empty
     *      array, all of the fields will be used.
     */
    public function addFields(array $newFields): void
    {
        foreach ($newFields as $field) {
            if (in_array($field, $this->validFields) && !in_array($field, $this->redcapFields)) {
                $this->redcapFields[] = $field;
            }
        }
    }

    /**
     * Removes fields from the list of fields to be retrieved/stored for the record.
     *
     * @param string[] $oldFields A list of field names to be removed.
     */
    public function removeFields(array $oldFields): void
    {
        foreach ($oldFields as $field) {
            $ndx = array_search($field, $this->redcapFields);
            if ($ndx !== false) {
                unset($this->redcapFields[$ndx]);
            }
        }
    }

    /**
     * Set the record id for the record.
     *
     * @param string $rcdId A string to use as the record id.
     */
    public function setRecordId(string $rcdId): void
    {
        $this->recordId = $rcdId;
    }

    /**
     * Returns the record id for the record.
     *
     * If no record is loaded or the record has yet to be saved null is returned.
     *
     * @return string|NULL The record id, or null if there is no record id.
     */
    public function getRecordId(): ?string
    {
        return $this->recordId;
    }

    /**
     * Fetch the value for a field.
     *
     * If the project uses events and no event is specified, the value from
     * the first event in the list of events will be returned.
     * <p>
     * If the project does not use events the $event parameter is ignored.
     *
     * @param string $field The name of a field.
     * @param string $event The event to fetch the value for.
     *
     * @return string|NULL The value for the field or null if there is no value.
     */
    public function getFieldValue(string $field, ?string $event = null): ?string
    {
        $result = null;

        if ($this->isValidField($field) && $this->isValidEvent($event) ) {
            $result = $this->fetchField($field, $event);
        }

        return $result;
    }


    /**
     * Fetch the most recent value for a field.
     *
     * "Most recent" being defined as a value that is set and not blank for
     * the latest event for the record.
     *
     * @param string $field The name of a field.
     *
     * @return string|NULL The most recent value for the field or null if
     *      there is no value set for the field or the field specified is Not
     *      valid.
     */
    public function getMostRecentFieldValue(string $field): ?string
    {
        $result = null;

        if ($this->isValidField($field)) {
            if ($this->projectUsesEvents()) {
                foreach ($this->redcapEvents as $event) {
                    $value = $this->fetchField($field, $event);
                    if (isset($value) && '' !== $value) {
                        $result = $value;
                    }
                }
            }
            else {
                $value = $this->fetchField($field, null);
                if (isset($value) && '' !== $value) {
                    $result = $value;
                }
            }
        }

        return $result;
    }

    /**
     * Fetch the value for a field from the field array using the specified event.
     *
     * If the project uses events but no event is specified the value from
     * the first event is returned, if present.
     * <p>
     * If the project does not use events the $event parameter is ignored.
     *
     * @param string $field The name of the field
     * @param string $event The name of an event, or null
     *
     * @return string|NULL Returns the value for the specified field/event pair or null.
     */
    private function fetchField(string $field, ?string $event): ?string
    {
        $result = null;

        if ($this->projectUsesEvents()) {
            if (is_null($event)) {
                if (empty($this->redcapEvents)) {
                    $event = $this->validEvents[0];
                }
                else {
                    $event = $this->redcapEvents[0];
                }
            }
            $result = $this->fieldArray[$event][$field] ?? null;
        }
        else {
            $result = $this->fieldArray[$field] ?? null;
        }

        return $result;
    }


    /**
     * Sets the value for a field.
     *
     * If the field or event specified is not valid for the project the value
     * will be ignored.
     * <p>
     * If the project uses events but no event is specified, the value will
     * be associated with the first event in the project.
     *
     * @param string $field The name of the field.
     * @param string $value The value to be associated with the field.
     * @param string $event The event the field value is associated with. Defaults to null.
     */
    public function setFieldValue(string $field, string $value, string $event = null): void
    {
        if ($this->isValidField($field) && $this->isValidEvent($event) ) {
            if (!in_array($field, $this->redcapFields)) {
                $this->redcapFields[] = $field;
            }

            $this->storeField($field, $value, $event);
        }
    }

    /**
     * Stores the field value in the field array.
     *
     * If the project does not use events the field array is used as an
     * associative array with the keys being the field name and the value
     * being the field value.
     * <p>
     * If the project does use events the field array is an associative
     * array of event names to an associative array of field names to values.
     * <p>
     * If the project uses events but no event is specified, the value is
     * associated with the first event for the project.
     * <p>
     * Note: This method assumes that the field and event provided are valid
     * for the project.
     *
     * @param string $field The name of a field
     * @param string $value The value to be associated with the field
     * @param string $event The name of an event or null
     */
    private function storeField(string $field, string $value, ?string $event): void
    {
        if ($this->projectUsesEvents()) {
            if (is_null($event)) {
                if (empty($this->redcapEvents)) {
                    $event = $this->validEvents[0];
                }
                else {
                    $event = $this->redcapEvents[0];
                }
            }
            $this->fieldArray[$event][$field] = $value;
            $this->dirtyFieldArray[$event][$field] = true;
        }
        else {
            $this->fieldArray[$field] = $value;
            $this->dirtyFieldArray[$field] = true;
        }
    }



    /**
     * Fetch an associative array of field values as REDCap would return them.
     *
     * If the project uses events and an event is specified, the array
     * returned will only contain the field values for that event and the
     * the array will have field names as keys.
     * <P>
     * If the project uses events but no event is specified, the array
     * returned will be an array of arrays where the outer array is keyed
     * by event name and each inner array is keyed by field name.
     * <P>
     * If the project does not use events and an event name is specified it
     * will be ignored. An array keyed by field name will be returned.
     *
     * @param string|null $event The name of an event. Defaults to null.
     *
     * @return array<mixed> An array of field names to values or
     *      events to arrays of field name to value.
     */
    public function getREDCapArray(?string $event = null): array
    {
        $methodLogger = new \RCS\Logging\MethodLogger($this->logger);

        $result = [];

        if ($this->isValidEvent($event)) {
            $base = [$this->recordIdField => $this->recordId];

            if ($this->projectUsesEvents()) {
                // Placeholder keeps key order: record id, event name, then fields
                $base[Record::REDCAP_EVENT_NAME] = null;

                $events = $event === null
                    ? $this->fieldArray
                    : array_intersect_key($this->fieldArray, [$event => true]);

                foreach ($events as $eventName => $fields) {
                    $result[] = [...$base, Record::REDCAP_EVENT_NAME => $eventName, ...$fields];
                }
            } else {
                $result[] = [...$base, ...$this->fieldArray];
            }
        }

        return $result;
    }


    /**
     * Collapes a set of checkbox fields into a single field.
     *
     * When REDCap returns a record with a checkbox field, each option is
     * represented as its own field (e.g. fielda___1, fielda___2,
     * fielda___888) each with a value of "0" or "1".
     *
     * This method collapes these fields into a single field with the value
     * set to the sum of the option values (e.g. fielda => 2).
     *
     * @param string[] $fieldsOfInterest An array of field names of interest.
     * @param string $event The event to examine. Ignored on classic projects.
     *
     * @return array<string, mixed> An associative array representing the collapsed record.
     */
    protected function collapeCheckboxFields(array $fieldsOfInterest, string $event = null): array
    {
        $collapsedRecord = array();

        if ($this->projectUsesEvents()) {
            $this->validateEvent($event, $this->fieldArray);

            if (is_null($event)) {
                if (empty($this->redcapEvents)) {
                    $event = $this->validEvents[0];
                }
                else {
                    $event = $this->redcapEvents[0];
                }

                $fieldsToExamine = $this->fieldArray[$event] ?? array();
            }
            else {
                $fieldsToExamine = $this->isValidEvent($event) && isset($this->fieldArray[$event]) ? $this->fieldArray[$event] : array();
            }
        }
        else {
            $fieldsToExamine = $this->fieldArray;
        }

        foreach ($fieldsToExamine as $fieldName => $value) {
            $matches = array();
            // is this a checkbox field?
            if (preg_match ('/^(.*)___\d+$/', $fieldName, $matches)) {
                $fieldName = $matches[1];

                if (in_array($fieldName, $fieldsOfInterest)) {
                    if (!isset($collapsedRecord[$fieldName])) {
                        $collapsedRecord[$fieldName] = 0;
                    }

                    $collapsedRecord[$fieldName] += $value;
                }
            }
            else {
                if (in_array($fieldName, $fieldsOfInterest)) {
                    $collapsedRecord[$fieldName] = $value;
                }
            }
        }

        // Add the record id field if its of interest
        if (in_array($this->recordIdField, $fieldsOfInterest)) {
            $collapsedRecord[$this->recordIdField] = $this->recordId;
        }

        return $collapsedRecord;
    }

    /**
     * Save record to REDCap.
     *
     * Saves any fields marked as dirty to the REDCap project.
     */
    public function save (): void
    {
        // If we don't have a record id, fetch one
        if (!isset($this->recordId)) {
            $this->recordId = $this->project->generateNextRecordName();
        }

        $fieldRcds = array();

        // copy all the dirty fields into the array
        if ($this->projectUsesEvents()) {
            foreach($this->dirtyFieldArray as $eventKey => $fields) {
                $eventRcd = array();
                $eventRcd[$this->recordIdField] = $this->recordId; // always include the record id
                $eventRcd[self::REDCAP_EVENT_NAME] = $eventKey;

                foreach(array_keys($fields) as $fieldName) {
                    $eventRcd[$fieldName] = $this->fieldArray[$eventKey][$fieldName];
                }

                $fieldRcds[] = $eventRcd;
            }

            if (empty($fieldRcds)) {
                // Creating a new record so just send the record id
                $fieldRcds[] = array($this->recordIdField => $this->recordId);
            }
        }
        else {
            $fieldRcd = array();
            $fieldRcd[$this->recordIdField] = $this->recordId; // always include the record id

            foreach(array_keys($this->dirtyFieldArray) as $fieldName) {
                $fieldRcd[$fieldName] = $this->fieldArray[$fieldName];
            }
            $fieldRcds[] = $fieldRcd;
        }

        $this->project->importRecords($fieldRcds);

        $this->dirtyFieldArray = array();	// assume nothing is dirty
    }

    /**
     * Test if project uses events.
     *
     * @return bool Returns true if the project uses events, otherwise returns false.
     */
    public function projectUsesEvents(): bool
    {
        return $this->usesEvents;
    }


    /**
     * Check if the field specified is valid.
     *
     * @param string $field The name of a field
     *
     * @return bool Returns true if the project uses the specified Field
     *      otherwise returns false.
     */
    private function isValidField(string $field): bool {
        return isset($this->validFieldSet[$field]);
    }

    /**
     * Check if the event specified is valid.
     *
     * If a project uses events but the event is null it is still considered
     * to be valid as the code elsewhere in this class will handle it.
     *
     * @param string|null $event The name of a valid event for the project or
     *      null.
     *
     * @return bool Returns true if the project does not use events or the
     *      event specified is valid for the project. Otherwise returns false.
     */
    private function isValidEvent(?string $event): bool {
        return !$this->usesEvents || $event === null || isset($this->validEventSet[$event]);
    }

    protected function getRecordIdFieldName(): string
    {
        return $this->recordIdField;
    }

    /**
     * Check if an array is in a format consistent with what would be
     * returned by REDCap.
     *
     * To be valid each entry in the array must be a non-associative array
     * containing an associative array of strings to numbers or strings.
     * <p>
     * <code>
     * [<br>
     *   {
     *    'key1' => 'a string',
     *    'key2' => 3
     *   },<br>
     *   {
     *    'key1' => 'another string',
     *    'key2' => 99
     *   },<br>
     * ]
     * </code>
     *
     * @param array<mixed, mixed> $rcd And array to test for REDCap conformance.
     *
     * @return bool Returns true if the entry is valid, othewise ReturnSelf
     *      false.
     */
//     private function isValidRedcapStructure(array $rcd): bool
//     {
//         $isValid = true;

//         foreach($rcd as $ndx => $entry) {
//             if (!is_numeric($ndx) || !is_array($entry) || !$this->isValidRedcapEntry($entry)) {
//                 $isValid = false;
//                 break;
//             }
//         }

//         return $isValid;
//     }

    /**
     * Checks if an inner entry of a REDCap record looks valid.
     *
     * To be valid each of the keys must be a string and values must be
     * either a string or a number.
     *
     * @param array<string, mixed> $entry An array to test for REDCap conformance.
     *
     * @return bool Returns true if the entry is valid, otherwise returns
     *      false.
     */
//     private function isValidRedcapEntry(array $entry): bool
//     {
//         $isValid = true;

//         foreach ($entry as $key => $value) {
//             if (!is_string($key) || !(is_string($value) || is_numeric($value)) ) {
//                 $isValid = false;
//                 break;
//             }
//         }

//         return $isValid;
//     }


    /**
     *
     * @param string $event
     * @param array<string, mixed>|array<string, array<string, mixed>> $checkArray
     */
    protected function validateEvent(?string $event, array $checkArray = null): void
    {
        if (is_null($event) || !in_array($event, $this->getEvents()) ) {
            throw new \InvalidArgumentException('A valid event must be specified for multi-event projects');
        }
        else {
            if (isset($checkArray) && !isset($checkArray[$event]) && false) { /* @phpstan-ignore booleanAnd.rightAlwaysFalse */
                throw new \InvalidArgumentException('The event is not being tracked in this record');
            }
        }
    }
}
