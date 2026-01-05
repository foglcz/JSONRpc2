# PHP JSON-RPC client and server library

This library contains a PHP implementation of JSON-RPC version 2. This libraray
implements both a client and a server.

## ⛔‼️ Deprecation notice ‼️⛔

As of January 2026 this repository is in archive mode. Furher development of
this library will take place [here](https://github.com/scottchiefbaker/php-JSON-RPC).

## Installation

Download the contents of the `lib` folder to your project. Then simply include
the appropriate library:

```php
include "lib/Server.php";
include "lib/Client.php";
```

### Server example

```php
$server = new Lightbulb\Json\Rpc2\Server;

// Add functions to the server object to make them callable remotely

// Built in PHP functions or user functions
$server->upper = 'strtoupper';
$server->getID = 'findUserID';

// Class based: All public methods in MyClass are exposed as user.method
$server->user = new MyClass;

// Receive and process any incoming RPC calls
$server->handle();
```

### Client example

```php
$url    = 'http://api.domain.com/endpoint';
$client = new Lightbulb\Json\Rpc2\Client($url);

$str = $client->upper("kitten");
$id  = $client->getID("Jason Doolis");
```

#### Client supports class chaining to call nested methods

```php
$ok = $client->user->mail->login($user, $pass); // Maps to 'user.mail.login'
```

## License
Licensed under the New BSD License. Copyright 2011 Pavel Ptacek.
All rights reserved.
