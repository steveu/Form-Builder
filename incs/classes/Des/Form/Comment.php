<?php

/**
 * Extends Database Form to implement a Comment Delay
 *
 * @author steve
 * @copyright Designition Ltd
 */
class Des_Form_Comment extends Des_Form_Database
{

    // Number of Minutes to Delay
    protected $delayMinutes = 3;

    // Message to show if too soon
    protected $delayMessage = '<strong>Comment Too Soon</strong>: Please wait 5 minutes before commenting again.';

    // User IP
    protected $userIP = '';

    // Should an admin user be sent an email
    protected $emailAdmin = true;


    /**
     * Container Validation Function, controls order of validation
     *
     * @param array $post
     * @return boolean
     * @author steve
     */
    public function validate()
    {

        // check if already commented within time delay
        if (!$this->allowComment()) {
            return false;
        }

        // Run Master Validate function
        if (!Des_Form_Base::validate()) {
            return false;
        }

        return true;
    }

    /**
     * Extra Process Functionality for Comments (send admin email, set session var)
     *
     * @return boolean
     * @author steve
     */
    public function process()
    {

        Des_Form_Database::process();

        if ($this->emailAdmin) {

            // Send Email to Admin
            $email = array(
                'email_toname' => 'STEVE',
                'email_toemail' => 'steve@designition.co.uk',
                'email_subject' => 'Comment Requires Moderation',
                'email_name' => 'KU Website',
                'email_email' => 'website@kingstonunity.co.uk'
            );

            $email['email_message']
                = 'A comment has been made on your website. Please log in and '
                . 'moderate at:' . "\n\n"
                . CONFIG_SETTINGS_DOMAIN . '/control/actions/comments.php?mod=23&id='
                . $this->dbFields['uniqueid'];
            ;

            $siteConfig = array();
            
            emails_send($siteConfig, $email);
        }

        // Set session variable
        $_SESSION['blog_comment'] = $this->dbFields['uniqueid'];

        return true;
    }

     /**
     * If Comment within time delay, show message, else process as normal
     *
     * @return boolean
     * @author steve
     */
    protected function allowComment()
    {

        $sql    = 'SELECT com_id FROM ' . $this->dbTable . ' '
                . 'WHERE com_ipaddress = "' . $this->userIP . '"'
                . 'AND com_date > '
                . 'DATE_SUB(NOW(), INTERVAL ' . $this->delayMinutes . ' MINUTE)';

        $res = mysql_query($sql) or die(mysql_error());
        $num = mysql_num_rows($res);

        if($num > 0) {

            $this->errors[] = $this->delayMessage;
            return false;
        }
        
        return true;
    }
    
}