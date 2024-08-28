<?php
prado::using ('Application.MainPageAPI');
class CLogAktivitasUser extends MainPageAPI {
    public function onLoad($param) {        
        parent::onLoad($param);                 
        $this->showLogAktivitasUser=true;               
        if (!$this->IsPostBack&&!$this->IsCallBack) {   
             $this->populateData();                   
        }                
    }
    protected function populateData () {
    	$userid=$this->Pengguna->getDataUser('userid');
        $str = "SELECT idlog,halaman,aktivitas,date_activity FROM log_aktivitas_user WHERE userid='$userid' ORDER BY date_activity DESC";                         
        $this->DB->setFieldTable(array('idlog','halaman','aktivitas','date_activity'));
        $r = $this->DB->getRecord($str);  
        $result=array();
        while (list($k,$v)=each($r)) {
            $v['date_activity']=$this->Page->TGL->tanggal('d/m/Y H:m:s',$v['date_activity']);       
            $result[$k]=$v;
        }
        $this->RepeaterS->DataSource=$result;
        $this->RepeaterS->dataBind();        
    }
}