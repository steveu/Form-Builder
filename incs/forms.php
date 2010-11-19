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
    'titleWrap' =>  '<h2>$$</h2>',
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

        'title'         =>  'Example Form',
        'id'            =>  'form_enquiry',
        'buttonTitle'   =>  'Send Enquiry',
        'redirect'      =>  'test',
        'completedMessage' => '<p>Your enquiry has been sent.</p>',
        'fieldsets'     =>  array(

            'example' =>  array(
                'legend'    =>  false,
                'fields'    =>  array(

                    'name' => array(
                        'type'=>'text', 'label'=>'Full Name', 'req'=>true,
                    ),
                    'email' => array(
                        'type'=>'text', 'label'=>'Email Address', 'req'=>true,
                        'regex'=>'email', 'initial'=>'must be valid',
                    ),
                    'username' => array(
                        'type'=>'text', 'label'=>'Username', 'req'=>true,
                        'hint'=>'3-20 characters',
                        'min'=>3,
                        'max'=>20,
                        'class'=>'small',
                        'regex'=>'username',
                    ),
                    'phone' => array(
                        'type'=>'text', 'label'=>'Telephone', 'req'=>false,
                    ),
                    'number' => array(
                        'type'=>'select', 'label'=>'Pick a number', 'req'=>false,
                        'options'=>range(0,10),
                    ),
                    'gender' => array(
                        'type'=>'radio', 'label'=>'Gender', 'req'=>false,
                        'options'=>array(
                            'M' => array('title'=>'Male'),
                            'F' => array('title'=>'Female'),
                            'U' => array('title'=>'Unknown'),
                        ),
                    ),
                    'message' => array(
                        'type'=>'textarea', 'label'=>'Enquiry', 'req'=>true,
                    ),
                    'terms' => array(
                        'type'=>'checkbox', 'label'=>'Accept the <a href="">terms and conditions</a>?', 'req'=>true,
                        'error_msgs' => array(
                            'checkbox_required' => array(
                                'form' => 'You must accept the <strong>Terms &amp; Conditions</strong>',
                            ),
                        ),
                    ),
                    'hidden' => array(
                        'type'=>'hidden', 'label'=>'not shown', 'value'=>'Know you secrets',
                    ),
                ),
            ),

            'pick_one' =>  array(
                'legend'    =>  'Pick at least 1 sport',
                'legendWrap' => '<legend class="group_title"><em class="req">*</em> $$:</legend>',
                'fields'    =>  array(
                    'football' => array(
                        'type'=>'checkbox', 'label'=>'Football',
                    ),
                    'cricket' => array(
                        'type'=>'checkbox', 'label'=>'Cricket',
                    ),
                    'rugby' => array(
                        'type'=>'checkbox', 'label'=>'Rugby',
                    ),
                    'darts' => array(
                        'type'=>'checkbox', 'label'=>'Darts',
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

        'groups' => array(

            array(
                'fieldset' => 'pick_one',
                'fields' => array('football', 'cricket', 'rugby', 'darts'),
                'error' => 'You must select at least 1 <strong>sport</strong>',
            ),
        ),

        'showShortErrors' => false,
        'showLongErrors' => true,

    ),



);