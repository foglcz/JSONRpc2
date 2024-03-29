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

namespace Lightbulb\Json\Rpc2;

require_once __DIR__ . '/exceptions.php';

/**
 * This is JSON-RPCv2 server handler class
 *
 * Usage:
 * <code>
 * $server = new \Lightbulb\Json\Rpc\Server;
 *
 * // Bind the classes for "inst.method" calls
 * $server->inst = new MyHandler;
 *
 * // Bind functions for "method" calls
 * $server->method1 = function(param1, param2, ...) {  }
 * $server->method2 = array($obj, 'method');
 * $server->method3 = 'some_php_method';
 * $server->method4 = 'SomeClass::staticMethod';
 *
 * // Bind classes with inline methods
 * $server->inst2 = new stdClass;
 * $server->inst2->some = function() {} // call with "inst2.some"
 *
 * // Clear binded method / class
 * unset($server->inst2->some);
 * unset($server->method2);
 * </code>
 *
 * The server can be used as stand-alone:
 * <code>
 * $server = new \Lightbulb\Json\Rpc\Server;
 *
 * // set the stuff here
 *
 * $server->run();
 * </code>
 *
 * Or embedded into any framework like this:
 * <code>
 * $server = new \Lightbulb\Json\Rpc\Server;
 *
 * // set the stuff here
 *
 * // First way: server loads the input directly from raw_post_data
 * $server->supressOutput();
 * $output = $server->handle();
 * $myFramework->sendResponse($structuredOutput);
 *
 * // Second way: you give the server either raw json string, or structured
 * // parameters of the request
 * $server->supressOutput();
 * $output = $server->handle($incommingJsonOrParsedData);
 * $myFramework->sendResponse($output);
 *
 * // Another option: you can get raw output like this:
 * $server->supressOutput();
 * $server->handle();
 * $rawOutput = $server->getRawOutput();
 * echo $rawOutput;
 * exit; // this one is pretty much nasty solution
 * </code>
 *
 * The class support following callbacks:
 * - onBeforeCall($server) -> called before calling the actual method
 * - onSuccess($server)    -> called after calling the actual method
 * - onError($server)      -> called on errors
 *
 * Bind callbacks this way:
 * <code>
 * // Both will *append* the callback into stack
 * $server->onBeforeCall[] = function() {}
 * $server->onBeforeCall = function() {}
 * $server->addOnBeforeCall(function() {});
 * $server->addOnBeforeCall(array($obj, 'someMethod'));
 *
 * // clear the stack
 * unset($server->onSuccess);
 * $server->clearOnBeforeCall();
 * </code>
 *
 * Use $server->supressOutput() in callbacks, in order to push your output
 * instead of server's
 *
 * Throw exceptions in order to have the server return standard error.
 * Use exception code in order to show the exception's code in the json err output.
 *
 * Use $server->setOutput($output) in order to send that output instead of
 * the output of the standard server-generated format
 *
 * @author Pavel Ptacek
 */

#[\AllowDynamicProperties]
final class Server {
    /** @var mixed structured output sent to browser */
    private $_output;

    /** @var string raw output sent to browser */
    private $_rawOutput;

    /** @var bool */
    private $_supressOutput;

    /** @var bool */
    private $_isError;

    /** @var array of on* callbacks */
    private $_callbacks;

    /** @var array of server callbacks */
    private $_server;

    /** @var array of errors during execution outside scope of the server */
    private $_errors = array();

    /** @var int Error level to be reported when handling server callbacks, @see http://www.php.net/manual/en/function.error-reporting.php */
    private $_error_handling_level = '';

    /** Allow calls to be made via GET in addition to POST. Disabled by default, but can be enabled at runtime */
    public $_allow_get_calls = 0;

    /**
     * Get reflection for the function
     *
     * @param mixed $method
     */
    private function _getReflection($callback) {
        if(is_array($callback)) {
            $last = array_pop($callback);
            $current = $this;
            foreach($callback as $one) {
                $current = $current->$one;
            }

            return array(
                'reflection' => new \ReflectionMethod($current, $last),
                'object' => $last,
            );
        }

        // class::method
        if(is_string($callback) && strpos($callback, '::') !== false) {
            $ex = explode('::', $callback); // php 5.4 compatibility
            return array(
                'reflection' => new \ReflectionMethod($ex[0], $ex[1]),
                'object' => null,
            );
        }

        // objects as functions
        if(method_exists($callback, '__invoke') && is_object($callback)) {
            return array(
                'reflection' => new \ReflectionMethod($callback, '__invoke'),
                'object' => $callback,
            );
        }

        // closures & functions
        return array(
            'reflection' => new \ReflectionFunction($callback),
            'object' => false,
        );
    }

    /**
     * Check validity of the request
     *
     * @return void
     * @throws Exception
     */
    private function _checkRequest($request) {
        // If batch request, everything is ok
        if(!$request instanceof \stdClass) {
            throw new \Exception('Invalid Request. Internal error: request is not a stdClass instance.', -32600);
        }

        // Check that it's a valid 1.0 or 2.0 request
        if(!$this->get_request_version($request)) {
            throw new \Exception('Invalid Request. Request does not contain version number in "jsonrpc" parameter for 2.0, or "request_version" for 1.x', -32600);
        }
        if(!isset($request->method) || !is_string($request->method)) {
            throw new \Exception('Invalid Request. Request does not contain method.', -32600);
        }
        if(substr($request->method, 0, 4) == 'rpc.') {
            throw new \Exception('Method not found.', -32601);
        }
        if(isset($request->id) && !is_int($request->id) && !is_string($request->id)) {
            throw new \Exception('Invalid Request. Request contains ID, but it\'s not string or integer.', -32600);
        }
    }

    /**
     * Handle function
     */
    private function _handle($request) {
        $batch = $responses = array();

        // To simplify stuff, make everything as batch
        if(is_array($request)) {
            $batch = $request;
        }
        else {
            $batch[] = $request;
        }

        // Empty?
        if(empty($batch)) {
            $error = new \stdClass;
            $error->jsonrpc = '2.0';
            $error->error = new \stdClass;
            $error->error->code = -32600;
            $error->error->message = 'Invalid Request.';
            $error->id = null;

            return $error;
        }

        // Loop through the batch & execute each
        foreach($batch as $one) {
            try {
                $this->_checkRequest($one);

                if(!isset($one->params)) {
                    $one->params = null;
                }

                $this->last->method = $one->method;
                $this->last->params = $one->params;

                // call_user_func_array() wants an array
                if (!is_array($one->params)) {
                    $one->params = array($one->params);
                }

                // All the functions should be stored inside of $this so we need to call them
                $func = $this->{$one->method}; // Method: $s->foo = array($obj,$method);
                if (!is_callable($func)) {
                    $func = array($this,$one->method); // Class: $s->foo = new myClass;
                }

                $return = call_user_func_array($func, $one->params);

                // No response for no id -> it's a notification
                if(!isset($one->id)) {
                    continue;
                }

                // Build the response
                $response = new \stdClass;
                $response->jsonrpc = '2.0';
                $response->result = $return;
                $response->id = $one->id;
                $responses[] = $response;
            }

            // Build error reponse on wrong requests
            catch(\Exception $e) {
                $error                 = new \stdClass;
                $error->jsonrpc        = '2.0';
                $error->error          = new \stdClass;
                $error->error->code    = $e->getCode();
                $error->error->message = $e->getMessage();

                // If there is an error calling that method trickle it up so we catch onError().
                if ($error->error->code == -32601) {
                    throw new \Exception("Bad parameters or method '{$one->method}' not found", -32601);
                }

                if(isset($one->id)) {
                    $error->id = $one->id;
                }
                else {
                    $error->id = null;
                }

                $responses[] = $error;
            }
        }

        // If the request is batch, return response batch
        if(is_array($request)) {
            return $responses;
        }

        // Return first response if the request is stdClass with id
        elseif($request instanceof \stdClass && isset($request->id)) {
            return $responses[0];
        }

        // Or return nothing
        else {
            return null;
        }
    }


    /**
     * End the execution with given response object
     *
     * Outputs the data into browser or not - depending on the setup.
     * Also, prepares $this->_output and $this->_rawOutput variables.
     *
     * @param stdClass $response
     * @return stdClass the response object
     */
    private function _end($response) {
        // Version 1.x doesn't understand the JSONRPC in the response
        if ($this->request_version < 2) {
            unset($response->jsonrpc);
        }

        $this->_output = $response;
        $this->_rawOutput = json_encode($response);

        // Output to browser if needed
        if($this->_supressOutput === false) {
            echo $this->_rawOutput;
        }

        return $this->_output;
    }

    /**
     * Construct the class
     */
    public function __construct() {
        $this->_output = array();
        $this->_rawOutput = '';
        $this->_supressOutput = false;
        $this->_isError = false;
        $this->_callbacks = array(
            'onbeforecall' => array(),
            'onsuccess'    => array(),
            'onerror'      => array(),
        );
        $this->_server = new \stdClass;

        /** Defaults to the same level as the calling code **/
        $current_error_level         = error_reporting();
        $this->_error_handling_level = $current_error_level;
    }

    /**
     * The setter -> used for the dot magic
     *
     * @param type $name
     * @param type $val
     */
    public function __set($name, $val) {
        // on* method?
        $lower = strtolower($name);
        if(isset($this->_callbacks[$lower])) {
            $this->addCallback($lower, $val);
            return;
        }

        // The dot magic
        $exploded = explode('.', $name);
        $method   = array_pop($exploded);
        $current  = $this;
        foreach($exploded as $one) {
            if(!isset($current->$one)) {
                $current->$one = new \stdClass;
            }

            $current = $current->$one;
        }

        // Append the variable / function
        $current->$method = $val;
    }

    /**
     * Getter for the dot magic
     */
    public function &__get($name) {
        // on* method?
        $lower = strtolower($name);
        if(isset($this->_callbacks[$lower])) {
            throw new \Exception('Getting the callback via __get is forbidden');
        }

        // The dot magic
        $exploded = explode('.', $name);
        $method   = array_pop($exploded);
        $current  = $this;
        foreach($exploded as $one) {
            if(!isset($current->$one)) {
                throw new \Exception('Method not found.', -32601);
            }

            $current = $current->$one;
        }

        // Append the variable / function
        $function = &$current->$method;
        return $function;
    }

    /**
     * Caller with the dot magic
     */
    public function __call($methodName, $args) {
        // on* method?
        $lower = strtolower($methodName);
        if(isset($this->_callbacks[$lower])) {
            foreach($this->_callbacks[$lower] as $one) {
                call_user_func($one, $this);
            }
            return;
        }

        // The dot magic
        $exploded = explode('.', $methodName);
        $function = array_pop($exploded);
        $current  = $this;
        foreach($exploded as $one) {
            if(!isset($current->$one)) {
                throw new \Exception("Method not found. ($methodName)", -32601);
            }

            $current = $current->$one;
        }

        // Get the reflection
        try {
            if(isset($current->$function)) {
                $method = $this->_getReflection($current->$function);
            }
            else {
                $method = array(
                    'reflection' => new \ReflectionMethod($current, $function),
                    'object' => $current,
                );
            }
        }
        catch(\Exception $e) {
            throw new \Exception("Procedure not found. ($methodName)", -32601);
        }

        // Call with named arguments
        if($args instanceof \stdClass) {
            $pass   = array();
            foreach($method['reflection']->getParameters() as $param) {
                if(isset($args->{$param->getName()})) {
                    $pass[] = $args->{$param->getName()};
                }
                else {
                    if(!$param->isOptional()) {
                        throw new \Exception('Invalid params', -32602);
                    }

                    $pass[] = $param->getDefaultValue();
                }
            }

            $args = $pass;
        }

        // No arguments?
        if(empty($args)) {
            $args = array();
        }

        // Check the ammount of arguments
        $wanted = $method['reflection']->getNumberOfRequiredParameters();
        if($wanted > count($args)) {
            throw new \Exception('Invalid params', -32602);
        }

        // Invoke
        if($method['object'] === false) {
            return $method['reflection']->invokeArgs($args);
        }
        else {
            return $method['reflection']->invokeArgs($method['object'], $args);
        }
    }

    /**
     * Unsetter
     */
    public function __unset($name) {
        // The dot magic
        $exploded = explode('.', $name);
        $method = array_pop($exploded);
        $current = $this;
        foreach($exploded as $class) {
            $current = $current->$class;
        }

        unset($current->$method);
    }

    /**
     * Issetter
     */
    public function __isset($name) {
        // The dot magic
        $exploded = explode('.', $name);
        $method = array_pop($exploded);
        $current = $this;
        foreach($exploded as $class) {
            $current = $current->$class;
        }

        return isset($current->$method);
    }

    /**
     * Add onBeforeCall, onSuccess, onError callbacks
     *
     * @param string $name onBeforeCall || onSuccess || onError
     * @param callable $callback
     * @return Server fluent interface
     * @throws InvalidArgumentException
     */
    public function addCallback($name, $callback) {
        $name = strtolower($name);
        if(!isset($this->_callbacks[$name])) {
            throw new \InvalidArgumentException('Callback "' . $name . '" is not a valid callback. Use onBeforeCall, onSuccess or onError callbacks.');
        }
        if(!is_callable($callback)) {
            throw new \InvalidArgumentException('Callback "' . print_r($callback, true) . '" is not a valid callback');
        }

        $this->_callbacks[$name][] = $callback;
        return $this;
    }

    /**
     * Clear callback entirely
     *
     * @param string $name onBeforeCall || onSuccess || onError
     * @return Server fluent interface
     * @throws InvalidArgumentException
     */
    public function clearCallback($name) {
        $name = strtolower($name);
        if(!isset($this->_callbacks[$name])) {
            throw new \InvalidArgumentException('Callback "' . $name . '" is not a valid callback. Use onBeforeCall, onSuccess or onError callbacks.');
        }

        $this->_callbacks[$name] = array();
        return $this;
    }

    /**
     * TRUE if the server is not supposed to send anything to browser
     * If so, use $this->getOutput or $this->getRawOutput after handle()
     *
     * @param bool $supress
     * @return Server fluent interface
     */
    public function supressOutput($supress = true) {
        $this->_supressOutput = (bool)$supress;
        return $this;
    }

    /**
     * The magic happens here
     *
     * @param mixed $params either JSON string or the parameters directly
     */
    public function handle($params = null) {
        header("Content-Type: application/json", true);

        // Setup error handler
        $handler = set_error_handler(array($this, '_errorHandler'), $this->_error_handling_level);

        // Prepare current output -> in case of wrong json string
        $error                 = new \stdClass;
        $error->jsonrpc        = '2.0';
        $error->error          = new \stdClass;
        $error->error->code    = -32700;
        $error->error->message = 'Parse error.';
        $error->id             = null;

        // Build the "last" object so we can reference it later
        $this->last = new \stdClass;

        // Callback time!
        try {
            $this->onBeforeCall($this);
        } catch (\Exception $e) {
            // Wrap the exception into request
            $error->error->code = $e->getCode();
            $error->error->message = get_class($e) . ': ' . $e->getMessage();
            $this->onError($this);
            return $this->_end($error);
        }

        // Raw json string?
        if(is_string($params)) {
            $input = json_decode($params);

            // End immidiatelly
            if($input === null) {
                $this->onError($this);
                return $this->_end($error);
            }
        // Already a set of parameters?
        } elseif($params !== null) {
            $input = $params;
        // Attemping a GET call but they're not enabled
        } elseif(!$this->_allow_get_calls && isset($_GET['method'])) {
            $error->error->message = 'GET method calls are not allowed';
            $this->onError($this);
            return $this->_end($error);
        // A valid GET call
        } elseif($this->_allow_get_calls && isset($_GET['method'])) {
            $method = $_GET['method'];
            $p_str  = $_GET['params'] ?? "";

            $params = array();
            if ($p_str) {
                $params = preg_split("/,/",$p_str);
            }

            $input          = new \stdClass;
            $input->method  = $method;
            $input->params  = $params;
            $input->id      = 4;
            $input->jsonrpc = '2.0';

            $this->method = $method;
            $this->params = $params;

            $this->request_version = $input->jsonrpc;
        // From raw post data
        } else {
            $rawPost = trim(file_get_contents('php://input'));
            $input   = json_decode($rawPost);

            // Some weird stuff going on here
            if(is_string($input)) {
                $input = json_decode($input);
            }

            // Set the JSONRPC version for later
            $ver = $this->get_request_version($input);

            $this->last->method = '';
            $this->last->params = array();

            // Store the request version so we can respond properly
            $this->request_version       = $ver;
            $this->last->request_version = $ver;

            // End immidiatelly?
            if($input === null) {
                $error->error->message = 'Null input';
                $this->onError($this);
                return $this->_end($error);
            }
        }

        // ------------------------- Execution time ----------------------------
        try {
            $output = $this->_handle($input);
            $this->onSuccess($this);

            if($handler) {
                set_error_handler($handler);
            }

            return $this->_end($output);
        }
        catch(\Exception $e) {
            // restore error handler
            if($handler) {
                set_error_handler($handler);
            }

            // Wrap the exception into request
            $error->error->code = $e->getCode();
            $error->error->message = get_class($e) . ': ' . $e->getMessage();
            $this->onError($this);
            return $this->_end($error);
        }
    }

    /**
     * Gets structured output from the server
     *
     * @return stdClass
     * @throws InvalidStateException
     */
    public function getOutput() {
        if(empty($this->_output)) {
            throw new \InvalidStateException('You are requesting output from server while the handle() function has not been called');
        }

        return $this->_output;
    }

    /**
     * Get raw output
     *
     * @return string
     * @throws InvalidStateException
     */
    public function getRawOutput() {
        if(empty($this->_rawOutput)) {
            throw new \InvalidStateException('You are requesting output from server while the handle() function has not been called');
        }

        return $this->_rawOutput;
    }

    /**
     * Returns true if there has been an error
     */
    public function isError() {
        return $this->_isError;
    }

    /**
     * Error handler
     */
    public function _errorHandler($severity, $message, $file = null, $line = null, $context = null) {
        // ignore error messages from expressions prepended the at sign (@) see https://www.php.net/manual/en/language.operators.errorcontrol.php
        if (error_reporting() === 0) { 
            return false;
        }        
        throw new \ErrorException($message . "\n" . 'in file ' . $file . "\n" . 'on line ' . $line, 0, $severity, $file, $line);
    }

    /**
     * Sets the error level to be reported when handling server callbacks
     *
     * @param int $level
     * @see http://www.php.net/manual/en/function.error-reporting.php
     */
    public function setErrorHandlingLevel($level) {
        $this->_error_handling_level = $level;
    }

    private function get_request_version($i) {
        if (isset($i->jsonrpc)) {
            $ver = $i->jsonrpc;
        } elseif (isset($i->version)) {
            $ver = $i->version;
        } elseif (isset($i->method)) {
            $ver = 1.0;
        } else {
            $ver = false;
        }

        return $ver;
    }

    private function log($str) {
        $str  = trim($str);
        $time = date("H:i:s");
        $str  = "$time $str";
        $file = "/tmp/test.log";

        if (is_writable($file)) {
            file_put_contents($file,$str,FILE_APPEND);
        }
    }
}

// vim: tabstop=4 shiftwidth=4 expandtab autoindent softtabstop=4
