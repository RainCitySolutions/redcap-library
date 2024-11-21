<?php
declare(strict_types = 1);
namespace RainCity\REDCap;

use RainCity\SerializeAsArrayTrait;

class Event implements \Serializable
{
    use SerializeAsArrayTrait;

    private string $name;

    /**
     *
     * @param array<string, string> $data
     */
    public function __construct(array $data)
    {
        if (!isset($data['unique_event_name'])) {
            throw new \InvalidArgumentException('Array passed is not a valid REDCap event');
        }

        $this->name = $data['unique_event_name'];
    }

    public function getName(): string
    {
        return $this->name;
    }
}
