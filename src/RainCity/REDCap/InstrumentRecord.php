<?php
declare(strict_types = 1);
namespace RainCity\REDCap;

use IU\PHPCap\RedCapProject;

/**
 * An extension of the Record class that retains data for a particular
 * instrument.
 *
 * If an instrument has a next instrument, an InstrumentRecord
 * will be created and attached as the nextInstrument.
 */
class InstrumentRecord extends Record
{
    private Instrument $instrument;

    private ?InstrumentRecord $nextInstrumentRcd = null;

    /**
     * @var SurveyStatus|SurveyStatus[]
     *  A SurveyStatus object if the instrument is part of a classic project.
     *  Otherwise an associative array of SurveyStatus objects keyed by event
     *  name.
     */
    private mixed $status = array();

    /**
     * Initialize instance.
     *
     * @param RedCapProject $proj The a REDCapProject instance to use in communicating with REDCap.
     * @param Instrument $instrument An initialized instance of Instrument.
     * @param string $recordId The id of an existing REDCap record to load for this instrument.
     */
    public function __construct(RedCapProject $proj, Instrument $instrument, string $recordId = null)
    {
        $this->instrument = $instrument;

        if ($instrument->hasNextInstrument()) {
            $this->nextInstrumentRcd = new InstrumentRecord($proj, $instrument->getNextInstrument(), $recordId);
        }

        parent::__construct($proj, $instrument->getAllFieldNames(), $recordId, array($instrument->getName()), $instrument->getEvents());

        $this->initSurveyStatus();
    }

    /**
     * Fetch the associated Instrument
     *
     * @return Instrument The Instrument instance provided at instantiation.
     */
    public function getInstrument(): Instrument
    {
        return $this->instrument;
    }

    /**
     * Fetch the next InstrumentRecord if there is one.
     *
     * @return InstrumentRecord|null The next InstrumentRecord in the chain
     *      or null if there is no next record.
     */
    public function getNextInstrumentRecord(): ?InstrumentRecord
    {
        return $this->nextInstrumentRcd;
    }

    /**
     * Fetch the SurveyStatus for the record.
     *
     *
     *
     * @param string|null $event An event to retrieve the status for. Ignored
     *      for classic projects.
     *
     * @return SurveyStatus An instance of SurveyStatus.
     *
     * @throws \InvalidArgumentException Only throw for multi-event projects
     *      when no event is provided, the event is not associated with the
     *      instrument or the record is not tracking the event.
     */
    public function getStatus(string $event = null): SurveyStatus
    {
        $surveyStatus = null;

        if ($this->projectUsesEvents()) {
            $this->validateEvent($event, $this->status);

            $surveyStatus = $this->status[$event];
        }
        else {
            $surveyStatus = $this->status;
        }

        return $surveyStatus;
    }

    /**
     * Determine if this record or any decendent records are editable.
     *
     * @param string|null $event The name of an event to check. (optional)<br>Ignored for
     *      classic projects.
     *
     * @return bool Returns true if any record in the chain is editable.
     *      Otherwiser returns false.
     *
     * @throws \InvalidArgumentException Only throw for multi-event projects
     *      when no event is provided, the event is not associated with the
     *      instrument or the record is not tracking the event.
     */
    public function canEdit(?string $event = null): bool
    {
        $canEdit = false;

        if ($this->projectUsesEvents()) {
            $this->validateEvent($event, $this->status);

            $canEdit = $this->status[$event]->canEdit();
        }
        else {
            $canEdit = $this->status->canEdit();
        }

        if (!$canEdit && $this->nextInstrumentRcd) {
            $canEdit = $this->nextInstrumentRcd->canEdit($event);
        }

        return $canEdit;
    }

    /**
     * Check if the instrument, and optionally any child instruments have
     * been started yet.
     *
     * @param bool $checkNextInstrument Whether to check child instruments as
     *      well.
     * @param string|null $event The name of an event to check. (optional)<br>Ignored for
     *      classic projects.
     *
     * @return bool Returns true if the instrument or any child instruments
     *      have not been started. Otherwise returns false.
     *
     * @throws \InvalidArgumentException Only throw for multi-event projects
     *      when no event is provided, the event is not associated with the
     *      instrument or the record is not tracking the event.
     */
    public function notStarted(bool $checkNextInstrument = true, ?string $event = null): bool
    {
        $notStarted = false;

        if ($this->projectUsesEvents()) {
            $this->validateEvent($event, $this->status);

            $notStarted = $this->status[$event]->notStarted();
        }
        else {
            $notStarted = $this->status->notStarted();
        }

        // If the parent says it hasn't been stated, check the children as
        // there can be a case where the parent has no fields thus will always
        // be seen as "notStarted" but in fact they children may have been
        // started.
        if ($notStarted && $this->nextInstrumentRcd && $checkNextInstrument) {
            $notStarted = $this->nextInstrumentRcd->notStarted($checkNextInstrument, $event);
        }

        return $notStarted;
    }

    /**
     * Check if the instrument is complete, and optional if the child
     * instruments are complete.
     *
     * @param bool $checkNextInstrument Whether to check the child instruments.
     * @param string|null $event The name of an event to check. (optional)<br>Ignored for
     *      classic projects.
     *
     * @return bool Returns true if the instrument is complete and any child
     *      instruments are complete. If any of the instruments or not complete
     *      or the instrument status has not been determined, false is returned.
     *
     * @throws \InvalidArgumentException Only throw for multi-event projects
     *      when no event is provided, the event is not associated with the
     *      instrument or the record is not tracking the event.
     */
    public function isComplete(bool $checkNextInstrument = true, ?string $event = null): bool
    {
        $isComplete = false;

        if ($this->projectUsesEvents()) {
            $this->validateEvent($event, $this->status);

            $isComplete = $this->status[$event]->isComplete();
        }
        else {
            $isComplete = $this->status->isComplete();
        }

        if ($isComplete && $this->nextInstrumentRcd && $checkNextInstrument) {
            $isComplete = $this->nextInstrumentRcd->isComplete($checkNextInstrument, $event);
        }

        return $isComplete;
    }

/*
 * TODO: check if this function is necessary, and if so how should it work.
     public function isCATComplete(bool $checkChildren = true) {
        $isCat = $this->instrument->isCAT(false);

        if (!$isCat && $checkChildren) {
            $catChildren = 0;
            $catChildrenComplete = 0;

            foreach ($this->instrument->getChildren() as $child) {
                if ($child->isCAT()) {
                    $catChildren++;
                    $childInstRcd = new InstrumentRecord($this->redcapProj, $child);
                    if ($childInstRcd->isComplete()) {
                        $catChildrenComplete++;
                    }
                }
            }

            if ($catChildren > 0 && $catChildren == $catChildrenComplete) {
                $isCat = true;
            }
        }

        return $isCat;
    }
*/

    /**
     * Fetch the field completion count including the number of completed
     * fields and the number of required fields.
     *
     * Only returns the counts for this InstrumentRecord. Use
     * getCumulativeFieldCounts() to fetch the counts of this record and any
     * children.
     *
     * @param string|null $event The event to fetch the counts for.
     *
     * @return CompletedFieldCount A CompletedFieldCount instance.
     */
    public function getCompletedFieldCounts(?string $event = null): CompletedFieldCount
    {
        $fieldCnt = null;

        if ($this->instrument->isCAT(false)) {
            // When the survey is a CAT, fake the required field count.
            // Say there is one required field and base the completed count
            // on whether the form has been completed or not.
            $fieldCnt = new CompletedFieldCount($this->isComplete(true, $event) ? 1 : 0, 1);
        }
        else {
            $fieldCnt = $this->initRequiredFieldCounts($event);
        }

        return $fieldCnt;
    }


    /**
     * Fetch the field completion count including the number of completed
     * fields and the number of required fields for this InstrumentRecord and
     * any children.
     *
     * @param string|null $event The event to fetch the counts for.
     *
     * @return CompletedFieldCount A CompletedFieldCount instance.
     */
    public function getCumulativeFieldCounts(?string $event = null): CompletedFieldCount
    {
        $fieldCnt = $this->getCompletedFieldCounts($event);

        if ($this->nextInstrumentRcd) {
            $nextCnt = $this->nextInstrumentRcd->getCumulativeFieldCounts($event);

            $fieldCnt->merge($nextCnt);
        }

        return $fieldCnt;
    }


    /**
     * Determine the number of required fields, how many have been completed and which is the first incomplete field.
     *
     * Only acts on the current Instrument/Record. Any next records will take care of it for themselves.
     */
    protected function initRequiredFieldCounts(?string $event = null): CompletedFieldCount
    {
        // Ignore the record id field
        $reqFieldNames = \array_diff($this->getRequiredFormFieldNames($event), [$this->getRecordIdFieldName()]);

        if (empty($reqFieldNames) || !$this->isLoaded()) {
            $fieldCount = new CompletedFieldCount();
        }
        else {
            // Fetch the answers for the "required" fields in the specified survey
//            $this->logger->debug("Form ({formName}) required fields:", array ('formName' => $this->instrument->getName(), $this->instrument->getRequiredFieldNames()));

            if ($this->projectUsesEvents()) {
                $rcd = $this->collapeCheckboxFields($reqFieldNames, $event);
            }
            else {
                $rcd = $this->collapeCheckboxFields($reqFieldNames);
            }

            $completeFieldCnt = 0;
            $firstIncompleteField = null;
            foreach ($rcd as $field => $value) {
                if (in_array($field, $reqFieldNames)) {
                    if ($value === '' || $value === 0) {
                        if (!isset($firstIncompleteField)) {
                            $firstIncompleteField = $field;
                        }
                    }
                    else {
                        $completeFieldCnt++;
                    }
                }
            }

            // If we don't have all the required fields and we didn't
            // determine which is the first incomplete field, use the first
            // required field which is missing from the record.
            if ($completeFieldCnt != count($reqFieldNames) && !isset($firstIncompleteField)) {
                $missingFields = \array_diff($reqFieldNames, array_keys($rcd));
                if (!empty($missingFields)) {
                    $firstIncompleteField = array_shift($missingFields);
                }
            }

            $fieldCount = new CompletedFieldCount($completeFieldCnt, count($reqFieldNames), $this->instrument->getName(), $firstIncompleteField);
        }

        return $fieldCount;
    }

    /**
     *
     * @return string[]
     */
    protected function getRequiredFormFieldNames(?string $event = null): array
    {
        $reqFieldNames = $this->instrument->getRequiredFormFieldNames();

        // TODO: Look through the optional fields to see if any would now be required because their branching case is true.
        foreach ($this->instrument->getOptionalFields() as $fieldname => $field) {
            if ($field->hasBranching()) {
                $branching = $field->getBranching();
/*
                if ($this->matchesBranching($branching, $event)) {
                    $reqFieldNames[] = $fieldname;
                }
*/
            }
        }

        return $reqFieldNames;
    }

//     private function matchesBranching(string $branching): bool
//     {
//         $parser = new BranchingParser($branching);

//         return $parser->matches($this);
//     }

    /**
     * Appeands an anchor tag on the end of the url for the field row of the
     * first incomplete field on the form, if there is one.
     *
     * @param string $inUrl The base URL for a form.
     *
     * @return string The incoming url with an anchor potentially added to
     *         the end.
     */
/*
    public function appendNextFieldToUrl(string $inUrl): string {
        $outUrl = $inUrl;

        if (isset($this->firstIncompleteField)) {
            $outUrl .= ('#' . $this->firstIncompleteField . '-tr');
        }
        else {
            if ($this->nextInstrumentRcd) {
                $outUrl = $this->nextInstrumentRcd->appendNextFieldToUrl($inUrl);
            }
        }

        return $outUrl;
    }
*/
    /**
     * Fetch a value for a field in the associated Instrument.
     *
     * If the field isn't part of the associated Instrument, pass the
     * request to the next Instrument if there is one.
     *
     * @see \RainCity\REDCap\Record::getFieldValue()
     */
    public function getFieldValue(string $field, string $event = null): ?string
    {
        $result = null;

        if (in_array($field, $this->instrument->getAllFieldNames()) ) {
            $result = parent::getFieldValue($field, $event);
        }
        else {
            if (isset($this->nextInstrumentRcd)) {
                $result = $this->nextInstrumentRcd->getFieldValue($field, $event);
            }
        }

        return $result;
    }

    /**
     * Set the value for a field in the associated Instrument.
     *
     * If the field isn't part of the associated Instrument, pass the
     * request to the next Instrument if there is one.
     *
     * @see \RainCity\REDCap\Record::setFieldValue()
     */
    public function setFieldValue(string $field, string $value, string $event = null): void
    {
        if (in_array($field, $this->instrument->getAllFieldNames()) ) {
            parent::setFieldValue($field, $value, $event);
        }
        else {
            if (isset($this->nextInstrumentRcd)) {
                $this->nextInstrumentRcd->setFieldValue($field, $value, $event);
            }
        }
    }

    /**
     *
     * {@inheritDoc}
     * @see \RainCity\REDCap\Record::getREDCapArray()
     */
    public function getREDCapArray(string $event = null): array
    {
        $result = parent::getREDCapArray($event);
        if (isset($this->nextInstrumentRcd)) {
            $nextResult = $this->nextInstrumentRcd->getREDCapArray($event);

            if ($this->projectUsesEvents()) {
                // We need to walk through our result, find the matching events
                // from the next record, and merge them into our result.
                array_walk(
                    $result,
                    function(&$item, $key, $nxtRst) {
                        // find the matching event and merge the data in
                        $nxtRcd = $this->findObjWithEvent($nxtRst, $item[Record::REDCAP_EVENT_NAME]);
                        if (isset($nxtRcd)) {
                            $item = array_merge($nxtRcd, $item);
/*
                            foreach ($nxtRcd as $field => $value) {
                                if (Record::REDCAP_EVENT_NAME != $field && $this->getRecordIdFieldName() != $field) {
                                    $item[$field] = $value;
                                }
                            }
*/
                        }
                    },
                    $nextResult
                );
            }
            else {
                $result = array(array_merge($nextResult[0], $result[0]));
            }
        }

        return $result;
    }

    /**
     *
     * {@inheritDoc}
     * @see \RainCity\REDCap\Record::loadRecord()
     */
    public function loadRecord (array $rcd): bool
    {
        $result = parent::loadRecord($rcd);

        if ($this->nextInstrumentRcd) {
            $this->nextInstrumentRcd->loadRecord($rcd);
        }

        $this->initSurveyStatus();

        return $result;
    }

    private function initSurveyStatus(): void
    {
        if ($this->projectUsesEvents()) {
            foreach ($this->getEvents() as $event) {
                $this->status[$event] = new SurveyStatus($this, $event);
            }
        }
        else {
            $this->status = new SurveyStatus($this);
        }
    }


    /**
     * Given an array of REDCap event records, find the one with the specified
     * event and return it.
     *
     * This function assumes that the 'redcap_event_name' field exists in each
     * inner array. Because of this, the function should only be called from
     * getREDCapArray() were we know this field would have been included.
     *
     * @param array<array<string, mixed>> $nxtRst An array of event data arrays.
     * @param string $event The event to look for.
     *
     * @return array<string, mixed>|null The inner event data array if an entry is found with the
     *      specified event. Otherwise returns null.
     */
    private function findObjWithEvent(array $nxtRst, string $event): ?array
    {
        $result = null;

        foreach($nxtRst as $rcd) {
            if (array_key_exists(Record::REDCAP_EVENT_NAME, $rcd) && $rcd[Record::REDCAP_EVENT_NAME] == $event) {
                $result = $rcd;
                break;
            }
        }

        return $result;
    }

    /**
     * Get a list of events used by this InstrumentRecord.
     *
     * Overrides Record::getEvents() returning only the events for the instrument.
     *
     * {@inheritDoc}
     * @see \RainCity\REDCap\Record::getEvents()
     */
    public function getEvents(): array
    {
        return $this->instrument->getEvents();
    }

    /**
     * Fetch the timestamp for when the instrument/survey was last updated.
     *
     * @param string $event The event to retrive the timestamp for
     *
     * @return \DateTime|NULL A DateTime instance or null if the Instrument
     *      doesn't have a timestamp.
     */
    public function getTimestamp(?string $event = null): ?\DateTime
    {
        $dateTime = null;

        $value = parent::getFieldValue($this->instrument->getName().'_timestamp', $event);
        // 2019-06-15 17:45:07

        // if the timestamp says not completed, try fetching a valid timestamp from the next survey
        if ('[not completed]' == $value && isset($this->nextInstrumentRcd)) {
            $dateTime = $this->nextInstrumentRcd->getTimestamp($event);
        }
        else {
            if (isset($value) && '' !== $value) {
                $dateTime = \DateTime::createFromFormat(static::REDCAP_TIMESTAMP_FORMAT, $value);
                if (false === $dateTime) {
                    $dateTime = null;
                }
            }
        }

        return $dateTime;
    }
}
