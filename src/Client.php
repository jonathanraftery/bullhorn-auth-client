<?php

namespace jonathanraftery\Bullhorn\REST\Authentication;

use \League\OAuth2\Client\Provider\GenericProvider as OAuth2Provider;
use \GuzzleHttp\Client as HttpClient;

class Client
{
    const AUTH_URL  = 'https://auth.bullhornstaffing.com/oauth/authorize';
    const TOKEN_URL = 'https://auth.bullhornstaffing.com/oauth/token';
    const LOGIN_URL = 'https://rest.bullhornstaffing.com/rest-services/login';

    private $username;
    private $password;
    private $authProvider;
    private $accessToken;
    private $accessTokenUsed;

    public function __construct(
        $clientId,
        $clientSecret,
        $username,
        $password
    ){
        $this->username = $username;
        $this->password = $password;
        $this->accessToken = NULL;
        $this->accessTokenUsed = false;

        $this->authProvider = new OAuth2Provider([
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'urlAuthorize' => self::AUTH_URL,
            'urlAccessToken' => self::TOKEN_URL,
            'urlResourceOwnerDetails' => '',
        ]);
    }

    public function createSession()
    {
        $accessToken = $this->getAccessToken();

        $loginUrl = self::LOGIN_URL
            . '?version=*'
            . '&access_token='
            . $accessToken->getToken();

        $loginRequest = $this->authProvider->getAuthenticatedRequest(
            'GET',
            $loginUrl,
            $accessToken
        );
        $response = $this->authProvider->getResponse($loginRequest);

        if ($response->getStatusCode() == 200) {
            $this->accessTokenUsed = true;
            return json_decode($response->getBody());
        }
        else {

        }
    }

    private function getAccessToken()
    {
        if (NULL !== $this->accessToken)
            $this->refreshAccessToken();
        else
            $this->createAccessToken();
        return $this->accessToken;
    }

    private function createAccessToken()
    {
        $authCode = $this->getAuthorizationCode();
        $this->accessToken = $this->authProvider->getAccessToken(
            'authorization_code',
            [
                'code' => $authCode,
            ]
        );
    }

    private function refreshAccessToken()
    {
        if ($this->accessToken->hasExpired() || $this->accessTokenUsed) {
            $refreshToken = $this->accessToken->getRefreshToken();
            $this->accessToken = $this->authProvider->getAccessToken(
                'refresh_token',
                [
                    'refresh_token' => $refreshToken
                ]
            );

            $this->accessTokenUsed = false;
        }
    }

    private function getAuthorizationCode()
    {
        $authCode = '';
        while ('' === $authCode) {
            $authRequest = $this->authProvider->getAuthorizationUrl([
                'response_type' => 'code',
                'action'=> 'Login',
                'username' => $this->username,
                'password' => $this->password
            ]);
            $response = $this->makeHttpRequest(
                $authRequest,
                ['allow_redirects' => false]
            );
            $responseBody = $response->getBody()->getContents();
            $this->checkAuthorizationErrors($responseBody);

            $locationHeader = $response->getHeaders()['Location'][0];
            $authCode = $this->parseAuthorizationCodeFromUrl($locationHeader);
        }

        return $authCode;
    }

    private function checkAuthorizationErrors($responseBody)
    {
        if (FALSE !== strpos($responseBody, 'Invalid Client Id'))
            throw new \InvalidArgumentException("Invalid client ID");
        elseif (FALSE !== strpos($responseBody, '<p class="error">'))
            throw new \InvalidArgumentException("Invalid account credentials");
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
