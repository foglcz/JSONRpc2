<?php
/**
 * This file is part of the Lightbulb Project
 * 
 * @copyright Pavel Ptacek and Animal Group <birdie at animalgroup dot cz>
 * @license New BSD License
 */

/**
 * This is example implementation under Nette Framework (TM)
 * 
 * @author Pavel Ptacek
 */
final class JsonPresenter extends BasePresenter {
    public function renderDefault() {
        // Get server
        $server = new Lightbulb\Json\Rpc2\Server;
        
        // Bind available functions
        $server->myTest = new MyTestHandler; // contains mytest.* methods
        $server->myFunction = function($param1, $param2) { /* ... */ };
        $server->{'mytesthandler.myfunc'} = array($myObject, 'myMethod');
        $server->myStaticHandler = 'MyStaticClass::theStaticFunction';
        
        // Bind events
        $server->onBeforeCall[] = function($server) {};
        $server->onBeforeCall[] = function($server) {};
        $server->onSuccess[] = function($server) {};
        $server->onError[] = function($server) {};
        
        // Another way of in-binding the events; it does *not* remove the last one
        $server->onError = function($server) {};
        
        // Supress handling of output, as we are sending it differently
        $server->supressOutput();
        
        // Showtime!
        $this->sendResponse(new \Nette\Application\Responses\JsonResponse($server->handle()));
    }
    
    /* test usage within the same presenter */
    public function renderTest() {
        $client = new Lightbulb\Json\Rpc2\Client('http://localhost/nette/endpoint');
        
        // Call functions that are binded in renderDefault method
        $result = $client->myTest->someFunc($param1, $param2);
        $result = $client->myFunction($param1, $param2);
        $result = $client->myTestHandler->myFunc();
        $result = $client->myStaticHandler($param);
        
        // $result always contain as per the RFC either:
        var_dump($result->result);
        
        // Or an error if there has been some
        var_dump($result->error);
    }
}