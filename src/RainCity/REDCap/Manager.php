<?php
namespace RainCity\REDCap;

use IU\PHPCap\RedCapProject;
use RainCity\MethodLogger;
use Psr\SimpleCache\CacheInterface;


class Manager
{
    private RedCapProject $redcapProject;
    private ?CacheInterface $cache;

    private string $cacheKey;

    public function __construct(RedCapProject $redcapProject, ?CacheInterface $cache = null)
    {
        $this->redcapProject = $redcapProject;
        $this->cache = $cache;

        $this->cacheKey =
            parse_url($redcapProject->getConnection()->getUrl(), PHP_URL_HOST) .
            '-' .
            $redcapProject->getApiToken();
/*
        }
        else {
        TODO: do this when handling a REDCap exception?
            $msg = 'Unable to communication with REDCap, incorrectly configured RedCapProject object?';
            $this->logger->warning($msg);

            throw new \Exception ($msg);
        }
*/
    }

    public function getProject(): ?Project
    {
        $projectCacheKey = 'RedcapProject-'.$this->cacheKey;

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
        $project = $this->getProject();

        return $project->getInstruments();
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
        $record = new Record($this->redcapProject, $fields, null, $instruments, $events);

        if (!$record->loadRecordById($recordId)) {
            $record = null;
        }

        return $record;
    }


    private function loadProject(): ?Project
    {
        $project = null;

        $projInfo = $this->redcapProject->exportProjectInfo();

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

        $fieldnames = $this->redcapProject->exportFieldNames();
        $metadata = $this->redcapProject->exportMetadata();
        $eventmappings = $this->redcapProject->exportInstrumentEventMappings();

        $instruments = $this->redcapProject->exportInstruments();
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

        $events = $this->redcapProject->exportEvents();
        foreach ($events as $event) {
            $project->addEvent(new Event ($event));
        }
        unset($events);
    }
}
