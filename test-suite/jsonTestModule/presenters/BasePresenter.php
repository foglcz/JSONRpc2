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

namespace jsonTestModule;

/**
 * The base presenter
 * 
 * @author Pavel Ptacek
 */
class BasePresenter extends \Nette\Application\UI\Presenter {
    /** @var string absolute URL to your endpoint of json server */
    private $endpoint;
    
    public function startup() {
        parent::startup();
        
        if(!$this->isLoggedIn() && $this->getAction() != 'jsonAccessLogin') {
            $this->redirect('Base:jsonAccessLogin');
        }
        
        // Setup endpoint
        $this->endpoint = $this->link('//:JsonTestSuite:default');
    }
    
    /**
     * Gets token from session
     */
    public function getToken() {
        $sess = $this->getSession('Access');
        if(!empty($sess->token)) {
            return $sess->token;
        }
        
        return '';
    }
    
    /**
     * Determines whether the user has logged in into the test suite api
     */
    public function isLoggedIn() {
        $sess = $this->getSession('ApiTest');
        if(!isset($sess->auth)) {
            return false;
        }
        
        return (bool)$sess->auth;
    }
    
    /**
     * Renders the login action of the user
     */
    public function handleLogin() {
        $users = array(
            'test' => sha1('test'),
        );
        
        if(empty($_POST['username']) || empty($_POST['pass'])) {
            $this->flashMessage('Invalid username or password');
            return;
        }
        
        // validate
        $username = strtolower(trim($_POST['username']));
        $pass = sha1($_POST['pass']);
        
        if(!isset($users[$username])) {
            $this->flashMessage('Invalid username or password');
            return;
        }
        
        if($users[$username] === $pass) {
            $this->getSession('ApiTest')->auth = true;
            $this->redirect('Base:listModules');
            return;
        }
        
        $this->flashMessage('Invalid username or password');
        return;
    }
    
    /**
     * Handles generic send of the payload
     */
    public function handleSendJson($method) {
        // Prepare the request
        $args = $this->_mapJson($_POST, $_POST);
        
        // Showtime!
        $client = new \Lightbulb\Json\Rpc2\Client($this->endpoint);
        $client->_debug();
        $return = $client->__call($method, $args);
        
        // Assign into the template
        $this->template->jsonRequest = \Lightbulb\Json\Rpc2\Client::formatJson($client->_getRequest());
        $this->template->jsonResponse = \Lightbulb\Json\Rpc2\Client::formatJson($client->_getResponse());
        $this->template->parsedResponse = $return;
    }
    
    /**
     * Maps POST-ed data onto json data class
     */
    private function _mapJson($data, $sendArr) {
        $args = array();
        foreach($data as $key => $val) {
            if(strpos($key, 'json_') !== 0) {
                continue;
            }
            $jsonKey = substr($key, 5);
            
            // Check for skipping of the value --> if the val is array, we check
            // for object. If it's not, then we simply check the $sendArr
            if(strpos($jsonKey, '_checkbox_') !== 0 && !is_array($val) && !isset($sendArr['send_' . $jsonKey])) {
                continue;
            }
            elseif(strpos($jsonKey, '_checkbox_') !== 0 && is_array($val) && !isset($sendArr['send_' . $jsonKey . '__object'])) {
                continue;
            }

            if(is_array($val)) {
                $arr = $this->_mapJson($val, $sendArr['send_' . $jsonKey], true);
                $args[$jsonKey] = $this->_mapArrToObj($arr);
            }
            elseif(strpos($jsonKey, '_checkbox_') === 0) {
                $jsonKey = substr($jsonKey, strlen('_checkbox_'));
                if(isset($data['json_' . $jsonKey])) {
                    $args[$jsonKey] = true;
                }
                else {
                    $args[$jsonKey] = false;
                }
            }
            else {
                $args[$jsonKey] = $val;
            }
        }
        
        return $args;
    }
    
    /**
     * Map array to stdClass
     */
    private function _mapArrToObj($arr) {
        // check for numeric keys only
        $keys = array_keys($arr);
        $return = true;
        $copy = array();
        foreach($keys as $one) {
            if(!is_int($one)) {
                $return = false;
                break;
            }
            
            if(!empty($arr[$one])) {
                $copy[] = $arr[$one];
            }
        }
        
        if($return === true) {
            return $copy;
        }
        
        // OK! Map to the stdclass
        $out = new \stdClass;
        foreach($arr as $key => $val) {
            if(is_array($val)) {
                $out->{$key} = $this->_mapArrToObj($val);
            }
            else {
                $out->{$key} = $val;
            }
        }
        return $out;
    }
    
    /**
    * Formats view template file names.
    * @return array
    */
    public function formatTemplateFiles() {
        $name = $this->getName();
        $presenter = substr($name, strrpos(':' . $name, ':'));
        $dir = dirname(dirname($this->getReflection()->getFileName()));
        $tpl = array(
            "$dir/templates/$presenter/$this->view.latte",
            "$dir/templates/$presenter.$this->view.latte",
            "$dir/templates/$presenter/$this->view.phtml",
            "$dir/templates/$presenter.$this->view.phtml",
        );
        
        // Append template based on moduleFunction / moduleList
        if($this->getAction() == 'default') {
            $tpl[] = "$dir/templates/Base/moduleDefault.latte";
        }
        else {
            $tpl[] = "$dir/templates/Base/moduleMethod.latte";
        }
        
        return $tpl;
    }
    
    /**
     * Loads list of modules within the namespace
     */
    public function beforeRender() {
        // Get the presenters from this module
        $files = \Nette\Utils\Finder::findFiles('*Presenter.php')->from(APP_DIR . '/' . __NAMESPACE__);
        $out = array();
        
        foreach($files as $file) {
            $presenter = substr($file->getFilename(), 0, strlen('Presenter.php')*-1);
            if($presenter == 'Base') {
                continue;
            }
            
            $out[] = array(
                'link' => $presenter . ':default',
                'name' => strtolower($presenter) . '.*',
            );
        }
        
        $this->template->modules = $out;
        
        // Load the current module if applicable
        if($this->getName() != 'jsonTest:Base') {
            $current = explode(':', $this->getName());
            $this->template->currentModule = strtolower(end($current));
            
            // & load methods of the class
            $reflection = new \ReflectionObject($this);
            $out = array();
            foreach($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if(strpos($method->getName(), 'render') !== 0) {
                    continue;
                }
                
                $name = substr($method->getName(), strlen('render'));
                $name = strtolower($name);
                $out[] = array(
                    'name' => $this->template->currentModule . '.' . $name,
                    'link' => $this->template->currentModule . ':' . $name,
                );
            }
            
            $this->template->methods = $out;
        }
    }
    
}