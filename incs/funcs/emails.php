<?php
/* ------------------------------------------------------------------
Copyright: (c) 2004-[-FR-YEAR] Designition Ltd
Author: Designition Ltd (www.designition.co.uk)
Date: [-FR-DATE]
Site: [-FR-NAME]

These PHP Scripts can not be copied, redistributed or reused on any 
web site other than the one they have been installed on. For full
terms and conditions please visit www.designition.co.uk/terms/
------------------------------------------------------------------ */


//-----------------------------------------------------------------------------------------------------
// 1) Open template / find & replace values
//-----------------------------------------------------------------------------------------------------
function emails_return($siteConfig,$clean,$id) {
    $sql = 'SELECT email_toname, email_toemail, email_subject, email_name, email_email, email_message ';
    $sql.= 'FROM tbl_emails WHERE email_id = '.$id.' LIMIT 1';
    $res = mysql_query($sql) or die(mysql_error());
    $num = mysql_num_rows($res);
    if($num > 0) {
        $row = mysql_fetch_assoc($res);
        foreach($clean AS $cleanKey=>$cleanVal) {
            if($cleanKey) {
                if(!$cleanVal) $cleanVal = 'N/A';
                $find[] = '{'.$cleanKey.'}';
                $replace[] = $cleanVal;
            }
        }
        foreach($row AS $rowKey=>$rowVal) {
            $return[$rowKey] = str_replace($find,$replace,$rowVal);
        }
    }
    else {
        $return = false;
    }
    return $return;
}

//-----------------------------------------------------------------------------------------------------
// ) Send
//-----------------------------------------------------------------------------------------------------
function emails_send($siteConfig,$emailTemplate) {

    $return = true;

    // Headers (different for some, e.g. Eland)
    // ----------------------------------------------------------------
    if (CONFIG_SETTINGS_DEVMODE == 'Y') {
        $emailTemplate['headers'] = "MIME-Version: 1.0\r\n";
        $emailTemplate['headers'].= "From: ".$emailTemplate['email_name']." <".$emailTemplate['email_email'].">\r\n";
        $emailTemplate['headers'].= "Reply-To: ".$emailTemplate['email_name']." <".$emailTemplate['email_email'].">\r\n";
        $emailTemplate['headers'].= "X-Mailer: Just My Server";
    }
    else {
        $emailTemplate['headers'] = "MIME-Version: 1.0\n";
        $emailTemplate['headers'].= "From: ".$emailTemplate['email_name']." <".$emailTemplate['email_email'].">\n";
        $emailTemplate['headers'].= "Reply-To: ".$emailTemplate['email_name']." <".$emailTemplate['email_email'].">\n";
        $emailTemplate['headers'].= "X-Mailer: Just My Server";
    }

    //if (CONFIG_SETTINGS_DEVMODE == 'Y') $emailTemplate['email_toemail'] = 'steve@designition.co.uk';

    if (CONFIG_SETTINGS_DEVMODE == 'Y') {
        $emailReply = "-f" . $emailTemplate['email_email'];
        if (!mail($emailTemplate['email_toemail'], $emailTemplate['email_subject'], $emailTemplate['email_message'],$emailTemplate['headers'],$emailReply)) {
            $return = false;
        }
    }
    else {
        if (!mail($emailTemplate['email_toemail'], $emailTemplate['email_subject'], $emailTemplate['email_message'],$emailTemplate['headers'])) {
            $return = false;
        }
    }

    // Insert into DB
    // ----------------------------------------------------------------
    emails_insert($siteConfig,$emailTemplate);

    return $return;

}

//-----------------------------------------------------------------------------------------------------
// ) Insert into DB
//-----------------------------------------------------------------------------------------------------
function emails_insert($siteConfig,$emailTemplate) {

    if(!$emailTemplate['email_date']) $emailTemplate['email_date'] = date('Y-m-d H:i:s');

    $emailTemplate['email_useful'] = 'Referer: '.htmlspecialchars($_SERVER['HTTP_REFERER'])."\n";
    $emailTemplate['email_useful'].= 'User Agent: '.htmlspecialchars($_SERVER['HTTP_USER_AGENT'])."\n";
    $emailTemplate['email_useful'].= 'IP Address: '.htmlspecialchars($_SERVER['REMOTE_ADDR'])."\n";
    $emailTemplate['email_useful'].= 'Script Name: '.htmlspecialchars($_SERVER['SCRIPT_NAME'])."\n";
    $emailTemplate['email_useful'].= 'Request URI: '.htmlspecialchars($_SERVER['REQUEST_URI'])."\n";

    $emailTemplate['email_message'] = addslashes($emailTemplate['email_message']);

    $sql['insert'] = 'INSERT INTO tbl_admin_emails (email_toname, email_toemail, email_subject, email_name, email_email, email_message, email_useful, email_date) ';
    $sql['insert'].= 'VALUES (';
    $sql['insert'].= create_sql_value($emailTemplate['email_toname'],'text').', ';
    $sql['insert'].= create_sql_value($emailTemplate['email_toemail'],'text').', ';
    $sql['insert'].= create_sql_value($emailTemplate['email_subject'],'text').', ';
    $sql['insert'].= create_sql_value($emailTemplate['email_name'],'text').', ';
    $sql['insert'].= create_sql_value($emailTemplate['email_email'],'text').', ';
    $sql['insert'].= create_sql_value($emailTemplate['email_message'],'text').', ';
    $sql['insert'].= create_sql_value($emailTemplate['email_useful'],'text').', ';
    $sql['insert'].= create_sql_value($emailTemplate['email_date'],'date').')';

    mysql_query($sql['insert']);
}








/*
//-----------------------------------------------------------------------------------------------------
// ) Emails (open template from DB and return contents)
//-----------------------------------------------------------------------------------------------------
function emails_get_settings($siteConfig,$id) {
	$sql = 'SELECT email_id, email_title, email_toname, email_toemail, email_subject, email_name, email_email, email_message, email_order ';
	$sql.= 'FROM tbl_emails WHERE email_id = '.$id;
	$res = mysql_query($sql) or die(mysql_error()); 
	$row = mysql_fetch_assoc($res); 
	return $row;
}

//-----------------------------------------------------------------------------------------------------
// ) Send
//-----------------------------------------------------------------------------------------------------
function emails_send($siteConfig,$emailTemplate) {

	$emailHeaders = "MIME-Version: 1.0\r\n";
	$emailHeaders.= 'From: '.$emailTemplate['email_name'].' <'.$emailTemplate['email_email'].'>'."\r\n";
	$emailHeaders.= 'Reply-To: '.$emailTemplate['email_name'].' <'.$emailTemplate['email_email'].'>'."\r\n";
	$emailHeaders.= "X-Mailer: Just My Server";

	$emailReply = "-f" . $emailTemplate['email_email'];

//	if(CONFIG_SETTINGS_DEVMODE == 'Y') $emailTemplate['email_toemail'] = 'jon@designition.co.uk';

	mail($emailTemplate['email_toemail'], $emailTemplate['email_subject'], $emailTemplate['email_message'],$emailHeaders,$emailReply);


//	if(mail($emailTemplate['email_toemail'], $emailTemplate['email_subject'], $emailTemplate['email_message'],$emailHeaders,$emailReply)) {
//		echo '<p>mail sent</p>';
//	}
//	else {
//		echo '<p>mail failed</p>';
//	}
//	echo '<pre>'; print_r($emailTemplate); echo '</pre>';
//	exit;


}
*/












?>