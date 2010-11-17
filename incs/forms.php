<?php
/**
 * Forms Configuration
 *
 */

/**
 * Associative array of site wide form defaults
 * Just shorthand really!
 * different to class defaults
 */
$formDefaults = array(

    'class'     =>  'default clearfix',
    'action'    =>  $_SERVER['REQUEST_URI'],
    'titleWrap' =>  '<h1>$$</h1>',
    'fieldWrap' =>  '<p class="clearfix">$$</p>',
    'redirect'  =>  $_SERVER['REQUEST_URI'].'thanks/',
    'legendWrap' => '<h3 class="legend">$$</h3>',
    'starPosition' => 'label_start',

    'buttonHTML' => '<button type="submit" name="submit" value="true" class="btn">$$</button>',

    'adminEmail'=>  array(
        'fromName'  => 'Website',
        'fromEmail' => 'website@',
        'toName'    => 'Website Admin',
        'toEmail'   => 'steve@designition.co.uk',
    ),
);


/**
 * Associative Array of Individual Form Configurations
 * See example at bottom for docs
 */
$formConfig = array(

    /*
     * -----------------------------------------------------------------
     * Enquiry Form
     * class Des_Form_base
     * -----------------------------------------------------------------
    */
    'enquiry'  =>  array(

        'title'         =>  'Enquiry Form',
        'id'            =>  'form_enquiry',
        'buttonTitle'   =>  'Send Enquiry',
        'redirect'      =>  'test',
        //'completedMessage' => '<p>Your enquiry has been sent.</p>',
        'fieldsets'     =>  array(

            'enquiry_form_person' =>  array(
                'legend'    =>  false,
                'fields'    =>  array(

                    'enquiry_name' => array(
                        'type'=>'text', 'label'=>'Full Name', 'req'=>true,
                    ),
                    'enquiry_email' => array(
                        'type'=>'text', 'label'=>'Email Address', 'req'=>true,
                        'regex'=>'email',
                    ),
                    'enquiry_phone' => array(
                        'type'=>'text', 'label'=>'Telephone', 'req'=>false,
                    ),
                    'enquiry_message' => array(
                        'type'=>'textarea', 'label'=>'Enquiry', 'req'=>true,
                    ),
                ),
            ),
        ),

        // Array of Admin Email parameters
        'adminEmail' => array(
            'fromName'  => 'Website',
            'fromEmail' => 'website@',
            'toName'    => 'Steve',
            'toEmail'   => 'steve@designition.co.uk',
        ),

    ),

);