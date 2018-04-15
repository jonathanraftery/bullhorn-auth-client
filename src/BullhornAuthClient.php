<?php

require_once __DIR__.'/vendor/autoload.php';

use \League\OAuth2\Client\Provider\GenericProvider as OAuth2Provider;
use \GuzzleHttp\Client as HttpClient;

class BullhornAuthClient
{
    const AUTH_URL  = 'https://auth.bullhornstaffing.com/oauth/authorize';
    const TOKEN_URL = 'https://auth.bullhornstaffing.com/oauth/token';
    const LOGIN_URL = 'https://rest.bullhornstaffing.com/rest-services/login';

    private $username;
    private $password;
    private $authProvider;
    private $accessToken;

    public function __construct(
        $clientId, $clientSecret, $username, $password
    )
    {
        $this->username = $username;
        $this->password = $password;

        $this->authProvider = new OAuth2Provider([
            'clientId'                => $clientId,
            'clientSecret'            => $clientSecret,
            'urlAuthorize'            => self::AUTH_URL,
            'urlAccessToken'          => self::TOKEN_URL,
            'urlResourceOwnerDetails' => '',
        ]);
    }

    public function getSession()
    {
        $authCode = $this->getAuthCode();
        $accessToken = $this->authProvider->getAccessToken(
            'authorization_code',
            [
                'code' => $authCode,
            ]
        );
        $this->accessToken = $accessToken;

        $loginRequest = $this->authProvider->getAuthenticatedRequest(
            'GET',
            self::LOGIN_URL.'?version=*&access_token='.$accessToken->getToken(),
            $accessToken
        );

        $response = $this->authProvider->getResponse($loginRequest);
        return json_decode($response->getBody());
    }

    private function refreshToken()
    {
        if ($this->accessToken->hasExpired()) {
            $refreshToken = $this->accessToken->getRefreshToken();
            $this->accessToken = $this->authProvider->getAccessToken(
                'refresh_token',
                [
                    'refresh_token' => $refreshToken
                ]
            );
        }
    }

    private function getAuthCode()
    {
        $authUrl = $this->authProvider->getAuthorizationUrl();
        $request = $authUrl.'&'.http_build_query([
            'response_type' => 'code',
            'action'=> 'Login',
            'username' => $this->username,
            'password' => $this->password
        ]);
        $response = $this->makeHttpRequest($request);

        $locationHeader = $response->getHeaders()['Location'][0];
        $encodedAuthCode = $this->getAuthCodeFromUrl($locationHeader);
        return urldecode($encodedAuthCode);
    }

    private function getAuthCodeFromUrl($url)
    {
        $temp = preg_split("/code=/", $url);
        $temp = preg_split("/&/", $temp[1]);
        return $temp[0];
    }

    private function makeHttpRequest($request)
    {
        $client = new HttpClient();
        return $client->get($request, [
            'allow_redirects' => false
        ]);
    }
}
