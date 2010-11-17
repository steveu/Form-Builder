<?php

/**
 * Extends the Database class to do some Rafi's specific stuff
 *
 * @author steve
 * @copyright Designition Ltd
 */
class Des_Form_Checkout extends Des_Form_Database
{

     protected $siteConfig = array();

     protected $checkoutTotal = 0.00;

     protected $checkoutPostage = 0.00;

     protected $merchant = array();


     /**
     * Add email check to master validation function
     *
     * @param array $post
     * @return boolean
     * @author steve
     */
    public function validate()
    {

        if (Des_Form_Base::validate()) {

            // check email is unique (if creating account)
            if ($this->getFieldValue('checkout_general', 'user_account') == 'on') {

                $sql    = 'SELECT user_email, user_imported '
                        . 'FROM tbl_users '
                        . 'WHERE user_email = "' . $this->getFieldValue('checkout_general', 'user_email') . '" '
                        . 'AND user_deleted = "0000-00-00"';

                $res = mysql_query($sql) or die(mysql_error());
                $num = mysql_num_rows($res);

                if($num > 0) {

                    $row = mysql_fetch_assoc($res);

                    if ($row['user_imported'] == 'Y') {

                        $this->generateErrors(
                            'checkout_general', 'user_email',
                            $this->getField('checkout_general', 'user_email'),
                            'imported_account'
                        );
                    }
                    else {

                        $this->generateErrors(
                            'checkout_general', 'user_email',
                            $this->getField('checkout_general', 'user_email'),
                            'email_in_use'
                        );
                    }

                    return false;
                }
            }

            return true;
        }
    }


    /**
     * Modify Check Required to account for Delivery Same As Checkbox
     *
     * @return boolean
     * @author steve
     */

    protected function checkRequired()
    {

        foreach($this->fieldsets AS $setKey => $setArray) {

            foreach($setArray['fields'] AS $fieldKey => $fieldArray) {

                if (
                    $setKey == 'checkout_delivery'
                    &&
                    $setArray['fields']['delivery_option']['value'] != 'off'
                    &&
                    $setArray['fields']['delivery_option']['value'] != 'addnew')
                {

                }
                elseif ($fieldArray['req'] != false) {

                    // handle checkboxes differently
                    if ($fieldArray['type']=='checkbox' && $fieldArray['value']=='off') {

                        $this->generateErrors($setKey, $fieldKey, $fieldArray, 'checkbox_required');
                    }

                    elseif (!isset($fieldArray['value']) || $fieldArray['value']=='') {

                        $this->generateErrors($setKey, $fieldKey, $fieldArray, 'required');
                    }
                }
            }
        }

        if (count($this->errors)> 0) {
            return false;
        } else {
            return true;
        }
    }

     /**
     * Process the Form
     *
     * @author steve
     */

    public function process()
    {

        // Create short variable names (with clean data)
        foreach($this->fieldsets AS $setKey => $setArray) {

            foreach($setArray['fields'] AS $fieldKey => $fieldArray) {

                $clean[$fieldKey] = $fieldArray['value'];
            }
        }


        // if logged in, update any account fields
        if (count($this->dbUpdate) > 0) {

            Des_Form_Database::process();
        }

        // else assign values, and insert user
        else {

            // Insert New User (If Requested)
            if ($clean['user_account']=='on') {

                $newPassword = global_generate_password(7);

                // add extra field to update
                $this->dbFields = array(
                    'user_active'       => 'Y',
                    'user_registered'   => 'NOW',
                    'user_password'     => md5($newPassword),
                );

                // Insert new user
                Des_Form_Database::process();

                // Send Email
                $emailValues = array(
                    'NAME'=>$clean['user_fullname'],
                    'EMAIL'=>$clean['user_email'],
                    'PASSWORD'=>$newPassword
                );

                $emailTemplate = emails_return($this->siteConfig,$emailValues,11);
                if ($emailTemplate) emails_send($this->siteConfig,$emailTemplate);
            }

            else {

                // Assign values to object (clean from POST)
                Des_Form_Base::assignValues();
            }
        }


        // Is Newsletter Subscribe on?
        $newsletterSubscribe = $this->getFieldValue('checkout_terms', 'checkout_newsletter');
        if ($newsletterSubscribe == 'on') {

            $this->campaignSubscribe();
        }


        $invoiceCountry = global_return_vars($this->siteConfig['localities']['countries'], 'id', $clean['user_country']);

        $invoiceAddress = array(
            'name'      => $clean['user_fullname'],
            'line1'     => $clean['user_address1'],
            'line2'     => $clean['user_address2'],
            'city'      => $clean['user_city'],
            'postcode'  => $clean['user_postcode'],
            'country'   => $invoiceCountry['title'],
        );

        $invoiceAddress = array_filter($invoiceAddress);
        $clean['invoice_address'] = implode("\n",$invoiceAddress);

        // Delivery Address Same as Invoice
        if ($clean['delivery_option'] == 'sameas' || $clean['delivery_option'] == 'on') {

            $deliveryCountry = $invoiceCountry;


            $deliveryAddress = array(
                'name'      => $clean['user_fullname'],
                'line1'     => $clean['user_address1'],
                'line2'     => $clean['user_address2'],
                'city'      => $clean['user_city'],
                'postcode'  => $clean['user_postcode'],
                'country'   => $deliveryCountry['title'],
            );

            $clean['delivery_id'] = 0;
            $clean['delivery_zone'] = $deliveryCountry['zone'];

            $deliveryAddress = array_filter($deliveryAddress);
            $clean['delivery_address'] = implode("\n",$deliveryAddress);
        }

        // Else if Delivery Address is New
        elseif($clean['delivery_option'] == 'addnew' || $clean['delivery_option'] == 'off') {

            $deliveryCountry = global_return_vars($this->siteConfig['localities']['countries'], 'id', $clean['delivery_country']);

            $deliveryAddress = array(
                'line1'     => $clean['delivery_address1'],
                'line2'     => $clean['delivery_address2'],
                'city'      => $clean['delivery_city'],
                'county'    => $clean['delivery_county'],
                'postcode'  => $clean['delivery_postcode'],
                'country'   => $deliveryCountry['title'],
            );

            if ($clean['delivery_company'] != '') {
                if ($clean['delivery_name'] != '') {
                    $deliveryAddress = array_merge(array('name'=>'FAO: ' . $clean['delivery_name']), $deliveryAddress);
                }
                $deliveryAddress = array_merge(array('company'=>$clean['delivery_company']), $deliveryAddress);
            }
            elseif ($clean['delivery_name'] != '') {
                $deliveryAddress = array_merge(array('name'=>$clean['delivery_name']), $deliveryAddress);
            }
            else {
                $deliveryAddress = array_merge(array('name'=>$clean['user_fullname']), $deliveryAddress);
            }

            $clean['delivery_id'] = 0;
            $clean['delivery_zone'] = $deliveryCountry['zone'];

            $deliveryAddress = array_filter($deliveryAddress);
            $clean['delivery_address'] = implode("\n",$deliveryAddress);


            // Insert Address
            $this->insertAddress($clean);
        }

        // Else it's an existing address
        else {

            $fields = array(
                'address_id', 'address_name', 'address_company', 'address_line1', 'address_line2',
                'address_city', 'address_postcode', 'address_county', 'address_country',
            );

            // select address
            $sql    = 'SELECT ' . implode(',', $fields) . ' '
                    . 'FROM tbl_addresses '
                    . 'WHERE userid = ' . $this->dbUpdate['value'] . ' '
                    . 'AND address_id = ' . $clean['delivery_option'] . ' '
                    . 'LIMIT 1';

            $res = mysql_query($sql) or die(mysql_error());
            $num = mysql_num_rows($res);

            if($num > 0) {

                $row = mysql_fetch_assoc($res);
                extract ($row);

                $deliveryCountry = global_return_vars($this->siteConfig['localities']['countries'], 'id', $address_country);

                $deliveryAddress = array(
                    'line1'     => $address_line1,
                    'line2'     => $address_line2,
                    'city'      => $address_city,
                    'county'    => $address_county,
                    'postcode'  => $address_postcode,
                    'country'   => $deliveryCountry['title'],
                );

                if ($address_company != '') {
                    if ($address_name != '') {
                        $deliveryAddress = array_merge(array('name'=>'FAO: ' . $address_name), $deliveryAddress);
                    }
                    $deliveryAddress = array_merge(array('company'=>$address_company), $deliveryAddress);
                }
                elseif ($address_name != '') {
                    $deliveryAddress = array_merge(array('name'=>$address_name), $deliveryAddress);
                }

                $clean['delivery_id'] = $address_id;
                $clean['delivery_zone'] = $deliveryCountry['zone'];

                $deliveryAddress = array_filter($deliveryAddress);
                $clean['delivery_address'] = implode("\n",$deliveryAddress);

            }
        }

        // Postage Cost
        $postageOption = global_return_vars($this->siteConfig['localities']['zones'], 'id', $clean['delivery_zone']);
        $this->checkoutPostage = $postageOption['costs'];

        $itemCount = 0;

        // Loop Order Items
        foreach ($this->siteConfig['cart']['contents'] AS $key=>$itemArray) {

            $lineTotal = $itemArray['price'] * $itemArray['quantity'];

            $items[] = array(
                //'item_prod_id'   => $itemArray['id'],
                'item_prod_id'   => $itemArray['item_id'],
                'item_prod_type' => $itemArray['type'],
                'item_title'     => $itemArray['title'],
                'item_price'     => number_format($itemArray['price'], 2),
                'item_quantity'  => $itemArray['quantity'],
                'item_strength'  => $itemArray['strength'],
                'item_nogluten'  => $itemArray['gluten'],
                'item_total'     => number_format($lineTotal, 2),
            );

            $this->checkoutTotal = $this->checkoutTotal + $lineTotal;
            $itemCount = $itemCount + $itemArray['quantity'];
        }

        // Insert Order array
        $order = array(
            'order_total'           => $this->checkoutTotal,
            'order_postage'         => $this->checkoutPostage,
            'order_trade'           => 'N',
            'order_instructions'    => $clean['special_instructions'],
            'custom_name'           => $clean['user_fullname'],
            'custom_telephone'      => $clean['user_telephone'],
            'delivery_address'      => $clean['delivery_address'],
            'delivery_zone'         => $postageOption['abbrev'],
            'delivery_id'           => $clean['delivery_id'],
            'invoice_address'       => $clean['invoice_address'],
        );



        // If Logged in
        if (count($this->dbUpdate) > 0) {

            $order['custom_email'] = $this->siteConfig['user']['email'];
            $order['custom_id'] = $this->siteConfig['user']['id'];
        }

        else {
            $order['custom_email'] = $clean['user_email'];
        }


        //echo '<pre>';
        //print_r($insert);
        //exit();

        // Build Insert Order SQL
        $sql = sprintf(
                'INSERT INTO %s (order_date, %s) VALUES (NOW(), "%s")',
                'tbl_orders',
                implode(', ', array_keys($order)),
                implode('", "', $order)
        );

        //echo $sql;
        //exit();

        $res = mysql_query($sql) or die(mysql_error());

        $this->insertedID = mysql_insert_id();

        // Insert Order Items from Array
        foreach($items As $num => $item) {

            $sql = sprintf(
                'INSERT INTO %s (orderid, %s) VALUES ('.$this->insertedID .', "%s")',
                'tbl_orders_items',
                implode(', ', array_keys($item)),
                implode('", "', $item)
            );

            $res = mysql_query($sql) or die(mysql_error());
        }

        $totalCost = $this->checkoutTotal + $this->checkoutPostage;

        // Build Secure Hosting Variables
        $secure = array(

            // Config
            'shreference'       => $this->merchant['id'],   // Client Reference
            'checkcode'         => $this->merchant['checkcode'],    // Secondary Client Reference
            'filename'          => $this->merchant['id'].'/' . $this->merchant['template'], // Template File reference
            'callbackurl'       => $this->merchant['callbackurl'],  // URL for callbacks
            'returnurl'         => $this->merchant['returnurl'],  // htmlgood redirects to this (not standard field)

            // Numbers
            'transactionamount' => number_format($totalCost, 2),  // Total Amount of order
            'subtotal'          => number_format($this->checkoutTotal, 2),
            'shippingcharge'    => $this->checkoutPostage,
            'currency'          => 'GBP',  // Currency Value

            // Person Purchasing
            'cardholdersname'   => $order['custom_name'],
            'cardholdersemail'  => $order['custom_email'],
            'cardholdertelephoneNumber' => $order['custom_telephone'],
            'cardholderaddr1'   => $clean['user_address1'],
            'cardholderaddr2'   => $clean['user_address2'],
            'cardholderpostcode'   => $clean['user_postcode'],
            'cardholdercity'    => $clean['user_city'],

            // Admin
            //'orderref'          => global_order_reference($this->insertedID), // Order Reference
            'pamount'           => $itemCount,  // Used to show basket count on payment pages
            'orderid'           => $this->insertedID, // ID of inserted record
        );

        // Callback Data Fields
        $callbackFields = array(
            'orderid', 'cardholdersname','cardholderaddr1', 'cardholderpostcode', 'cardholdercity'
        );
        if ($clean['user_address2'] != '') {
            $callbackFields[] = 'cardholderaddr2';
        }

        foreach($callbackFields AS $field) {
            $callbackStrings[] = $field.'|#'.$field;
        }
        $secure['callbackdata'] = implode('|',$callbackStrings);

        // add user id, or session flag
        if (isset($this->siteConfig['user']['id'])) {
            $secure['userid'] = global_encode($this->siteConfig['user']['id']);
            $secure['returnurl'] .= global_encode($this->insertedID . '-user-' . $this->siteConfig['user']['id']).'/';
        }
        else {
            $secure['sessionid'] = global_encode($this->siteConfig['session-id']);
            $secure['returnurl'] .= global_encode($this->insertedID . '-session-' . $this->siteConfig['session-id']).'/';
        }


        // Build encrypted fields
        //$encryptFields = array(
            //'transactionamount' => number_format($totalCost, 2),  // Total Amount of order
            //'subtotal'          => number_format($this->checkoutTotal, 2),
            //'shippingcharge'    => $this->checkoutPostage,
            //'currency'          => 'GBP',  // Currency Value
        //);

        //$encryptedString = $this->returnEncryptedFields($encryptFields);

        // Self Submit Merchant Form
        echo $this->merchantForm($secure, $encryptedString);


        // stop any further display
        exit();

    }


    /**
     * Build a self submitting form, dealing with a few browser quirks
     *
     * @param array $fields
     * @param string $encryptedString
     * @return string
     * @author steve
     */
    public function merchantForm($fields, $encryptedString)
    {



        // echo loading image
        echo '<div id="payment_loading" style="display: none; width: 500px; color: #555; line-height: 2em; margin: 150px auto; font-family: arial, sans-serif; text-align: center;">';
        echo 'Please wait... <br /><img src="/img/design/loading_payment.gif" width="128" height="15"/></div>';

        echo '<script type="text/javascript">';
        echo 'document.getElementById("payment_loading").style.display = "";';
        echo '</script>';

        $form .= "\n" . '<form id="paymentpage" name="paymentpage" action="'.$this->merchant['url'].'" method="post">';


        foreach ($fields as $key => $value) {
            $form .= "\n\t" . '<input type="hidden" name="'.$key.'" value="'.$value.'">';
        }

        //echo $encryptedString;

        // Firefox requires something to be outputted, odd as well
        $form .= "\n\t" . '<p style="color: #fff;">form submitted</p>';

        $form .= "\n" . '<noscript>';
        $form .= "\n\t" . '<div style="width: 500px; margin: 50px auto; border: 1px solid #08a538; padding: 30px; font-family: arial, sans-serif; text-align: center;">';
        $form .= "\n\t" . '<p>Please confirm that you wish to proceed to make a payment of <strong>&pound;'.number_format( ($fields['transactionamount']),2).'<strong></p>';
        $form .= "\n\t" . '<input type="submit" name="sub_btn" id="sub_btn" value="Confirm" style="font-size: 1.8em;" />';
        $form .= "\n\t" . '</div>';
        $form .= "\n" . '</noscript>';


        $form .= "\n" . '</form>';

        $form .= "\n" . '<script type="text/javascript">';
        //$form .= "\n\t" . 'document.paymentpage.submit();';
        $form .= "\n" . '</script>';


        return $form;

    }

    public function campaignSubscribe()
    {

        $campaignUser['name']  = $this->getFieldValue('checkout_general', 'user_fullname');

        // If Logged in
        if (count($this->dbUpdate) > 0) {
            $campaignUser['email'] = $this->siteConfig['user']['email'];
        }
        else {
            $campaignUser['email'] = $this->getFieldValue('checkout_general', 'user_email');
        }

        // Campaign Monitor API
        $cmObject = new CampaignMonitor(
            $this->siteConfig['campaigns']['api'],
            $this->siteConfig['campaigns']['client'],
            NULL,
            $this->siteConfig['campaigns']['list']
        );

		$addResult = $cmObject->subscriberAdd($campaignUser['email'], $campaignUser['name']);

        // If Success
        if ($addResult['Result']['Code'] == 0) {
            return true;
        }
        else {
            $this->errors = array(
                'Sorry your request could not be processed at this time. Please try again.'
            );
        }
    }


    public function returnEncryptedFields($fields)
    {

        $secuStringFields = 'shreference='.$this->merchant['id'].'&secuitems=[4444xt||dell monitor|29.85|1|29.85]&secuphrase=des1976&';

        foreach($fields AS $key => $value) {
            $extraFields[] = $key . '=' . $value;
        }
        $secuStringFields .= implode('&', $extraFields);

        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, "https://www.secure-server-hosting.com/secutran/create_secustring.php");
        curl_setopt ($ch, CURLOPT_POST, 1);
        curl_setopt ($ch, CURLOPT_POSTFIELDS, $secuStringFields);
        curl_setopt ($ch, CURLOPT_HEADER, 0);
        curl_setopt ($ch, CURLOPT_REFERER, "http://spicebox.designition.co.uk/checkout/");
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt ($ch, CURLOPT_TIMEOUT, 10);
        $secuString = trim(curl_exec ($ch));
        if($secuString == "") $secuString='Call to create_secustring Failed';
        curl_close ($ch);

        return $secuString;
    }


    private function insertAddress($clean)
    {
        // Insert Address
        $addressInsert = array(
            'address_company'   => $clean['delivery_company'],
            'address_name'      => $clean['delivery_name'],
            'address_line1'     => $clean['delivery_address1'],
            'address_line2'     => $clean['delivery_address2'],
            'address_postcode'  => $clean['delivery_postcode'],
            'address_county'    => $clean['delivery_county'],
            'address_country'   => $clean['delivery_country'],
        );

        // Is logged in?
        if (count($this->dbUpdate) > 0) {
            $addressInsert['userid'] = $this->siteConfig['user']['id'];
        }
        // else have inserted new user
        elseif($this->insertedID) {
            $addressInsert['userid'] = $this->insertedID;
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES ("%s")',
            'tbl_addresses',
            implode(', ', array_keys($addressInsert)),
            implode('", "', $addressInsert)
        );

        $res = mysql_query($sql) or die(mysql_error());
    }

}