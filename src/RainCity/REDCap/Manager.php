<?php
declare(strict_types = 1);
namespace RainCity\REDCap;

use RainCity\MethodLogger;
use Psr\SimpleCache\CacheInterface;

class Manager
{
    public function __construct(
        private RedCapProjectFactoryIntf $redcapProjFactory,
        private string $projectId,
        private ?CacheInterface $cache = null
        )
    {
    }

    public function getProject(): ?Project
    {
        $projectCacheKey = 'RedcapProject-'.$this->redcapProjFactory->getProject($this->projectId)->getHash();

        $project = isset($this->cache) ? $this->cache->get($projectCacheKey) : null;
        if (!isset($project)) {
            $project = $this->loadProject();

            if (isset($this->cache) && isset($project)) {
                $this->cache->set($projectCacheKey, $project);
            }
        }

        return $project;
    }

    /**
     *
     * @return Instrument[]
     */
    public function getInstruments(): array
    {
        $instruments = [];

        $project = $this->getProject();

        if (!is_null($project)) {
            $instruments = $project->getInstruments();
        }

        return $instruments;
    }

    /**
     *
     * @return Event[]
     */
    public function getEvents(): array
    {
        $project = $this->getProject();

        return $project->getEvents();
    }

    /**
     * Load a record from REDCap
     *
     * @param string        $recordId   The identifier for the record to be loaded.
     * @param string[]|null $fields     The fields to be loaded. If not specifed
     *      or an empty array all fields will be loaded.
     * @param string[]|null $instruments The instruments to be loaded. If not
     *      specified or an empty array all instrument fields will be loaded.
     * @param string[]|null $events     The events to be loaded. If not specified
     *      or an empty array all events will be loaded.
     *
     * @return Record|NULL  A Record instance representing the REDCap record
     *      or null if the record could not be loaded.
     */
    public function fetchRecord (
        string $recordId,
        ?array $fields = array(),
        ?array $instruments = array(),
        ?array $events = array()
        ): ?Record
    {
        $record = new Record($this->redcapProjFactory->getProject($this->projectId), $fields, null, $instruments, $events);

        if (!$record->loadRecordById($recordId)) {
            $record = null;
        }

        return $record;
    }


    private function loadProject(): ?Project
    {
        $project = null;

        $projInfo = $this->redcapProjFactory->getProject($this->projectId)->exportProjectInfo();

        if (count($projInfo) != 0) {
            $project = new Project($projInfo);

            $this->loadInstruments($project);
            $this->loadEvents($project);
        }

        return $project;
    }


    /**
     * Load the instruments into the project.
     *
     * @param Project $project The \RainCity\REDCap\Project to load the
     *      instruments into.
     */
    private function loadInstruments(Project $project): void
    {
        $methodLogger = new MethodLogger(); // NOSONAR - ignore unused variable

        $redcapProject = $this->redcapProjFactory->getProject($this->projectId);

        $fieldnames = $redcapProject->exportFieldNames();
        $metadata = $redcapProject->exportMetadata();
        /** @var array<array<string, string>> */
        $eventmappings = $redcapProject->exportInstrumentEventMappings();

        $instruments = $redcapProject->exportInstruments();
        foreach ($instruments as $name => $label) {
            $project->addInstrument(new Instrument($name, $label, $fieldnames, $metadata, $eventmappings));
        }
    }


    /**
     * Load the events for the project.
     *
     * @param Project $project The \RainCity\REDCap\Project instance to load
     *      the events into.
     */
    private function loadEvents(Project $project): void
    {
        $methodLogger = new MethodLogger(); // NOSONAR - ignore unused variable

        $events = $this->redcapProjFactory->getProject($this->projectId)->exportEvents();

        foreach ($events as $event) {
            $project->addEvent(new Event ($event));
        }
        unset($events);
    }
}
