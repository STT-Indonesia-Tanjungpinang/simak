<?php

class MainTemplate extends TTemplateControl {
    public function onLoad($param) {
		parent::onLoad($param);		        
		if (!$this->Page->IsPostBack&&!$this->Page->IsCallback) {		
            $tipeuser = $this->Page->Pengguna->getTipeUser();
            $this->linkTopTASemester->NavigateUrl=$tipeuser=='sa'?$this->Page->constructUrl('settings.Variables', true):'#';
            $this->lblStatusUser->Text = $this->getStatusUser();
            $this->loggerJS->Visible=$this->Page->setup->getSettingValue('jslogger');
            
            $this->populateThemes();
            
		}        
	}
    public function populateThemes() {
        $themes = $this->Page->setup->getListThemes();
        $daftarthemes = array();
        foreach ($themes as $k => $v) {
            $daftarthemes[] = array('idtheme' => $k,'namatheme' => $v);
        }        
        $this->RepeaterTheme->DataSource=$daftarthemes;
        $this->RepeaterTheme->DataBind();
    }
    public function changeTheme ($sender, $param) {
        $theme=$sender->CommandParameter;        
        $_SESSION['theme'] = $theme;
        $userid = $this->Page->Pengguna->getDataUser('userid');
        $page = $this->Page->Pengguna->getDataUser('page');
        switch($page) {
            case 'm':
                $str = "UPDATE simak_user SET theme='$theme' WHERE userid='$userid'";
                $this->Page->DB->updateRecord($str);
            break;
            case 'mh':
                $str = "UPDATE profiles_mahasiswa SET theme='$theme' WHERE nim='$userid'";                 
                $this->Page->DB->updateRecord($str);
            break;
        }        
        $this->Parent->Page->redirect ('Home', true);
    }
    public function getStatusUser() {
        $page = $this->Page->Pengguna->getDataUser('page');
        $iconstatus='';
        switch($page) {
            case 'mh':
                $statusmhs = $this->Page->DMaster->getNamaStatusMHSByID ($this->Page->Pengguna->getDataUser('k_status'));
                $icon='<i class="fa fa-circle"></i> '.$statusmhs;            
            break;
        }
        return $iconstatus;
    }
    public function logoutUser ($sender, $param) {
        if (!$this->User->isGuest) {
            $this->Application->getModule ('auth')->logout();
            $this->Page->redirect('Login');
		}
    }
    
}