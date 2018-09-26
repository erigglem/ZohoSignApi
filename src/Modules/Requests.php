<?php

namespace Webleit\ZohoSignApi\Modules;
use Webleit\ZohoSignApi\Actions\SignDocument;
use Webleit\ZohoSignApi\Models\Request;


/**
 * Class Requests
 * @package Webleit\ZohoSignApi\Modules
 */
class Requests extends Module
{
    /**
     * @param $id
     * @return \GuzzleHttp\Psr7\Response
     * @throws \Webleit\ZohoSignApi\Exception\ApiError
     * @throws \Webleit\ZohoSignApi\Exception\GrantCodeNotSetException
     */
    public function download($id)
    {
        return $this->client->call($this->getUrl() . '/' . $id . '/pdf', 'GET');
    }

    /**
     * @param $id
     * @return \GuzzleHttp\Psr7\Response
     * @throws \Webleit\ZohoSignApi\Exception\ApiError
     * @throws \Webleit\ZohoSignApi\Exception\GrantCodeNotSetException
     */
    public function certificate($id)
    {
        return $this->client->call($this->getUrl() . '/' . $id . '/completioncertificate', 'GET');
    }
}