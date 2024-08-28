<?php
class LoginAPI extends MainPage { 
    public function OnPreInit ($param) {	
		parent::onPreInit ($param);	
		$this->MasterClass="Application.layouts.LoginTemplate";				
        $this->Theme='nifty';
	}
	public function onLoad($param) {		
		parent::onLoad($param);				
		if (!$this->IsPostBack&&!$this->IsCallBack) { 
            $this->loggerlogin->Visible=$this->setup->getSettingValue('jslogger');            
		}
	}    
	public function checkUsernameAndPassword($sender,$param) {		
        $username=addslashes($param->Value);
        if ($username != '') {
            try {  
                $auth = $this->Application->getModule ('auth');	
                $page='Api';		
                $password = trim(addslashes($this->txtPassword->Text));
                $auth->login ($username.'/'.$page,$password);	
            }catch (Exception $e) {		
                $message='<br /><div class="alert alert-danger">
                    <strong>Error!</strong>
                    '.$e->getMessage().'</div>';
				$sender->ErrorMessage=$message;					
				$param->IsValid=false;		
			}
        }							
		
	}    
    public function checkUsernameFormat($sender,$param) {		
        $username=addslashes($param->Value);
        if ($username != '') {
            try {  
                if (!preg_match('/^[a-z\d_]{1,20}$/i', $username)||filter_var($username,FILTER_VALIDATE_EMAIL)) {			                    
                    throw new Exception ("Gagal. Format penulisan username tidak dikenali.");
                }
            }catch (Exception $e) {		
                $message='<br /><div class="alert alert-danger">
                    <strong>Error!</strong>
                    '.$e->getMessage().'</div>';
				$sender->ErrorMessage=$message;					
				$param->IsValid=false;		
			}
        }							
		
	}
    public function doLogin ($sender,$param) {
        if ($this->IsValid) {                        
            $pengguna=$this->getLogic('Users');    
            $_SESSION['foto']='resources/userimages/no_photo.png';
            $_SESSION['theme']=$pengguna->getDataUser('theme');            
            $_SESSION['outputreport']='pdf';
            $_SESSION['outputcompress']='none';            
            $this->redirect('Home',true);
        }
    }    
}