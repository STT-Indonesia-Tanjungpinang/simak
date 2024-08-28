<?php
prado::using ('Application.MainPageAPI');
class CHome extends MainPageAPI {
    public function onLoad($param) {        
        parent::onLoad($param);                 
        $this->showDashboard=true;               
        if (!$this->IsPostBack&&!$this->IsCallBack) {   
             $this->populateData();                   
        }                
    }
    protected function populateData () {
    	$userid=$this->Pengguna->getDataUser('userid');
        $str = "SELECT u.userid,u.username,u.nama,u.email,u.active,u.foto,u.ipaddress,u.token,u.logintime,u.active FROM user u WHERE page='api' AND userid='$userid'";                         
        $this->DB->setFieldTable(array('userid','username','nama','email','email','ipaddress','token','foto','logintime','active'));
        $r = $this->DB->getRecord($str);  
        $result=array();
        while (list($k,$v)=each($r)) {
            $v['logintime']=$v['logintime']=='0000-00-00 00:00:00'?'BELUM PERNAH':$this->Page->TGL->tanggal('d F Y',$v['logintime']);       
            $result[$k]=$v;
        }
        $this->RepeaterS->DataSource=$result;
        $this->RepeaterS->dataBind();        
    }  
    public function editRecord ($sender,$param) {
        $this->idProcess='edit';        
        $id=$this->getDataKeyField($sender,$this->RepeaterS);        
        $this->hiddenid->Value=$id;     
        
        $str = "SELECT userid,username,nama,email,ipaddress,active FROM user WHERE userid='$id'";
        $this->DB->setFieldTable(array('userid','username','nama','email','ipaddress','active'));
        $r=$this->DB->getRecord($str);
        
        $result=$r[1];          
        $this->txtEditNama->Text=$result['nama'];
        $this->txtEditEmail->Text=$result['email'];
        $this->hiddenemail->Value=$result['email'];     
        $this->txtEditIPAddress->Text=$result['ipaddress']; 
    }  
    public function checkEmail ($sender,$param) {
        $this->idProcess=$sender->getId()=='addEmail'?'add':'edit';
        $email=$param->Value;       
        if ($email != '') {
            try {   
                if ($this->hiddenemail->Value!=$email) {                    
                    if ($this->DB->checkRecordIsExist('email','user',$email)) {                                
                        throw new Exception ("Email ($email) sudah tidak tersedia silahkan ganti dengan yang lain.");       
                    }                               
                }                
            }catch (Exception $e) {
                $param->IsValid=false;
                $sender->ErrorMessage=$e->getMessage();
            }   
        }   
    }     
    public function updateData ($sender,$param) {
        if ($this->Page->isValid) {         
            $id=$this->hiddenid->Value;
            $nama = addslashes($this->txtEditNama->Text);
            $email = addslashes($this->txtEditEmail->Text);
            $ipaddress=addslashes($this->txtEditIPAddress->Text);
            
            if ($this->txtEditPassword1->Text == '') {
                $str = "UPDATE user SET email='$email',nama='$nama',ipaddress='$ipaddress' WHERE userid=$id";
            }else {
                $data=$this->Pengguna->createHashPassword($this->txtEditPassword1->Text);
                $salt=$data['salt'];
                $password=$data['password'];                              
                $str = "UPDATE user SET userpassword='$password',salt='$salt',ipaddress='$ipaddress',nama='$nama',email='$email' WHERE userid=$id";
            }
            $this->DB->updateRecord($str); 
            $this->redirect('Home',true);
        }
    }  
    public function resetToken ($sender,$param) {
    	$userid=$this->Pengguna->getDataUser('userid');
    	$data=$this->Pengguna->createHashPassword(mt_rand(1,1000));        
        $password=$data['password'];
        $str = "UPDATE user SET token='$password' WHERE userid=$userid";
        $this->DB->updateRecord($str); 
    	$this->redirect('Home',true);
    }
}