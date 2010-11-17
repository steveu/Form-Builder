<?php

class Des_Form_Workmaze_Login extends Des_Form_Login {

    protected $userSession = array(
        'id'            => array('db'=>'sch_id'),
        'name'          => array('db'=>'sch_name'),
        'username'      => array('db'=>'sch_username'),
        'intro'         => array('db'=>'sch_intro'),
    );


    /**
     * Overide Constructor, to set new defaults
     *
     * @param array $config
     * @author steve
     */
    public function __construct($config, $defaults)
    {

        // Create some new default error messages
        $this->defaults['error_msgs']['account_not_found'] = array(
            'form'  =>  'No Account was found matching your username/password',
            'field' =>  'Account not found',
        );

        // run parent construct
        Des_Form_Base::__construct($config, $defaults);

        return true;
    }



    public function process()
    {
        // Set Session
        foreach($this->userSession AS $key => $array) {
            $_SESSION['user'][$key] = $array['value'];
        }

        header("Location: ".$this->redirect);


    }

    /**
     * Builds a HTML list of errors
     *
     * @return string $errors
     * @author steve
     */
    protected function formErrors()
    {
        $set = array();

        $errors = "\n\t".'<div id="formErrors" class="alert error">'
                . "\n\t\t" . '<ul>';

        foreach($this->errors AS $msg) {

 
            if (!in_array($msg,$set)) {

                $set[] = $msg;

                $errors .= "\n\t\t\t".'<li>'.$msg.'</li>';
            }

        }
        $errors .= "\n\t\t".'</ul>' . "\n\t" . '</div>';
        
        return $errors;
    }
}