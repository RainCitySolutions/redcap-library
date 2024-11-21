<?php
declare(strict_types = 1);
namespace RainCity\REDCap;

use RainCity\SerializeAsArrayTrait;

class FieldsEvents implements \Serializable
{
    use SerializeAsArrayTrait;

    /** @var string[] Array of event names */
    public $eventNames;
    /** @var string[] Array of field names */
    public $fieldNames;

    /**
     *
     * @param string[] $eventNames
     * @param string[] $fieldNames
     */
    public function __construct(array $eventNames = array(), array $fieldNames = array())
    {
        $this->eventNames = $eventNames;
        $this->fieldNames = $fieldNames;
    }

    public function add(FieldsEvents $fieldsEvents): void
    {
        $this->eventNames =  array_unique(array_merge($this->eventNames, $fieldsEvents->eventNames));
        $this->fieldNames = array_unique(array_merge($this->fieldNames, $fieldsEvents->fieldNames));
    }
}
