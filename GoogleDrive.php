<?php

class MaestroKHGoogleDrive{
    //Every public function name in the class start with a undersorce,and private function vice versa.
    //This is because of two methods to catch all Exception thorw inside this class.(check __call() and __callStatic() for detail)
    //However,all public function should invoke normally without the undersorce, 
    //eg. $googleDrive = new MaestroKHGoogleDrive();
    //$googleDrive->setSubject('abc@abc.com')->getFiles();

    private $_google,
    $_service,
    $_scopes = [
        'https://www.googleapis.com/auth/drive',
        'https://www.googleapis.com/auth/drive.file',
        'https://www.googleapis.com/auth/drive.metadata'
    ],
    $_selectedFile = [],
    $_selectedFilePermission = [];


    function __construct($clientSecretPath){
        $this->_google = new Google_Client();
        $this->_google->setAuthConfig($clientSecretPath);
        $this->_google->setScopes($this->_scopes);
        $this->_service = new Google_Service_Drive($this->_google);
        
    }

    public function __call($name, $args){
        try{
            if(method_exists($this, '_'.$name)){
                return call_user_func_array([$this, '_'.$name], $args);
            }
        }catch (Exception $e){
            echo $e->getMessage();
            return false;
        }
        throw new BadMethodCallException(': Call to undefined method MaestroKHGoogleDrive::'.$name.'()');
    }

    public static function __callStatic($name, $args){
        try{
            if(method_exists('MaestroKHGoogleDrive', '_'.$name)){
                return call_user_func_array(['MaestroKHGoogleDrive', '_'.$name], $args);
            }
        }catch (Exception $e){
            echo $e->getMessage();
            return false;
        }
        throw new BadMethodCallException(': Call to undefined method MaestroKHGoogleDrive::'.$name.'()');
    }

    private function addRole($roleName, $fileId, $email, $optParams = []){
        $permission = new Google_Service_Drive_Permission();
        $permission->setType('user');
        $permission->setRole($roleName);
        $permission->setEmailAddress($email);

        $this->_service->permissions->create($fileId, $permission, $optParams);
    }

    private function setRole($roleName, $fileId, $permissionId, $optParams = []){
        $newPermission = new Google_Service_Drive_Permission();
        $newPermission->setRole($roleName);

        $this->_service->permissions->update($fileId, $permissionId, $newPermission, $optParams);
    }

    public function _setSubject($email){
        $this->_google->setSubject($email);
        return $this;
    }

    public function _selectFile($fileId){
        //This function should predefine file data and permission,so it wont need to fetch for every action.

    }

    public function _setReader($fileId, $email){

        $permissionId = null;
        $permissions = $this->getFilePermissions($fileId);

        foreach($permissions as $permission){
            if($permission['emailAddress'] == $email){
                $permissionId = $permission['id'];
                break;
            }
        }

        if($permissionId == null){
            $this->addRole('reader', $fileId, $email);
        }else{
            $this->setRole('reader', $fileId, $permissionId);
        }

        return $this;
    }

    public function _setWriter($fileId, $email){

        $permissionId = null;
        $permissions = $this->getFilePermissions($fileId);

        foreach($permissions as $permission){
            if($permission['emailAddress'] == $email){
                $permissionId = $permission['id'];
                break;
            }
        }

        if($permissionId == null){
            $this->addRole('writer', $fileId, $email);
        }else{
            $this->setRole('writer', $fileId, $permissionId);
        }

        return $this;
    }

    public function _setCommenter($fileId, $email){

        $permissionId = null;
        $permissions = $this->getFilePermissions($fileId);

        foreach($permissions as $permission){
            if($permission['emailAddress'] == $email){
                $permissionId = $permission['id'];
                break;
            }
        }

        if($permissionId == null){
            $this->addRole('commenter', $fileId, $email);
        }else{
            $this->setRole('commenter', $fileId, $permissionId);
        }

        return $this;
    }

    public function _setOwner($fileId, $email){
    //Origin owner will downgrade to writer.

        $optParams = ['transferOwnership'=>1];
        $permissionId = null;
        $permissions = $this->getFilePermissions($fileId);

        foreach($permissions as $permission){
            if($permission['emailAddress'] == $email){
                $permissionId = $permission['id'];
                break;
            }
        }

        if($permissionId == null){
            $this->addRole('owner', $fileId, $email, $optParams);
        }else{
            $this->setRole('owner', $fileId, $permissionId, $optParams);
        }

        return $this;
    }

    public function _setReadOnly($fileId){
        $permissions = $this->getFilePermissions($fileId);

        $this->_google->setUseBatch(true);
        $batch = new Google_Http_Batch($this->_google);

        foreach($permissions as $permission){
            if($permission['role'] != 'owner'){
                $newPermission = new Google_Service_Drive_Permission();
                $newPermission->setRole('reader');
                $batch->add($this->_service->permissions->update($fileId, $permission->id, $newPermission));
            }
        }

        $batch->execute();
        $this->_google->setUseBatch(false);

        return $this;
    }

    public function _removePermission($fileId, $email){
        $permissionId = null;
        $permissions = $this->getFilePermissions($fileId);

        foreach($permissions as $permission){
            if($permission['emailAddress'] == $email){
                $permissionId = $permission['id'];
                break;
            }
        }

        if($permissionId == null) return false;
        $this->_service->permissions->delete($fileId, $permissionId);
        return $this;
    }

    static public function _getIdFromUrl($url){
        $pattern = '/(?:\/d\/)(.*)(?:\/)/';
        $matches = [];
        preg_match($pattern, $url, $matches);
        if(empty($matches)) return false;
        return $matches[1];
    }

    public function _getFiles($name = null){
        $pageToken = null;
        $result = [];
        $optParams = $name == null ? [] : ['q'=>"name='{$name}'"] ;
        do{
            if($pageToken){
                $optParams = array_merge($optParams, ['pageToken'=>$pageToken]);
            }
            $files = $this->_service->files->listFiles($optParams);
            $result = array_merge($result, $files->getFiles());
            $pageToken = $files->getNextPageToken();
        }while($pageToken);
        return $result;
    }

    public function _getFilePermissions($fileId){

        $pageToken = null;
        $result = [];
        $optParams = ['fields'=>'permissions(displayName,emailAddress,id,role,type)'];
        do{
            if($pageToken){
                $optParams = array_merge($optParams, ['pageToken'=>$pageToken]);
            }
            $permissions = $this->_service->permissions->listPermissions($fileId, $optParams);
            $result = array_merge($result, $permissions->getPermissions());
            $pageToken = $permissions->getNextPageToken();
        }while($pageToken);
        return $result;
    }

    public function _getClient(){
        return $this->_google;
    }

    public function _getService(){
        return $this->_service;
    }

}
