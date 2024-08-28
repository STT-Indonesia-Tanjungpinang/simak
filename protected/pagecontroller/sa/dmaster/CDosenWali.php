<?php
prado::using ('Application.MainPageSA');
class CDosenWali extends MainPageSA {               
    public function onLoad($param) {
        parent::onLoad($param);
        $this->showSubMenuLembaga=true;          
        $this->showDosenWali=true;   
        if (!$this->IsPostBack&&!$this->IsCallback) {
            if (!isset($_SESSION['currentPageDosenWali'])||$_SESSION['currentPageDosenWali']['page_name']!='sa.dmaster.DosenWali') {
                $_SESSION['currentPageDosenWali']=array('page_name'=>'sa.dmaster.DosenWali','page_num'=>0,'search'=>false);
            }
            $_SESSION['currentPageDosenWali']['search']=false;
            $this->populateData();            
        }
    }       
    public function renderCallback ($sender,$param) {
        $this->RepeaterS->render($param->NewWriter);    
    }
    public function Page_Changed ($sender,$param) {
        $_SESSION['currentPageDosenWali']['page_num']=$param->NewPageIndex;
        $this->populateData($_SESSION['currentPageDosenWali']['search']);
    }
    
    public function searchRecord ($sender,$param) {
        $_SESSION['currentPageDosenWali']['search']=true;
        $this->populateData($_SESSION['currentPageDosenWali']['search']);
    }    
    protected function populateData ($search=false) {
        if ($search) {
            $str = "SELECT dw.iddosen_wali,d.nidn,d.nipy,d.gelar_depan,d.nama_dosen,d.gelar_belakang,d.telp_hp,d.username,d.status FROM dosen_wali dw JOIN LEFT dosen d ON (d.iddosen=dw.iddosen)";         
            $txtsearch=addslashes($this->txtKriteria->Text);
            switch ($this->cmbKriteria->Text) {
                case 'nidn' :
                    $clausa="WHERE d.nidn='$txtsearch'";
                    $jumlah_baris=$this->DB->getCountRowsOfTable("dosen $clausa",'iddosen');                    
                    $str = "$str $clausa";
                break;
                case 'nip' :
                    $clausa="WHERE d.nipy='$txtsearch'";
                    $jumlah_baris=$this->DB->getCountRowsOfTable("dosen $clausa",'iddosen');                    
                    $str = "$str $clausa";
                break;
                case 'nama_dosen' :
                    $clausa="WHERE d.nama_dosen LIKE '%$txtsearch%'";
                    $jumlah_baris=$this->DB->getCountRowsOfTable("dosen $clausa",'iddosen');                    
                    $str = "$str $clausa";
                break;
            }
        }else{
            $jumlah_baris=$this->DB->getCountRowsOfTable("dosen_wali",'iddosen');                    
            $str = "SELECT dw.iddosen_wali,d.nidn,d.nipy,d.gelar_depan,d.nama_dosen,d.gelar_belakang,d.telp_hp,d.username,d.status FROM dosen_wali dw LEFT JOIN dosen d ON (d.iddosen=dw.iddosen)";         
        }
        $this->RepeaterS->CurrentPageIndex=$_SESSION['currentPageDosenWali']['page_num'];
        $this->RepeaterS->VirtualItemCount=$jumlah_baris;
        $currentPage=$this->RepeaterS->CurrentPageIndex;
        $offset=$currentPage*$this->RepeaterS->PageSize;        
        $itemcount=$this->RepeaterS->VirtualItemCount;
        $limit=$this->RepeaterS->PageSize;
        if (($offset+$limit)>$itemcount) {
            $limit=$itemcount-$offset;
        }
        if ($limit < 0) {$offset=0;$limit=$this->setup->getSettingValue('default_pagesize');$_SESSION['currentPageDosenWali']['page_num']=0;}
        $str = "$str ORDER BY nama_dosen ASC LIMIT $offset,$limit";             
        $this->DB->setFieldTable(array('iddosen_wali','nidn','nipy','gelar_depan','nama_dosen','gelar_belakang','telp_hp','username','status'));
        $r = $this->DB->getRecord($str,$offset+1);  
        
        $this->RepeaterS->DataSource=$r;
        $this->RepeaterS->dataBind();     
        $this->paginationInfo->Text=$this->getInfoPaging($this->RepeaterS);        
    }
    private function populateDosen () {
        $str = "SELECT iddosen,CONCAT(gelar_depan,' ',nama_dosen,gelar_belakang) AS nama_dosen,nidn FROM dosen WHERE iddosen NOT IN (SELECT iddosen FROM dosen_wali) ORDER BY nama_dosen ASC";
        $this->DB->setFieldTable (array('iddosen','nama_dosen','nidn'));            
        $r= $this->DB->getRecord($str);
        $dataitem['none']='Daftar Dosen';   
        while (list($k,$v)=each($r)) {
            $dataitem[$v['iddosen']]=$v['nama_dosen']. ' ['.$v['nidn'].']';
        }
        return $dataitem;
    }           
    public function addProcess ($sender,$param) {
        $this->idProcess='add';        
        $this->cmbAddDosen->dataSource=$this->populateDosen ();
        $this->cmbAddDosen->dataBind();                
    }  
   
    public function saveData ($sender,$param) {
        if ($this->Page->isValid) {
            $iddosen=$this->cmbAddDosen->Text;            
            $str = "INSERT INTO dosen_wali SET iddosen_wali=NULL,iddosen=$iddosen";
            $this->DB->insertRecord($str);
            if ($this->Application->Cache) {  
                $str = "SELECT dw.iddosen_wali,d.nidn,CONCAT(d.gelar_depan,' ',d.nama_dosen,' ',d.gelar_belakang) AS nama_dosen FROM dosen d,dosen_wali dw WHERE d.iddosen=dw.iddosen ORDER BY idjabatan DESC,nama_dosen ASC";
                $this->DB->setFieldTable(array('iddosen_wali','nidn','nama_dosen'));                    
                $r = $this->DB->getRecord($str);                
                $dataitem['none']='Daftar Dosen Wali';              
                while (list($k,$v)=each($r)) {          
                    $dataitem[$v['iddosen_wali']]=$v['nama_dosen'] . ' ['.$v['nidn'].']';   ;                   
                }   
                $this->Application->Cache->set('listdw',$dataitem);                
            }
            $this->Redirect('dmaster.DosenWali',true);            
        }
    }
    public function deleteRecord ($sender,$param) {        
        $iddosen_wali=$this->getDataKeyField($sender,$this->RepeaterS);          
        if ($this->DB->checkRecordIsExist('iddosen_wali','register_mahasiswa',$iddosen_wali)) {
            $this->lblHeaderMessageError->Text='Menghapus Dosen Wali';
            $this->lblContentMessageError->Text="Anda tidak bisa menghapus dosen wali dengan ID ($iddosen_wali) karena sedang digunakan di register mahasiswa.";
            $this->modalMessageError->Show();        
        }else{
            $this->DB->deleteRecord("dosen_wali WHERE iddosen_wali=$iddosen_wali");            
            if ($this->Application->Cache) {  
                $str = "SELECT dw.iddosen_wali,d.nidn,CONCAT(d.gelar_depan,' ',d.nama_dosen,' ',d.gelar_belakang) AS nama_dosen FROM dosen d,dosen_wali dw WHERE d.iddosen=dw.iddosen ORDER BY idjabatan DESC,nama_dosen ASC";
                $this->DB->setFieldTable(array('iddosen_wali','nidn','nama_dosen'));                    
                $r = $this->DB->getRecord($str);                
                $dataitem['none']='Daftar Dosen Wali';              
                while (list($k,$v)=each($r)) {          
                    $dataitem[$v['iddosen_wali']]=$v['nama_dosen'] . ' ['.$v['nidn'].']';   ;                   
                }   
                $this->Application->Cache->set('listdw',$dataitem);                
            }
            $this->redirect('dmaster.DosenWali',true);
        }        
    }   
    
}