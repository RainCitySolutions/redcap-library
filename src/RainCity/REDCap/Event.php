<?php
namespace RainCity\REDCap;

class Event
{
    private $name;

    public function __construct(array $data) {
        if (!isset($data['unique_event_name'])) {
            throw new \InvalidArgumentException('Array passed is not a valid REDCap event');
        }

        $this->name = $data['unique_event_name'];
    }

    public function getName() {
        return $this->name;
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
