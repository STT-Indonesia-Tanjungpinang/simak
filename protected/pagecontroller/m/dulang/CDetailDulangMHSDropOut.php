<?php
prado::using ('Application.MainPageM');
class CDetailDulangMHSDropOut Extends MainPageM {		
	public function onLoad($param) {
		parent::onLoad($param);				
        $this->showSubMenuAkademikDulang=true;
        $this->showDulangMHSDropOut=true;                
        $this->createObj('Nilai');
		if (!$this->IsPostBack && !$this->IsCallback) {
            try {
                if (isset($_SESSION['currentPageDulangMHSDropOut']['DataMHS']['no_formulir'])) {
                    $datamhs = $_SESSION['currentPageDulangMHSDropOut']['DataMHS'];
                    $nim = $datamhs['nim'];
                    $this->Nilai->setDataMHS($datamhs);
                    
                    $str = "SELECT MAX(tahun) AS tahun,MAX(idsmt) AS idsmt FROM dulang WHERE nim='$nim' GROUP BY tahun,idsmt ORDER BY tahun DESC,idsmt DESC LIMIT 1";
                    $this->DB->setFieldTable(array('tahun', 'idsmt'));
                    $datadulang = $this->DB->getRecord($str);	    
                    
                    $this->cmbAddTADropOut->DataSource = $this->DMaster->removeIdFromArray($this->DMaster->getListTA($datamhs['tahun_masuk']), 'none');
                    $this->cmbAddTADropOut->Text = $datadulang[1]['tahun'];
                    $this->cmbAddTADropOut->dataBind();
                     				
                    $this->cmbAddSMTDropOut->DataSource=array(1=>'GANJIL',2=>'GENAP');
                    $this->cmbAddSMTDropOut->Text = $datadulang[1]['idsmt'];
                    $this->cmbAddSMTDropOut->dataBind();
                    
                    $this->Nilai->getTranskripFromKRS ();
                    $jumlah_sks = $this->Nilai->getTotalSKSAdaNilai();
                    $iddata_konversi = $datamhs['iddata_konversi'];
                    if ($iddata_konversi > 0) {
                        $jumlah_sks+=$this->DB->getSumRowsOfTable ('sks',"v_konversi2 WHERE iddata_konversi = $iddata_konversi");
                    }
                    $this->literalJumlahSKS->Text = $jumlah_sks;
                    $this->literalBebasKeuangan->Text = '<span class="label label-info">NOT YET IMPLEMENTED</span>';
                    $this->literalBebasPerpustakaan->Text = '<span class="label label-info">NOT YET IMPLEMENTED</span>';
                    
                    $_SESSION['semester'] = $datadulang[1]['idsmt'];
                    $_SESSION['ta'] = $datadulang[1]['tahun'];
                    $_SESSION['kjur']= $datamhs['kjur'];

                    $this->setInfoToolbar();
                }else{
                    throw new Exception("Data Mahasiswa belum ada di session.");
                }
            } catch (Exception $ex) {
                $this->idProcess = 'view';	
                $this->errorMessage->Text = $ex->getMessage();
            }
		}	
	}
    public function setInfoToolbar() {        
        $kjur = $_SESSION['kjur'];        
		$ps = $_SESSION['daftar_jurusan'][$kjur];
        $ta = $this->DMaster->getNamaTA($_SESSION['ta']);		
        $semester = $this->setup->getSemester($_SESSION['semester']);
		$this->lblModulHeader->Text = "Program Studi $ps T.A $ta Semester $semester";        
	}
    
    public function getDataMHS($idx) {		       
        if (isset($_SESSION['currentPageDulangMHSDropOut']['DataMHS'][$idx])) {
            return $_SESSION['currentPageDulangMHSDropOut']['DataMHS'][$idx];
        }
    }    
    public function checkDulang($sender, $param) {
        $datamhs = $_SESSION['currentPageDulangMHSDropOut']['DataMHS'];
        $ta=addslashes($param->Value);		       
        $semester = $this->cmbAddSMTDropOut->Text;            
        try {            
            $nim = $datamhs['nim'];
            $this->Nilai->setDataMHS(array('nim' => $nim));
            $datadulang = $this->Nilai->getDataDulang($semester, $ta);
            
            if (isset($datadulang['iddulang'])) {
                throw new Exception ("Mahasiswa Dengan NIM ($nim) telah daftar ulang di T.A dan Semester ini.");
            }          
        }catch (Exception $e) {
            $param->IsValid = false;
            $sender->ErrorMessage = $e->getMessage();
        }	    
    }  
    public function saveData($sender, $param) {		
		if ($this->IsValid) {	
            $datamhs = $_SESSION['currentPageDulangMHSDropOut']['DataMHS'];						
			$ta = $this->cmbAddTADropOut->Text;							
			$semester = $this->cmbAddSMTDropOut->Text;
            $_SESSION['semester'] = $semester;
            $_SESSION['ta'] = $ta;
            $_SESSION['kjur']= $datamhs['kjur'];
            $_SESSION['currentPageDulangMHSDropOut']['tahun_masuk'] = $datamhs['tahun_masuk'];
            $nim=  $datamhs['nim'];           
			$kelas = $datamhs['idkelas'];
			$str = "UPDATE register_mahasiswa SET k_status='D' WHERE nim='$nim'";			
			$this->DB->query ('BEGIN');
			if ($this->DB->updateRecord($str)) {
                $status_sebelumnnya = $datamhs['k_status'];
                $tasmt=$ta.$semester;        
                $str = "INSERT INTO dulang (iddulang,nim,tahun,idsmt,tasmt,tanggal,idkelas,status_sebelumnya,k_status) VALUES (NULL,'$nim', '$ta', '$semester', '$tasmt',NOW(), '$kelas', '$status_sebelumnnya', 'D')";
                $this->DB->insertRecord($str);               
				$this->DB->query('COMMIT');
                unset($_SESSION['currentPageDulangMHSDropOut']['DataMHS']);
                $this->redirect('dulang.DulangMHSDropOut', true);
			}else {
				$this->DB->query('ROLLBACK');
			}
		}
	}
    public function closeDetailDulang($sender, $param) {
        unset($_SESSION['currentPageDulangMHSDropOut']['DataMHS']);
        $this->redirect('dulang.DulangMHSDropOut', true);
    }
}