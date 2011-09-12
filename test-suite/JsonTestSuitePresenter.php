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
final class JsonTestSuitePresenter extends BasePresenter {
    public function renderDefault() {
        // Get server
        $server = new Lightbulb\Json\Rpc2\Server;
        $server->access = new AccessHandler;
        $server->user = new UserHandler;
        
        // Supress handling of output, as we are sending it differently
        $server->supressOutput();
        
        // When running the request, we need to check whether there has been anything returned. If not, 
        // then we have to send an empty text response, as Nette\...\JsonResponse throws exception
        // on empty responses.
        $response = $server->handle();
        if(!empty($response) || is_array($response)) { // is_array in order to send []
            $this->sendResponse(new \Nette\Application\Responses\JsonResponse($response));
        }
        else {
            $this->sendResponse(new \Nette\Application\Responses\TextResponse(''));
        }
    }
}

/**
 * Access handler used for examples
 */
class AccessHandler {
    const TOKEN  = '123';
    const SECRET = '456';
    
    /**
     * Log-in the user into API
     * @param string $email
     * @param string $password sha1 encoded
     * @param string $language czech|english|german
     */
    public function login($email, $password, $language) {
        // Handle the login function with database
        
        // Prepare the token:
        $output = new \stdClass;
        $output->token = self::TOKEN;
        $output->secret = self::SECRET;
        $output->valid  = date('Y-m-d H:i:s', strtotime('+14 days'));
        return $output;
    }
    
    /**
     * Log-out user
     * @param string $access_token 
     */
    public function logout($access_token) {
        self::validate($access_token);
        
        // .. remove the token from database ..
        
        return true;
    }
    
    /**
     * Extend the token
     * @param string $access_token
     * @param string $secret
     * @param string $language 
     */
    public function extendToken($access_token, $secret, $language) {
        self::validate($access_token);
        
        // Check secret
        if($secret != self::SECRET) {
            throw new \Exception('Invalid token secret', -31002);
        }
        
        // .. extend token within database & return new one ..
        $output = new \stdClass;
        $output->token = self::TOKEN;
        $output->secret = self::SECRET;
        $output->valid  = date('Y-m-d H:i:s', strtotime('+14 days'));
        return $output;        
    }
    
    /**
     * Static function for the sake of the example
     * 
     * @param string $access_token
     * @throws Exception
     * @return bool
     */
    public static function validate($access_token) {
        if($access_token != self::TOKEN) {
            throw new \Exception('Invalid access token', -31001); // note the error code
        }
        
        return true;
    }
}


/**
 * User handler used for examples
 */
class UserHandler {
    /**
     * Returns generic user information
     * @param string $access_token
     * @param mixed $last_update unix timestamp
     */
    public function getInfo($access_token, $last_update = null) {
        AccessHandler::validate($access_token);
        
        // Prepare the return object
        $ret = new \stdClass;
        $ret->user_id = 1;
        $ret->username = 'example user';
        $ret->roles = array('admin', 'user');
        
        // Check whether the last update has been provided or not
        // !! WARNING: you CANNOT use "===" operator, as the PHP is .. the way it is
        if($last_update != null) { // intentional ==
            $ret->requested_with_lastupdate = true;
        }
        else {
            $ret->requested_with_lastupdate = false;
        }
        
        return $ret;
    }
    
    /**
     * Stores given user object within database
     * @param string $access_token
     * @param stdClass $user 
     */
    public function store($access_token, $user) {
        AccessHandler::validate($access_token);
        
        // parse the user object, which is given as stdClass:
        // eg: $user = MyUserClass::fromJson($user);
        
        // And for the sake of example, return the sent user object
        $user->recieved = date('Y-m-d H:i:s');
        return $user;
    }
}