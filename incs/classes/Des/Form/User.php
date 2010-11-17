<?php

/**
 * Extends the Database Form to pull initial values from User Table
 *
 * @author steve
 * @copyright Designition Ltd
 * @todo Extend the process method to update the user table with new values
 */
class Des_Form_User extends Des_Form_Database
{

    // Table to Pull User Data From
    protected $userTable = 'tbl_users';

    // Identifier of User
    /* Format as below
    array(
        'field' => 'user_id',
        'value' => 8801,
    );
    */
    protected $userIdentifier = array();

    /**
     * Assigns POST Values If Available, Otherwise Gets from User DB
     * @author steve
     */
    protected function assignValues()
    {

        // run parent assigniment (POST if set)
        Des_Form_Base::assignValues();

        // If No POST and db row passed, lookup record
        if (empty($_POST) && $this->userIdentifier) {

            $dbUserFields = array();

            foreach($this->fieldsets AS $setKey => $setArray) {

                foreach($setArray['fields'] AS $fieldKey => $fieldArray) {

                    if ($fieldArray['user']) {
                    // Special Name stuff (maybe only BIGGA)
                        if ($fieldArray['user']=='user_name') {
                            $dbUserFields['user_forename'] = 'user_forename';
                            $dbUserFields['user_surname'] = 'user_surname';
                        }

                        else {
                            $dbUserFields[$fieldArray['user']] = $fieldArray['user'];
                        }
                    }
                }
            }
            
            // Build User Query
            $sql    = 'SELECT ' . implode(", ", $dbUserFields) . ' '
                    . 'FROM ' . $this->userTable . ' '
                    . 'WHERE ' . $this->userIdentifier['field'] . ' = '
                    . '"' . $this->userIdentifier['value'] . '"';


            //echo $sql;
            //exit();

            $res = mysql_query($sql) or die(mysql_error());
            $num = mysql_num_rows($res);
            if($num > 0) {

                $row = mysql_fetch_assoc($res);
                extract ($row);

                // Work around name stuff
                $row['user_name'] = $row['user_forename'] . ' ' . $row['user_surname'];
                $this->userFields['book_contact'] = 'user_name';

                // Loop fields and assign DB values
                foreach($this->fieldsets AS $setKey => $setArray) {

                    foreach($setArray['fields'] AS $fieldKey => $fieldArray) {

                        if ($fieldArray['user']) {

                            $dbValue = $row[$fieldArray['user']];
                            
                            $this->fieldsets[$setKey]['fields'][$fieldKey]['value'] = $dbValue;
              
                        }
                    }
                }
            }
        }
    }


}