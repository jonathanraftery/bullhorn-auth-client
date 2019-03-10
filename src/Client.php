<?php

namespace jonathanraftery\Bullhorn\Rest\Authentication;

use GuzzleHttp\Client as HttpClient;
use League\OAuth2\Client\Provider\GenericProvider as OAuth2Provider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use jonathanraftery\Bullhorn\Rest\authentication\Exception\InvalidRefreshTokenException;
use jonathanraftery\Bullhorn\JsonDataStore;

class Client
{
    const AUTH_URL  = 'https://auth.bullhornstaffing.com/oauth/authorize';
    const TOKEN_URL = 'https://auth.bullhornstaffing.com/oauth/token';
    const LOGIN_URL = 'https://rest.bullhornstaffing.com/rest-services/login';

    private $clientId;
    private $authProvider;
    private $dataStore;

    private $lastResponseBody;
    private $lastResponseHeaders;

    public function __construct($clientId, $clientSecret, $dataStore = null)
    { 
        $this->clientId = $clientId;
        $this->dataStore = $dataStore  ? $dataStore : new JsonDataStore();
        $this->authProvider = new OAuth2Provider([
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'urlAuthorize' => self::AUTH_URL,
            'urlAccessToken' => self::TOKEN_URL,
            'urlResourceOwnerDetails' => ''
        ]);
    }

    public function getRestToken()
    { return $this->dataStore->get($this->getRestTokenKey()); }

    public function getRestUrl()
    { return $this->dataStore->get($this->getRestUrlKey()); }

    public function getRefreshToken()
    { return $this->dataStore->get($this->getRefreshTokenKey()); }

    private function getRestTokenKey()
    { return $this->clientId.'-restToken'; }

    private function getRestUrlKey()
    { return $this->clientId.'-restUrl'; }

    private function getRefreshTokenKey()
    { return $this->clientId.'-refreshToken'; }

    private function storeData($name, $value)
    { $this->dataStore->store($name, $value); }

    public function initiateSession($username, $password, $options = [])
    {
        $authCode = $this->getAuthorizationCode(
            $username,
            $password
        );
        $accessToken = $this->createAccessToken($authCode);
        $session = $this->createSession(
            $accessToken,
            $options
        );
        $this->storeSession($session);
    }

    public function refreshSession($options = [])
    {
        $refreshToken = $this->dataStore->get(
            $this->getRefreshTokenKey()
        );
        if (!isset($refreshToken))
            throw new Exception\InvalidRefreshTokenException('attempted session refresh with invalid refresh token');

        $accessToken = $this->refreshAccessToken($refreshToken);
        $session = $this->createSession(
            $accessToken,
            $options
        );
        $this->storeSession($session);
    }

    public function sessionIsValid()
    {
        return (
            !empty($this->getRestToken())
            && !empty($this->getRestUrl())
        );
    }

    private function createSession($accessToken, $options)
    {
        $response = $this->doRestLogin($accessToken->getToken(), $options);
        if ($response->getStatusCode() == 200) {
            $this->storeData(
                $this->getRefreshTokenKey(),
                $accessToken->getRefreshToken()
            );
            return json_decode($response->getBody());
        }
        else {

        }
    }

    private function storeSession($session)
    {
        $this->storeData(
            $this->getRestTokenKey(),
            $session->BhRestToken
        );
        $this->storeData(
            $this->getRestUrlKey(),
            $session->restUrl
        );
    }

    private function doRestLogin($accessToken, $options)
    {
        $options = array_merge(
            $this->getDefaultSessionOptions(),
            $options
        );
        $options['access_token'] = $accessToken;

        $fullUrl = self::LOGIN_URL
            . '?' . http_build_query($options);
        $loginRequest = $this->authProvider->getAuthenticatedRequest(
            'GET',
            $fullUrl,
            $accessToken
        );
        return $this->authProvider->getResponse($loginRequest);
    }

    private function getDefaultSessionOptions()
    {
        return [
            'version' => '*',
            'ttl' => 60
        ];
    }

    private function createAccessToken($authCode)
    {
        try {
            return $this->authProvider->getAccessToken(
                'authorization_code',
                ['code' => $authCode]
            );
        } catch (IdentityProviderException $e) {
            error_log("failed to create access token with auth code: " . $authCode);
            error_log($e);
            error_log("Last response body: " . print_r($this->lastResponseBody, true));
            error_log("Last response headers: " . print_r($this->lastResponseHeaders, true));
            throw new AuthorizationException('Identity provider exception');
        }
    }

    private function refreshAccessToken($refreshToken)
    {
        try {
            return $this->authProvider->getAccessToken(
                'refresh_token',
                ['refresh_token' => $refreshToken]
            );
        } catch (IdentityProviderException $e) {
            throw new Exception\InvalidRefreshTokenException('attempted session refresh with invalid refresh token');
        }
    }

    private function getAuthorizationCode($username, $password)
    {
        $authRequest = $this->authProvider->getAuthorizationUrl([
            'response_type' => 'code',
            'action'=> 'Login',
            'username' => $username,
            'password' => $password
        ]);
        $response = $this->makeHttpRequest(
            $authRequest,
            ['allow_redirects' => false]
        );
        $responseBody = $response->getBody()->getContents();
        $this->checkAuthorizationErrors($responseBody);

        $this->lastResponseBody = $responseBody;
        $this->lastResponseHeaders = $response->getHeaders();

        $locationHeader = $response->getHeaders()['Location'][0];
        return $this->parseAuthorizationCodeFromUrl($locationHeader);
    }

    private function checkAuthorizationErrors($responseBody)
    {
        if (FALSE !== strpos($responseBody, 'Invalid Client Id'))
            throw new AuthorizationException("Invalid client ID");
        elseif (FALSE !== strpos($responseBody, '<p class="error">'))
            throw new AuthorizationException("Invalid account credentials");
    }

    private function parseAuthorizationCodeFromUrl($url)
    {
        $temp = preg_split("/code=/", $url);
        if (count($temp) > 1) {
            $temp = preg_split("/&/", $temp[1]);
            return urldecode($temp[0]);
        }
        else
            return '';
    }

    private function makeHttpRequest($request, $options = [])
    {
        $client = new HttpClient();
        return $client->get($request, $options);
    }
}
