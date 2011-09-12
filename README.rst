==============================
 JSON-RPC version 2 libraries
==============================
This library contains a PHP implementation of JSON-RPC version 2.
The library consist of both server and client implementations.

Both server and client supports for "dots magic". That is, every method called
using the RPC classes can contain dots. The dots then separates individual
objects. ie:

 $client = new Lightbulb\Json\Rpc2\Client('http://endpoint');
 $client->first->second->third($arg);

will actually result in following json call:

 {... method: "first.second.third" ...}

With server, you just define the variable with a class. Eg. "user.login" and
"user.stuff" can be achieved like this:

 $server = new Lightbulb\Json\Rpc2\Server;
 $server->user = new MyJsonUserHandler;

This is particulary handy in order to separate individual methods within robust APIs.
Handlers can be individual methods as well. Feel free to do anything of the following:

    $server->myTest = new MyTestHandler; // contains mytest.* methods
    $server->myFunction = function($param1, $param2) { /* ... */ };
    $server->{'mytesthandler.myfunc'} = array($myObject, 'myMethod');
    $server->myStaticHandler = 'MyStaticClass::theStaticFunction';

The methods, which are given to the server, can be then called via numbered
or named parameters (see json-rpc specification here: http://groups.google.com/group/json-rpc/web/json-rpc-2-0?pli=1 )

Methods can have optional arguments:

    $server->user->login = function($email, $password, $permanent = false)

The server class respects binding of event methods:

    // Bind events
    $server->onBeforeCall[] = function($server) {};
    $server->onBeforeCall[] = function($server) {};
    $server->onSuccess[] = function($server) {};
    $server->onError[] = function($server) {};

    // Another way of in-binding the events; it does *not* remove the last one
    $server->onError = function($server) {};

For detailed usage see comments with the server and clients class.
For detailed tests see tests folder.

There is an example implementation of the presenter within "example" folder,
which can be used for Nette Framework (TM)

Test suite
==========
Within "test-suite" folder you can find implementation of the generic test-suite
module for Nette Framework projects. With that, you can simply semi-generate
the test suite, which you can use both for reference & mainly testing
of all your methods.

Installation
============
Download contents of the lib/* folder and put them into your project.
If you want to use test-suite or examples, you need to use Nette Framework.
That awesome framework can be obtained at http://www.nette.org

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
