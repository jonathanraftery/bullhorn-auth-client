<?php

use PHPUnit\Framework\TestCase;
use jonathanraftery\Bullhorn\REST\Authentication\Client as Client;

final class ClientTest extends TestCase
{
    /**
     * @dataProvider credentialsProvider
     */
    function testCreatesSessionForValidCredentials(
        $clientId,
        $clientSecret,
        $username,
        $password
    ) {
        $client = new Client(
            $clientId,
            $clientSecret,
            $username,
            $password
        );
        $session = $client->createSession();
        $this->assertTrue(!empty($session->BhRestToken));
    }

    function credentialsProvider()
    {
        $credentialsFileName = __DIR__.'/data/client-credentials.json';
        $credentialsFile = fopen($credentialsFileName, 'r');
        $credentialsJson = fread($credentialsFile, filesize($credentialsFileName));
        $credentials = json_decode($credentialsJson);
        return [
            'testing valid credentials' => [
                $credentials->clientId,
                $credentials->clientSecret,
                $credentials->username,
                $credentials->password
            ]
        ];
    }

    function testThrowsExceptionOnInvalidClientId()
    {
        $this->expectException(InvalidArgumentException::class);
        $badClient = new Client(
            'testing_invalid_client_id',
            '',
            '',
            ''
        );
        $badClient->createSession();
    }

    function testThrowsExceptionOnInvalidUsername()
    {
        $this->expectException(InvalidArgumentException::class);
        $badClient = new Client(
            '',
            '',
            '',
            ''
        );
        $badClient->createSession();
    }
}
