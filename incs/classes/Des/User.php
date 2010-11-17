<?php


class Des_User {

    private static $instance;

    protected $userSessionKey = 'user';

    // Array of User Session Variables
    public $userSession = array(
        'id'            => array('db'=>'user_id'),
        'identifier'    => array('db'=>'user_email'),
        'name'          => array('db'=>'user_username'),
        'access'        => array('db'=>'user_access'),
    );


	protected $logged = false;

	protected $messages = array();

    private function __construct()
    {

        // loop userSession and check if values passed
        foreach($this->userSession AS $k => $v) {

            if (isset($_SESSION[$this->userSessionKey][$k])) {

                $this->userSession[$k]['value'] = $_SESSION[$this->userSessionKey][$k];
            }
        }
    }


    public static function singleton()
    {
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c();

        }
        return self::$instance;
    }


	public function checkAuth() {

		if (is_array($_SESSION[$this->userSessionKey])) {
			$this->logged = true;
			return true;
		}
		else {
			return false;
		}
	}


	public function login($formUser)
    {

        $this->userSession = $formUser;

        // loop userSession and check if values passed
        foreach($this->userSession AS $k => $v) {

            $_SESSION[$this->userSessionKey][$k] = $v['value'];

        }

	}

    public function logout()
    {
        unset($_SESSION[ $this->userSessionKey]);

        return true;
    }


    public function getName()
    {
        $name = $this->userSession['name']['value'];
        return $name;
    }

    public function getIdentifier()
    {
        $name = $this->userSession['identifier']['value'];
        return $name;
    }

    public function getId()
    {
        $id = $this->userSession['id']['value'];
        return $id;
    }



    public function activate($code)
    {

        $activate_email = global_decode($code);

        $sql    = 'UPDATE tbl_users '
                . 'SET user_active = "Y" '
                . 'WHERE user_email = "' . $activate_email . '" '
                . 'LIMIT 1';


        mysql_query($sql) or die(mysql_error());

        foreach($this->userSession AS $key => $array) {
            $extract[] = $array['db'];
        }

        // Build the query from db_columns array
        $sql =  'SELECT ' . implode(", ",$extract) . ' ';
        $sql .= 'FROM tbl_users ';
        $sql .= 'WHERE user_email = "' . $activate_email . '"';

        $res = mysql_query($sql) or die(mysql_error());
        $num = mysql_num_rows($res);

        if($num > 0) {

            $row = mysql_fetch_assoc($res);

            // Loop session array to enter values from DB
            foreach($this->userSession AS $key => $array) {
                if(isset($row[$array['db']])) {

                    $this->userSession[$key]['value'] = stripslashes($row[$array['db']]);
                }
            }



            $this->login($this->userSession);
        }

    }
    
}