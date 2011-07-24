<?php
require_once __DIR__ . '/server.php';

// Instantiate
$server = new Lightbulb\Json\Rpc2\Server;

/**
 * Available methods:
 * - notify
 * - echo
 * - math.sum
 * - math2.special.substract
 */

// Bind methods
$server->notify = function() {
    return 'notify called';
};

$server->echo = function($what) {
    return $what;
};

class Math {
    public function sum() {
        $args = func_get_args();
        $result = 0;
        foreach($args as $one) {
            $result += $one;
        }
        
        return $result;
    }
}
$server->math = new Math;

$server->{'math2.special.substract'} = function() {
    $args = func_get_args();
    $result = array_shift($args);
    foreach($args as $one) {
        $result -= $one;
    }
    
    return $result;
};

// Handle!
$server->handle();