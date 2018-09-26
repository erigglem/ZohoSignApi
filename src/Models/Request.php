<?php

namespace Webleit\ZohoSignApi\Models;


/**
 * Class Request
 * @package Webleit\ZohoSignApi\Models
 */
class Request extends Model
{
    /**
     * @param string $locale
     * @return string
     */
    public function getSignUrl($locale = 'en')
    {
        return "https://sign.zoho.com/zsguest?locale=" . $locale ."&sign_id=" . $this->sign_id . "&action_type=SIGN";
    }

    /**
     * @return string
     */
    public function download()
    {
        return $this->getModule()->download($this->getId());
    }

    /**
     * @return mixed
     */
    public function certificate()
    {
        return $this->getModule()->certificate($this->getId());
    }
}