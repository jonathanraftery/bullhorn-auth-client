# Bullhorn Auth Client
This package provides a simple way to automate Bullhorn REST API logins.

## Installation
``` bash
$ composer require jonathanraftery/bullhorn-auth-client
```

## Usage
```php
use jonathanraftery\Bullhorn\Rest\Authentication\Client as BullhornAuthClient;

$client = new BullhornAuthClient(
    'client_id',
    'client_secret'
);

$client->initiateSession(
    'bullhorn_username',
    'bullhorn_password'
);
$restToken = $session->getRestToken();
$restUrl = $session->getRestUrl();

// make Bullhorn calls

// once  your session expires, refresh with the stored refresh token
$client->refreshSession();
```
