<?php
declare(strict_types = 1);
namespace RainCity\REDCap;

use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use RCS\Logging\MethodLogger;
use RCS\Logging\ScopeTimer;
use RCS\Logging\Timer;

class InstrumentMgr
{
    /** @var Instrument[] Associative array where the key is the form name */
    private array $instruments = [];

    public function __construct(
        private RedCapProject $redcapProject, 
        private CacheInterface $cache,
        private LoggerInterface $logger
        )
    {
        $this->loadInstruments();
    }

    private function loadInstruments(): void
    {
        $methodLogger = new MethodLogger($this->logger); //NOSONAR

        $this->instruments = $this->cache->get('RedcapInstruments-'.$this->redcapProject->getConnection()->getUrl(), []);

        if (empty($this->instruments)) {
            $scopeTimer = new ScopeTimer($this->logger, new Timer(), 'Time to export REDCap instruments: %s');   // NOSONAR

            $localInstruments = $this->redcapProject->exportInstruments();
            foreach ($localInstruments as $name => $label) {
                $this->instruments[$name] = new Instrument($name, $label);
            }
            unset($localInstruments);

            $scopeTimer = new ScopeTimer($this->logger, new Timer(), 'Time to export REDCap metadata: %s');

            $metadata = $this->redcapProject->exportMetadata();
            foreach($metadata as $field) {
                $form = $this->instruments[$field['form_name']];
                $form->addField(new Field($field));
//                $form->addField(new Field($form, $field['field_name'], $field['field_type'], $field['required_field'], $field['branching_logic'], $field['field_note']));
            }
            unset($metadata);

            $scopeTimer = new ScopeTimer($this->logger, new Timer(), 'Time to export REDCap event mapping: %s');

            $eventdata = $this->redcapProject->exportInstrumentEventMappings();
            foreach($eventdata as $entry) {
                $form = $this->instruments[$entry['form']];
//TODO: needed?                $form->addEvent($entry['unique_event_name']);
            }
            unset($eventdata);

            $this->cache->set('RedcapInstruments-'.$this->redcapProject->getConnection()->getUrl(), $this->instruments);
        } else {
            // Loaded from the cache so initialize references to objects
            $this->initializeInstrumentReferences($this->instruments);
        }
    }

    /**
     *
     * @param Instrument[] $instruments
     */
    private function initializeInstrumentReferences(array $instruments): void
    {
        foreach ($instruments as $instrument) {
            foreach ($instrument->getRequiredFields() as $field) {
                $field->setFormName($instrument->getName());
            }

            foreach ($instrument->getOptionalFields() as $field) {
                $field->setFormName($instrument->getName());
            }
        }
    }

    /**
     *
     * @return Instrument[]
     */
    public function getInstruments(): array
    {
        return $this->instruments;
    }
}
