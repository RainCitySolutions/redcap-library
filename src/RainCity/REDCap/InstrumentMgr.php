<?php
namespace RainCity\REDCap;

use IU\PHPCap\RedCapProject;
use RainCity\DataCache;
use RainCity\MethodLogger;
use RainCity\ScopeTimer;
use RainCity\Logging\Logger;

class InstrumentMgr
{
    private $logger;

    /** @var RedCapProject */
    private $redcapProject;

    private $cache;

    /** @var Instrument[] Associative array where the key is the form name */
    private $instruments = array();

    public function __construct(RedCapProject $redcapProject, DataCache $cache = null) {
        $this->logger = Logger::getLogger(get_class($this));
        $this->redcapProject = $redcapProject;
        $this->cache = $cache;

        $this->loadInstruments();
    }

    private function loadInstruments() {
        $methodLogger = new MethodLogger();

        $this->instruments = isset($this->cache) ? $this->cache->get('RedcapInstruments-'.$this->redcapProject->getConnection()->getUrl()) : null;

        if (isset($this->instruments)) {
            // Loaded from the cache so initialize references to objects
            foreach ($this->instruments as $instrument) {
                foreach ($instrument->getRequiredFields() as $field) {
                    $field->setForm($instrument);
                }
                foreach ($instrument->getOptionalFields() as $field) {
                    $field->setForm($instrument);
                }
            }
        }
        else {
            $scopeTimer = new ScopeTimer($this->logger, 'Time to export REDCap instruments: %s');

            $instruments = $this->redcapProject->exportInstruments();
            foreach ($instruments as $name => $label) {
                $this->instruments[$name] = new Instrument($name, $label);
            }
            unset($instruments);

            $scopeTimer = new ScopeTimer($this->logger, 'Time to export REDCap metadata: %s');

            $metadata = $this->redcapProject->exportMetadata();
            foreach($metadata as $field) {
                $form = $this->instruments[$field['form_name']];
                $form->addField(new Field($form, $field['field_name'], $field['field_type'], $field['required_field'], $field['branching_logic'], $field['field_note']));
            }
            unset($metadata);

            $scopeTimer = new ScopeTimer($this->logger, 'Time to export REDCap event mapping: %s');

            $eventdata = $this->redcapProject->exportInstrumentEventMappings();
            foreach($eventdata as $entry) {
                $form = $this->instruments[$entry['form']];
                $form->addEvent($entry['unique_event_name']);
            }
            unset($eventdata);

            if (isset($this->cache)) {
                $this->cache->set('RedcapInstruments-'.$this->redcapProject->getConnection()->getUrl(), $this->instruments);
            }
        }
    }

    public function getInstruments(): array {
        return $this->instruments;
    }
}
