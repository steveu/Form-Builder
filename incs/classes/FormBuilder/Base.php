<?php

/**
 *
 * Displays, Validates and Emails a form
 * @author steve [at] clearbar [fullstop] org
 *
 */
class FormBuilder_Base
{

    /* -----------------------------------------------
    Properties

        Any properties can be overidden by passing the
        new value in array to constructor
    -------------------------------------------------- */

    // Form Attributes
    protected $action = '';
    protected $method = 'post';
    protected $id = false;
    protected $class = false;

    // If Form should use Spam Checking
    protected $spamCheck = true;

    // HTTP Referrer
    protected $referrer = '';

    // Fieldset Array, format as below
    /*
        'fieldset_id' => array(
            'legend'    =>  'Fieldset Title',
            'fields'    =>  array(
                'field_name'  =>  array( 'label'=>'Your Name', 'req'=>true )
        ),
    */
    protected $fieldsets = array();

    // Tag to Wrap around Fields (<tag><label>+<input></tag>)
    protected $fieldWrap = '<p>$$</p>';

    // Array of grouped fields, one from group must be filled
    // Useful for checkbox groups where 1 must be true
    var $groups = array();

    // Use Table for Form Layout
    protected $useTable = false;

    // Form Title, Heading Above Form + Email
    protected $title = false;
    protected $showTitle = true;

    // Wrap Form Title In:
    protected $titleWrap = '<h3>$$</h3>';

    // Submit Button
    protected $buttonTitle = 'Submit';
    protected $buttonHTML = '<button type="submit" name="submit" value="true">$$</button>';
    protected $showButton = true;

    
    // Tag to Wrap Fieldset Legend in
    protected $legendWrap = '<legend>$$</legend>';

    // Introduction paragraph for form
    protected $intro = '';

    // Where to Redirect if Successful Process
    public $redirect = '/thanks/';

    // Empty Error Array
    protected $errors = array();

    // ID of Email Template in DB
    protected $emailTemplateId = false;

    protected $emailReplaceFields = array();

    // Array of Admin Email parameters
    protected $adminEmail = array(
        'fromName'  => 'Website',
        'fromEmail' => 'website@',
        'toName'    => 'Steve',
        'toEmail'   => 'steve@designition.co.uk',
    );

    // If Short Error Messages should be shown with fields (e.g. ! Required Field)
    protected $showShortErrors = false;

    // If Long Error Messages Block should be shown (e.g. 1. username required, 2. password required)
    protected $showLongErrors = true;

    // Position of the Required *
    // Possible values: false, label_start, label_end, after_input
    protected $starPosition = 'label_start';

    // HTML to use for star indicator
    protected $starHTML = '<em class="req">*</em>';

    // HTML to use for star indicator
    protected $labelEnd = ':';

    // Extra HTML to add just before ending form tag
    protected $footerHTML = '';

    // Set Some Default Values (can be overidden in form config)
    protected $defaults = array(

        'max_chars' =>  array(
            'text'      =>  250,
            'hidden'    =>  250,
            'password'  =>  50,
            'textarea'  =>  1500,
            'checkbox'  =>  50,
            'radio'     =>  50,
            'select'    =>  250,
            'special'   =>  500,
        ),

        'error_msgs'=>  array(

            'required'  =>  array(
                'form'  => '<strong>{LABEL}</strong> is a required field and was left blank',
                'field' => 'Required Field',
            ),
            'checkbox_required' => array(
                'form'  => 'You must select <strong>{LABEL}</strong>',
                'field' => 'Required Field',
            ),
            'too_long'  =>  array(
                'form'  =>  '<strong>{LABEL}</strong> is too long (Maximum {MAX} characters)',
                'field' =>  'Too Long',
            ),
            'too_short'  =>  array(
                'form'  =>  '<strong>{LABEL}</strong> is too short (Minimum {MIN} characters)',
                'field' =>  'Too Long',
            ),
            'too_many_words'  =>  array(
                'form'  =>  '<strong>{LABEL}</strong> has too many words (Maximum {WORDS} words)',
                'field' =>  'Too many words',
            ),
            'invalid'   =>  array(
                'form'  =>  '<strong>{LABEL}</strong> is invalid',
                'field' =>  'Invalid',
            ),
            'spam'   =>  array(
                'form'  =>  '<strong>{LABEL}</strong> looks too much like spam',
                'field' =>  'Spam',
            ),

            'no_match'  =>  array(
                'form'  =>  '<strong>{LABEL}</strong> must match {MATCH}',
                'field' =>  'Must Match',
            ),
            'in_use'  =>  array(
                'form'  =>  '<strong>{LABEL}</strong> is already being used',
                'field' =>  'In Use',
            ),
        ),

    );

    // Flag if form successfully process (can show message rather than redirect)
    public $isComplete = false;

    // Completed Message
    protected $completedMessage = false;

    // Should the form show again after the completed message
    protected $showFormAfterComplete = false;



    /**
     * Constructor: config => properties, fields => properties + (clean) values
     *
     * @param array $config
     * @author steve
     * @todo throw exception if non-existent property
     */
    public function __construct($config, $defaults=array())
    {

        // Required attributes
        if (!isset($config['id'])) {
            die('ID is a required config variable');
        }

        // loop defaults attributes
        foreach($defaults AS $k => $v) {

            // Merge any Defaults passed (must be array passed)
            if ($k == 'defaults' && is_array($v)) {

                // merge multidimensional defaults arrays, preserving distinct values
                $this->defaults = $this->array_merge_recursive_distinct($this->defaults, $v);
            }

            // All other overides
            else {
                if (isset($this->$k)) {
                    $this->$k = $v;
                }
            }
        }

        // loop form attributes
        foreach($config AS $k => $v) {

            // Merge any Defaults passed (must be array passed)
            if ($k == 'defaults' && is_array($v)) {

                // merge multidimensional defaults arrays, preserving distinct values
                $this->defaults = $this->array_merge_recursive_distinct($this->defaults, $v);
            }

            // All other overides
            else {
                if (isset($this->$k)) {
                    $this->$k = $v;
                }
            }
        }

        // Assign values ($_POST || $_GET)
        $this->assignValues();

        // Add extra class title if only 1 fieldset
        if (count($this->fieldsets) == 1) {

            if ($this->class) {
                $this->class = $this->class . ' single';
            }
            else {
                $this->class = 'single';
            }
        }


        return true;
    }


    /**
     * Shorthand to process a form
     *
     * @return boolean
     * @author steve
     */
    public function autoProcess()
    {
        if ($this->submitted()) {

            if ($this->validate()) {

                if ($this->process()) {

                    // Set completed flag
                    if($this->markCompleted()) {
                        return true;
                    }
                }
            }
        }
    }

    public function redirect()
    {
        header("Location: " . $this->redirect);
    }

    /**
     * Returns the config array of a particular field
     *
     * @param string $setKey
     * @param string $fieldKey
     * @return array
     * @author steve
     */
    protected function getField($setKey, $fieldKey)
    {
        $field = $this->fieldsets[$setKey]['fields'][$fieldKey];
        return $field;
    }

    /**
     * Returns the value of a particular field
     *
     * @param string $setKey
     * @param string $fieldKey
     * @return array
     * @author steve
     */
    public function getFieldValue($setKey, $fieldKey)
    {
        $value = $this->fieldsets[$setKey]['fields'][$fieldKey]['value'];
        return $value;
    }


    /**
     * Returns the correct method array of user submitted values
     *
     * @return array GET/POST
     * @author steve
     */
    protected function getMethodArray()
    {
        if ($this->method == 'get') {
            return $_GET;
        }
        else {
            return $_POST;
        }
    }

    /**
     * Checks if the form has been submitted
     * @return boolean
     * @author steve
     */
    public function submitted()
    {
        $valueArray = $this->getMethodArray();

        // Check a hidden input for formID (auto handled, but why the form requires an ID
        if (!empty($valueArray) && $valueArray['formID'] == $this->id) {
            return true;
        }

        return false;
    }


    /**
     * Assigns values to 'fieldsets' property (if values are available)
     * @author steve
     */
    protected function assignValues()
    {

        $valueArray = $this->getMethodArray();

        // If Values, Assign
        if (!empty($valueArray)) {

            foreach($this->fieldsets AS $setKey => $setArray) {

                foreach($setArray['fields'] AS $fieldKey => $fieldArray) {

                    // Assign the Individual Field
                    $this->fieldAssignValue($valueArray, $setKey, $fieldKey, $fieldArray);
                }
            }
        }

    }

    /**
     * Assign values to individual fields (different types)
     *
     * @param string $setKey
     * @param string $fieldKey
     * @param array $fieldArray
     * @author steve
     */
    protected function fieldAssignValue($valueArray, $setKey, $fieldKey, $fieldArray)
    {
        
        // Checkboxes need 'off' as not passed by form
        if ($fieldArray['type']=='checkbox') {

            $cbox_val = (isset($valueArray[$fieldKey])) ? 'on' : 'off';
            $this->fieldsets[$setKey]['fields'][$fieldKey]['value'] = $cbox_val;
        }

        // new line to br for textareas
        elseif ($fieldArray['type']=='textarea') {

            $this->fieldsets[$setKey]['fields'][$fieldKey]['value'] = global_clean($valueArray[$fieldKey]);
        }

        // Date Value Concentration
        elseif ($fieldArray['type']=='date') {
            $dateVal    = $valueArray[$fieldKey . '-year'] . '-'
                . $valueArray[$fieldKey . '-month'] . '-'
                . $valueArray[$fieldKey . '-day'];

            $this->fieldsets[$setKey]['fields'][$fieldKey]['value'] = global_clean($dateVal);
        }

        // Default Simple Field
        elseif (isset($valueArray[$fieldKey])) {

            $this->fieldsets[$setKey]['fields'][$fieldKey]['value'] = global_clean($valueArray[$fieldKey]);
        }

    }

    /**
     * Magic attribute setter
     *
     * @param string $attribute
     * @param various $value
     * @return boolean
     * @author steve
     */
    public function setFormAttribute($attribute, $value)
    {
        if (isset($this->$attribute)) {
            
            $this->$attribute = $value;

            return true;

        } else {
            return false;
        }
    }

    /**
     * Magic attribute setter
     *
     * @param string $fieldset
     * @param string $attribute
     * @param various $value
     * @return boolean
     * @author steve
     */
    public function setFieldsetAttribute($fieldset, $attribute, $value)
    {

        if (isset($this->fieldsets[$fieldset])) {
            
            $this->fieldsets[$fieldset][$attribute] = $value;

            return ture;
        }
        else {
            return false;
        }
    }

    /**
     * Sets an atrribute for a field, e.g. set req=>true
     *
     * @param string $setKey
     * @param string $fieldKey
     * @param string $attribute
     * @param various $value
     * @author steve
     * @todo Probably should error check if attribute exists (or should), and checking what value assigned
     */
    public function setFieldAttribute($setKey, $fieldKey, $attribute, $value)
    {
        $this->fieldsets[$setKey]['fields'][$fieldKey][$attribute] = $value;

        return true;
    }


    /**
     * Build the HTML for a form (needs echoing)
     *
     * @return string $form
     * @author steve
     */
    public function build()
    {

        $form = '';

        if ($this->title && $this->showTitle) {
            $form .= str_replace('$$', $this->title, $this->titleWrap);
        }

        // Show completed message if set
        if($this->isComplete && $this->completedMessage) {
            $form .= $this->showCompletedMessage();
        }

        // Show Error Messages
        elseif ($this->showLongErrors && count($this->errors) > 0) {

            $form .= $this->formErrors();
        }
        // Else show intro paragraph
        elseif (!empty($this->intro)) {
            $form .= "\n" . '<p class="form_intro">' . $this->intro . '</p>';
        }


        // If form is complete, and not showing the form afterward: Return early
        if($this->isComplete && !$this->showFormAfterComplete) {
            return $form;
        }


        $form .= "\n".'<form action="' . $this->action . '" method="' . $this->method . '"';

        if ($this->id != '') {
            $form .= ' id="' . $this->id . '"';
        }
        if ($this->class != '') {
            $form .= ' class="' . $this->class . '"';
        }

        $form .= '>';

        
        
        // Show completed message if set
        //elseif($this->isComplete && $this->completedMessage) {
        //    $form .= $this->showCompletedMessage();
        //}

        

        // Loop Fieldsets
        foreach($this->fieldsets AS $setKey => $setArray) {

            $fieldset = "\n\t".'<fieldset id="' . $setKey . '">';

            // Legend
            if ($setArray['legend']) {

                $legend = $setArray['legend'];

                // if legendWrap , wrap legend in tag
                if ($this->legendWrap != '') {
                    $legend = str_replace('$$', $legend, $this->legendWrap);
                }

                $fieldset .= "\n\t\t" . $legend;
            }

            // Intro Paragraph
            if ($setArray['intro']) {
                $fieldset .= "\n\t\t" . '<p class="set_intro">' . $setArray['intro'] . '</p>';
            }

            // If Using a Table for Layout
            if ($this->useTable) {
                $fieldset .= "\n\t\t" . '<table cellpadding="0" cellspacing="0" class="form">';
            }

            foreach($setArray['fields'] AS $fieldKey => $fieldArray) {

                $field = '';

                // switch based on field type
                switch($fieldArray['type']) {

                    case 'password':
                        $field .= $this->fieldInput($fieldKey, $fieldArray, 'password');
                        break;

                    case 'textarea':
                        $field .= $this->fieldTextarea($fieldKey, $fieldArray);
                        break;

                    case 'checkbox':
                        $field .= $this->fieldCheckbox($fieldKey, $fieldArray);
                        break;

                    case 'radio':
                        $field .= $this->fieldRadio($fieldKey, $fieldArray);
                        break;

                    case 'select':
                        $field .= $this->fieldSelect($fieldKey, $fieldArray);
                        break;

                    case 'date':
                        $field .= $this->fieldDate($fieldKey, $fieldArray);
                        break;

                    case 'hidden':
                        $hiddenInputs .= $this->fieldHidden($fieldKey, $fieldArray);
                        break;

                    case 'special':
                        $functionName = $fieldArray['buildFunction'];
                        $field .= $this->$functionName($fieldKey, $fieldArray);
                        break;

                    default:
                        $field .= $this->fieldInput($fieldKey, $fieldArray, 'text');
                }


                // If Using a Table, wrap in <tr>
                if($this->useTable) {

                    $field = "\n\t\t" . '<tr>' . $field;

                    // Show Short Error Messages
                    if ($this->showShortErrors) {
                        $field .= "\t" . '<td class="err_msg">';
                            if ($fieldArray['error']) {
                                $field .= $fieldArray['error'];
                            }

                        $field .= '</td>' . "\n\t\t";
                    }


                    $field .= '</tr>';
                }

                // Else use wrapper tag around fields (e.g. <p>)
                elseif ($this->fieldWrap != '' && $setArray['noWrap'] != true) {

                    $field  = "\n\t\t" . str_replace('$$', ($field), $this->fieldWrap);
                }


                if ($fieldArray['type'] != 'hidden') { // don't add hidden fields yet

                    $fieldset .= $field; // add field to fieldset
                }
            }

            if ($this->useTable) {
                $fieldset .= "\n\t\t" . '</table>';
            }

            if (isset($setArray['footer'])) {
                $fieldset .= $setArray['footer'];
            }
            $fieldset .= "\n\t" . '</fieldset>';

            $form .= $fieldset; // add fieldset to forn
        }

        // Finish off form
        $form .= $hiddenInputs; // Hidden Inputs

        if ($this->showButton) {
            $form .= "\n\t" . str_replace('$$', $this->buttonTitle, $this->buttonHTML);
        }

        // Hidden form ID input
        $form .= "\n\t" . '<input type="hidden" name="formID" value="' . $this->id . '" />';

        // Extra Footer HTML
        if ($this->footerHTML) {
            $form .= "\n\t" . $this->footerHTML;
        }

        $form .= "\n" . '</form>';

        return $form . "\n";
    }


    /**
     * Master validation function, controls order of validation
     *
     * @param array $post
     * @return boolean
     * @author steve
     */
    public function validate()
    {

        // Check referring site against config list
        if ($this->spamCheck && !$this->checkReferrer()) {
            return false;
        }

        // Check required fields are present
        if (!$this->checkRequired()) {
            return false;
        }

        // Checks Min/Max, Regex and Spam
        if (!$this->checkSpecial()) {
            return false;
        }

        return true;
    }


    /**
     * Standard Process, calls email function
     *
     * @return boolean
     * @author steve
     */
    public function process()
    {

        if ($this->sendEmail()) {
            return true;
        }

    }

    /**
     * Send an email, using DB template or form dump
     *
     * @return boolean
     * @author steve
     */
    public function sendEmail()
    {

        // If Passed an ID, Lookup Template
        if ($this->emailTemplateId) {


            $replaceFields = array();

            // Loop Fields in Array for Possible Replacement
            foreach($this->fieldsets AS $setKey => $setArray) {

                foreach($setArray['fields'] AS $fieldKey => $fieldArray) {

                    if ($fieldArray['emailReplace']) {
                        $replaceFields[$fieldArray['emailReplace']] = $fieldArray['value'];
                    }
                }
            }

            // Add Extra replace fields
            foreach($this->emailReplaceFields AS $key => $val) {
                $replaceFields[$key] = $val;
            }

            $steve = array();
            
            // Return Email Template
            $dbEmail = emails_return($steve, $replaceFields, $this->emailTemplateId);
           

            if($dbEmail) {

                if(emails_send($steve,$dbEmail)) {

                    return true;
                }
            }
        }

        else {

            // Build Email Body
            $email  = 'Someone completed the ' . $this->title . ' on '
                    . CONFIG_SETTINGS_DOMAIN . ' on '. date('d/m/y')
                    . ' at ' . date('h:i A') . "\n";

            // Loop Fields in Email
            $loop = 0;
            foreach($this->fieldsets AS $setKey => $setArray) {

                // If Fieldset Legend, show
                if ($setArray['legend']) {

                    if($loop > 0) $email .= "\n";

                    $email  .=  "\n" . '-------------------------------------------------'
                            .   "\n" . $setArray['legend']
                            .   "\n" . '-------------------------------------------------';
                }

                foreach($setArray['fields'] AS $fieldKey => $fieldArray) {

                    // handle checkboxes differently
                    // always have to handle damn checkboxes differently!!
                    if ($fieldArray['type']=='checkbox') {

                        if ($fieldArray['value']=='on') {
                            $email .= "\n" . $fieldArray['label'];
                        }
                    }
                    else {
                        $email .= "\n" . $fieldArray['label'] . ': ' . $fieldArray['value'];
                    }
                }

                $loop++;
            }

            $email .= "\n\n" . '(Email automatically sent from website)';

            $emailTemplate['email_name'] = $this->adminEmail['fromName'];
            $emailTemplate['email_email'] = $this->adminEmail['fromEmail'] . CONFIG_SETTINGS_COOKIES;

            $emailTemplate['email_title'] = 'Website ' . $this->title . '(' . CONFIG_SETTINGS_DOMAIN . ')';
            $emailTemplate['email_subject'] = $this->title . ' Form Sent';
            $emailTemplate['email_message'] = $email;

            $emailTemplate['email_toname'] = $this->adminEmail['toName'];
            $emailTemplate['email_toemail'] = $this->adminEmail['toEmail'];

            if (emails_send($siteConfig,$emailTemplate)) {

                return true;
            }
        }

        return false;

    }


     /**
     * Checks the referrer against config list
     *
     * @return boolean
     * @author steve
     * @todo Needs testing using CURL
     */
    protected function checkReferrer() {

        $allowed = str_replace('www.', '', CONFIG_ANTISPAM_REFERER);

        $this->referrer = str_replace('www', '', $_SERVER['HTTP_REFERER']);

        $position = strpos($referer, $allowed);

        if($position>0) {

            $this->errors = array(
                'External Access Not Allowed'
            );

            return false;
        }

        return true;
    }


    /**
     * Checks all required fields are set, and not empty
     *
     * @return boolean
     * @author steve
     */
    protected function checkRequired()
    {

        foreach($this->fieldsets AS $setKey => $setArray) {

            foreach($setArray['fields'] AS $fieldKey => $fieldArray) {

                if ($fieldArray['req'] != false) {

                    // handle checkboxes differently
                    if ($fieldArray['type']=='checkbox' && $fieldArray['value']=='off') {

                        $this->generateErrors($setKey, $fieldKey, $fieldArray, 'checkbox_required');
                    }

                    elseif (!isset($fieldArray['value']) || $fieldArray['value']=='') {

                        $this->generateErrors($setKey, $fieldKey, $fieldArray, 'required');
                    }
                }
            }
        }

        // check group fields for 1 match
        if(count($this->groups) > 0) {
            foreach($this->groups AS $num => $groupArray) {

                $oneSet = false;

                // loop group fields
                foreach($groupArray['fields'] AS $fieldKey) {

                    $fieldArray = $this->fieldsets[$groupArray['fieldset']]['fields'][$fieldKey];

                    // handle checkboxes differently
                    if ($fieldArray['type']=='checkbox') {
                        if ($fieldArray['value']=='on') {
                            $oneSet = true;
                            break;
                        }
                    }

                    elseif (isset($fieldArray['value']) && $fieldArray['value'] != '') {
                        $oneSet = true;
                        break;
                    }
                }

                // if none of group are filled (or checked)
                if(!$oneSet) {
                    // Generate global error
                    $this->errors[] = $groupArray['error'];
                }
            }
        }


        if (count($this->errors)> 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Checks the lengths of values against 'min' / 'max',
     * Matches Regex if set,
     * Spam checks if set
     *
     * @return boolean
     * @author steve
     */
    protected function checkSpecial()
    {

        $regex = array(
            'number' => '/^[0-9 .,]{1,200}$/i',
            'username' => '/^[a-z \d_]{3,20}$/i',
            'email' => '/^[^@\s<&>]+@([-a-z0-9]+\.)+[a-z]{2,}$/i',
            'uk_postcode' => '/^([A-PR-UWYZ]([0-9]([0-9]|[A-HJKSTUW])?|[A-HK-Y][0-9]([0-9]|[ABEHMNPRVWXY])?)[0-9][ABD-HJLNP-UW-Z]{2}|GIR0AA)$/',
        );

        $filters = explode('[]', CONFIG_ANTISPAM_CONTAIN);

        foreach($this->fieldsets AS $setKey => $setArray) {

            foreach($setArray['fields'] AS $fieldKey => $fieldArray) {

                if (isset($fieldArray['value'])) {

                    // Check is field is database unique
                    if (is_array($fieldArray['unique'])) {

                        $sql    = 'SELECT ' . $fieldArray['unique']['field'] . ' '
                                . 'FROM ' . $fieldArray['unique']['table'] . ' '
                                . 'WHERE ' . $fieldArray['unique']['field'] . ' = '
                                . '"' . $fieldArray['value'] . '"';

                        if ($this->dbUpdate) {
                           $sql .= ' AND ' . $this->dbUpdate['field'] . ' != '
                                . '"' . $this->dbUpdate['value'] . '"';
                        }

                        $res = mysql_query($sql) or die(mysql_error());
                        $num = mysql_num_rows($res);

                        if ($num > 0) {
                            $this->generateErrors($setKey, $fieldKey, $fieldArray, 'in_use');
                        }

                    }

                    // Check Mix/Max Characters
                    $min = ($fieldArray['min']) ? $fieldArray['min'] : false;
                    $max = ($fieldArray['max']) ? $fieldArray['max'] : $this->defaults['max_chars'][$fieldArray['type']];

                    $length = strlen($fieldArray['value']);

                    if ($length > $max) {

                        $this->generateErrors($setKey, $fieldKey, $fieldArray, 'too_long');
                    }
                    elseif ($min && ($length < $min)) {

                        $this->generateErrors($setKey, $fieldKey, $fieldArray, 'too_short');
                    }
                    
                    // Check Max Word Count (if set)
                    if (isset($fieldArray['max_words'])) {

                        $word_length = $this->word_count($fieldArray['value']);

                        if ($word_length > ($fieldArray['max_words'] + 1)) {
                            $this->generateErrors($setKey, $fieldKey, $fieldArray, 'too_many_words');
                        }
                    }


                     // Check fields which should match
                    if (isset($fieldArray['match'])) {


                        if ($fieldArray['value'] != $setArray['fields'][$fieldArray['match']]['value']) {
                            $this->generateErrors($setKey, $fieldKey, $fieldArray, 'no_match');

                            $this->fieldsets[$setKey]['fields'][$fieldArray['match']]['error'] = 'temp';
                            //$this->fieldsets[$setArray['fields'][$fieldArray['match']]['error'] = true;
                        }
                    }

                    // Check Regex if no other error present
                    if (!isset($fieldArray['error'])) {

                        if (isset($fieldArray['regex']) && array_key_exists($fieldArray['regex'],$regex)) {

                            // Temp variable to test
                            $testValue = $fieldArray['value'];

                            // Optional Transformations
                            if ($fieldArray['regex']=='uk_postcode') {
                                $testValue = strtoupper(str_replace(' ', '', $testValue));
                            }

                            if(!preg_match($regex[$fieldArray['regex']],$testValue)) {

                                $this->generateErrors($setKey, $fieldKey, $fieldArray, 'invalid');
                            }
                        }
                    }

                    // Check field for spam
                    if ($this->spamCheck) {

                        $numFilters = count($filters);

                        for($i = 0; $i < $numFilters; $i++) {

                            $checkVal = strtolower($fieldArray['value']);

                            if (substr_count($checkVal, $filters[$i]) > 0 ) {

                                $this->generateErrors($setKey, $fieldKey, $fieldArray, 'spam');
                            }
                        }
                    }
                }
            }
        }

        if (count($this->errors)> 0) {
            return false;
        }

        return true;
    }

    /**
     * Adds a form field to object after creation
     *
     * @param string $setKey
     * @param string $fieldKey
     * @param array $fieldArray
     * @author steve
     */
    public function addField($setKey, $fieldKey, $fieldArray)
    {

        $valueArray = $this->getMethodArray();

        // Add to Fields array
        $this->fieldsets[$setKey]['fields'][$fieldKey] = $fieldArray;

        // Add the New Field's Value (Could be set)
        if (!empty($valueArray)) {
            $this->fieldAssignValue($valueArray, $setKey, $fieldKey, $fieldArray);
        }
    }

    /**
     * Adds error messages to form, and to fields, based on $type from $defaults
     *
     * @param string $setKey
     * @param string $fieldKey
     * @param array $fieldArray
     * @param string $type
     * @return boolean
     */
    protected function generateErrors($setKey, $fieldKey, $fieldArray, $type)
    {

        if ($fieldArray['error_msgs'] && $fieldArray['error_msgs'][$type]['form']) {
            $form_msg   = $fieldArray['error_msgs'][$type]['form'];
            //$field_msg  = $this->defaults['error_msgs'][$type]['field'];
        }
        else {
            // Default Messages
            $form_msg   = $this->defaults['error_msgs'][$type]['form'];
            $field_msg  = $this->defaults['error_msgs'][$type]['field'];
        }
    
        $find = array(
            '{LABEL}',
            '{MIN}',
            '{MAX}',
            '{MATCH}',
            '{WORDS}',
        );
        $replace = array(
            $fieldArray['label'],
            $fieldArray['min'],
            $fieldArray['max'],
            '<strong>'.$this->fieldsets[$setKey]['fields'][$fieldArray['match']]['label'].'</strong>',
            $fieldArray['max_words'],
        );

        $form_msg   = str_replace($find, $replace, $form_msg);
        $field_msg  = str_replace($find, $replace, $field_msg);

        // Field Message
        $this->fieldsets[$setKey]['fields'][$fieldKey]['error'] = $field_msg;

        // Form Message
        $this->errors[] = $form_msg;

        return true;

    }


    /**
     * Builds a HTML list of errors
     *
     * @return string $errors
     * @author steve
     */
    protected function formErrors()
    {
        $errors = "\n\t".'<div id="formErrors" class="alert error">'
                . "\n\t\t" . '<ul>';

        foreach($this->errors AS $msg) {

            $errors .= "\n\t\t\t".'<li>'.$msg.'</li>';

        }
        $errors .= "\n\t\t".'</ul>' . "\n\t" . '</div>';

        return $errors;
    }

    /**
     * Marks a Form Completed, and Resets values
     *
     */
    public function markCompleted() {

        // unset field values
        foreach($this->fieldsets AS $setKey => $setArray) {

            foreach($setArray['fields'] AS $fieldKey => $fieldArray) {

                $this->setFieldAttribute($setKey, $fieldKey, 'value', '');

            }

        }

        // Unset POST
        $_POST = '';

        // Reassign values (used in other classes
        $this->assignValues();

        // Mark as complete
        $this->isComplete = true;

        return true;
    }
    
    /**
     * Shows a completed message, so page doesn't have to change
     *
     * @return string
     */
    public function showCompletedMessage() {

        $message    = "\n" . '<div class="alert done">'
                    . $this->completedMessage
                    . '</div>';

        return $message;
    }

    /**
     * Builds a label tag
     * Adds an 'error' class if required
     * Adds a * required indicator in different places depending on config
     * May also show a field hint
     *
     * @param string $key
     * @param array $field
     * @return string $label
     * @author steve
     */
    protected function fieldLabel($key, $field)
    {

        // Return early if label=false
        if (!$field['label']) {
            return '';
        }

        // Should a star indicator be shown
        if ($field['req']) {
            $star = $this->starHTML;
        } else {
            $star = '';
        }

        $label = '<label for="' . $key . '"';
        if (isset($field['req'])) {

            if (isset($field['value']) && $field['value']=='') {
                $label .= ' class="error"';
            }
        }
        $label .= '>';
        
        if ($this->starPosition=='label_start') {
            $label .= $star . ' ';
        }
        
        $label .= $field['label'] . $this->labelEnd;

        if ($this->starPosition=='label_end') {
            $label .= ' ' . $star;
        }

        if ($field['hint']) {
            $label .= ' <em class="form_hint">' . $field['hint'] . '</em>';
        }

         // Show Short Error Messages
        if (!$this->useTable && $this->showShortErrors && $field['error']) {

            $label .= ' <strong class="short_error">' . $field['error'] . '</strong>';
        }

        $label .= '</label>';

        if($this->useTable) {
            $label = '<th>' . $label  . '</th>';
        }

        return "\n\t\t\t" . $label;
    }


    /**
     * Builds a Standard Text Input (Plus Password if type='password')
     *
     * @param string $key
     * @param array $field
     * @param string $type
     * @return string $input
     * @author steve
     */
    protected function fieldInput($key, $field, $type)
    {

        $label  = $this->fieldLabel($key, $field);

        $input  = '<input type="' . $type . '" name="' . $key . '" '
                . 'id="' . $key . '"'
                . $this->fieldClass($field)
                . ' value="' . $this->fieldValue($field) . '"'
                . ' />';

        if ($this->starPosition=='after_input' && $field['req']) {
            $input .= $this->starHTML;
        }

        if($this->useTable) {
            $input = '<td>' . $input . '</td>';
        }

        return $label . "\n\t\t\t" . $input . "\n\t\t";
    }

    /**
     * Adds a Hidden Input
     *
     * @param string $key
     * @param array $field
     * @return string $hidden
     * @author steve
     */
    protected function fieldHidden($key, $field)
    {

        $hidden = "\n\t" . '<input type="hidden" name="' . $key . '"'
                . ' value="' . $this->fieldValue($field) . '" />';

        return $hidden;
    }


    /**
     * Builds a Textarea
     *
     * @param string $key
     * @param array $field
     * @return string $textarea
     * @author steve
     */
    protected function fieldTextarea($key, $field)
    {

        $label  = $this->fieldLabel($key, $field);

        $textarea   = '<textarea name="' . $key . '" id="' .$key . '" '
                    . $this->fieldClass($field) . '>'
                    . $this->fieldValue($field)
                    . '</textarea>';

        if ($this->starPosition=='after_input' && $field['req']) {
            $textarea .= $this->starHTML;
        }

        if($this->useTable) {
            $textarea = '<td>' . $textarea . '</td>';
        }

        return $label . "\n\t\t\t" . $textarea . "\n\t\t";
    }

    /**
     * Builds a Checkbox
     *
     * @param string $key
     * @param array $field
     * @return string $checkbox
     * @author steve
     */
    protected function fieldCheckbox($key, $field)
    {
        $checkbox   = '<label for="' . $key . '" class="cbox">'
                    . "\n\t\t\t\t" . '<input type="checkbox" name="' . $key . '"'
                    . ' id="' . $key . '"'
                    . $this->fieldClass($field)
                    . $this->checkboxChecked($field)
                    . ' /> ' . $field['label'] . "\n\t\t\t" . '</label>';

        if ($this->starPosition=='after_input' && $field['req']) {
            $checkbox .= $this->starHTML;
        }
        
        if($this->useTable) {
            $checkbox   = '<th colspan="2" class="cbox">' . $checkbox . '</th>';
        }

        return "\n\t\t\t" . $checkbox . "\n\t\t";
    }


    /**
     * Builds Radio Button List
     *
     * @param string $key
     * @param array $field
     * @return string $radio
     * @author steve
     */
    protected function fieldRadio($key, $field)
    {

        $label = $this->fieldLabel($key, $field);

        $i=0;

        

        if (is_array($field['options'])) {

            foreach($field['options'] AS $val => $option) {

                $radioChecked = $this->radioChecked($field, $val, $i);

                if ($radioChecked != '') {
                    $checkedClass = 'checked';
                }
                else {
                    $checkedClass = '';
                }

                $radio  .=  "\n\t\t\t" . '<label class="radio '.$checkedClass.'">'
                        .   '<input type="radio" name="' . $key . '"'
                        .   ' value="' . $val . '"'
                        .   $radioChecked
                        .   ' /> ' . $option['title'] . '</label>';

                $i++;
            }

        }

        if ($this->starPosition=='after_input' && $field['req']) {
            $radio .= $this->starHTML;
        }

        if($this->useTable) {
            $radio   = "\n\t\t\t" . '<td>' . $radio . "\n\t\t\t" . '</td>';
        }

        return "\n\t\t\t" . $label . $radio . "\n\t\t";

    }


    /**
     * Build Select Box
     *
     * @param string $key
     * @param array $field
     * @return string $select
     * @author steve
     */
    protected function fieldSelect($key, $field)
    {

        $label = $this->fieldLabel($key, $field);

        $select = "\n\t\t\t" . '<select name="' . $key . '" id="' . $key . '">';


        if ( !is_array($field['options']) || empty($field['options']) ) {
            return false;
        }

        // Determine if Options array is multi level (allows range 1,10 to be used as well)
        $arrayMulti = false;
        foreach($field['options'] as $k=>$v) {

            if (is_array($v)) {
                $arrayMulti = true;
                break;
            }
        }

        // If Multi Level Array
        if ($arrayMulti) {

            $use_key = true;

            // Work out if Array Key should be used as value, by looking for ID in array
            if (isset($field['options'][0]['id'])) {

                $use_key = false;
            }

            $i=0;

            foreach($field['options'] AS $array_key => $option) {

                $val = ($use_key) ? $array_key : $option['id'];

                $select .=  "\n\t\t\t\t" . '<option value="' . $val . '"'
                        .   $this->optionSelected($field, $val, $i)
                        .   '>' . $option['title'] . '</option>';

                $i++;
            }
        }

        // Else Simple array
        else {
            foreach($field['options'] AS $option) {

                $select .=  "\n\t\t\t\t" . '<option value="' . $option . '"'
                        .   $this->optionSelected($field, $option, $i)
                        .   '>' . $option . '</option>';

                $i++;
            }
        }


        $select .= "\n\t\t\t" . '</select>';

        if ($this->starPosition=='after_input' && $field['req']) {
            $select .= $this->starHTML;
        }

        if($this->useTable) {
            $select   = "\n\t\t\t" . '<td>' . $select . "\n\t\t\t" . '</td>';
        }

        return "\n\t\t\t" . $label . $select . "\n\t\t";

    }


       /**
     * Builds Date Selection Boxes
     *
     * @param string $key
     * @param array $field
     * @return string $date
     * @author steve
     */
    protected function fieldDate($key, $field)
    {

        $selected = date('Y-m-d');

        if (isset($field['value'])) {
            $selected = $field['value'];
        }
        list (
            $value['year'],
            $value['month'],
            $value['day']
        ) = explode('-', $selected);


        $label = $this->fieldLabel($key, $field);

        // Day Select
        $date   = "\n\t\t\t" . '<select name="' . $key . '-day">';
        for ($i = 1; $i <= 31; $i++) {
            $date   .=  "\n\t\t\t\t" . '<option value="' . sprintf("%02d", $i) . '"'
                    .   global_is($value['day'], $i, 'selected') . '>'
                    .   sprintf("%02d", $i) . '</option>';
        }
        $date .= "\n\t\t\t" . '</select>';

        // Month Select
        $date   .= "\n\t\t\t" . '<select name="' . $key . '-month">';
        for ($i = 1; $i <= 12; $i++) {
            $monthName = date("F", mktime(0, 0, 0, $i+1, 0, 0, 0));
            $monthNum = sprintf("%02d", $i);
            $date   .=  "\n\t\t\t\t" . '<option value="' . $monthNum . '"'
                    .   global_is($value['month'], $monthNum, 'selected')
                    .   '>' . $monthName . '</option>';
        }
        $date .= "\n\t\t\t" . '</select>';

        // Year Select
        $start  = date('Y');
        $end    = date('Y') + 5;

        $date   .= "\n\t\t\t" . '<select name="' . $key . '-year">';
        while ($start <= $end) {
            $date   .=  "\n\t\t\t\t" . '<option value="' . $start . '"'
                    .   global_is($value['year'], $start, 'selected') . '>'
                    .   $start . '</option>';
            $start++;
        }
        $date .= "\n\t\t\t" . '</select>';

        if ($this->starPosition=='after_input') {
            $date .= $this->starHTML;
        }

        if($this->useTable) {
            $date   = "\n\t\t\t" . '<td>' . $date . "\n\t\t\t" . '</td>';
        }

        return "\n\t\t\t" . $label . $date . "\n\t\t";

    }


    /**
     * Is Option Selected
     *
     * @param array $field
     * @param string $val
     * @param int $count
     * @return string
     * @author steve
     */
    protected function optionSelected($field, $val, $count)
    {

        $selected = ' selected="selected"';

        if (!isset($field['value']) && $count==0) {
            return $selected;
        }
        elseif($val == $field['value']) {
            return $selected;
        }

        return '';
    }



    /**
     * Is Radio Checked
     *
     * @param array $field
     * @param string $val
     * @param int $count
     * @return string
     * @author steve
     */
    protected function radioChecked($field, $val, $count)
    {

        $checked = ' checked="checked"';

        if (!isset($field['value']) && $count==0) {
            return $checked;
        }
        elseif($val == $field['value']) {
            return $checked;
        }

        return '';
    }


    /**
     * Is Checkbox Checked
     *
     * @param array $field
     * @return string
     * @author steve
     */
    protected function checkboxChecked($field)
    {

        $checked = ' checked="checked"';

        if(isset($field['value'])) {

            if ($field['value']=='on') {
                return $checked;
            } else {
                return '';
            }
        }

        elseif($field['default']=='on') {
            return $checked;
        }

        return '';
    }


    /**
     * Build class="" string
     *
     * @param array $field
     * @return string $class
     * @author steve
     */
    protected function fieldClass($field)
    {

        $class = '';
        $classes = array();

        if ($field['type']=='text' || $field['type']=='password') {
            $classes[] = 'def';
        }
        

        if ($field['class'] != '') {
            $classes[] = $field['class'];
        }

        if ($field['error'] != '') {
            $classes[] = 'error';
        }

        if (count($classes) > 0) {
            $class = ' class="' . implode(" ", $classes) . '"';
        }

        return $class;
    }

    /**
     * Show Value if Not Empty
     *
     * @param array $field
     * @return string
     * @author steve
     */
    function fieldValue($field)
    {

        $val = $field['value'];

        if ($val != '') {

            $val = stripslashes($val);

            return $val;
        }
        elseif(isset($field['initial'])) {
            return $field['initial'];
        }
        else {
            return '';
        }
    }

    /**
     * Merges an array recursively, but unlike array_merge_recursive(),
     * if second array value is not array, assigns it directly
     *
     * @param array $array1
     * @param array $array2
     * @return array
     * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
     * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
     */
    protected function array_merge_recursive_distinct(array &$array1, array &$array2)
    {
        $merged = $array1;

        foreach ($array2 AS $key => &$value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged [$key] = $this->array_merge_recursive_distinct ($merged[$key], $value);
            }
            else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    /**
     * Returns the number of words in a string
     *
     * @param string $str
     * @return int
     */
    protected function word_count($str)
    {
        $words = 0;

        $str = eregi_replace("\n", " ", $str);
        $str = eregi_replace("\t", " ", $str);

        $str = eregi_replace(" +", " ", $str);

        $array = explode(" ", $str);
        foreach($array AS $word) {
            if (eregi("[0-9A-Za-z]", $word)) {
                $words++;
            }
        }

        return $words;
    }


}