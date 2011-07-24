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

For detailed usage see comments with the server and clients class.
For detailed tests see tests folder.

There is an example implementation of the presenter within "example" folder,
which can be used for Nette Framework (TM)

Installation
============
Simply download individual files and start using.
Server requires "exceptions.php"

Support
=======
Contact me directly using e-mail at <birdie at animalgroup dot cz> or on twitter
@foglcz , or of course, in here.

License
=======
Licensed under the New BSD License. Copyright 2011 Pavel Ptacek. All rights reserved.
