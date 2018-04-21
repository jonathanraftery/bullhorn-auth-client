<?php

namespace jonathanraftery\Bullhorn\Rest\Authentication;

final class ClientTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider credentialsProvider
     */
    function testCreatesSessionForValidCredentials($credentials)
    {
        $client = new Client(
            $credentials['clientId'],
            $credentials['clientSecret']
        );
        $client->initiateSession(
            $credentials['username'],
            $credentials['password'],
            ['ttl' => 1]
        );
        $this->assertTrue($client->sessionIsValid());
    }

    /**
     * @dataProvider credentialsProvider
     */
    function testThrowsExceptionOnInvalidClientId($credentials)
    {
        $this->expectException(AuthorizationException::class);
        $credentials['clientId'] = 'testing_invalid_client_id';
        $client = new Client(
            $credentials['clientId'],
            $credentials['clientSecret']
        );
        $client->initiateSession(
            $credentials['username'],
            $credentials['password'],
            ['ttl' => 1]
        );
    }

    /**
     * @dataProvider credentialsProvider
     */
    function testThrowsExceptionOnInvalidClientSecret($credentials)
    {
        $this->expectException(AuthorizationException::class);
        $credentials['clientSecret'] = 'testing_invalid_client_secret';
        $client = new Client(
            $credentials['clientId'],
            $credentials['clientSecret']
        );
        $client->initiateSession(
            $credentials['username'],
            $credentials['password'],
            ['ttl' => 1]
        );
    }

    /**
     * @dataProvider credentialsProvider
     */
    function testThrowsExceptionOnInvalidUsername($credentials)
    {
        $this->expectException(AuthorizationException::class);
        $credentials['username'] = 'testing_invalid_username';
        $client = new Client(
            $credentials['clientId'],
            $credentials['clientSecret']
        );
        $client->initiateSession(
            $credentials['username'],
            $credentials['password'],
            ['ttl' => 1]
        );
    }

    /**
     * @dataProvider credentialsProvider
     */
    function testThrowsExceptionOnInvalidPassword($credentials)
    {
        $this->expectException(AuthorizationException::class);
        $credentials['password'] = 'testing_invalid_password';
        $client = new Client(
            $credentials['clientId'],
            $credentials['clientSecret']
        );
        $client->initiateSession(
            $credentials['username'],
            $credentials['password'],
            ['ttl' => 1]
        );
    }

    /**
     * @dataProvider credentialsProvider
     */
    function testTimeToLiveParameterSetsTimeToLive($credentials)
    {
        $this->expectException(AuthorizationException::class);
        $credentials['password'] = 'testing_invalid_password';
        $client = new Client(
            $credentials['clientId'],
            $credentials['clientSecret']
        );
        $client->initiateSession(
            $credentials['username'],
            $credentials['password'],
            ['ttl' => 1]
        );
    }

    /**
     * @dataProvider credentialsProvider
     */
    function testClientRefreshesSessionWhenDirected($credentials)
    {
        $client = new Client(
            $credentials['clientId'],
            $credentials['clientSecret']
        );
        $client->initiateSession(
            $credentials['username'],
            $credentials['password'],
            ['ttl' => 1]
        );
        $initialToken = $client->getRestToken();
        $client->refreshSession();
        $secondToken = $client->getRestToken();
        $this->assertNotEquals($initialToken, $secondToken);
    }

    function credentialsProvider()
    {
        $credentialsFileName = __DIR__.'/data/client-credentials.json';
        $credentialsFile = fopen($credentialsFileName, 'r');
        $credentialsJson = fread($credentialsFile, filesize($credentialsFileName));
        $decodeAsArray = true;
        $credentials = json_decode($credentialsJson, $decodeAsArray);
        return ['credentials' => [$credentials]];
    }
}
