<?php

namespace Webleit\ZohoSignApi;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\UriInterface;
use Webleit\ZohoSignApi\Mixins\ProvidesModules;
use Webleit\ZohoSignApi\Modules;

/**
 * Class ZohoBooks
 * @package Webleit\ZohoBooksApi
 * @property-read Modules\Templates $templates
 * @property-read Modules\Requests $requests
 */
class ZohoSign implements \Webleit\ZohoSignApi\Contracts\ProvidesModules
{
    use ProvidesModules;

    /**
     * Zoho Books Api Auth Token
     * @var string
     */
    protected $authToken = '';

    /**
     * The client class
     * @var Client
     */
    protected $client;

    /**
     * Stored locally the total number per resource type
     * @var array
     */
    protected $totals = [];

    /**
     * @var string
     */
    protected $modulesNamespace = '\\Webleit\\ZohoSignApi\\Modules\\';

    /**
     * List of available Zoho Sign Api endpoints (see https://www.zoho.com/sign/api)
     * @var array
     */
    protected $availableModules = [
        'templates' => Modules\Templates::class,
        'requests' => Modules\Requests::class,
    ];

    /**
     * ZohoSign constructor.
     * @param $clientId
     * @param $clientSecret
     * @param string $refreshToken
     */
    public function __construct($clientId, $clientSecret, $refreshToken = '')
    {
        $this->client = new Client($clientId, $clientSecret, $refreshToken);
    }

    /**
     * @return Client
     */
    public function getClient (): Client
    {
        return $this->client;
    }

    /**
     * @param $code
     * @return $this
     */
    public function setGrantCode($code)
    {
        $this->client->setGrantCode($code);
        return $this;
    }

    /**
     * @param $token
     * @return $this
     */
    public function setRefreshToken($token)
    {
        $this->client->setRefreshToken($token);
        return $this;
    }

    /**
     * @param CacheItemPoolInterface $cache
     * @return $this
     */
    public function useCache(CacheItemPoolInterface $cache)
    {
        $this->client->useCache($cache);
        return $this;
    }

    /**
     * Proxy any module call to the right api call
     * @param $name
     * @return Modules\Module
     */
    public function __get($name)
    {
        return $this->createModule($name);
    }

    /**
     * @param $redirectUri
     * @return string
     */
    public function getGrantCodeConsentUrl ($redirectUri)
    {
        return $this->getClient()->getGrantCodeConsentUrl($redirectUri);
    }

    /**
     * @param UriInterface $uri
     * @return null|string
     */
    public static function parseGrantTokenFromUrl(UriInterface $uri)
    {
        return Client::parseGrantTokenFromUrl($uri);
    }
}