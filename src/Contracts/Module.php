<?php

namespace Webleit\ZohoSignApi\Contracts;

use Webleit\ZohoSignApi\Client;

interface Module
{
    /**
     * @return Client
     */
    public function getClient();
}