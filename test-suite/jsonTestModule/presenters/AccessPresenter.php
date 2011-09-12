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
 * The test presenter for access.* functions
 * 
 * @author Pavel Ptacek
 */
class AccessPresenter extends BasePresenter {
    /**
     * Prepare login function
     */
    public function renderLogin() {
        if(!empty($_POST['sha1enc'])) {
            $this->template->encrypted = sha1($_POST['sha1enc']);
        }
        
        if(!empty($this->template->parsedResponse) && !empty($this->template->parsedResponse->result)) {
            $this->getSession('Access')->token = $this->template->parsedResponse->result->token;
            $this->getSession('Access')->secret = $this->template->parsedResponse->result->secret;
        }
        
        $this->template->formData = array(
            'method' => 'access.login',
            'params' => array(
                'email'    => null,
                'password' => null,
                'language' => array('czech', 'english', 'slovak'),
            ),
        );
    }
    
    /**
     * Prepare logout function
     */
    public function renderLogout() {
        $sess = $this->getSession('Access');
        $token = isset($sess->token) ? $sess->token : null;
        
        if(!empty($this->template->parsedResponse)) {
            $s = $this->getSession('Access');
            unset($s->token);
            unset($s->secret);
        }
        
        $this->template->formData = array(
            'method' => 'access.logout',
            'params' => array(
                'access_token' => $token,
            ),
        );
    }
    
    /**
     * Prepare extend token function
     */
    public function renderExtendToken() {
        $sess = $this->getSession('Access');
        $token = isset($sess->token) ? $sess->token : null;
        $secret = isset($sess->secret) ? $sess->secret : null;
        
        $this->template->formData = array(
            'method' => 'access.extendToken',
            'params' => array(
                'access_token' => $token,
                'secret' => $secret,
                'language' => array('czech', 'english', 'german'),
            ),
        );
    }
}