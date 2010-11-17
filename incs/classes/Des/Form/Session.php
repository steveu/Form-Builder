<?php

/**
 * Extends the base form to Add Results to Session
 *
 * @author steve
 * @copyright Designition Ltd
 */
class Des_Form_Session extends Des_Form_Base
{

    // Id If Session Array to Store Values in
    protected $sessionKey = 'event_booking';


    /**
     * Assigns Values to 'fieldsets' Property (If Available)
     * @author steve
     */
    protected function assignValues()
    {

        Des_Form_Base::assignValues();

        $valueArray = $this->getMethodArray();

        if (empty($valueArray)) {

            //echo 'session';
            //exit();
           
            // assign session values
            foreach($this->fieldsets AS $setKey => $setArray) {

                foreach($setArray['fields'] AS $fieldKey => $fieldArray) {

                    if ($fieldArray['session']) {

                        $this->fieldsets[$setKey]['fields'][$fieldKey]['value'] = $_SESSION[ $this->sessionKey ][$fieldKey];
                    }
                }
            }
        }
    }

    
    public function process()
    {

        // Loop Fields into Session
        foreach($this->fieldsets AS $setKey => $setArray) {

            foreach($setArray['fields'] AS $fieldKey => $fieldArray) {

                if ($fieldArray['session']) {

                    $_SESSION[ $this->sessionKey ][$fieldKey] = $fieldArray['value'];
                }
            }
        }
        
        header("Location: ".$this->redirect);
    }


}