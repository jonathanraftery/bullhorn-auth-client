# Bullhorn Auth Client
This package provides a simple way to automate Bullhorn REST API logins.

## Usage
```php
use jonathanraftery\Bullhorn\REST\Authentication\Client as BullhornAuthClient;

$client = new BullhornAuthClient(
    'client_id',
    'client_secret',
    'bullhorn_username',
    'bullhorn_password'
);

$session = $client->createSession();
$restToken = $session->BhRestToken;
$restUrl = $session->restUrl;

// make Bullhorn calls

// once  your session expires, simply create a new one
$session = $client->createSession();
$restToken = $session->BhRestToken;
$restUrl = $session->restUrl;
```
