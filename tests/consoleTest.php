<?php
/**
 * This file is part of The Lightbulb Project
 * 
 * Copyright 2011 Pavel Ptacek and Animal Group
 * 
 * @author Pavel Ptacek <birdie at animalgroup dot cz>
 * @copyright Pavel Ptacek and Animal Group <www dot animalgroup dot cz>
 * @license New BSD License
 */

/**
 * The JSON-RPCv2 test suite: http://groups.google.com/group/json-rpc/web/json-rpc-2-0?pli=1
 */
$requests = array(
    // RPC with positional parameters:
    '{"jsonrpc": "2.0", "method": "substract", "params": [42, 23], "id": 1}',
    '{"jsonrpc": "2.0", "method": "substract", "params": [23, 42], "id": 2}',
    
    // RPC with named parameters
    '{"jsonrpc": "2.0", "method": "substract", "params": {"subtrahend": 23, "minuend": 42}, "id": 3}',
    '{"jsonrpc": "2.0", "method": "substract", "params": {"minuend": 42, "subtrahend": 23}, "id": 4}',
    
    // Notifications:
    '{"jsonrpc": "2.0", "method": "update", "params": [1,2,3,4,5]}',
    '{"jsonrpc": "2.0", "method": "notificator.test"}',
    
    // Non existent method:
    '{"jsonrpc": "2.0", "method": "foobar", "id": "1"}',
    
    // RPC call with invalid request object:
    '{"jsonrpc": "2.0", "method": 1, "params": "bar"}',
    
    // RPC batch, invalid json:
    '[ {"jsonrpc": "2.0", "method": "sum", "params": [1,2,4], "id": "1"},{"jsonrpc": "2.0", "method" ]',
    
    // RPC call with empty Array
    '[]',
    
    // RPC call with an invalid Batch (but not empty)
    '[1]',
    
    // rpc call with an invalid Batch
    '[1,2,3]',
    
    // RPC call batch:
    '[
        {"jsonrpc": "2.0", "method": "math.sum", "params": [1,2,4], "id": "1"},
        {"jsonrpc": "2.0", "method": "notify_hello", "params": [7]},
        {"jsonrpc": "2.0", "method": "substract", "params": [42,23], "id": "2"},
        {"foo": "boo"},
        {"jsonrpc": "2.0", "method": "foo.get", "params": {"name": "myself"}, "id": "5"},
        {"jsonrpc": "2.0", "method": "get_data", "id": "9"} 
    ]',
    
    // RPC call batch (all notifications)
    '[
        {"jsonrpc": "2.0", "method": "notify_sum", "params": [1,2,4]},
        {"jsonrpc": "2.0", "method": "notify_hello", "params": [7]}
    ]',
);

$responses = array(
    // Positional parameters:
    '{"jsonrpc": "2.0", "result": 19, "id": 1}',
    '{"jsonrpc": "2.0", "result": -19, "id": 2}',
    
    // Named parameters:
    '{"jsonrpc": "2.0", "result": -19, "id": 3}',
    '{"jsonrpc": "2.0", "result": -19, "id": 4}',
    
    // Notifications:
    '',
    '',
    
    // Non-existent method:
    '{"jsonrpc": "2.0", "error": {"code": -32601, "message": "Procedure not found."}, "id": "1"}',
    
    // RPC call with invalid request object:
    '{"jsonrpc": "2.0", "error": {"code": -32600, "message": "Invalid Request."}, "id": null}',
    
    // RPC batch, invalid json:
    '{"jsonrpc": "2.0", "error": {"code": -32700, "message": "Parse error."}, "id": null}',
    
    // RPC call with empty Array
    '{"jsonrpc": "2.0", "error": {"code": -32600, "message": "Invalid Request."}, "id": null}',
    
    // RPC call with an invalid batch (but not empty(
    '[
        {"jsonrpc": "2.0", "error": {"code": -32600, "message": "Invalid Request."}, "id": null}
    ]',
    
    // RPC call with an invalid batch
    '[
        {"jsonrpc": "2.0", "error": {"code": -32600, "message": "Invalid Request."}, "id": null},
        {"jsonrpc": "2.0", "error": {"code": -32600, "message": "Invalid Request."}, "id": null},
        {"jsonrpc": "2.0", "error": {"code": -32600, "message": "Invalid Request."}, "id": null}
    ]',
    
    // RPC call batch:
    '[
        {"jsonrpc": "2.0", "result": 7, "id": "1"},
        {"jsonrpc": "2.0", "result": 19, "id": "2"},
        {"jsonrpc": "2.0", "error": {"code": -32600, "message": "Invalid Request."}, "id": null},
        {"jsonrpc": "2.0", "error": {"code": -32601, "message": "Method not found."}, "id": "5"},
        {"jsonrpc": "2.0", "result": ["hello", 5], "id": "9"}
    ]',
    
    // RPC call batch (all notifications)
    '', // nothing is returned for notifications
);

/**
 * Test suite
 */
require_once __DIR__ . '/../lib/Server.php';
$server = new Lightbulb\Json\Rpc2\Server;

// Bind on* methods
//$server->onBeforeCall[] = function() {
//    echo 'first onBeforeCall called<br />';
//};
//$server->onBeforeCall = function() {
//    echo 'second onBeforeCall called<br />';
//};
//$server->addCallback('onSuccess', function() { echo 'the action has been successfull'; });
//$server->onError = function() {
//    echo 'theres been an error';
//};

// Bind request inline method
$server->substract = function($subtrahend, $minuend) {
    return $subtrahend - $minuend;
};

// Bind request callback
function updateFunction() {
    echo 'update function has been called with following parameters:<br />';
    var_dump(func_get_args());
}
$server->update = 'updateFunction';

// Bind object callback
class Notificator {
    public function test() {
        echo 'notificator.test called<br />';
    }
}
$server->notificator = new Notificator;

// Bind functions for the batch
$server->{'math.sum'} = function() {
    $params = func_get_args();
    $result = 0;
    foreach($params as $arg) {
        $result += $arg;
    }
    return $result;
};
$server->notify_hello = function() {
    $params = func_get_args();
    $result = array();
    foreach($params as $par) {
        $result[] = 'Hello, ' . $par;
    } 
    echo 'notify_hello called:<br />  - ';
    echo implode('<br />  - ', $result);
};
$server->notify_sum = function() {
    $params = func_get_args();
    $result = 0;
    foreach($params as $arg) {
        $result += $arg;
    }
    echo 'notify_sum called, result: ' . $result;
};
$server->get_data = function() {
    return array('hello', 5);
};

/**
 * The test suite!
 */
$server->supressOutput();
$pass = $fail = $total = 0;

echo '<pre>';
foreach($requests as $key => $request) {
    $total++;
    
    echo '<div>--> ' . $request . '</div>';
    $output = $server->handle($request);
    $rawOutput = $server->getRawOutput();
    
    // Check the output
    $wanted = json_decode($responses[$key]);
    if($wanted == $output) {
        $pass++;
        $color = '#00aa00';
    }
    else {
        $fail++;
        $color = '#ff0000';
    }
    
    // Format raw output for the tests
    if($rawOutput[0] == '[') {
        $rawOutput = str_replace('[{', '[<br />        {', $rawOutput);
        $rawOutput = str_replace('},{', '},<br />        {', $rawOutput );
        $rawOutput = str_replace('}]', '}<br />    ]', $rawOutput);
    }
    
    echo '<div style="color: ' . $color . '">';
    echo '<-- ' . $rawOutput . '<br />';
    echo '</div>';
    
    echo '<div>sup:' . $responses[$key] . '</div>';
    echo '<hr />';
}

echo '<br />';
echo 'total tests:  ' . $total . '<br />';
echo 'passed tests: <span style="color: #0f0">' . $pass . '</span><br />';
echo 'failed tests: <span style="color: #f00">' . $fail . '</span>';