<?php
namespace RainCity\REDCap;

class Project implements \Serializable
{
    private $id;
    private $title;
    private $created;
    private $surveysEnabled;
    private $isLongitudinal;
    private $autoNumber;

    /** @var Instrument[] Associative array where the key is the form name */
    private $instruments = array();

    /** @var Event[] Associative array where the key is the unique event name */
    private $events = array();

    public function __construct(array $data) {
        // Check that the data array contains a minimal set of REDCap properties
        if (!empty(array_diff(array(
            'project_id',
            'project_title',
            'creation_time',
            'surveys_enabled',
            'is_longitudinal',
            'record_autonumbering_enabled'
        ), array_keys($data))) ) {
            throw new \InvalidArgumentException('Array passed is not valid REDCap project data');
        }

        $this->id = $data['project_id'];
        $this->title = $data['project_title'];
        $this->created = $data['creation_time'];
        $this->surveysEnabled = $data['surveys_enabled'] == 1;
        $this->isLongitudinal = $data['is_longitudinal'] == 1;
        $this->autoNumber = $data['record_autonumbering_enabled'] == 1;
    }

    public function getCacheKey (): string {
        return preg_replace ('/[\{\}\(\)\/\\\@: ]/', '_', sprintf('%d-%s', $this->id, $this->created));
    }

    public function getId(): int {
        return $this->id;
    }

    public function getTitle(): string {
        return $this->title;
    }

    public function isSurveyEnabled(): bool {
        return $this->surveysEnabled;
    }

    public function isLongitudinal(): bool {
        return $this->isLongitudinal;
    }

    public function isAutoNumbered(): bool {
        return $this->autoNumber;
    }

    public function addInstrument (Instrument $instrument) {
        $this->instruments[$instrument->getName()] = $instrument;
    }

    public function getInstrument (string $instrumentName): ?Instrument {
        return $this->instruments[$instrumentName] ?? null;
    }

    public function getInstrumentNames():array {
        return array_keys($this->instruments);
    }

    public function getInstruments():array {
        return $this->instruments;
    }

    public function addEvent (Event $event) {
        $this->events[$event->getName()] = $event;
    }

    public function getEvent(string $eventName): ?Event {
        return $this->events[$eventName] ?? null;
    }

    public function getEventNames ():array {
        return array_keys($this->events);
    }

    public function getEvents(): array {
        return $this->events;
    }

    public function serialize(): string
    {
        $vars = get_object_vars($this);

        return serialize($vars);
    }

    public function unserialize($serialized)
    {
        $vars = unserialize($serialized);

        foreach ($vars as $var => $value) {
            /**
             * Only set values for properties of the object.
             *
             * Generally this will be the case but this accounts for the
             * possiblity that a field may be removed from the class in the
             * future.
             */
            if (property_exists(__CLASS__, $var))
            {
                $this->$var = $value;
            }
        }
    }
}
