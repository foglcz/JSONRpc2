<pre>
<?php
/**
 * Available methods:
 * - notify
 * - echo
 * - math.sum
 * - math2.special.substract
 */
require_once __DIR__ . '/../lib/Client.php';

// Instantiate and call
$client = new Lightbulb\Json\Rpc2\Client('http://github.loc/JSONRpc2/tests/serverTest.php');
//$client->debug = true;

echo 'call $client->notify();<br />';
var_dump($client->notify());

echo '<hr />';
echo 'call $client->echo("something");<br />';
var_dump($client->echo('something'));

echo '<hr />';
echo 'call $client->math->sum(25, 30, 45);<br />';
var_dump($client->math->sum(25, 30, 45));

echo '<hr />';
echo 'call $client->math2->special->substract(25, 5, 10);<br />';
var_dump($client->math2->special->substract(25, 5, 10));

echo '<hr />';
echo 'call $client->__call("math2.special.substract", array(25, 5, 10));<br />';
var_dump($client->__call('math2.special.substract', array(25, 5, 10)));