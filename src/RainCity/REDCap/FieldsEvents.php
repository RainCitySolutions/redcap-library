<?php
namespace RainCity\REDCap;

class FieldsEvents implements \Serializable
{
    /** @var string[] Array of event names */
    public $eventNames;
    /** @var string[] Array of field names */
    public $fieldNames;

    public function __construct(array $eventNames = array(), array $fieldNames = array()) {
        $this->eventNames = $eventNames;
        $this->fieldNames = $fieldNames;
    }

    public function add(FieldsEvents $fieldsEvents) {
        $this->eventNames =  array_unique(array_merge($this->eventNames, $fieldsEvents->eventNames));
        $this->fieldNames = array_unique(array_merge($this->fieldNames, $fieldsEvents->fieldNames));
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
