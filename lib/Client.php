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
    /** @var string */
    protected $_endpoint;
    
    /** @var array */
    private $_callstack;
    
    /** @var int */
    private $_id;
    
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
     * Creates new cURL handle
     */
    protected function &_curlFactory($data) {
        $options = array(
            CURLOPT_FRESH_CONNECT => true,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $data,
        );
        
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
        $return  = json_decode(curl_exec($curl));
        curl_close($curl);
        
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
        $return = json_decode(curl_exec($curl));
        curl_close($curl);
        return $return;
    }
}