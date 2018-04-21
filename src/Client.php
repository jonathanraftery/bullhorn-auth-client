<?php

namespace jonathanraftery\Bullhorn\Rest\Authentication;

use GuzzleHttp\Client as HttpClient;
use League\OAuth2\Client\Provider\GenericProvider as OAuth2Provider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use jonathanraftery\Bullhorn\DataStore;

class Client
{
    const AUTH_URL  = 'https://auth.bullhornstaffing.com/oauth/authorize';
    const TOKEN_URL = 'https://auth.bullhornstaffing.com/oauth/token';
    const LOGIN_URL = 'https://rest.bullhornstaffing.com/rest-services/login';

    private $clientId;
    private $authProvider;
    private $restSession;
    private $dataStore;

    public function __construct($clientId, $clientSecret, $dataStore = null)
    { 
        $this->clientId = $clientId;
        $this->dataStore = $dataStore ?: new DataStore();
        $this->authProvider = new OAuth2Provider([
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'urlAuthorize' => self::AUTH_URL,
            'urlAccessToken' => self::TOKEN_URL,
            'urlResourceOwnerDetails' => ''
        ]);
    }

    public function getRestToken()
    { return $this->dataStore->get('BhRestToken'); }

    public function getRestUrl()
    { return $this->restSession->restUrl; }

    private function getRefreshTokenKey()
    { return $this->clientId.'-refresh'; }

    private function storeData($name, $value)
    { $this->dataStore->store($name, $value); }

    public function initiateSession($username, $password, $options = [])
    {
        $authCode = $this->getAuthorizationCode(
            $username,
            $password
        );
        $accessToken = $this->createAccessToken($authCode);

        $this->restSession = $this->createSession(
            $accessToken,
            $options
        );
    }

    public function refreshSession($options = [])
    {
        $refreshToken = $this->dataStore->get(
            $this->getRefreshTokenKey()
        );
        $accessToken = $this->refreshAccessToken($refreshToken);
        $this->restSession = $this->createSession(
            $accessToken,
            $options
        );
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
            $this->storeToken(
                $this->getRefreshTokenKey(),
                $accessToken->getRefreshToken()
            );
            return json_decode($response->getBody());
        }
        else {

        }
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
            throw new AuthorizationException('Identity provider exception');
        }
    }

    private function authorize($username, $password, $maxAttempts = 5)
    {
        $authCode = new AuthorizationCode();
        $attempts = 0;
        do {
            $authCode->setCode(
                $this->getAuthorizationCode($username, $password)
            );

            ++$attemps;
            if ($attempts > $maxAttempts)
                throw new AuthorizationException();
        } while (!$authCode->isValid());
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
        $temp = preg_split("/&/", $temp[1]);
        return urldecode($temp[0]);
    }

    private function makeHttpRequest($request, $options = [])
    {
        $client = new HttpClient();
        return $client->get($request, $options);
    }
}
