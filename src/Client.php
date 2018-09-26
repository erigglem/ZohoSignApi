<?php

namespace Webleit\ZohoSignApi;

use GuzzleHttp\Psr7\Response;
use Psr\Cache;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Webleit\ZohoSignApi\Exception\ApiError;
use Webleit\ZohoSignApi\Exception\GrantCodeNotSetException;

/**
 * Class Client
 * @see https://github.com/opsway/zohobooks-api
 * @package Webleit\ZohoBooksApi
 */
class Client
{
    const OAUTH_GRANT_URL = "https://accounts.zoho.com/oauth/v2/auth";
    const OAUTH_API_URL = "https://accounts.zoho.com/oauth/v2/token";
    const ZOHO_SIGN_API_URL = "https://sign.zoho.com/api/v1/";

    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $grantCode;

    /**
     * @var string
     */
    protected $clientSecret;

    /**
     * @var string
     */
    protected $clientId;

    /**
     * @var string
     */
    protected $accessToken = '';

    /**
     * @var string
     */
    protected $refreshToken = '';

    /**
     * @var Cache\CacheItemPoolInterface
     */
    protected $cache;

    /**
     * Client constructor.
     * @param $clientId
     * @param $clientSecret
     * @param $grantCode
     */
    public function __construct ($clientId, $clientSecret, $refreshToken = null)
    {
        $this->client = new \GuzzleHttp\Client();

        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;

        if ($refreshToken) {
            $this->setRefreshToken($refreshToken);
        }
    }

    /**
     * @param string $grantCode
     * @return $this
     */
    public function setGrantCode (string $grantCode)
    {
        $this->grantCode = $grantCode;
        return $this;
    }

    /**
     * @param Cache\CacheItemPoolInterface $cacheItemPool
     * @return $this
     */
    public function useCache (Cache\CacheItemPoolInterface $cacheItemPool)
    {
        $this->cache = $cacheItemPool;
        return $this;
    }

    /**
     * @param $uri
     * @param $method
     * @param $query
     * @param $data
     * @param array $extraData
     * @return Response
     * @throws ApiError
     * @throws GrantCodeNotSetException
     */
    public function call ($uri, $method, $query = [], $data = [], $extraData = [])
    {
        $data = [
            'data' => json_encode($data)
        ];

        $data = array_merge($data, $extraData);

        return $this->client->$method(self::ZOHO_SIGN_API_URL . $uri, [
            'query' => $query,
            'form_params' => $data,
            'headers' => [
                'Authorization' => 'Zoho-oauthtoken ' . $this->getAccessToken()
            ]
        ]);
    }

    /**
     * @param $uri
     * @param $start
     * @param $limit
     * @param $orderBy
     * @param $orderDir
     * @param array $search
     * @return mixed
     * @throws ApiError
     * @throws GrantCodeNotSetException
     */
    public function getList($uri, $start = 1, $limit = 10, $orderBy = 'created_time', $orderDir = 'DESC', $search = [])
    {
        $pageContext = $this->getPageContext($start, $limit, $orderBy, $orderDir, $search);

        $response = $this->call($uri, 'GET', ['data' => json_encode($pageContext)]);

        $body = $response->getBody();

        $data = json_decode($body, true);

        return $data;
    }

    /**
     * @param $url
     * @param null $id
     * @return array|mixed|string
     * @throws ApiError
     * @throws GrantCodeNotSetException
     */
    public function get($url, $id = null)
    {
        if ($id !== null) {
            $url .= '/' . $id;
        }

        return $this->processResult(
            $this->call($url, 'GET')
        );
    }

    /**
     * @param int $start
     * @param int $limit
     * @param string $orderBy
     * @param string $orderDir
     * @param array $search
     * @return array
     */
    protected function getPageContext($start = 1, $limit = 10, $orderBy = 'created_time', $orderDir = 'DESC', $search = [])
    {
        return [
            'page_context' => [
                'row_count' => $limit,
                'start_index' => $start,
                //'search_columns' => $search,
                'sort_column' => $orderBy,
                'sort_order' => $orderDir
            ]
        ];
    }

    /**
     * @param ResponseInterface $response
     * @return array|mixed|string
     * @throws ApiError
     */
    protected function processResult(ResponseInterface $response)
    {
        try {
            $result = json_decode($response->getBody(), true);
        } catch (\InvalidArgumentException $e) {

            // All ok, probably not json, like PDF?
            if ($response->getStatusCode() >= 200 && $response->getStatusCode() <= 299) {
                return (string) $response->getBody();
            }

            $result = [
                'message' => 'Internal API error: ' . $response->getStatusCode() . ' ' . $response->getReasonPhrase(),
            ];
        }

        if (!$result) {
            // All ok, probably not json, like PDF?
            if ($response->getStatusCode() >= 200 && $response->getStatusCode() <= 299) {
                return (string) $response->getBody();
            }

            $result = [
                'message' => 'Internal API error: ' . $response->getStatusCode() . ' ' . $response->getReasonPhrase(),
            ];
        }

        if (isset($result['code']) && 0 == $result['code']) {
            return $result;
        }

        throw new ApiError('Response from Zoho is not success. Message: ' . $result['message']);
    }

    /**
     * @return \GuzzleHttp\Client
     */
    public function getHttpClient (): \GuzzleHttp\Client
    {
        return $this->client;
    }

    /**
     * @return mixed
     * @throws ApiError
     * @throws GrantCodeNotSetException
     */
    public function getAccessToken ()
    {
        if (!$this->cache) {
            return $this->generateAccessToken();
        }

        try {
            $cachedAccessToken = $this->cache->getItem('zoho_sign_access_token');

            $value = $cachedAccessToken->get();
            if ($value) {
                return $value;
            }

            $accessToken = $this->generateAccessToken();
            $cachedAccessToken->set($accessToken);
            $cachedAccessToken->expiresAfter(60 * 59);
            $this->cache->save($cachedAccessToken);

            return $accessToken;

        } catch (\Psr\Cache\InvalidArgumentException $e) {
            return $this->generateAccessToken();
        }
    }

    /**
     * @return mixed
     * @throws ApiError
     * @throws GrantCodeNotSetException
     */
    protected function generateAccessToken ()
    {
        $response = $this->client->post(self::OAUTH_API_URL, [
            'query' => [
                'refresh_token' => $this->getRefreshToken(),
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'refresh_token'
            ]
        ]);

        $data = json_decode($response->getBody());

        if (!isset($data->access_token)) {
            throw new ApiError(@$data->error);
        }

        $this->setAccessToken($data->access_token, $data->expires_in_sec);

        return $data->access_token;
    }

    /**
     * @return mixed|string
     * @throws ApiError
     * @throws GrantCodeNotSetException
     */
    public function getRefreshToken ()
    {
        if ($this->refreshToken) {
            return $this->refreshToken;
        }

        if (!$this->cache) {
            return $this->generateRefreshToken();
        }

        try {
            $cachedAccessToken = $this->cache->getItem('zoho_sign_refresh_token');

            $value = $cachedAccessToken->get();
            if ($value) {
                return $value;
            }

            $accessToken = $this->generateRefreshToken();
            $cachedAccessToken->set($accessToken);
            $cachedAccessToken->expiresAfter(60 * 59);
            $this->cache->save($cachedAccessToken);

            return $accessToken;

        } catch (\Psr\Cache\InvalidArgumentException $e) {
            return $this->generateRefreshToken();
        }
    }

    /**
     * @param $token
     * @param int $expiresInSeconds
     * @return $this|mixed
     */
    public function setAccessToken($token, $expiresInSeconds = 3600)
    {
        $this->accessToken = $token;

        if (!$this->cache) {
            return $this;
        }

        try {
            $cachedToken = $this->cache->getItem('zoho_sign_access_token');

            $cachedToken->set($token);
            $cachedToken->expiresAfter($expiresInSeconds);
            $this->cache->save($cachedToken);

            return $this;

        } catch (\Psr\Cache\InvalidArgumentException $e) {
            return $this;
        }
    }

    /**
     * @param $token
     * @param int $expiresInSeconds
     * @return $this|mixed
     */
    public function setRefreshToken($token, $expiresInSeconds = 3600)
    {
        $this->refreshToken = $token;

        if (!$this->cache) {
            return $this;
        }

        try {
            $cachedToken = $this->cache->getItem('zoho_sign_refresh_token');

            $cachedToken->set($token);
            $cachedToken->expiresAfter($expiresInSeconds);
            $this->cache->save($cachedToken);

            return $this;

        } catch (\Psr\Cache\InvalidArgumentException $e) {
            return $this;
        }
    }


    /**
     * @return string
     * @throws ApiError
     * @throws GrantCodeNotSetException
     */
    protected function generateRefreshToken ()
    {
        if (!$this->grantCode) {
            throw new GrantCodeNotSetException('You need to pass a grant code to use the Api. To generate a grant code visit ' . $this->getGrantCodeConsentUrl());
        }

        $response = $this->client->post(self::OAUTH_API_URL, [
            'query' => [
                'code' => $this->grantCode,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'state' => 'testing',
                'grant_type' => 'authorization_code',
                'scope' => 'ZohoSign.documents.all,ZohoSign.templates.all,ZohoSign.account.all'
            ]
        ]);

        $data = json_decode($response->getBody());

        if (!isset($data->refresh_token)) {
            throw new ApiError(@$data->error);
        }

        $this->setAccessToken($data->access_token, $data->expires_in_sec);
        $this->setRefreshToken($data->refresh_token, $data->expires_in_sec);

        return $data->refresh_token;
    }

    /**
     * @param $redirectUri
     * @return string
     */
    public function getGrantCodeConsentUrl ($redirectUri)
    {
        return self::OAUTH_GRANT_URL . '?' . http_build_query([
            'client_id' => $this->clientId,
            'state' => 'testing',
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'access_type' => 'offline',
            'scope' => 'ZohoSign.documents.all,ZohoSign.templates.all,ZohoSign.account.all'
        ]);
    }

    /**
     * @param UriInterface $uri
     * @return string|null
     */
    public static function parseGrantTokenFromUrl(UriInterface $uri)
    {
        $query = $uri->getQuery();
        $data = explode('&', $query);

        foreach ($data as &$d) {
            $d = explode("=", $d);
        }

        if (isset($data['code'])) {
            return $data['code'];
        }

        return null;
    }
}