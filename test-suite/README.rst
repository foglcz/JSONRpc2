======================
 NETTE API TEST SUITE
======================
This is an example (base module) for creating a test suite on top of Nette 
Framework. 

We used the test-suite in order to develop easily a web-based UI to test all
of the methods, with the required / optional arguments. Note that this is
just a test suite, which connects to your server endpoint and therefore,
it is not a "documentation-generator." We used common Microsoft Word in order
to do that.

Installation & fast start
=========================
1) Copy contents of "lib" folder to your Nette Framework project
2) Put the "jsonTestModule" folder into the app/ folder of your Nette Framework project.
3) Copy the jsonTest.css file into your www/css/ folder of Nette Framework project.
4) Append following routes to your bootstrap.php file::

    $router[] = new Route('rpc/test', 'jsonTest:Base:jsonAcessLogin');
    $router[] = new Route('rpc/test/<presenter>[/<action=default>]', array('module' => 'jsonTest'));

5) Copy JsonTestSuitePresenter.php from test-suite/ folder & create following route::

    $router[] = new Route('rpc/json', 'JsonTestSuite:default');

6) Edit jsonTestModule/presenters/BasePresenter.php:63 & set-up credentials
7) Go to "your-project-url/rpc/test", hack around and enjoy the class!

Should you want to use different endpoint from the "JsonTestSuite:default", which
is for example only, edit the endpoint within jsonTestModule/presenters/BasePresenter.php
class, in the startup() method.

Example set-up
==============
The example set-up is based on our real-world implementation. We used the "module"
features of the JsonRPC Server ("the dot-magic") in following manner:

- Every application login requires to call access.* methods first, in order to obtain
  an access-token. That is a string, which is then used subsequently in order to
  authenticate user against application & no credentials are actually beign sent
  with every request towards the API.
- Every token has limited time-validity (in our case, that's 14 days) after which
  it needs to be extended via access.extendToken method
- When user logs-out, the token is destroyed.

Hence, prior to calling any methods of the Json API, even in test suite, you need
to call access.login which will return the token. That token is subsequently saved
in session and you can see how it's provided as default value.

Keep in mind
============
- The modules (access.*) are loaded automatically based on classes within "jsonTestModule" namespace
- The methods (*.something) are loaded automatically from those classes, based on
  "render*" methods.

Implementation of custom methods
================================
Everything is custom-generated based on arrays provided to the template. The forms
are automatically proccessed and every parameter can be un-checked in order not to
send it within request towards the API (hence you can test optional arguments.)

The request / response forms are auto-generated. All you need to do is implement
respective render*() method within respective presenter, and pass "$formData"
parameter to the template.

The structure of the formData array is as follows::

    array(
        'method' => 'the name of json method, eg user.getInfo',
        'params' => array(
            // the array of parameters, eg:
            'first'   => null,
            'second'  => 'parameterName',
            'third'   => 'parameterName:datatype',
            'fourth'  => array('a', 'b', 'c'), // this is "select box variable"
            'fifth'   => false, // to generate a checkbox
            'sixth'   => '~array~', // pass "~array~" in order to generate 5 inputs for parameter
            'seventh' => $object, // in order to pass an object
        )
    )

When passing an object, it has to be an instance of a "stdClass". Also, it has to have
one property set, which is "_objectName" - that is used as title to the object.

Other properties of the object will be generated respectively to the structure of params.

Support
=======
Contact me directly using e-mail at <birdie at animalgroup dot cz> or on twitter
@foglcz , or of course, in here.

License
=======
Licensed under the New BSD License. Copyright 2011 Pavel Ptacek. All rights reserved.
