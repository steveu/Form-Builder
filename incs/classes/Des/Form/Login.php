<?php

class Des_Form_Login extends Des_Form_Base {

    // User Object (pulled from Des_User)
    protected $user;

    // Array of User Session Variables (pulled from Des_User)
    protected $userSession = array();

    // Table to Extract From
    protected $dbTable = 'tbl_users';

    // Fields which must also match
    protected $dbMatch = array(
        'user_active'   => 'Y',
    );

    // Fieldset which holds username/password
    protected $userFieldset = 'login_details';

    // Username / Email
    protected $userIdentifier = array(
        'db'=>'user_username',
        'field'=>'username',
    );

    // Password
    protected $userPassword = array(
        'db'=>'user_password',
        'field'=>'password',
    );

    protected $md5 = true;


    /**
     * Overide Constructor, to set new defaults
     *
     * @param array $config
     * @author steve
     */
    public function __construct($config, $defaults)
    {

        // Pull in User Object
        $this->user = Des_User::singleton();

        // Grab the Session fields from Des_User object
        $this->userSession = $this->user->userSession;

        // Create some new default error messages
        $this->defaults['error_msgs']['account_not_found'] = array(
            'form'  =>  'No Account was found matching your username/password',
            'field' =>  'Account not found',
        );

        // run parent construct
        parent::__construct($config, $defaults);

        return true;
    }

    /**
     * Validate Login Form
     *
     * @return boolean
     * @author steve
     */
	public function validate() {


        // run parent validation
        if (Des_Form_Base::validate()) {

            // Field Arrays for Username & Password
            $usernameArray = $this->fieldsets[ $this->userFieldset ]['fields'][ $this->userIdentifier['field'] ];
            $passwordArray = $this->fieldsets[ $this->userFieldset ]['fields'][ $this->userPassword['field'] ];

            if ($usernameArray['value'] && $passwordArray['value']) {

                $extract = array();

                foreach($this->userSession AS $key => $array) {
                    $extract[] = $array['db'];
                }

                // MD5 or Not?
                if ($this->md5) {
                    $passVal = md5($passwordArray['value']);
                }
                else {
                    $passVal = $passwordArray['value'];
                }

                // Build the query from db_columns array
                $sql =  'SELECT ' . implode(", ",$extract) . ' ';
                $sql .= 'FROM ' . $this->dbTable . ' ';
                $sql .= 'WHERE ' . $this->userIdentifier['db'] . ' = "' . $usernameArray['value'] . '" ';
                $sql .= 'AND ' . $this->userPassword['db'] . ' = "' . $passVal . '" ';

                // loop extra db fields to match
                $matches = array();
                foreach($this->dbMatch AS $field => $value) {
                    $matches[] = $field . ' = ' . '"' . $value . '"';
                }
                if (count($matches) > 0) {
                    $sql .= ' AND ' . implode(' AND ', $matches);
                }

                 // run query
                $res = mysql_query($sql) or die(mysql_error());
                $num = mysql_num_rows($res);

                if($num>0) {

                    $row = mysql_fetch_assoc($res);

                    // Loop session array to enter values from DB
                    foreach($this->userSession AS $key => $array) {
                        if(isset($row[$array['db']])) {

                            $this->userSession[$key]['value'] = stripslashes($row[$array['db']]);
                        }
                    }
                    //echo '<pre>';
                    //print_r($this->userSession);
                    //exit();
                    

                    return true;
                }

                else {

                    // Generate some errors
                    $this->generateErrors($this->userFieldset, $this->userIdentifier['field'], $usernameArray, 'account_not_found');
                    $this->generateErrors($this->userFieldset, $this->userPassword['field'], $passwordArray, 'account_not_found');

                    return false;
                }
            }
        }

        return false;
	}


    public function process()
    {

        // Set Session

        $this->user->login($this->userSession);

        header("Location: ".$this->redirect);


    }

}