<?php
namespace RainCity\REDCap;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;
use RainCity\TestHelper\ReflectionHelper;

#[CoversClass('\RainCity\REDCap\Project')]
#[CoversClass('\RainCity\REDCap\Event')]
#[CoversClass('\RainCity\REDCap\Instrument')]
class ProjectTest extends REDCapTestCase
{
    const TEST_INSTRUMENT_NAME = 'testInstrument';
    const TEST_EVENT_NAME = 'testEvent';

    public static function getTestProject(): array {
        return array (
            'project_id' => 8626,
            'project_title' => 'testProjectTitle',
            'creation_time' => '2016-09-29 11:55:49',
            'surveys_enabled' => 1,
            'is_longitudinal' => 1,
            'record_autonumbering_enabled' => 1
        );
    }

    protected function getTestEvent(): Event {
        $testEvent = $this->createMock(Event::class);
        $testEvent->method('getName')->willReturn(self::TEST_EVENT_NAME);

        return $testEvent;
    }

    protected function getTestInstrument(): Instrument {
        $instrument = $this->createMock(Instrument::class);
        $instrument->method('getName')->willReturn(self::TEST_INSTRUMENT_NAME);

        return $instrument;
    }

    public function testCtor_invalidArg() {
        $this->expectException("ArgumentCountError");
        new Project();  // NOSONAR - ignore useless object instantiation

        $this->expectException("TypeError");
        new Project("test");    // NOSONAR - ignore useless object instantiation
    }

    public function testCtor_noREDCapData() {
        $this->expectException("InvalidArgumentException");
        new Project(array());   // NOSONAR - ignore useless object instantiation
    }

    public function testCtor_() {
        $data = self::getTestProject();

        $project = new Project($data);

        $this->assertTrue($project->isSurveyEnabled());
        $this->assertTrue($project->isLongitudinal());
        $this->assertTrue($project->isAutoNumbered());

        $this->assertEquals($data['project_id'], $project->getId());
        $this->assertEquals($data['project_title'], $project->getTitle());

        $this->assertEquals($data['creation_time'], ReflectionHelper::getObjectProperty(Project::class, 'created', $project));
    }

    public function testGetCacheKey() {
        $data = self::getTestProject();

        $project = new Project($data);

        $key = $project->getCacheKey();

        $expected = str_replace(':', '_', $data['project_id'].'-'.$data['creation_time']);
        $expected = str_replace(' ', '_', $expected);
        $this->assertEquals($expected, $key);
    }

    public function testAddInstrument() {
        $project = new Project(self::getTestProject());
        $project->addInstrument($this->getTestInstrument());

        $instruments = ReflectionHelper::getObjectProperty(Project::class, 'instruments', $project);
        $this->assertArrayHasKey(self::TEST_INSTRUMENT_NAME, $instruments);

        return $project;
    }

    #[Depends('testAddInstrument')]
    public function testGetInstrument($project) {
        $instrument = $project->getInstrument(self::TEST_INSTRUMENT_NAME);

        $this->assertInstanceOf(Instrument::class, $instrument);
        $this->assertEquals(self::TEST_INSTRUMENT_NAME, $instrument->getName());
    }

    public function testGetInstrument_noInstrument() {
        $project = new Project(self::getTestProject());

        $event = $project->getInstrument('missingInstrument');

        $this->assertNull($event);
    }

    #[Depends('testAddInstrument')]
    public function testGetInstrumentNames($project) {
        $names = $project->getInstrumentNames();

        $this->assertIsArray($names);
        $this->assertContains(self::TEST_INSTRUMENT_NAME, $names);
    }

    #[Depends('testAddInstrument')]
    public function testGetInstruments($project) {
        $instruments = $project->getInstruments();

        $this->assertIsArray($instruments);
        $this->assertArrayHasKey(self::TEST_INSTRUMENT_NAME, $instruments);
    }




    public function testAddEvent() {
        $project = new Project(self::getTestProject());

        $project->addEvent($this->getTestEvent());

        $events = ReflectionHelper::getObjectProperty(Project::class, 'events', $project);
        $this->assertArrayHasKey(self::TEST_EVENT_NAME, $events);

        return $project;
    }

    #[Depends('testAddEvent')]
    public function testGetEvent($project) {
        $event = $project->getEvent(self::TEST_EVENT_NAME);

        $this->assertInstanceOf(Event::class, $event);
        $this->assertEquals(self::TEST_EVENT_NAME, $event->getName());
    }

    public function testGetEvent_noEvent() {
        $project = new Project(self::getTestProject());

        $event = $project->getEvent('missingEvent');

        $this->assertNull($event);
    }

    #[Depends('testAddEvent')]
    public function testGetEventNames($project) {
        $names = $project->getEventNames();

        $this->assertIsArray($names);
        $this->assertContains(self::TEST_EVENT_NAME, $names);
    }

    #[Depends('testAddEvent')]
    public function testGetEvents(Project $project) {
        $events = $project->getEvents();

        $this->assertIsArray($events);
        $this->assertArrayHasKey(self::TEST_EVENT_NAME, $events);
    }


    const ARRAY_SIZE_REGEX          = '/a:8:{.+}/';
    const ID_FIELD_REGEX            = '/i:0;i:(\d{1,});/U';
    const TITLE_FIELD_REGEX         = '/i:1;s:(\d{1,}):"(.+)";/U';
    const CREATED_FIELD_REGEX       = '/i:2;s:(\d{1,}):"(.+)";/U';
    const SURVEYS_FIELD_REGEX       = '/i:3;b:([01]);/';
    const LONGITUIDIAL_FIELD_REGEX  = '/i:4;b:([01]);/';
    const AUTONUMBER_FIELD_REGEX    = '/i:5;b:([01]);/';
    const INSTRUMENTS_FIELD_REGEX   = '/i:6;a:\d{1,}:{(s:\d{1,}:".+";)/U';
    const EVENTS_FIELD_REGEX        = '/i:7;a:\d{1,}:{(s:\d{1,}:".+";)/U';

    public function testSerialize() {
        $data = self::getTestProject();

        $testEvent = new Event([
            'unique_event_name' => self::TEST_EVENT_NAME
            ]
        );

        $testInstrument = new Instrument(self::TEST_INSTRUMENT_NAME, self::TEST_INSTRUMENT_NAME);

        $project = new Project($data);
        $project->addEvent($testEvent);
        $project->addInstrument($testInstrument);

        $serializedObj = $project->serialize();

        $this->assertMatchesRegularExpression(self::ARRAY_SIZE_REGEX, $serializedObj); // should be an array of 8 elements

        $this->assertStringContainsString($data['project_id'], $serializedObj);
        $this->assertStringContainsString($data['project_title'], $serializedObj);
        $this->assertStringContainsString($data['creation_time'], $serializedObj);
        $this->assertStringContainsString($data['surveys_enabled'], $serializedObj);
        $this->assertStringContainsString($data['is_longitudinal'], $serializedObj);
        $this->assertStringContainsString($data['record_autonumbering_enabled'], $serializedObj);

        $this->assertStringContainsString(self::TEST_INSTRUMENT_NAME, $serializedObj);
        $this->assertStringContainsString(self::TEST_EVENT_NAME, $serializedObj);

        return $serializedObj;
    }

    #[Depends('testSerialize')]
    public function testUnserialize($serializedObj) // $serialObj is passed from testSerialize
    {
        $data = self::getTestProject();    // Assumption: that testSerialize used getTestProject()

        $project = (new \ReflectionClass(Project::class))->newInstanceWithoutConstructor();

        $project->unserialize($serializedObj);

        $this->assertEquals($data['is_longitudinal'], $project->isLongitudinal());
        $this->assertEquals($data['surveys_enabled'], $project->isSurveyEnabled());
        $this->assertEquals($data['record_autonumbering_enabled'], $project->isAutoNumbered());

        $this->assertContains(self::TEST_INSTRUMENT_NAME, $project->getInstrumentNames());
        $this->assertContains(self::TEST_EVENT_NAME, $project->getEventNames());
    }
}
