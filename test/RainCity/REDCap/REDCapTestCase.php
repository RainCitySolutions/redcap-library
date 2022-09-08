<?php
namespace RainCity\REDCap;

use IU\PHPCap\RedCapApiConnectionInterface;
use IU\PHPCap\RedCapProject;
use RainCity\Logging\Logger;
use RainCity\TestHelper\RainCityTestCase;
use RainCity\TestHelper\StubLogger;


abstract class REDCapTestCase
    extends RainCityTestCase
{
    const RCD_ID_FIELD = 'record_id';
    const EMAIL_FIELD = 'email';
    const ADDRESS_FIELD = 'address';
    const PHONE_FIELD = 'phone';
    const RACE_FIELD = 'race';
    const RELATIONSHIP_FIELD = 'relationship';
    const FULLNAME_FIELD = 'name_full';
    const SHORTNAME_FIELD = 'name_short';
    const DATEOFBIRTH_FIELD = 'dob';
    const CONSENT_FIELD = 'consent_yn';
    const MISSING_DATA_FIELD = 'missing_data_field';
    const PAST_ILLNESS_FIELD = 'past_illness';
    const SHUNT_FIELD = 'shunt_yn';
    const CSF_DIVERSION = 'csf_diversion';
    const SINGULAR_CHECKBOX_FIELD = 'singular_checkbox';

    const DEMOGRAPHICS_FORM = 'demographics';
    const DEMOGRAPHICS_LABEL = 'Demographics';
    const CONSENT_FORM = 'consent_form';
    const CONSENT_LABEL = 'Consent Form';
    const HISTORY_FORM = 'history_general';
    const HISTORY_LABEL = 'History: General';
    const SINGULAR_CHECKBOX_FORM = 'singular_checkbox';
    const SINGULAR_CHECKBOX_LABEL = 'Singular Checkbox';


    const TEST_RCD_0_ID = 0;
    const TEST_RCD_1_ID = 1;
    const TEST_RCD_2_ID = 2;
    const TEST_RCD_3_ID = 3;
    const TEST_RCD_4_ID = 4;
    const TEST_RCD_5_ID = 5;
    const TEST_RCD_6_ID = 6;
    const INVALID_TEST_RCD_ID = 99;

    const ALL_VALID_RCD_IDS = array (
        self::TEST_RCD_1_ID,
        self::TEST_RCD_2_ID,
        self::TEST_RCD_3_ID,
        self::TEST_RCD_4_ID,
        self::TEST_RCD_5_ID,
        self::TEST_RCD_6_ID
    );

    const TEST_EVENT_A = 'initial_visit_arm_1';
    const TEST_EVENT_B = 'annual_visit_1_arm_1';
    const TEST_EVENT_C = 'annual_visit_2_arm_1';

    const ALL_VALID_EVENTS = array(
        self::TEST_EVENT_A,
        self::TEST_EVENT_B,
        self::TEST_EVENT_C
    );

    const TEST_PROJECT = array (
        'project_id' => 8626,
        'project_title' => 'testProjectTitle',
        'creation_time' => '2016-09-29 11:55:49',
        'surveys_enabled' => 1,
        'is_longitudinal' => 1,
        'record_autonumbering_enabled' => 1
    );

    const TEST_INSTRUMENTS = array(
        array (
            'instrument_name' => self::DEMOGRAPHICS_FORM,
            'instrument_label' => self::DEMOGRAPHICS_LABEL
        ),
        array(
            'instrument_name' => self::CONSENT_FORM,
            'instrument_label' => self::CONSENT_LABEL
        ),
        array(
            'instrument_name' => self::HISTORY_FORM,
            'instrument_label' => self::HISTORY_LABEL
        ),
        array(
            'instrument_name' => self::SINGULAR_CHECKBOX_FORM,
            'instrument_label' => self::SINGULAR_CHECKBOX_LABEL
        )
    );

    const TEST_EVENTS = array(
        array(
            'event_name' => 'initial_visit',
            'arm_num' => 1,
            'unique_event_name' => self::TEST_EVENT_A
        ),
        array(
            'event_name' => 'annual_visit_1',
            'arm_num' => 1,
            'unique_event_name' => self::TEST_EVENT_B
        ),
        array(
            'event_name' => 'annual_visit_2',
            'arm_num' => 1,
            'unique_event_name' => self::TEST_EVENT_C
        )
    );

    const TEST_INSTRUMENT_EVENT_MAP = array(
        array(
            'arm_num' => 1,
            'unique_event_name' => self::TEST_EVENT_A,
            'form' => self::DEMOGRAPHICS_FORM
        ),
        array(
            'arm_num' => 1,
            'unique_event_name' => self::TEST_EVENT_A,
            'form' => self::CONSENT_FORM
        ),
        array(
            'arm_num' => 1,
            'unique_event_name' => self::TEST_EVENT_A,
            'form' => self::HISTORY_FORM
        ),
        array(
            'arm_num' => 1,
            'unique_event_name' => self::TEST_EVENT_B,
            'form' => self::HISTORY_FORM
        ),
        array(
            'arm_num' => 1,
            'unique_event_name' => self::TEST_EVENT_C,
            'form' => self::HISTORY_FORM
        )
    );

    const TEST_FIELD_NAMES = array(
        array(
            'original_field_name' => self::RCD_ID_FIELD,
            'choice_value' => '',
            'export_field_name' => self::RCD_ID_FIELD
        ),
        array(
            'original_field_name' => self::EMAIL_FIELD,
            'choice_value' => '',
            'export_field_name' => self::EMAIL_FIELD
        ),
        array(
            'original_field_name' => self::ADDRESS_FIELD,
            'choice_value' => '',
            'export_field_name' => self::ADDRESS_FIELD
        ),
        array(
            'original_field_name' => self::PHONE_FIELD,
            'choice_value' => '',
            'export_field_name' => self::PHONE_FIELD
        ),
        array(
            'original_field_name' => self::RELATIONSHIP_FIELD,
            'choice_value' => '',
            'export_field_name' => self::RELATIONSHIP_FIELD
        ),
        array(
            'original_field_name' => self::FULLNAME_FIELD,
            'choice_value' => '',
            'export_field_name' => self::FULLNAME_FIELD
        ),
        array(
            'original_field_name' => self::SHORTNAME_FIELD,
            'choice_value' => '',
            'export_field_name' => self::SHORTNAME_FIELD
        ),
        array(
            'original_field_name' => self::DATEOFBIRTH_FIELD,
            'choice_value' => '',
            'export_field_name' => self::DATEOFBIRTH_FIELD
        ),
        array(
            'original_field_name' => self::RACE_FIELD,
            'choice_value' => '1',
            'export_field_name' => self::RACE_FIELD.'___1'
        ),
        array(
            'original_field_name' => self::RACE_FIELD,
            'choice_value' => '2',
            'export_field_name' => self::RACE_FIELD.'___2'
        ),
        array(
            'original_field_name' => self::RACE_FIELD,
            'choice_value' => '3',
            'export_field_name' => self::RACE_FIELD.'___3'
        ),
        array(
            'original_field_name' => self::RACE_FIELD,
            'choice_value' => '4',
            'export_field_name' => self::RACE_FIELD.'___4'
        ),
        array(
            'original_field_name' => self::RACE_FIELD,
            'choice_value' => '5',
            'export_field_name' => self::RACE_FIELD.'___5'
        ),
        array(
            'original_field_name' => self::RACE_FIELD,
            'choice_value' => '777',
            'export_field_name' => self::RACE_FIELD.'___777'
        ),
        array(
            'original_field_name' => self::RACE_FIELD,
            'choice_value' => '888',
            'export_field_name' => self::RACE_FIELD.'___888'
        ),
        array(
            'original_field_name' => self::RACE_FIELD,
            'choice_value' => '999',
            'export_field_name' => self::RACE_FIELD.'___999'
        ),
        array(
            'original_field_name' => self::CONSENT_FIELD,
            'choice_value' => '',
            'export_field_name' => self::CONSENT_FIELD
        ),
        array(
            'original_field_name' => self::MISSING_DATA_FIELD,
            'choice_value' => '',
            'export_field_name' => self::MISSING_DATA_FIELD
        ),
        array(
            'original_field_name' => self::PAST_ILLNESS_FIELD,
            'choice_value' => '',
            'export_field_name' => self::PAST_ILLNESS_FIELD
        ),
        array(
            'original_field_name' => self::SHUNT_FIELD,
            'choice_value' => '',
            'export_field_name' => self::SHUNT_FIELD
        ),
        array(
            'original_field_name' => self::CSF_DIVERSION,
            'choice_value' => '1',
            'export_field_name' => self::CSF_DIVERSION.'___1'
        ),
        array(
            'original_field_name' => self::CSF_DIVERSION,
            'choice_value' => '2',
            'export_field_name' => self::CSF_DIVERSION.'___2'
        ),
        array(
            'original_field_name' => self::CSF_DIVERSION,
            'choice_value' => '3',
            'export_field_name' => self::CSF_DIVERSION.'___3'
        ),
        array(
            'original_field_name' => self::CSF_DIVERSION,
            'choice_value' => '4',
            'export_field_name' => self::CSF_DIVERSION.'___4'
        ),
        array(
            'original_field_name' => self::SINGULAR_CHECKBOX_FIELD,
            'choice_value' => '1',
            'export_field_name' => self::SINGULAR_CHECKBOX_FIELD.'___1'
        )
    );

    const TEST_METADATA_RECORDID = array(
        array(
            'field_name' => self::RCD_ID_FIELD,
            'form_name' => self::DEMOGRAPHICS_FORM,
            'field_type' => 'text',
            'branching_logic' => '',
            'field_note' => '',
            'required_field' => '',
            'field_label' => 'Record ID'
        )
    );

    const TEST_METADATA_DEMOGRAPHICS = array(
        array(
            'field_name' => self::EMAIL_FIELD,
            'form_name' => self::DEMOGRAPHICS_FORM,
            'field_type' => 'text',
            'field_label' => 'Email Address:',
            'field_note' => '',
            'branching_logic' => '',
            'required_field' => ''
            ),
        array(
            'field_name' => self::ADDRESS_FIELD,
            'form_name' => self::DEMOGRAPHICS_FORM,
            'field_type' => 'text',
            'field_label' => 'Address:',
            'field_note' => '',
            'branching_logic' => '',
            'required_field' => ''
        ),
        array(
            'field_name' => self::PHONE_FIELD,
            'form_name' => self::DEMOGRAPHICS_FORM,
            'field_type' => 'text',
            'field_label' => 'Phone Number:',
            'field_note' => '',
            'branching_logic' => '',
            'required_field' => ''
        ),
        array(
            'field_name' => self::RELATIONSHIP_FIELD,
            'form_name' => self::DEMOGRAPHICS_FORM,
            'field_type' => 'radio',
            'field_label' => 'What is your relationship to the person?',
            'select_choices_or_calculations' => '1, Self | 2, Parent | 3, Spouse/Partner | 4, Sibling | 5, Other Relative | 6, Caregiver',
            'field_note' => '',
            'branching_logic' => '',
            'required_field' => ''
        ),
        array(
            'field_name' => self::SHORTNAME_FIELD,
            'form_name' => self::DEMOGRAPHICS_FORM,
            'field_type' => 'text',
            'field_label' => 'How should we refer to the person with CP?',
            'field_note' => 'E.g. \'Will\' or \'Susan\'',
            'branching_logic' => '',
            'required_field' => ''
        ),
        array(
            'field_name' => self::DATEOFBIRTH_FIELD,
            'form_name' => self::DEMOGRAPHICS_FORM,
            'field_type' => 'text',
            'field_label' => 'Date of Birth',
            'field_note' => '',
            'branching_logic' => '',
            'required_field' => '',
        ),
        array(
            'field_name' => self::RACE_FIELD,
            'form_name' => self::DEMOGRAPHICS_FORM,
            'field_type' => 'checkbox',
            'field_label' => 'Race',
            'select_choices_or_calculations' => '1, American Indian or Alaska Native | 2, Asian | 3, Black or African American | 4, Native Hawaiian or Other Pacific Islander | 5, White | 777, Refused to Answer | 888, Unknown | 999, Not Reported',
            'field_note' => '',
            'branching_logic' => '',
            'required_field' => ''
        ),
        array(
            'field_name' => self::MISSING_DATA_FIELD,
            'form_name' => self::DEMOGRAPHICS_FORM,
            'field_type' => 'text',
            'field_label' => 'Missing data test field',
            'field_note' => '',
            'branching_logic' => '',
            'required_field' => ''
        )
    );

    const TEST_METADATA_CONSENT = array(
        array(
            'field_name' => self::FULLNAME_FIELD,
            'form_name' => self::CONSENT_FORM,
            'field_type' => 'text',
            'field_label' => 'Full Name',
            'field_note' => 'E.g. Jane Mary Smith',
            'branching_logic' => '',
            'required_field' => ''
        ),
        array(
            'field_name' => self::CONSENT_FIELD,
            'form_name' => self::CONSENT_FORM,
            'field_type' => 'yesno',
            'field_label' => 'Do you consent to participate?',
            'field_note' => '',
            'branching_logic' => '',
            'required_field' => '',
            )
    );

    const TEST_METADATA_HISTORY = array(
        array(
            'field_name' => self::PAST_ILLNESS_FIELD,
            'form_name' => self::HISTORY_FORM,
            'field_type' => 'text',
            'field_label' => 'Past Illness',
            'field_note' => '',
            'branching_logic' => '',
            'required_field' => ''
        ),
        array(
            'field_name' => self::SHUNT_FIELD,
            'form_name' => self::HISTORY_FORM,
            'field_type' => 'yesno',
            'field_label' => 'Does [name_short] also have a hydrocephalus?',
            'field_note' => '',
            'branching_logic' => '',
            'required_field' => ''
        ),
        array(
            'field_name' => self::CSF_DIVERSION,
            'form_name' => self::HISTORY_FORM,
            'field_type' => 'checkbox',
            'field_label' => 'What type of cerebrospinal fluid diversion does [name_short] have?',
            'select_choices_or_calculations' => '1, Shunt | 2, ETV (Endoscopic Third Ventriculostomy) | 3, ETV/CPC (ETV plus Choroid Plexus Coagulation) | 4, Unknown',
            'field_note' => '',
            'branching_logic' => '[shunt_yn] = \'1\'',
            'required_field' => ''
        )
    );

    const TEST_METADATA_SINGULAR_CHECKBOX = array(
        array(
            'field_name' => self::SINGULAR_CHECKBOX_FIELD,
            'form_name' => self::SINGULAR_CHECKBOX_FORM,
            'field_type' => 'checkbox',
            'field_label' => 'A Singular checkbox / toggle',
            'select_choices_or_calculations' => '1, Is True',
            'field_note' => '',
            'branching_logic' => '',
            'required_field' => ''
        )
    );

    /**
     * @var array An array of Field to potential field values
     *
     * Include blanks for each field so there is a 50/50 chance of getting an empty value.
     */
    static $fieldDataMap = array(
        self::EMAIL_FIELD => array (
            'test@foo.com',
            'test@bar.org',
            'bob@foo.co',
            '',
            '',
            ''
        ),
        self::ADDRESS_FIELD => array (
            '123 Main St',
            '321 High St',
            '987 maple st',
            '',
            '',
            ''
        ),
        self::PHONE_FIELD => array (
            '(212) 555-1212',
            '(212) 555-4614',
            '(212) 555-9988',
            '',
            '',
            ''
        ),
        self::RELATIONSHIP_FIELD => array (
            'Self',
            'Parent',
            'Gardian',
            'Caregiver',
            '',
            '',
            ''
        ),
        self::SHORTNAME_FIELD => array (
            '',
            '',
            'Sally',
            '',
            'Larry'
        ),
        self::DATEOFBIRTH_FIELD => array (
            '',
            '',
            '',
            '2018-04-01'
        ),
        self::RACE_FIELD => array (
            array (
                self::RACE_FIELD.'___1' => '1',
                self::RACE_FIELD.'___2' => '0',
                self::RACE_FIELD.'___999' => '1',
            ),
            array (
                self::RACE_FIELD.'___1' => '0',
                self::RACE_FIELD.'___3' => '0',
                self::RACE_FIELD.'___999' => '1',
            ),
            array (
                self::RACE_FIELD.'___4' => '1',
                self::RACE_FIELD.'___5' => '0'
            ),
            array (
                self::RACE_FIELD.'___1' => '0',
                self::RACE_FIELD.'___2' => '1'
            ),
        ),
        self::MISSING_DATA_FIELD => array (
            ''
        ),
        self::FULLNAME_FIELD => array (
            'Robert Smith',
            'Loretta Jones',
            'Haily Johnson',
            'Michael Levion',
            '',
            '',
            '',
            ''
        ),
        self::CONSENT_FIELD => array (
            '',
            '',
            'n',
            'y'
        ),
        self::PAST_ILLNESS_FIELD => array (
            '',
            'Mumps',
            'Chickenpox',
            'Measles',
            ''
        ),
        self::SHUNT_FIELD => array (
            '1',
            '0',
            '',
            '1'
        ),
        self::CSF_DIVERSION => array (
            array (
                self::CSF_DIVERSION.'___1' => '1',
                self::CSF_DIVERSION.'___2' => '0',
                self::CSF_DIVERSION.'___3' => '0',
                self::CSF_DIVERSION.'___4' => '0'
            ),
            array (
                self::CSF_DIVERSION.'___1' => '0',
                self::CSF_DIVERSION.'___2' => '1',
                self::CSF_DIVERSION.'___3' => '0',
                self::CSF_DIVERSION.'___4' => '0'
            ),
            array (
                self::CSF_DIVERSION.'___1' => '0',
                self::CSF_DIVERSION.'___2' => '0',
                self::CSF_DIVERSION.'___3' => '1',
                self::CSF_DIVERSION.'___4' => '0'
            ),
            array (
                self::CSF_DIVERSION.'___1' => '0',
                self::CSF_DIVERSION.'___2' => '0',
                self::CSF_DIVERSION.'___3' => '0',
                self::CSF_DIVERSION.'___4' => '1'
            ),
        ),
    );

    /**
     * @var array A map of Form to Fields
     */
    static $formToFieldsMap = array(
        self:: DEMOGRAPHICS_FORM => array (
            self::RCD_ID_FIELD,
            Record::REDCAP_EVENT_NAME,
            self::EMAIL_FIELD,
            self::ADDRESS_FIELD,
            self::PHONE_FIELD,
            self::RELATIONSHIP_FIELD,
            self::SHORTNAME_FIELD,
            self::DATEOFBIRTH_FIELD,
            self::RACE_FIELD,
            self::MISSING_DATA_FIELD
        ),
        self::CONSENT_FORM => array (
            self::FULLNAME_FIELD,
            self::CONSENT_FIELD
        ),
        self::HISTORY_FORM => array (
            self::PAST_ILLNESS_FIELD,
            self::SHUNT_FIELD,
            self::CSF_DIVERSION
        ),
        self::SINGULAR_CHECKBOX_FORM => array (
            self::SINGULAR_CHECKBOX_FIELD
        )
    );

    public static function getTestMetadata($forms = array()): array {
        $result = array();

        if (empty($forms)) {
            $result = array_merge(self::TEST_METADATA_RECORDID, self::TEST_METADATA_DEMOGRAPHICS, self::TEST_METADATA_CONSENT, self::TEST_METADATA_HISTORY, self::TEST_METADATA_SINGULAR_CHECKBOX);
        }
        else {
            $result = array_merge(self::TEST_METADATA_RECORDID);

            if (in_array(self::DEMOGRAPHICS_FORM, $forms)) {
                $result = array_merge($result, self::TEST_METADATA_DEMOGRAPHICS);
            }

            if (in_array(self::CONSENT_FORM, $forms)) {
                $result = array_merge($result, self::TEST_METADATA_CONSENT);
            }

            if (in_array(self::HISTORY_FORM, $forms)) {
                $result = array_merge($result, self::TEST_METADATA_HISTORY);
            }

            if (in_array(self::SINGULAR_CHECKBOX_FORM, $forms)) {
                $result = array_merge($result, self::TEST_METADATA_SINGULAR_CHECKBOX);
            }
        }

        return $result;
    }


    protected static $nextRcdId = 1;

    /** @var RedCapProject */
    protected $stubRedcapProj;

    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUpBeforeClass()
     */
    public static function setUpBeforeClass(): void
    {
        // Use the stub logger so no logging is actually done
        Logger::setLogger(StubLogger::class);
    }


    protected static function getCurrentRcdId(): string {
        return 'ID_X_'. self::$nextRcdId;
    }

    protected static function getNextRcdId(): string {
        self::$nextRcdId++;

        return self::getCurrentRcdId();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupStubProject();
    }

    protected function tearDown(): void {
        $this->stubRedcapProj = null;

        parent::tearDown();
    }


    private $mockCallbacks = array();

    // TODO: Add support for with() callback, i.e. setWillCallback(), setWithCallback()
    public function setCallback(string $method, callable $callback) {
        if (!isset($this->mockCallbacks[$method])) {
            $this->stubRedcapProj->method($method)->will($this->returnCallback(function() use ($method) {
                $callbackFunc = $this->mockCallbacks[$method];
                return call_user_func_array($callbackFunc, func_get_args());
            } ) );
        }

        $this->mockCallbacks[$method] = $callback;
    }

    /**
     * Setup the stub REDCap project used for testing.
     *
     * Initialize default responses.
     */
    private function setupStubProject() {
        // create a mock REDCapProject instance
        $this->stubRedcapProj = $this->createMock(RedCapProject::class);

        // setup default return values
        $this->setCallback('exportRedcapVersion',       function() { return '9.5.23'; });
        $this->setCallback('getRecordIdFieldName',      function() { return self::RCD_ID_FIELD; });
        $this->setCallback('generateNextRecordName',    function() { return self::getNextRcdId(); } );
        $this->setCallback('exportProjectInfo',         function() { return self::TEST_PROJECT; } );
        $this->setCallback('exportEvents',              function() { return self::TEST_EVENTS; } );
        $this->setCallback('exportFieldNames',          function() { return self::TEST_FIELD_NAMES; } );
        $this->setCallback('exportInstrumentEventMappings', function() { return self::TEST_INSTRUMENT_EVENT_MAP; } );

        $this->setCallback('exportMetadata',            function($format = 'php', $fields = array(), $forms = array()) {
            $result = array();

            $allMetadata = self::getTestMetadata();

            if (empty($fields) && empty($forms)) {
                $result = $allMetadata;
            }
            else {
                foreach ($allMetadata as $field) {
                    if (in_array($field['field_name'], $fields) || in_array($field['form_name'], $forms)) {
                        $result[] = $field;
                    }
                }
            }

            return $result;
        } );

        $this->setCallback('exportInstruments', function() {
            $instMap = array();

            // convert to how exportInstruments returns the data
            foreach (self::TEST_INSTRUMENTS as $instrument) {
                $instMap[$instrument['instrument_name']] = $instrument['instrument_label'];
            }

            return $instMap;
        } );

        $this->setCallback(
            'exportRecords',
            function(
                $format = 'php',
                $type = 'flat',
                $recordIds = null,
                $fields = null,
                $forms = null,
                $events = null
/*
                $filterLogic = null,
                $rawOrLabel = 'raw',
                $rawOrLabelHeaders = 'raw',
                $exportCheckboxLabel = false,
                $exportSurveyFields = false,
                $exportDataAccessGroups = false,
                $dateRangeBegin = null,
                $dateRangeEnd = null,
                $csvDelimiter = ',',
                $decimalCharacter = null
*/
                )
            {
                return $this->generateRedcapRecord($recordIds, $fields, $forms, $events);
            }
            );


        // Setup a mock connection for the project
        $mockConnection = $this->createMock(RedCapApiConnectionInterface::class);
        $mockConnection->method('getUrl')->willReturn('https://redcap.test.co/redcap/api');

        $this->stubRedcapProj->method('getConnection')->willReturn($mockConnection);
    }

    protected function useClassicProject() {
        $this->setCallback('exportEvents', function() { return null; } );
    }

    /**
     * Generate a REDCap record containing a random selection of data from
     * the $fieldDataMap.
     *
     * Similar to the REDCap exportData function, this method will honor a
     * request for specific records, fields and/or fields and events. If any
     * of these parameters are null then all of the values are included.
     * <p>
     * If $fields and $forms are both specified, all of the fields for the
     * specified $forms will be included plus any others listed in $fields.
     * <p>
     * The $requireDataFields array can specify a list of fields which must
     * contain data, i.e. not be blank as they might otherwise be generated as.
     *
     * @param array $recordIds An array of record Ids to include, or all
     *      records if not specified.
     * @param array $fields An array of fields to include, or all fields if
     *      not specified (unless $forms is specified).
     * @param array $forms An array of forms to use. All fields from these
     *      forms will be included regardless of the contents of $fields.
     * @param array $events An array of events to include data for.
     * @param array $requireDataFields An array of field names for which data
     *      should be included, i.e. no blank values.
     *
     * @return array A REDCap record structure
     */
    protected function generateRedcapRecord(?array $recordIds = null, ?array $fields = null, ?array $forms = null, ?array $events = null, array $requireDataFields = array()): array {
        $result = array();
        $fieldsToUse = array();
        $eventsToUse = array();
        $projUsesEvents = !empty($this->stubRedcapProj->exportEvents());

        // Forms have precedence, add all the form fields to the list.
        if (isset($forms)) {
            foreach ($forms as $form) {
                if (array_key_exists($form, self::$formToFieldsMap)) {
                    $fieldsToUse = array_merge($fieldsToUse, self::$formToFieldsMap[$form]);
                }
            }
        }

        // Add any fields requested that aren't already in the list.
        if (isset($fields)) {
            foreach ($fields as $field) {
                // if it's a field we have data for AND it's not already in the list
                if (array_key_exists($field, self::$fieldDataMap) && !in_array($field, $fieldsToUse))
                {
                    $fieldsToUse[] = $field;
                }
            }
        }

        if (empty($fieldsToUse)) {
            // Use all of the fields
            $fieldsToUse = array_merge(array(self::RCD_ID_FIELD, Record::REDCAP_EVENT_NAME), array_keys(self::$fieldDataMap));
        }

        // We always include the record id
        if (!in_array(self::RCD_ID_FIELD, $fieldsToUse)) {
            $fieldsToUse[] = self::RCD_ID_FIELD;
        }
        // We always include the event name if it's a multi-event project
        if ($projUsesEvents && !in_array(Record::REDCAP_EVENT_NAME, $fieldsToUse)) {
            $fieldsToUse[] = Record::REDCAP_EVENT_NAME;
        }

        // If no record ids were requested include all the records
        if (!isset($recordIds) || empty($recordIds)) {
            $recordIds = self::ALL_VALID_RECORD_IDS;
        }
        else {
            // Otherwise use all the valid record ids
            $recordIds = array_intersect(self::ALL_VALID_RCD_IDS, $recordIds);
        }

        if ($projUsesEvents) {
            if (isset($events)) {
                $eventsToUse = array_intersect(self::ALL_VALID_EVENTS, $events);
            }
            else {
                $eventsToUse = static::ALL_VALID_EVENTS;
            }
        }
        else {
            $eventsToUse = array('classic_event');   // dummy event so we run through the loop below once
        }

        foreach ($recordIds as $rcdId) {
            foreach ($eventsToUse as $event) {
                $entry = array();

                foreach ($fieldsToUse as $field) {
                    switch ($field) {
                        case self::RACE_FIELD:
                            $raceEntry = self::$fieldDataMap[self::RACE_FIELD][rand(0, count(self::$fieldDataMap[$field]) - 1)];
                            foreach ($raceEntry as $raceField => $raceValue) {
                                $entry[$raceField] = $raceValue;
                            }
                            break;

                        case self::CSF_DIVERSION:
                            $csfEntry = self::$fieldDataMap[self::CSF_DIVERSION][rand(0, count(self::$fieldDataMap[$field]) - 1)];
                            foreach ($csfEntry as $csfField => $csfValue) {
                                $entry[$csfField] = (1 === (int)$entry[self::SHUNT_FIELD]) ? $csfValue : '0';
                            }
                            break;

                        case self::RCD_ID_FIELD:
                            $entry[$field] = $rcdId;
                            break;

                        case Record::REDCAP_EVENT_NAME:
                            if ($projUsesEvents) {
                                $entry[$field] = $event;
                            }
                            break;

                        default:
                            // pick a random value for the field, ensuring its not blank if the field is in $requireDataFields
                            do {
                                $entry[$field] = self::$fieldDataMap[$field][rand(0, count(self::$fieldDataMap[$field]) - 1)];
                            } while (empty($entry[$field]) && in_array($field, $requireDataFields));
                            break;
                    }
                }
                $result[] = $entry;
            }
        }

        return $result;
    }


    protected function createInstrument(string $instrumentName, bool $withEvents = true) {
        $testObj = null;

        $instruments = $this->stubRedcapProj->exportInstruments();

        foreach ($instruments as $instName => $instLabel) {
            if ($instName === $instrumentName) {
                $fieldNames = $this->stubRedcapProj->exportFieldNames();
                $metadata = $this->stubRedcapProj->exportMetadata();
                $eventMappings = $withEvents ? $this->stubRedcapProj->exportInstrumentEventMappings() : array();

                $testObj = new Instrument($instName, $instLabel, $fieldNames, $metadata, $eventMappings);
                break;
            }
        }

        return $testObj;
    }

    protected function createDummyInstrument(string $prefix) {
        return new Instrument ($prefix . 'Name', $prefix . 'Label', array(), array());
    }
}
