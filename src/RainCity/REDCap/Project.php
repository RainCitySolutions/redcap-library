<?php
declare(strict_types = 1);
namespace RainCity\REDCap;

use RainCity\SerializeAsArrayTrait;

class Project implements \Serializable
{
    use SerializeAsArrayTrait;

    private int $id;
    private string $title;
    private string $created;
    private bool $surveysEnabled;
    private bool $isLongitudinal;
    private bool $autoNumber;

    /** @var Instrument[] Associative array where the key is the form name */
    private array $instruments = array();

    /** @var Event[] Associative array where the key is the unique event name */
    private array $events = array();

    /**
     *
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
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

    public function getCacheKey (): string
    {
        return preg_replace ('/[\{\}\(\)\/\\\@: ]/', '_', sprintf('%d-%s', $this->id, $this->created));
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function isSurveyEnabled(): bool
    {
        return $this->surveysEnabled;
    }

    public function isLongitudinal(): bool
    {
        return $this->isLongitudinal;
    }

    public function isAutoNumbered(): bool
    {
        return $this->autoNumber;
    }

    public function addInstrument (Instrument $instrument): void
    {
        $this->instruments[$instrument->getName()] = $instrument;
    }

    public function getInstrument (string $instrumentName): ?Instrument
    {
        return $this->instruments[$instrumentName] ?? null;
    }

    /**
     *
     * @return string[]
     */
    public function getInstrumentNames(): array
    {
        return array_keys($this->instruments);
    }

    /**
     *
     * @return array<string, Instrument>
     */
    public function getInstruments(): array
    {
        return $this->instruments;
    }

    public function addEvent (Event $event): void
    {
        $this->events[$event->getName()] = $event;
    }

    public function getEvent(string $eventName): ?Event
    {
        return $this->events[$eventName] ?? null;
    }

    /**
     *
     * @return string[]
     */
    public function getEventNames (): array
    {
        return array_keys($this->events);
    }

    /**
     *
     * @return Event[]
     */
    public function getEvents(): array
    {
        return $this->events;
    }
}
