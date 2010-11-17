<?php

/**
 * A Password Reset Form
 *
 * @author steve
 * @copyright Designition Ltd
 */
class Des_Form_Password extends Des_Form_Base {


    // Table to Extract From
    protected $dbTable = 'tbl_users';

    protected $dbExtract = array(
        'user_email', 'user_fullname'
    );
    
    // Fields which must also match
    protected $dbMatch = array(
        'user_active'   => 'Y',
    );

    private $userRow = array();

    // Fieldset which holds user identifier
    protected $userFieldset = 'user_info_set';

    // Username / Email
    protected $userIdentifier = array(
        'db'=>'user_username',
        'field'=>'username',
    );

    // Alternative to username (email)
    protected $altIdentifier = array(
        'db'=>'user_email',
        'field'=>'username',
    );

    protected $dbPassword = 'user_password';

    protected $md5 = true;
    
    /**
     * Overide Constructor, to set new defaults
     *
     * @param array $config
     * @author steve
     */
    public function __construct($config, $defaults=array())
    {

        // Create some new default error messages
        $this->defaults['error_msgs']['account_not_found'] = array(
            'form'  =>  'No Account was found matching your Username / Email',
            'field' =>  'Account not found',
        );

        // run parent construct
        parent::__construct($config, $defaults);

        return true;
    }

    /**
     * Validate user identifier exists
     *
     * @return boolean
     * @author steve
     */
	public function validate() {

        // run parent validation
        if (Des_Form_Base::validate()) {

            // Field Arrays for Username & Password
            $usernameArray = $this->fieldsets[ $this->userFieldset ]['fields'][ $this->userIdentifier['field'] ];

            if ($usernameArray['value']) {

                // loop extra db fields to match
                foreach($this->dbMatch AS $field => $value) {
                    $matches[] = $field . ' = ' . '"' . $value . '"';
                }

                // if NOT using md5, add password to extract list
                if (!$this->md5) {
                    $this->dbExtract[] = $this->dbPassword;
                }

                // Build the query from db_columns array
                $sql    = 'SELECT ' . $this->userIdentifier['db'] . ' ';

                if ($this->dbExtract) {
                    $sql .= ', ' . implode(', ', $this->dbExtract) . ' ';
                }
                $sql .= 'FROM ' . $this->dbTable . ' ';


                // Match alt as well as main?
                if ($this->altIdentifier) {
                    $sql    .=  'WHERE (' . $this->userIdentifier['db'] . ' = "' . $usernameArray['value'] . '" '
                            .   'OR ' . $this->altIdentifier['db'] . ' = "' . $usernameArray['value'] . '") ';
                }
                else {
                    $sql .= 'WHERE ' . $this->userIdentifier['db'] . ' = "' . $usernameArray['value'] . '" ';
                }

                $sql .= 'AND  ' . implode(' AND ', $matches) . ' ';

                //echo $sql;
                //exit();
                
                 // run query
                $res = mysql_query($sql) or die(mysql_error());
                $num = mysql_num_rows($res);

                if($num>0) {

                    $this->userRow = mysql_fetch_assoc($res);

                    return true;
                }

                else {
                    // Generate some errors
                    $this->generateErrors($this->userFieldset, $this->userIdentifier['field'], $usernameArray, 'account_not_found');

                    return false;
                }
            }
        }

        return false;
	}

    /**
     * Overide process function to reset password
     *
     * @author steve
     */
    public function process()
    {

        // Field Arrays for Username
        $usernameArray = $this->fieldsets[ $this->userFieldset ]['fields'][ $this->userIdentifier['field'] ];

        if ($this->md5) {

            $newPassword = global_generate_password(8);

            $sql    = 'UPDATE ' . $this->dbTable . ' '
                    . 'SET ' . $this->dbPassword . ' = "' . md5($newPassword) . '" '
                    . 'WHERE ' . $this->userIdentifier['db'] . ' = "' . $usernameArray['value'] . '" '
                    . 'LIMIT 1';

            $res = mysql_query($sql) or die(mysql_error());
        }


        // Email new password using template

        // set fields to be replaced
        $this->emailReplaceFields = array(
            'NAME' => $this->userRow['user_fullname'],
            'EMAIL' => $this->userRow['user_email'],
            'PASSWORD' => $newPassword,
        );

        

        //echo '<pre>';
        //print_r($this->emailReplaceFields);
        //echo '</pre>';
        //exit();
        
        // Sends email using template
        parent::process();

        
        return true;


    }

}