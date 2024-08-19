<?php
namespace RainCity\REDCap;

/**
 * Represents the completion status for a particular event on a particular
 * InstrumentRecord.
 *
 * The class does not keep track of the Instrument, InstrumentRecord or event.
 * This would be up to the user.
 */
class SurveyStatus
{
    const UNKNOWN           = 0x00;
    const REDCAP_COMPLETE   = 0x01;
    const REDCAP_UNVERIFIED = 0x02;
    const REDCAP_SURVEY_INCOMPLETE = 0x04;

    const NOT_STARTED       = 0x10;
    const COMPLETE          = 0x20;
    const CANT_EDIT         = 0x80;
    //    const CAT_COMPLETE = 89;

    private int $status = self::UNKNOWN;

    /** @var string|null First incomplete instrument */
    private ?string $firstIncompleteInstrument = null;

    /** @var string|null First incomplete field in the incomplete instrument */
    private ?string $firstIncompleteField = null;

    /**
     *
     * @param InstrumentRecord $instRcd
     * @param string $event
     */
    public function __construct(InstrumentRecord $instRcd, ?string $event = null)
    {
        if ($instRcd->isLoaded()) {
            $instrument = $instRcd->getInstrument();

            $redcapStatus = $instRcd->getFieldValue($instrument->getName().'_complete', $event) ?? 0;
            $redcapTimestamp = $instRcd->getFieldValue($instrument->getName().'_timestamp', $event) ?? '';

            $this->setRedcapStatus($redcapStatus, $redcapTimestamp);

            // If REDCap says the survey is incomplete it means it was never started
            if ($this->isRedcapIncomplete() && !$this->isRedcapSurveyIncomplete()) {
                $this->status |= self::NOT_STARTED;
            }
            else {
                // If REDCap says the survey is complete, we need to determine if it was finished
                if ($instrument->isCAT(false)) {
                    $this->status |= self::COMPLETE;
//                    $this->status |= (self::CANT_EDIT | self::COMPLETE);
                }
            }

            if (!$instrument->isCAT(false)) {
                $fieldCnts = $instRcd->getCompletedFieldCounts($event);

                if (0 === $fieldCnts->getRequiredCount()) {
                    $this->status |= (self::CANT_EDIT | self::COMPLETE);
                }
                else {
                    if ($fieldCnts->getCompletedCount() == $fieldCnts->getRequiredCount() &&
                        !$this->isRedcapSurveyIncomplete()) {
                            $this->status |= self::COMPLETE;
                        }
                }
                $this->firstIncompleteInstrument = $fieldCnts->getFirstIncompleteInstrument();
                $this->firstIncompleteField = $fieldCnts->getFirstIncompleteField();
            }
        }
    }

    /**
     *
     * @return string|NULL The name of the first incomplete instrument, or
     *      null if there are no incomplete instruments.
     */
    public function getFirstIncompleteInstrument(): ?string
    {
        return $this->firstIncompleteInstrument;
    }

    /**
     *
     * @return string|NULL The name of the first incomplete field, or null if
     *      there are no incomplete fields.
     */
    public function getFirstIncompleteField(): ?string
    {
        return $this->firstIncompleteField;
    }

    public function isRedcapComplete(): bool
    {
        return ($this->status & self::REDCAP_COMPLETE) != 0;
    }

    public function isRedcapIncomplete(): bool
    {
        return ($this->status & self::REDCAP_COMPLETE) == 0;
    }

    public function isRedcapSurveyIncomplete(): bool
    {
        return ($this->status & self::REDCAP_SURVEY_INCOMPLETE) != 0;
    }

    public function notStarted (): bool
    {
        return ($this->status & self::NOT_STARTED) != 0;
    }

    public function isComplete (): bool
    {
        return ($this->status & self::COMPLETE) != 0;
    }

    public function canEdit (): bool
    {
        return ($this->status & self::CANT_EDIT) == 0;
    }

    /**
     * Converts the Survey Status retruned by REDCap into one of the
     * constants.
     *
     * @param int $redcapStatus The survey status from REDCap
     * @param string $redcapTimestamp The timestamp when a survey was
     *        completed. May be an empty string or "[not completed]".
     */
    private function setRedcapStatus(int $redcapStatus, string $redcapTimestamp): void
    {
        switch ($redcapStatus)
        {
            case 0:
                if (!empty($redcapTimestamp) && '[not completed]' != $redcapTimestamp) {
                    $this->status |= self::REDCAP_SURVEY_INCOMPLETE;
                }
                break;

            case 1:
                $this->status |= self::REDCAP_UNVERIFIED;
                break;

            case 2:
                $this->status |= self::REDCAP_COMPLETE;
                break;

            default:
                break;
        }
    }

    public function __toString(): string
    {
        $states = array();

        if ($this->notStarted()) { $states[] = 'Not Started'; }
        if ($this->isComplete()) { $states[] = 'Complete'; }
        if ($this->canEdit()) { $states[] = 'Can Edit'; }

        return join(' - ', $states);
    }
}
