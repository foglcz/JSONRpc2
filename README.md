# JSON-RPC client and server libraries
This library contains a PHP implementation of JSON-RPC version 2, both client and server.

## Installation
Download the contents of the `lib` folder to your project. Then simply include the library:

```
include "lib/Server.php";
$server = new Lightbulb\Json\Rpc2\Server;
```

With server, you just define define methods in a couple of different ways:

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

    // Another way of in-binding the events; it does *not* remove the last one
    $server->onError = function($server) {};

For detailed usage see comments with the server and clients class.
For detailed tests see tests folder.

There is an example implementation of the presenter within "example" folder,
which can be used for Nette Framework (TM)

Both server and client supports for "dots magic". That is, every method called
using the RPC classes can contain dots. The dots then separates individual
objects. ie:

    $client = new Lightbulb\Json\Rpc2\Client('http://endpoint');
    $client->first->second->third($arg);

will actually result in following json call:

    {... method: "first.second.third" ...}

Test suite
==========
Within "test-suite" folder you can find implementation of the generic test-suite
module for Nette Framework projects. With that, you can simply semi-generate
the test suite, which you can use both for reference & mainly testing
of all your methods.

If you want to use test-suite or examples, you need to use [Nette Framework](http://www.nette.org). 

Known bugs
==========
Due to the nature of PHP, when you use optional argument of method like this:

    $server->user->store($object = null)

you cannot then use "exact resolution operator" === . Hence, only following is applicable:

    if($object != null) // note the "!="

This applies only to NULL variables.

Support
=======
Contact me directly using e-mail at <birdie at animalgroup dot cz> or on twitter
@foglcz , or of course, in here.

License
=======
Licensed under the New BSD License. Copyright 2011 Pavel Ptacek. All rights reserved.
