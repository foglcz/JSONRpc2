# Advanced server options

```php
// Anonymous functions
$server->firstTwo = function($str) { return substr($str,0,2); };

// Force a namespace to map to an object method
$server->{'mytesthandler.myfunc'} = array($myObject, 'myMethod');

// Static method calls
$server->myStaticHandler = 'MyStaticClass::theStaticFunction';
```

The methods, which are given to the server, can be then called via numbered
or named parameters. More information available in the
[JSON-RPC specification](https://www.jsonrpc.org/specification).

The server class respects binding of event methods:

```php
// Bind events
$server->onBeforeCall[] = function($server) {};
$server->onBeforeCall[] = function($server) {};
$server->onSuccess[]    = function($server) {};
$server->onError[]      = function($server) {};
```

For detailed usage see comments with the server and clients class.
