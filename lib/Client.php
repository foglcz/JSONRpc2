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


/**
 * This is JSON-RPC version 2 client
 *
 * Conforms http://groups.google.com/group/json-rpc/web/json-rpc-2-0?pli=1
 *
 * Usage:
 * <code>
 * $client = new Lightbulb\Json\Rpc2\Client('http://endpoint');
 * $return = $client->method(arg1, arg2, ...);
 *
 * // optionally add custom curl headers (for example: timeout)
 * $client->setOption(CURLOPT_TIMEOUT, 400);
 *
 * // Results in method "math.sum"
 * $return = $client->math->sum(arg1, arg2, arg3, ...);
 *
 * // Any level of nesting is possible, hence following is valid:
 * $return = $client->strings->hash->encode("string");
 *
 * // You can also use
 * $return = $client->__call('strings.hash.encode', 'string');
 * </code>
 *
 * Currently, the implementation does not support batch calls from the client side
 * -> the server however, does.
 *
 * @author Pavel Ptacek
 */
class Client {
    /**
     * Determines indentation for debugging (self::formatJsonString)
     */
    const DEBUG_INDENT = 4;

    /** @var string */
    protected $_endpoint;

    /** @var bool */
    protected $_debug;

    /** @var array */
    private $_callstack;

    /** @var int */
    private $_id;

    /** @var string */
    protected $_debugRequest;

    // curl additonal options
    /** @var array */
    private $options = array();

    /** @var string */
    protected $_debugResponse;

    /**
     * Creates json-conforming request
     *
     * @param type $method
     * @param type $args
     * @return string
     */
    protected function _requestFactory($method, $args) {
        $request = new \stdClass;
        $request->jsonrpc = '2.0';
        $request->method = $method;
        $request->params = $args;
        $request->id = $this->_id++;
        return json_encode($request);
    }

    /**
     * add CURL option - see https://www.php.net/manual/en/function.curl-setopt.php
     *
     * @param int $option
     * @param mixed $value
     * @return void
     */
    public function setOption($option, $value) {
        $this->options[$option] = $value;
    }

    /**
     * Creates new cURL handle
     */
    protected function &_curlFactory($data) {
        $options = array(
            CURLOPT_FRESH_CONNECT => false,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $data,
        );
        foreach ($this->options as $option => $value) {
            $options[$option] = $value;
        }

        $curl = curl_init($this->_endpoint);
        curl_setopt_array($curl, $options);
        return $curl;
    }

    /**
     * The magic getter in order to make class.method calls possible
     */
    public function __get($name) {
        $this->_callstack[] = $name;
        return $this;
    }

    /**
     * The RPC actual caller
     */
    public function __call($method, $args) {
        // use callstack or not?
        if(strpos($method, '.') === false && count($this->_callstack) > 0) {
            $method = implode('.', $this->_callstack) . '.' . $method;
        }

        // Empty callstack, construct cURL object, call and return
        $this->_callstack = array();
        $request = $this->_requestFactory($method, $args);
        $curl    = $this->_curlFactory(json_encode($request));
        $raw     = curl_exec($curl);
        $return  = json_decode($raw);

        // Debugging?
        if($this->_debug === true) {
            $this->_debugRequest = $request;
            $this->_debugResponse = $raw;
        }

        return $return;
    }

    /**
     * Create new client to an endpoint
     *
     * @param string $endpointUrl
     */
    public function __construct($endpointUrl) {
        $this->_endpoint  = $endpointUrl;
        $this->_callstack = array();
        $this->_id = 0;
        $this->_debug = false;
    }

    /**
     * Send batch of requests into the server and return the response
     *
     * @param array $batch
     * @return array|null
     */
    public function _batchRequest($method, array $batch = array()) {
        $data = array();
        foreach($batch as $one) {
            $data[] = $this->_requestFactory($method, $one);
        }

        // Build the curl, execute and return
        $curl = $this->_curlFactory($data);
        $raw  = curl_exec($curl);
        $return = json_decode($raw);

        // Debug!
        if($this->_debug === true) {
            $this->_debugRequest = $data;
            $this->_debugResponse = $raw;
        }

        return $return;
    }

    /**
     * Enable or disable debug
     */
    public function _debug($enable = true) {
        $this->_debug = (bool)$enable;
    }

    /**
     * Get raw request string
     *
     * @return string|array (array if batch request)
     */
    public function _getRequest() {
        return $this->_debugRequest;
    }

    /**
     * Get raw response output
     *
     * @return string
     */
    public function _getResponse() {
        return $this->_debugResponse;
    }

    /**
     * Format the json string from debugging functions & return it
     *
     * @param string|array $jsonData
     * @return string|array
     */
    public static function formatJson($jsonData) {
        if(is_array($jsonData)) {
            $out = array();
            foreach($jsonData as $one) {
                $out[] = self::_formatJsonActual($one);
            }
            return $out;
        }

        // Or return the formatted string
        return self::_formatJsonActual($jsonData);
    }

    /**
     * Actually formats the data
     *
     * @param string $jsonData
     * @return string
     */
    private static function _formatJsonActual($jsonData) {
        $len   = strlen($jsonData);
        $out   = '';
        $level = 0;

        // Char-by-char
        $actionChars = array('{', '}', ':', ',', '"');
        $inQuotes = false;
        for($i = 0; $i < $len; ++$i) {
            $c = $jsonData[$i];

            if(!in_array($c, $actionChars)) {
                $out .= $c;
                continue;
            }

            // If we have ":", then we just add space before & after
            if($c == ':' && $inQuotes == false) {
                $out .= ' : ';
                continue;
            }
            elseif($c == ':') {
                $out .= ':';
                continue;
            }

            // If we have {, increment the nesting
            if($c == '{') {
                $level++;
                $out .= $c;
                $out .= "\n";

                if($level > 0) {
                    $out .= str_repeat(' ', $level * self::DEBUG_INDENT);
                }

                continue;
            }

            // If we have , -> newline
            if($c == ',') {
                $out .= ',';
                $out .= "\n";

                if($level > 0) {
                    $out .= str_repeat(' ', $level * self::DEBUG_INDENT);
                }

                continue;
            }

            // If we have }, then decrement nesting
            if($c == '}') {
                $appendIndent = false;

                // Check if next character is comma
                if($c == '}' && ($i+1) < $len && $jsonData[$i+1] == ',') {
                    $c .= ',';
                    $i++;
                    $appendIndent = true;
                }

                $level--;

                $indentLevel = $level * self::DEBUG_INDENT;
                $out .= "\n";
                if($indentLevel > 0) {
                    $out .= str_repeat(' ', $level * self::DEBUG_INDENT);
                }
                $out .= $c;
                $out .= "\n";

                if($appendIndent === true && $indentLevel > 0) {
                    $out .= str_repeat(' ', $level * self::DEBUG_INDENT);;
                }

                continue;
            }

            // If we have quote, mark it
            if($c == '"') {
                $inQuotes = !$inQuotes;
            }
        }

        return $out;
    }
}
