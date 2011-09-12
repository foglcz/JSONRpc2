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
 * Class for testing user.* methods
 * 
 * @author Pavel Ptacek
 */
class UserPresenter extends BasePresenter {
    /**
     * user.getInfo
     */
    public function renderGetInfo() {
        $this->template->formData = array(
            'method' => 'user.getInfo',
            'params' => array(
                'access_token' => $this->getToken(),
                'last_update'  => 'timestamp:optional',
            ),
        );
    }
    
    /**
     * user.store
     */
    public function renderStore() {
        // Create user object for the request generator
        $obj = new \stdClass;
        $obj->_objectName = 'user_object';
        $obj->id = null;
        $obj->language = array('czech', 'english', 'german');
        $obj->name = null;
        $obj->surname = null;
        $obj->gender = array('male', 'female');
        $obj->year_of_birth = null;
        $obj->email = null;
        $obj->password = null;
        $obj->last_logins = '~array~';
        $obj->registered = false; // checkbox
        
        // Create sub-data object
        $sub = new \stdClass;
        $sub->_objectName = 'sub_object';
        $sub->enum = array('one', 'two', 'three');
        $sub->something = null;
        $sub->def = 'default value';
        
        // And assign the sub object to the user object
        $obj->subobject = $sub;
        
        // And append the formdata
        $this->template->formData = array(
            'method' => 'user.store',
            'params' => array(
                'access_token' => $this->getToken(),
                'user'         => $obj,
            ),
        );
    }
}