<?php
declare(strict_types = 1);
namespace RainCity\REDCap;

use RainCity\SerializeAsArrayTrait;

class Field implements \Serializable
{
    use SerializeAsArrayTrait;

    const REQUIRED_FIELD_TYPES = array('yesno', 'truefalse', 'radio', 'text', 'checkbox'); // 'file'?
    const IGNORED_FIELD_TYPES = array('descriptive', 'calc', 'file', 'dropdown', 'notes');
    const NONINPUT_FIELD_TYPES = array('descriptive', 'calc', 'file');
    const OPTIONAL_INPUT_FIELD_TYPES = array('notes');
    const MINIMUM_REDCAP_FIELD_SET = array(
        'form_name',
        'field_name',
        'field_type',
        'branching_logic',
        'field_note',
        'required_field'
        );


    private string $formName;
    private string $name;
    private string $type;
    private string $branching;
    private string $note;

    private bool $isRequired;
    private bool $isOptional;
    private bool $isCAT;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        // Check that the data array contains a minimal set of REDCap fields
        if (!empty(array_diff(self::MINIMUM_REDCAP_FIELD_SET, array_keys($data))) ) {
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

    public function setFormName(string $formName): void
    {
        $this->formName = $formName;
    }

    public function getFormName(): string
    {
        return $this->formName;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getBranching(): string
    {
        return $this->branching;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    public function isOptional(): bool
    {
        return $this->isOptional;
    }

    public function isCAT(): bool
    {
        return $this->isCAT;
    }

    public function hasBranching(): bool
    {
        return $this->branching != '';
    }

    public function getCheckboxFieldName(): ?string
    {
        $fieldname = null;
        $matches = array();

        if ("checkbox" == $this->type &&
            preg_match ('/^(.*)___\d+$/', $this->name, $matches))
        {
            $fieldname = $matches[1];
        }

        return $fieldname;
    }
}
