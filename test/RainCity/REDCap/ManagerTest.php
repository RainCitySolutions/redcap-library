<?php
declare(strict_types = 1);
namespace RainCity\REDCap;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use RainCity\DataCache;
use RainCity\TestHelper\ReflectionHelper;


#[CoversClass('\RainCity\REDCap\Manager')]
#[CoversMethod('RainCity\REDCap\Project', '__construct')]
#[CoversMethod('RainCity\REDCap\Project', 'addEvent')]
#[CoversMethod('RainCity\REDCap\Project', 'getEventNames')]
#[CoversMethod('RainCity\REDCap\Project', 'addInstrument')]
#[CoversMethod('RainCity\REDCap\Project', 'getInstrumentNames')]
#[CoversMethod('RainCity\REDCap\Project', 'getInstruments')]
#[CoversMethod('RainCity\REDCap\Project', 'getEvents')]
#[CoversMethod('RainCity\REDCap\Event', '__construct')]
#[CoversMethod('RainCity\REDCap\Event', 'getName')]
#[CoversMethod('RainCity\REDCap\Field', '__construct')]
#[CoversMethod('RainCity\REDCap\Field', 'getCheckboxFieldName')]
#[CoversMethod('RainCity\REDCap\Field', 'getName')]
#[CoversMethod('RainCity\REDCap\Field', 'getType')]
#[CoversMethod('RainCity\REDCap\Field', 'isCAT')]
#[CoversMethod('RainCity\REDCap\Field', 'isRequired')]
#[CoversMethod('RainCity\REDCap\Instrument', '__construct')]
#[CoversMethod('RainCity\REDCap\Instrument', 'addField')]
#[CoversMethod('RainCity\REDCap\Instrument', 'getName')]
#[CoversMethod('RainCity\REDCap\Instrument', 'makeSingularCheckboxesOptional')]
#[CoversMethod('RainCity\REDCap\Instrument', 'readExportFieldNames')]
#[CoversMethod('RainCity\REDCap\Instrument', 'readMetadata')]
class ManagerTest extends REDCapTestCase
{
    private const PROJECT_ID = 'rpf_1234567890';

    public function testCtor_normal() {
        $mgr = new Manager($this->stubProjectFactory, self::PROJECT_ID);

        $redcapFactory = ReflectionHelper::getObjectProperty(Manager::class, 'redcapProjFactory', $mgr);

        $this->assertEquals($this->stubProjectFactory, $redcapFactory);
        $this->assertNull(ReflectionHelper::getObjectProperty(Manager::class, 'cache', $mgr));
    }

    public function testCtor_withCache() {
        $mockCache = $this->createMock(DataCache::class);

        $mgr = new Manager($this->stubProjectFactory, self::PROJECT_ID, $mockCache);
        $cache = ReflectionHelper::getObjectProperty(Manager::class, 'cache', $mgr);

        $this->assertEquals($mockCache, $cache);
    }

    public function testGetProject_noProject() {
        $this->setCallback('exportProjectInfo', function() { return array(); } );
        $this->setCallback('exportInstrumentEventMappings', function() {return null;});

        $mgr = new Manager($this->stubProjectFactory, self::PROJECT_ID);
        $project = $mgr->getProject();

        $this->assertNull($project);
    }

    public function testGetProject_cacheHit() {
        $testProject = new Project(ProjectTest::getTestProject());

        $mockCache = $this->createMock(DataCache::class);
        $mockCache->expects($this->once())->method('get')->willReturn($testProject);
        $mockCache->expects($this->never())->method('set');

        $mgr = new Manager($this->stubProjectFactory, self::PROJECT_ID, $mockCache);
        $mgr->getProject();
    }

    public function testGetProject_cacheMiss() {
        $this->setCallback('exportInstruments', function () { return array(); } );

        $mockCache = $this->createMock(DataCache::class);
        $mockCache->expects($this->once())->method('get')->willReturn(null);
        $mockCache->expects($this->once())->method('set');

        $mgr = new Manager($this->stubProjectFactory, self::PROJECT_ID, $mockCache);
        $mgr->getProject();
    }

    public function testGetProject_withEvents() {
        $this->setCallback('exportInstruments', function () { return array(); } );

        $mgr = new Manager($this->stubProjectFactory, self::PROJECT_ID);
        $project = $mgr->getProject();

        $this->assertEquals(3, count($project->getEventNames()) );
    }

    public function testGetProject_withInstruments() {
        $mgr = new Manager($this->stubProjectFactory, self::PROJECT_ID);
        $project = $mgr->getProject();

        $this->assertCount(count(static::TEST_INSTRUMENTS), $project->getInstrumentNames() );
    }

    public function testGetInstruments() {
        $mgr = new Manager($this->stubProjectFactory, self::PROJECT_ID);
        $instruments = $mgr->getInstruments();

        $this->assertIsArray($instruments);
        $this->assertCount(count(static::TEST_INSTRUMENTS), $instruments);
    }

    public function testGetEvents() {
        $mgr = new Manager($this->stubProjectFactory, self::PROJECT_ID);
        $events = $mgr->getEvents();

        $this->assertIsArray($events);
        $this->assertCount(count(static::TEST_EVENTS), $events);
    }

/*
    public function testCtor_noInstrument() {
        $this->stubRedcapProj->method('exportProjectInfo')->willReturn(ProjectTest::getTestProject());
        $this->stubRedcapProj->method('exportInstruments')->willReturn(array());
        $this->stubRedcapProj->method('exportMetadata')->willReturn(array());
        $this->stubRedcapProj->method('exportEvents')->willReturn(array());
        $this->stubRedcapProj->method('exportInstrumentEventMappings')->willReturn(array());

        $mgr = new Manager($this->stubProjectFactory);
        $project = $mgr->getProject();

        $this->assertSame($project->getInstruments(), $mgr->getInstruments());
    }

    public function testCtor_noMetadata() {
        $this->stubRedcapProj->method('exportProjectInfo')->willReturn(ProjectTest::getTestProject());
        $this->stubRedcapProj->method('exportInstruments')->willReturn($this->getTestInstruments());
        $this->stubRedcapProj->method('exportMetadata')->willReturn(array());
        $this->stubRedcapProj->method('exportEvents')->willReturn(array());
        $this->stubRedcapProj->method('exportInstrumentEventMappings')->willReturn(array());

        $mgr = new Manager($this->stubProjectFactory);
        $project = $mgr->getProject();

        $this->assertSame($project->getInstruments(), $mgr->getInstruments());
    }
*/
/*
    public function getProject(): Project {
        $methodLogger = new MethodLogger();

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

    public function getInstruments(): array {
        $project = $this->getProject();

        return $project->getInstruments();
    }

    public function getEvents(): array {
        $project = $this->getProject();

        return $project->getEvents();
    }

    private function loadProject(): ?Project {
        $methodLogger = new MethodLogger();
        $project = null;

        $projInfo = $this->redcapProject->exportProjectInfo();

        if (count($projInfo) != 0) {
            $project = new Project($projInfo);

            $this->loadInstruments($project);
            $this->loadEvents($project);
        }

        return $project;
    }


    private function loadInstruments(Project $project) {
        $methodLogger = new MethodLogger();
        $scopeTimer = new ScopeTimer($this->logger, 'Time to export REDCap instruments: %s');

        $instruments = $this->redcapProject->exportInstruments();
        foreach ($instruments as $name => $label) {
            $project->addInstrument(new Instrument($name, $label));
        }
        unset($instruments);

        $scopeTimer = new ScopeTimer($this->logger, 'Time to export REDCap metadata: %s');

        $metadata = $this->redcapProject->exportMetadata();
        foreach($metadata as $field) {
            $instrument = $project->getInstrument($field['form_name']);
            if (isset($instrument)) {
                $instrument->addField(new Field($field));
            }
        }
        unset($metadata);

        $scopeTimer = new ScopeTimer($this->logger, 'Time to export REDCap event mapping: %s');

        $eventdata = $this->redcapProject->exportInstrumentEventMappings();
        foreach($eventdata as $entry) {
            $instrument = $project->getInstrument($entry['form']);
            if (isset($instrument)) {
                $instrument->addEvent($entry['unique_event_name']);
            }
        }
        unset($eventdata);
    }


    private function loadEvents(Project $project) {
        $methodLogger = new MethodLogger();

        $scopeTimer = new ScopeTimer($this->logger, 'Time to export REDCap events: %s');

        $events = $this->redcapProject->exportEvents();
        foreach ($events as $event) {
            $project->addEvent(new Event ($event));
        }
        unset($events);
    }
*/
}
