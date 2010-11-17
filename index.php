<?php

require_once('incs/classes/autoload.php');
require_once('incs/funcs/global.php');
require_once('incs/forms.php');


// Forms
$enquiryForm = new FormBuilder_Base($formConfig['enquiry'], $formDefaults);
$enquiryForm->autoProcess();


require_once('incs/templates/header.php');


echo $enquiryForm->build();


require_once('incs/templates/footer.php');