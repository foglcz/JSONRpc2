# JSON-RPC client and server libraries

This library contains a PHP implementation of JSON-RPC version 2, both client and server.

## Installation

Download the contents of the `lib` folder to your project. Then simply include the library:

```
include "lib/Server.php";
```
### Server methods

    $server = new Lightbulb\Json\Rpc2\Server;

    // Class based where all the methods in myClass are exposed as user.method
    $server->user = new MyClass;

    // Anything that is "callable", either built in PHP functions or your own
    $server->upper     = 'strtoupper';
    $server->userClean = 'userClean';

    // Anonymous functions work too
    $server->firstTwo = function($str) { return substr($str,0,2); };

    // Force a namespace to map to an object method
    $server->{'mytesthandler.myfunc'} = array($myObject, 'myMethod');

    // Static method calls work
    $server->myStaticHandler = 'MyStaticClass::theStaticFunction';

The methods, which are given to the server, can be then called via numbered
or named parameters (see json-rpc specification here: http://groups.google.com/group/json-rpc/web/json-rpc-2-0?pli=1 )

The server class respects binding of event methods:

    // Bind events
    $server->onBeforeCall[] = function($server) {};
    $server->onBeforeCall[] = function($server) {};
    $server->onSuccess[]    = function($server) {};
    $server->onError[]      = function($server) {};

For detailed usage see comments with the server and clients class.
For detailed tests see tests folder.

### Client calls

    $client = new Lightbulb\Json\Rpc2\Client('http://api.domain.com/endpoint');
    $client->upper("kitten");
    $client->firstTwo("Hello");

#### Client supports class chaining to call nested methods

    $ok = $client->user->login($user, $pass);

will actually result in following json call:

    {... method: "user.login" ...}

## License
Licensed under the New BSD License. Copyright 2011 Pavel Ptacek. All rights reserved.
