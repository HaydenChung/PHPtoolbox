<?php

class GoogleDrive{

    //Every public function name in the class start with a undersorce,and private function vice versa.
    //This is because of two methods to catch all Exception thorw inside this class.(check __call() and __callStatic() for detail)
    //However,all public function should invoke normally without the undersorce, eg. $GoogleDrive::getInstance();

    private $_google,
    $_service,
    $_scopes = [

    ];
    private static $_instance = null;

    function __construct($clientSecretPath){
        $this->_google = new Google_Client();
        $this->_google->setConfig($clientSecretPath);
        $this->_google->setScopes($this->_scopes);

        $this->_service = new Google_Service_Drive($this->_google);
        
    }

    //This instance won't change once it is created. Use new GoogleDrive() to create another client if needed.
    static function _getInstance($clientSecretPath){
        if($this->_instance === null){
            self::$_instance = new GoogleDrive($clientSecretPath);
        }

        return self::$_instance;
    }

    public function __call($name, $args){
        try{
            if(method_exists($this, '_'.name)){
                call_user_func_array([$this, '_'.$name], $args);
            }
        }catch (Exception $e){
            return false;
        }
    }

    public function __callStatic($name, $args){
        try{
            if(method_exists($this, '_'.name)){
                call_user_func_array([$this, '_'.$name], $args);
            }
        }catch (Exception $e){
            return false;
        }
    }

    public function _pickUser($email){
        $this->_google->selectActive($email);
        return $this;
    }

    public function _setReader($email){
      $permission = new Google_Service_Drive_Permission();
      $permission->setType('user');
      $permission->setRole('reader');
      $permission->setEmailAddress($email);
      return $this;
    }
}


