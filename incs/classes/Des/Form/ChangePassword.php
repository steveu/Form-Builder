<?php

/**
 * A Password Reset Form
 *
 * @author steve
 * @copyright Designition Ltd
 */
class Des_Form_ChangePassword extends Des_Form_Database {


    protected $matchField = 'user_password';
    
    protected $md5 = true;


    /**
     * Validate current password is correct
     *
     * @return boolean
     * @author steve
     */
	public function validate() {
        
        // run parent validation
        if (Des_Form_Base::validate()) {

            // check current password matches db
            foreach($this->fieldsets AS $setKey => $setArray) {

                foreach($setArray['fields'] AS $fieldKey => $fieldArray) {

                    if ($fieldArray['dbCheck'] == true) {

                        if ($this->md5) {
                            $passValue = md5($fieldArray['value']);
                        }
                        else {
                            $passValue = $fieldArray['value'];
                        }

                        $sql    = 'SELECT ' . $this->matchField . ' '
                                . 'FROM ' . $this->dbTable . ' '
                                . 'WHERE ' . $this->dbUpdate['field'] . ' = '
                                . '"' . $this->dbUpdate['value'] . '"';

                        //echo $sql;
                        //exit();
                        
                        $res = mysql_query($sql) or die(mysql_error());
                        $num = mysql_num_rows($res);
                        if($num > 0) {

                            $row = mysql_fetch_assoc($res);

                            if ($row[$this->matchField] != $passValue) {

                                $this->generateErrors($setKey, $fieldKey, $fieldArray, 'incorrect');
                                
                                return false;
                            }
                            else {
                                return true;
                            }
                        }
                    }
                }
            }
        }

        return false;
	}


    /**
     * Process the Form;
     *
     * @author steve
     */
    public function process()
    {

        
        // Update database
        if (Des_Form_Database::process()) {

            $this->sendEmail();
            return true;
     
        }
    }
}