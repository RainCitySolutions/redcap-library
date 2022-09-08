<?php
namespace RainCity\REDCap;

class Field implements \Serializable
{
    const REQUIRED_FIELD_TYPES = array('yesno', 'truefalse', 'radio', 'text', 'checkbox'); // 'file'?
    const IGNORED_FIELD_TYPES = array('descriptive', 'calc', 'file');
    const NONINPUT_FIELD_TYPES = array('descriptive', 'calc', 'file');
    const OPTIONAL_INPUT_FIELD_TYPES = array('notes');

    private $formName;
    private $name;
    private $type;
    private $branching;
    private $note;

    private $isRequired;
    private $isOptional;
    private $isCAT;

    public function __construct(array $data) {
        // Check that the data array contains a minimal set of REDCap fields
        if (!empty(array_diff(array('form_name', 'field_name', 'field_type', 'branching_logic', 'field_note', 'required_field'), array_keys($data))) ) {
            throw new \InvalidArgumentException('Array passed is not a valid REDCap field');
        }

        $this->formName = $data['form_name'];
        $this->name = $data['field_name'];
        $this->type = $data['field_type'];
        $this->branching = $data['branching_logic'];
        $this->note = $data['field_note'];

        $required = $data['required_field'];

        if (($required == 'y' || $this->branching == '') &&
            !strstr($this->note, 'ptional') &&
            !in_array($this->type, self::NONINPUT_FIELD_TYPES))
        {
            $this->isRequired = true;
            $this->isOptional = false;
        }
        else {
            $this->isRequired = false;
            $this->isOptional = true;
        }

        $this->isCAT = preg_match ('/.?(_tscore|_std_error).*/', $this->name) == 1;
    }

    public function setFormName(string $formName) {
        $this->formName = $formName;
    }

    public function getFormName(): string {
        return $this->formName;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getType(): string {
        return $this->type;
    }

    public function getBranching(): string {
        return $this->branching;
    }

    public function getNote(): string {
        return $this->note;
    }

    public function isRequired(): bool {
        return $this->isRequired;
    }

    public function isOptional(): bool {
        return $this->isOptional;
    }

    public function isCAT(): bool {
        return $this->isCAT;
    }

    public function hasBranching(): bool {
        return $this->branching != '';
    }

    public function getCheckboxFieldName(): ?string {
        $fieldname = null;
        $matches = array();

        if ("checkbox" == $this->type &&
            preg_match ('/^(.*)___\d{1,}$/', $this->name, $matches))
        {
            $fieldname = $matches[1];
        }

        return $fieldname;
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
