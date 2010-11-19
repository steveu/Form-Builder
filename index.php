<?php

//if ($_SERVER['REMOTE_ADDR'] == '81.149.143.122') {
		error_reporting (E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
//	}

require_once('incs/classes/autoload.php');
require_once('incs/funcs/global.php');
require_once('incs/funcs/emails.php');
require_once('incs/forms.php');


// Forms
$enquiryForm = new FormBuilder_Base($formConfig['enquiry'], $formDefaults);
$enquiryForm->autoProcess();


require_once('incs/templates/header.php');


echo $enquiryForm->build();


require_once('incs/templates/footer.php');