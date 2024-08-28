<?php
prado::using ('Application.MainPageM');
class CCalonWisuda Extends MainPageM {		
	public function onLoad($param) {
		parent::onLoad($param);				
        $this->showSubMenuAkademikDulang=true;
        $this->showCalonWisuda=true;                
        $this->createObj('Akademik');
		if (!$this->IsPostBack&&!$this->IsCallBack) {
            if (!isset($_SESSION['currentPageCalonWisuda'])||$_SESSION['currentPageCalonWisuda']['page_name']!='m.kemahasiswaan.CalonWisuda') {
				$_SESSION['currentPageCalonWisuda']=array('page_name'=>'m.kemahasiswaan.CalonWisuda','page_num'=>0,'search'=>false);												
			}
            $_SESSION['currentPageCalonWisuda']['search']=false;
            
            $this->populateData();
		}	
	}
    public function Page_Changed ($sender,$param) {
		$_SESSION['currentPageCalonWisuda']['page_num']=$param->NewPageIndex;
		$this->populateData($_SESSION['currentPageCalonWisuda']['search']);
	}
	public function renderCallback ($sender,$param) {
		$this->RepeaterS->render($param->NewWriter);	
	}	
    public function searchRecord ($sender,$param){
        $_SESSION['currentPageCalonWisuda']['search']=true;
        $this->populateData($_SESSION['currentPageCalonWisuda']['search']);
    }
    public function populateData($search=false) {        
        if ($search) {
            $str = "SELECT d.iddulang,vdm.no_formulir,vdm.nim,vdm.nirm,vdm.nama_mhs,vdm.iddosen_wali,d.tanggal,d.tahun,d.idsmt,d.idkelas FROM v_datamhs vdm,dulang d WHERE vdm.nim=d.nim AND d.k_status='L'";
            $txtsearch=addslashes($this->txtKriteria->Text);
            switch ($this->cmbKriteria->Text) {
                case 'no_formulir' :
                    $clausa="AND vdm.no_formulir='$txtsearch'";
                    $jumlah_baris=$this->DB->getCountRowsOfTable ("v_datamhs vdm,dulang d WHERE vdm.nim=d.nim AND d.k_status='L' $clausa",'vdm.nim');
                    $str = "$str $clausa";
                break;
                case 'nim' :
                    $clausa="AND d.nim='$txtsearch'";
                    $jumlah_baris=$this->DB->getCountRowsOfTable ("v_datamhs vdm,dulang d WHERE vdm.nim=d.nim AND d.k_status='L' $clausa",'vdm.nim');
                    $str = "$str $clausa";
                break;
                case 'nirm' :
                    $clausa="AND vdm.nirm='$txtsearch'";
                    $jumlah_baris=$this->DB->getCountRowsOfTable ("v_datamhs vdm,dulang d WHERE vdm.nim=d.nim AND d.k_status='L' $clausa",'vdm.nim');
                    $str = "$str $clausa";
                break;
                case 'nama' :
                    $clausa="AND vdm.nama_mhs LIKE '%$txtsearch%'";
                    $jumlah_baris=$this->DB->getCountRowsOfTable ("v_datamhs vdm,dulang d WHERE vdm.nim=d.nim AND d.k_status='L' $clausa",'vdm.nim');
                    $str = "$str $clausa";
                break;
            }
        }else{                            
            $str = "SELECT d.iddulang,vdm.no_formulir,vdm.nim,vdm.nirm,vdm.nama_mhs,vdm.iddosen_wali,d.tanggal,d.tahun,d.idsmt,d.idkelas FROM v_datamhs vdm,dulang d WHERE vdm.nim=d.nim AND d.tahun=$ta AND d.idsmt=$idsmt AND vdm.kjur='$kjur' AND d.k_status='L' $str_dw $str_tahun_masuk";
            $jumlah_baris=$this->DB->getCountRowsOfTable ("v_datamhs vdm,dulang d WHERE vdm.nim=d.nim AND d.tahun=$ta AND d.idsmt=$idsmt AND vdm.kjur='$kjur' AND d.k_status='L' $str_dw $str_tahun_masuk",'vdm.nim');
        }
		
		$this->RepeaterS->CurrentPageIndex=$_SESSION['currentPageCalonWisuda']['page_num'];
		$this->RepeaterS->VirtualItemCount=$jumlah_baris;
		$offset=$this->RepeaterS->CurrentPageIndex*$this->RepeaterS->PageSize;
		$limit=$this->RepeaterS->PageSize;
		if ($offset+$limit>$this->RepeaterS->VirtualItemCount) {
			$limit=$this->RepeaterS->VirtualItemCount-$offset;
		}
		if ($limit < 0) {$offset=0;$limit=10;$_SESSION['currentPageCalonWisuda']['page_num']=0;}
		$str = "$str ORDER BY vdm.nama_mhs ASC LIMIT $offset,$limit";				        
		$this->DB->setFieldTable(array('iddulang','no_formulir','nim','nirm','nama_mhs','iddosen_wali','tanggal','tahun','idsmt','idkelas'));
		$result=$this->DB->getRecord($str,$offset+1);
		$this->RepeaterS->DataSource=$result;
		$this->RepeaterS->dataBind();
                
        $this->paginationInfo->Text=$this->getInfoPaging($this->RepeaterS);
	}
	public function cekNIM ($sender,$param) {		
        $nim=addslashes($param->Value);		
        if ($nim != '') {
            try {
                if (!isset($_SESSION['currentPageCalonWisuda']['DataMHS']['no_formulir'])) {
                    
                    $str = "SELECT vdm.no_formulir,vdm.nim,vdm.nirm,vdm.nama_mhs,vdm.jk,vdm.tempat_lahir,vdm.tanggal_lahir,vdm.kjur,vdm.nama_ps,vdm.idkonsentrasi,k.nama_konsentrasi,vdm.tahun_masuk,vdm.semester_masuk,iddosen_wali,vdm.k_status,sm.n_status AS status,vdm.idkelas,ke.nkelas,vdm.photo_profile FROM v_datamhs vdm LEFT JOIN konsentrasi k ON (vdm.idkonsentrasi=k.idkonsentrasi) LEFT JOIN status_mhs sm ON (vdm.k_status=sm.k_status) LEFT JOIN kelas ke ON (vdm.idkelas=ke.idkelas) WHERE vdm.nim='$nim'";
                    $this->DB->setFieldTable(array('no_formulir','nim','nirm','nama_mhs','jk','tempat_lahir','tanggal_lahir','kjur','nama_ps','idkonsentrasi','nama_konsentrasi','tahun_masuk','semester_masuk','iddosen_wali','k_status','status','idkelas','nkelas','photo_profile'));
                    $r=$this->DB->getRecord($str);	           
                    if (!isset($r[1])) {
                        throw new Exception ("Mahasiswa Dengan NIM ($nim) tidak terdaftar di Portal.");
                    }
                    $datamhs=$r[1]; 
                    $datamhs['iddata_konversi']=$this->Demik->isMhsPindahan($nim,true);
                    if ($datamhs['k_status'] == 'L') {
                        throw new Exception ("Mahasiswa Dengan NIM ($nim) telah dinyatakan lulus.");
                    }
                    if ($datamhs['k_status'] == 'N' || $datamhs['k_status'] == 'D' || $datamhs['k_status'] == 'K' || $datamhs['k_status'] == 'C') {
                        throw new Exception ("Untuk dinyatakan lulus Mahasiswa Dengan NIM ($nim) status akhirnya harus AKTIF.");
                    }
                    $this->Demik->setDataMHS($datamhs);
                    $datadulang=$this->Demik->getDataDulang($_SESSION['semester'],$_SESSION['ta']);
                    if (isset($datadulang['iddulang'])) {         
                        if ($datadulang['k_status']!='A') {
                            throw new Exception ("Mahasiswa Dengan NIM ($nim) telah daftar ulang di T.A dan Semester ini.");
                        }                        
                    }                    
                    $datamhs['nkelas']=$this->DMaster->getNamaKelasByID($datamhs['idkelas']);
                    $datamhs['nama_dosen']=$this->DMaster->getNamaDosenWaliByID ($datamhs['iddosen_wali']);
                    $datamhs['nkelas']=$this->DMaster->getNamaKelasByID($datamhs['idkelas']);
                    $datamhs['nama_konsentrasi']=($datamhs['idkonsentrasi']==0) ? '-':$datamhs['nama_konsentrasi'];                    
                    $datamhs['status']=$this->DMaster->getNamaStatusMHSByID($datamhs['k_status']);
                    $_SESSION['currentPageCalonWisuda']['DataMHS']=$datamhs;
                }
            }catch (Exception $e) {
                $param->IsValid=false;
                $sender->ErrorMessage=$e->getMessage();
            }	
        }	
    }
    public function Go($param,$sender) {	
        if ($this->Page->isValid) {            
            $nim=addslashes($this->txtNIM->Text);
            $this->redirect('dulang.DetailCalonWisuda',true,array('id'=>$nim));
        }
	}
    public function viewRecord($sender,$param) {	
		$this->idProcess='view';		
		$iddulang=$this->getDataKeyField($sender,$this->RepeaterS);
        $this->hiddeniddulang->Value=$iddulang;
        
        $str = "SELECT vdm.no_formulir,vdm.nim,vdm.nirm,vdm.nama_mhs,vdm.jk,vdm.tempat_lahir,vdm.tanggal_lahir,vdm.kjur,vdm.nama_ps,vdm.idkonsentrasi,k.nama_konsentrasi,vdm.tahun_masuk,semester_masuk,iddosen_wali,d.idkelas,d.k_status,d.idsmt,d.tahun FROM v_datamhs vdm JOIN dulang d ON (d.nim=vdm.nim) LEFT JOIN konsentrasi k ON (vdm.idkonsentrasi=k.idkonsentrasi) WHERE d.iddulang='$iddulang'";
        $this->DB->setFieldTable(array('no_formulir','nim','nirm','nama_mhs','jk','tempat_lahir','tanggal_lahir','kjur','nama_ps','idkonsentrasi','nama_konsentrasi','tahun_masuk','semester_masuk','iddosen_wali','idkelas','k_status','idsmt','tahun'));
        $r=$this->DB->getRecord($str);	           
        $datamhs=$r[1];
        $datamhs['nama_dosen']=$this->DMaster->getNamaDosenWaliByID ($datamhs['iddosen_wali']);
        $datamhs['nkelas']=$this->DMaster->getNamaKelasByID($datamhs['idkelas']);
        $datamhs['nama_konsentrasi']=($datamhs['idkonsentrasi']==0) ? '-':$datamhs['nama_konsentrasi'];                    
        $datamhs['status']=$this->DMaster->getNamaStatusMHSByID($datamhs['k_status']);
        
        $this->Demik->setDataMHS($datamhs);
	}
    public function deleteRecord ($sender,$param) {	
        $nim=$sender->CommandParameter;;
		$iddulang=$this->hiddeniddulang->Value;
		
		$this->DB->query ('BEGIN');
        
        $str = "SELECT status_sebelumnya FROM dulang WHERE iddulang=$iddulang";			
        $this->DB->setFieldTable(array('status_sebelumnya'));
        $r=$this->DB->getRecord($str);	
        
        $k_status=$r[1]['status_sebelumnya'];
        $str = "UPDATE register_mahasiswa SET k_status='$k_status' WHERE nim='$nim'";
		if ($this->DB->updateRecord($str)) {
            $this->DB->deleteRecord("dulang WHERE iddulang=$iddulang");
            $this->DB->deleteRecord("transkrip_asli WHERE nim='$nim'");
            $this->DB->deleteRecord("transkrip_asli_detail WHERE nim='$nim'");	
			$this->DB->query ('COMMIT');
            $this->redirect('dulang.CalonWisuda',true);
		}else {
			$this->DB->query ('ROLLBACK');
		}		
	}
    public function printOut ($sender,$param) {		
        $this->createObj('reportakademik');
        $this->linkOutput->Text='';
        $this->linkOutput->NavigateUrl='#';
        
        switch ($_SESSION['outputreport']) {
            case  'summarypdf' :
                $messageprintout="Mohon maaf Print out pada mode summary pdf tidak kami support.";                
            break;
            case  'summaryexcel' :
                $messageprintout="Mohon maaf Print out pada mode summary excel tidak kami support.";                
            break;
            case  'excel2007' :
                $messageprintout="Daftar Mahasiswa Daftar Ulang Status NON-AKTIF: <br/>";
                $dataReport['ta']=$_SESSION['ta'];
                $dataReport['nama_tahun']=$this->DMaster->getNamaTA($dataReport['ta']);
                $dataReport['idsmt']=$_SESSION['semester'];
                $dataReport['nama_semester']=$this->setup->getSemester($_SESSION['semester']);
                $dataReport['kjur']=$_SESSION['kjur'];
                $dataReport['nama_ps']=$_SESSION['daftar_jurusan'][$_SESSION['kjur']];
                $dataReport['linkoutput']=$this->linkOutput;                
                $this->report->setDataReport($dataReport); 
                $this->report->setMode($_SESSION['outputreport']);
                
                $this->report->printDulangLULUS($this->DMaster);
            break;
            case  'pdf' :
                $messageprintout="Mohon maaf Print out pada mode pdf belum kami support.";                
            break;
        } 
        $this->lblMessagePrintout->Text=$messageprintout;
        $this->lblPrintout->Text='Daftar Ulang Mahasiswa LULUS';
        $this->modalPrintOut->show();
    }
}