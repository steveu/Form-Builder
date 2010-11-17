<?php

/**
 * Extends the base form to Split Search Query
 *
 * @author steve
 * @copyright Designition Ltd
 */
class Des_Form_Search extends Des_Form_Base
{

    protected $encoded_search_string = '';

    /**
     * Assigns values to 'fieldsets' property (if values are available)
     * @author steve
     */
    protected function assignValues()
    {

        $valueArray = $this->getMethodArray();

        

        // If Values, Assign
        if (!empty($valueArray)) {

            $this->fieldsets['search_form']['fields']['search_input']['value'] = global_clean($valueArray['search_input']);


            $find = array(
                ' ',
                '"',
            );

            $replace = array(
                '+',
                '%22',
            );

            $searchQuery = preg_replace("%[^0-9a-z A-Z\"]%", "", $valueArray['search_input']);

            if (strtolower($searchQuery) == 'search our questions') {

                $searchQuery = '';

            }
            else {

                $this->encoded_search_string = stripslashes(str_replace($find, $replace, $searchQuery));
            }

        }

    }



    /**
     * Redirect to search page
     *
     * @author steve
     */
    public function process()
    {

        $url = '/search/' . $this->encoded_search_string . '/';

        header("Location: ".$url);

    }



}