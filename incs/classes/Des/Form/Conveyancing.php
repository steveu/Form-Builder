<?php

/**
 * Extends the Database class to do some Rafi's specific stuff
 *
 * @author steve
 * @copyright Designition Ltd
 */
class Des_Form_Conveyancing extends Des_Form_Database
{

    protected $sale_price = 0.00;

    protected $buy_price = 0.00;

    protected $total_price = 0.00;

    protected $table = '';


    protected $sessionKey = 'crombie_convey';


    /**
     * Send an email, using DB template or form dump
     *
     * @return boolean
     * @author steve
     */
    public function sendEmail()
    {

        // Build Email Body
        $email  = 'Someone completed the Conveyancing Estimate on '
                . CONFIG_ANTISPAM_REFERER . ' on '. date('d/m/y')
                . ' at ' . date('h:i A') . "\n\n"
                . 'View the results at ' . CONFIG_ANTISPAM_REFERER . '/control/actions/list.php?mod=28' . "\n";



        $email .= "\n\n" . '(Email automatically sent from website)';

        $emailTemplate['email_name'] = $this->adminEmail['fromName'];
        $emailTemplate['email_email'] = $this->adminEmail['fromEmail'] . CONFIG_SETTINGS_COOKIES;

        $emailTemplate['email_title'] = 'Website ' . $this->title . '(' . CONFIG_ANTISPAM_REFERER . ')';
        $emailTemplate['email_subject'] = $this->title . ' Form Sent';
        $emailTemplate['email_message'] = $email;

        $emailTemplate['email_toname'] = $this->adminEmail['toName'];
        $emailTemplate['email_toemail'] = $this->adminEmail['toEmail'];

        if (emails_send($siteConfig,$emailTemplate)) {

            return true;
        }

        return false;

    }


    /**
     * Assigns Values to 'fieldsets' Property (If Available)
     * @author steve
     */
    protected function assignValues()
    {

        Des_Form_Base::assignValues();

        $valueArray = $this->getMethodArray();

        if (empty($valueArray)) {

            //echo 'session';
            //exit();

            // assign session values
            foreach($this->fieldsets AS $setKey => $setArray) {

                foreach($setArray['fields'] AS $fieldKey => $fieldArray) {

                    //if ($fieldArray['session']) {

                        $this->fieldsets[$setKey]['fields'][$fieldKey]['value'] = $_SESSION[ $this->sessionKey ][$fieldKey];
                    //}
                }
            }
        }
    }
    

    /**
     * Adds VAT to a price and returns
     *
     * @param float $price
     * @return float 
     */
    protected function addVAT($price)
    {
        $vat = 17.5;
        return round($price + ($price * ($vat / 100)),2);
    }

    /**
     * Returns VAT of a given amount
     *
     * @param float $price
     * @return float
     */
    protected function getVAT($price)
    {
        $vat = 17.5;
        return round($price * ($vat / 100),2);
    }

    /**
     * Sorts the options by title before displaying
     *
     * @param string $key
     * @param array $field
     * @return string
     */
    protected function fieldSelect($key, $field)
    {
        $field['options'] = global_sort_array($field['options'], 'title');

        return Des_Form_Base::fieldSelect($key, $field);

    }

    protected function buildTable($header, $value, $class=false)
    {

        $this->table = $this->table . '<tr';

        if ($class) {
            $this->table = $this->table . ' class="' . $class . '"';
        }

        $this->table    = $this->table . '><th>' . $header . ':</th><td>&pound;'
                        . number_format($value,2) . '</td></tr>';
    }

    /**
     * Process the Conveyancing Form (mostly calculatios!)
     *
     * @return boolean
     * @author steve
     */
    public function process()
    {

        $this->sale_price = 0;
        
        // create short variable names
        foreach($this->fieldsets AS $setKey => $setArray) {

            foreach($setArray['fields'] AS $fieldKey => $fieldArray) {

                // Short variable name
                $$fieldKey = $fieldArray['value'];

                // set session
                $_SESSION[ $this->sessionKey ][$fieldKey] = $fieldArray['value'];
            }
        }
        

        //$vat = '17.5';
        
        $sale_prices = array(
            'legal' => array(
                600000 => 850,
                500000 => 750,
                400000 => 695,
                250000 => 555,
                150000 => 525,
                100000 => 495,
                0 => 485,
            ),
            'leasehold' => 50, // + VAT
            'mortgage' => 30, // + VAT
            'land_registry' => 8,
        );

        $buy_prices = array(
            'legal' => array(
                600000 => 925,
                500000 => 850,
                400000 => 750,
                250000 => 670,
                150000 => 555,
                100000 => 535,
                0 => 495,
            ),
            'leasehold' => 100, // + VAT
            'new_plot' => 100, // + VAT
            'declaration' => 150, // + VAT
            'leas' => array(
                1 => 156.80,
                2 => 161.80,
            ),
            'admin' => 30, // + VAT
            'buyers' => 2,
            'land_registry' => 4,
            'stamp_duty' => array(
                1000000 => 5,
                500000 => 4,
                250000 => 3,
                125000 => 1,
                0 => 0,
            ),
            'regsitration' => array(
                1000000 => 920,
                500000 => 550,
                200000 => 280,
                100000 => 200,
                80000 => 130,
                50000 => 80,
                0 => 50,
            ),
            'liability' => array(
                1000000 => 90,
                750000 => 55,
                500000 => 40,
                250000 => 37.5,
                0 => 25,
            ),
        );


        // Remove commas from sale/purchase prices
        $con_sale_price = str_replace(',','',$con_sale_price);
        $con_buy_price = str_replace(',','',$con_buy_price);

        $this->setFieldAttribute('convey_sale', 'con_sale_price', 'value', $con_sale_price);
        $this->setFieldAttribute('convey_buy', 'con_buy_price', 'value', $con_buy_price);



        /* ---------------------------------------------------------------------
            Sale Price
        --------------------------------------------------------------------- */
        $sp = 0;

        if ($con_sale_price > 0) {

            $this->table = $this->table . '<h3>Sale</h3><table cellpadding="0" cellspacing="0" class="convey_estimate">';

            // Legal Costs
            foreach($sale_prices['legal'] AS $threshold => $cost) {
                if ($con_sale_price >= $threshold) {

                    $sale_lc_vat = $this->getVAT($cost);
                    $sale_lc = $sale_lc_vat + $cost;

                    //$this->buildTable('Legal Costs', $cost);
                    //$this->buildTable('VAT (17.5%)', $sale_lc_vat);
                    $this->buildTable('Legal Costs', $sale_lc);

                    $sp = $sp + $sale_lc;

                    break;
                }
            }


            // Leasehold
            if ($con_sale_leasehold == 'Y') {

                $sale_lh = $this->addVAT($sale_prices['leasehold']);

                $this->buildTable('Leasehold Cost', $sale_lh);

                $sp = $sp + $sale_lh;
            }

            // Land Registry
            $this->buildTable('Copy Deeds From Land Registry', $sale_prices['land_registry']);
            $sp = $sp + $sale_prices['land_registry'];

            // Mortgage
            if ($con_sale_mortgage== 'Y') {

                $sale_mo = $this->addVAT($sale_prices['mortgage']);

                $this->buildTable('Solicitor\'s Admin Fee - Funds Transfer (Mortgage)', $sale_mo);

                $sp = $sp + $sale_mo;

            }


            // Update object var
            $this->sale_price = $sp;

            $this->buildTable('Total (incuding VAT @ 17.5%)', $sp, 'total');

            // Finish Table
            $this->table = $this->table . '</table>';

            // Add row to DB
            $this->dbFields['con_sale_total'] = $this->sale_price;
        }


        /* ---------------------------------------------------------------------
            Buy Price
        --------------------------------------------------------------------- */
        $bp = 0;

        if ($con_buy_price > 0) {


            $this->table = $this->table . '<h3>Purchase</h3><table cellpadding="0" cellspacing="0" class="convey_estimate">';

            // Legal Costs
            foreach($buy_prices['legal'] AS $threshold => $cost) {
                if ($con_buy_price >= $threshold) {

                    $buy_lc_vat = $this->getVAT($cost);
                    $buy_lc = $buy_lc_vat + $cost;

                    //$this->buildTable('Legal Costs', $cost);
                    //$this->buildTable('VAT (17.5%)', $buy_lc_vat);
                    $this->buildTable('Legal Costs', $buy_lc);

                    $bp = $bp + $buy_lc;

                    break;
                }
            }

            //echo $bp.'<br />';

            // Leasehold
            if ($con_buy_leashold == 'Y') {

                $buy_lh = $this->addVAT($buy_prices['leasehold']);

                $this->buildTable('Leasehold Cost', $buy_lh);

                $bp = $bp + $buy_lh;
            }

            // New Plot
            if ($con_buy_plot == 'Y') {

                $buy_np = $this->addVAT($buy_prices['new_plot']);

                $this->buildTable('New Plot Cost', $buy_lh);

                $bp = $bp + $buy_np;
            }

            // Declaration of Trust
            if ($con_buy_declaration == 'Y') {

                $buy_dec = $this->addVAT($buy_prices['declaration']);

                $this->buildTable('Simple Declaration of Trust', $buy_dec);

                $bp = $bp + $buy_dec;

            }

            // Local Authority
            if ($con_buy_lea < 10) {
                $lea_value = 1;
            }
            else {
                $lea_value = 2;
            }
            $buy_lea = $buy_prices['leas'][$lea_value];
            $this->buildTable('ISA Local, Water & Drainage, Envrio Search Package', $buy_lea);

            $bp = $bp + $buy_lea;

            // Lea Title for DB
            $lea_field = $this->getField('convey_buy', 'con_buy_lea');

            //$lea_title =global_return_vars($lea_field['options'],'id','title');

            $this->dbFields['con_buy_local_authority'] = $lea_field['options'][$con_buy_lea]['title'];
           


            // Solicitors Admin Fee
            $buy_admin = $this->addVAT($buy_prices['admin']);
            $this->buildTable('Solicitor\'s Admin Fee - Funds Transfer (Mortgage)', $buy_admin);
            $bp = $bp + $buy_admin;



            // Number of Buyers
            $buy_buyers = $con_buy_buyers * $buy_prices['buyers'];
            $this->buildTable('Land Charges Search (&pound;2 Per Person)', $buy_buyers);
            $bp = $bp + $buy_buyers;


            // Land Registry Search
            $this->buildTable('Land Registry Search', $buy_prices['land_registry']);
            $bp = $bp + $buy_prices['land_registry'];



            // Stamp Duty
            foreach($buy_prices['stamp_duty'] AS $threshold => $cost) {
                if ($con_buy_price > $threshold) {

                    $stamp_duty_rate = $cost;
                    $buy_stamp = $con_buy_price * ($stamp_duty_rate / 100);
                    break;
                }
            }
            $this->buildTable('Stamp Duty Land Tax ('.$stamp_duty_rate.'%)', $buy_stamp);
            $bp = $bp + $buy_stamp;


            // Land Registration Fee
            foreach($buy_prices['regsitration'] AS $threshold => $cost) {
                if ($con_buy_price >= $threshold) {

                    $this->buildTable('Land Registration Fee', $cost);
                    $bp= $bp + $cost;
                    break;
                }
            }


            // Liability Insurance
            foreach($buy_prices['liability'] AS $threshold => $cost) {
                if ($con_buy_price >= $threshold) {


                    $this->buildTable('Chancel Repair Liability Insurance', $cost);
                    $bp= $bp + $cost;
                    break;
                }
            }


            // Update object var
            $this->buy_price = $bp;

            $this->buildTable('Total (incuding VAT @ 17.5%)', $bp, 'total');

            // Finish Table
            $this->table = $this->table . '</table>';

            // Add row to DB
            $this->dbFields['con_buy_total'] = $this->buy_price;
        }

        // Total
        if ($con_buy_price > 0 && $con_sale_price > 0) {

            $buy_sale_total = $bp + $sp;

            $this->table = $this->table . '<h3>Sale &amp; Purchase</h3><table cellpadding="0" cellspacing="0" class="convey_estimate">';
            $this->buildTable('Total', $buy_sale_total, 'total');
            $this->table = $this->table . '</table>';

            // Add row to DB
            $this->dbFields['con_total'] = $this->buy_price + $this->sale_price;

        }

        // Session the table
        $_SESSION['conveyancing'] = $this->table;


        // update database
        Des_Form_Database::process();

        // Send email
        $this->sendEmail();

        // return
        header('Location: '. $this->redirect);
        return true;


    }

}