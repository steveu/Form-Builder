<?php

class Des_Form_Newsletter extends Des_Form_Base {

    protected $campaigns = array();

    
    public function process()
    {

        if ($this->campaignSubscribe()) {
            return true;
        }

        return false;
    }


    public function campaignSubscribe()
    {

        foreach($this->fieldsets AS $setKey => $setArray) {

            foreach($setArray['fields'] AS $fieldKey => $fieldArray) {

                if ($fieldArray['campaign']) {

                    $campaignUser[$fieldArray['campaign']] = $fieldArray['value'];
                }
            }
        }

        // Campaign Monitor API
        $cmObject = new CampaignMonitor(
            $this->campaigns['api'],
            $this->campaigns['client'],
            NULL,
            $this->campaigns['list']
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

}