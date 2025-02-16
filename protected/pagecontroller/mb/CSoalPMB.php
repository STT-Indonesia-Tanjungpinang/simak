<?php
prado::using ('Application.MainPageMB');
class CSoalPMB extends MainPageMB {
    public $DataUjian;
    public function onLoad($param) {
        parent::onLoad($param);        
        $this->showSoalPMB = true;	    
        $this->createObj('Akademik');
        if (!$this->IsPostBack && !$this->IsCallback) {	            
            try {          
                $no_formulir = $this->Pengguna->getDataUser('no_formulir');
                $this->Demik->setDataMHS(array('no_formulir' => $no_formulir));
                if (!$this->DB->getCountRowsOfTable('soal') > 0) {
                    throw new Exception ('Soal Ujian PMB belum di inputkan, silahkan hubungi panitia PMB');
                }
                if (!$this->Demik->isNoFormulirExist()) {
                    throw new Exception ('Untuk mengikuti ujian silahkan isi formulir terlebih dahulu');
                }         
                if (!$this->DB->checkRecordIsExist ('no_formulir', ' peserta_ujian_pmb', $no_formulir)){
                    throw new Exception ("No. Formulir ($no_formulir) belum terdaftar menjadi peserta ujian. Silahkan pilih Jadwal Ujian PMB yang Anda inginkan");
                }   
                if ($this->Demik->isMhsRegistered(true)) {
                    throw new Exception ('No. Formulir ($no_formulir) sudah terdaftar sebagai mahasiswa, maka dari itu tidak bisa melakukan ujian');
                }                
                $str = "SELECT ku.tgl_ujian,ku.tgl_selesai_ujian,ku.isfinish,num.jumlah_soal,num.jawaban_benar,num.jawaban_salah,num.soal_tidak_terjawab,num.nilai FROM kartu_ujian ku LEFT JOIN nilai_ujian_masuk num ON ku.no_formulir=num.no_formulir WHERE ku.no_formulir = $no_formulir";
                $this->DB->setFieldTable(array('tgl_ujian', 'tgl_selesai_ujian', 'isfinish', 'jumlah_soal', 'jawaban_benar', 'jawaban_salah', 'soal_tidak_terjawab', 'nilai'));
                $r = $this->DB->getRecord($str); 

                if (isset($r[1])) {
                    $dataujian = $r[1];
                    if ($dataujian['isfinish']) {
                        $this->idProcess = 'edit';
                        if ($dataujian['nilai'] == '') {     
                            $jawaban_benar = $this->DB->getCountRowsOfTable("jawaban_ujian ju LEFT JOIN jawaban j ON (j.idjawaban=ju.idjawaban) WHERE no_formulir='$no_formulir' AND ju.idjawaban!=0 AND status=1",'ju.idjawaban');
                            $dataujian['jawaban_benar'] = $jawaban_benar;
                            $jawaban_salah=$this->DB->getCountRowsOfTable("jawaban_ujian ju LEFT JOIN jawaban j ON (j.idjawaban=ju.idjawaban) WHERE no_formulir='$no_formulir' AND ju.idjawaban!=0 AND status=0",'ju.idjawaban');
                            $dataujian['jawaban_salah'] = $jawaban_salah;
                            $soal_tidak_terjawab=$this->DB->getCountRowsOfTable("jawaban_ujian WHERE idjawaban=0 AND no_formulir='$no_formulir'",'idjawaban');
                            $dataujian['soal_tidak_terjawab'] = $soal_tidak_terjawab;
                            $jumlah_soal = $jawaban_benar+$jawaban_salah+$soal_tidak_terjawab;
                            $dataujian['jumlah_soal'] = $jumlah_soal;
                            $nilai=($jawaban_benar/$jumlah_soal)*100;
                            $dataujian['nilai'] = $nilai;

                            $str = "SELECT fp.kjur1,fp.kjur2,pp.kjur,pp.nilai FROM formulir_pendaftaran fp,peserta_ujian_pmb pup,passinggrade pp WHERE pup.no_formulir=fp.no_formulir AND pp.idjadwal_ujian=pup.idjadwal_ujian AND (pp.kjur=fp.kjur1 OR pp.kjur=fp.kjur2) AND fp.no_formulir = $no_formulir ORDER BY pp.kjur ASC";
                            $this->DB->setFieldTable(array('kjur1', 'kjur2', 'kjur', 'nilai')); 
                            $r = $this->DB->getRecord($str);   
                            
                            $passing_grade_1=0;
                            $passing_grade_2=0;
                            if (isset($r[1])) {
                                while (list($k, $v) = each($r)) {
                                    if ($v['kjur1'] == $v['kjur']) {
                                        $passing_grade_1 = $v['nilai'];
                                    }
                                    if ($v['kjur2'] == $v['kjur']) {
                                        $passing_grade_2=$v['nilai'];
                                    }
                                }
                            }
                            $str= "REPLACE INTO nilai_ujian_masuk SET no_formulir = $no_formulir,jumlah_soal = $jumlah_soal,jawaban_benar = $jawaban_benar,jawaban_salah=$jawaban_salah,soal_tidak_terjawab=$soal_tidak_terjawab,passing_grade_1 = $passing_grade_1,passing_grade_2=$passing_grade_2,nilai = $nilai,ket_lulus=0";
                            $this->DB->insertRecord($str);   
                        }
                        $this->DataUjian = $dataujian;  
                    }else{
                        $this->timerSoalPMB->StartTimerOnLoad=true;
                        $this->populateSoal();
                    }
                }else{
                    $this->idProcess = 'add';
                    $str = "SELECT no_pin FROM pin WHERE no_formulir='$no_formulir'";
                    $this->DB->setFieldTable(array('no_pin')); 
                    $r = $this->DB->getRecord($str); 

                    $this->txtAddPIN->Text = $r[1]['no_pin'];
                }                                                   
            }catch (Exception $e) {
                $this->idProcess = 'view';
                $this->errorMessage->Text = $e->getMessage();
            }
        }
    }
    public function checkPIN($sender, $param) { 
        $pin = addslashes($param->Value);
        try {
            if ($pin != '') {			            
                $no_formulir = $this->Pengguna->getDataUser('no_formulir');
                if (!$this->DB->checkRecordIsExist ('no_pin', 'pin', $pin," AND no_formulir='$no_formulir'")) {
                    throw new Exception ("Nomor PIN ($pin) tidak terdaftar di Portal.");
                }     
            }
        }catch(Exception $e) {			
            $sender->ErrorMessage = $e->getMessage();				
            $param->IsValid = false;			
		}
    }
    public function startUjian($sender, $param) {        
        if ($this->IsValid) {
            $no_formulir = $this->Pengguna->getDataUser('no_formulir');
            $pin = addslashes($this->txtAddPIN->Text);
            if ($this->DB->checkRecordIsExist ('no_pin', 'pin', $pin," AND no_formulir='$no_formulir'")) {
                $str = "INSERT INTO kartu_ujian (no_formulir,no_ujian,tgl_ujian,tgl_selesai_ujian,isfinish,idtempat_spmb) VALUES ($no_formulir,'$no_formulir',NOW(), '0000-00-00 00:00:00',0,1)";            
                $this->DB->query('BEGIN');
                if ($this->DB->insertRecord($str)) {              
                    $str = "INSERT INTO jawaban_ujian (idsoal,idjawaban,no_formulir) SELECT s.idsoal,0, $no_formulir FROM soal s ORDER BY RAND() LIMIT 80";
                    $this->DB->insertRecord($str);        
                    $this->DB->query('COMMIT');          
                }else {
                    $this->DB->query('ROLLBACK');
                }        
                   
            }
            $this->redirect('SoalPMB', true);
        }
    }    
    public function populateSoal() {
        $no_formulir = $this->Pengguna->getDataUser('no_formulir');
        $str = "SELECT ju.idsoal,nama_soal,ju.idjawaban FROM jawaban_ujian ju,soal s WHERE ju.idsoal=s.idsoal AND ju.no_formulir = $no_formulir";
        $this->DB->setFieldTable(array('idsoal', 'nama_soal', 'idjawaban')); 
        $r = $this->DB->getRecord($str);	     
        if (isset($r[1])) {
            $this->RepeaterS->DataSource = $r;
            $this->RepeaterS->dataBind();
        }else{
            $this->DB->deleteRecord("kartu_ujian WHERE no_formulir = $no_formulir");
            $this->redirect('SoalPMB', true);
        }        
	}  
    public function dataBindRepeaterJawaban($sender, $param) {
        $item = $param->Item;
		if ($item->ItemType === 'Item' || $item->ItemType === 'AlternatingItem') {					
            $idsoal = $item->DataItem['idsoal'];
            $idjawaban_tersimpan = $item->DataItem['idjawaban'];
            $str = "SELECT idjawaban,idsoal,j.jawaban, $idjawaban_tersimpan AS jawaban_tersimpan FROM jawaban j WHERE idsoal = $idsoal";
            $this->DB->setFieldTable(array('idjawaban', 'idsoal', 'jawaban', 'jawaban_tersimpan')); 
            $r = $this->DB->getRecord($str);                   
            $item->RepeaterJawaban->DataSource = $r;
            $item->RepeaterJawaban->dataBind();
        }
    }
    public function setDataBound($sender, $param) {
		$item = $param->Item;
		if ($item->ItemType === 'Item' || $item->ItemType === 'AlternatingItem') {	
            $idsoal = $item->DataItem['idsoal'];
			$item->rdJawaban->setUniqueGroupName("jawaban$idsoal");
            if ($item->DataItem['jawaban_tersimpan'] > 0){                                
                $item->rdJawaban->Checked=$item->DataItem['jawaban_tersimpan'] == $item->DataItem['idjawaban'];
            }
		}
	} 
    private function simpanJawaban ($repeater, $no_formulir) {
        foreach($repeater->Items as $v) {                
            $repeaterJawaban = $v->RepeaterJawaban->Items;             
            $idsoal = $v->txtIDSoal->Value;             
            $idjawaban='';
            foreach ($repeaterJawaban as $inputan) {                        
                if ($inputan->rdJawaban->Checked) {
                    $item=$inputan->rdJawaban->getNamingContainer();
                    $idjawaban = $v->RepeaterJawaban->DataKeys[$item->getItemIndex()];
                    $str = "UPDATE jawaban_ujian SET idjawaban = $idjawaban WHERE idsoal = $idsoal AND no_formulir = $no_formulir";
                    $this->DB->updateRecord($str);
                    break;
                }
            }            
        }
    }
    public function saveJawaban($sender, $param) {
        if ($this->IsValid) {
            $no_formulir = $this->Pengguna->getDataUser('no_formulir');        
            $this->simpanJawaban($this->RepeaterS, $no_formulir);
            $this->redirect('SoalPMB', true);
        }
    }
    public function saveJawabanTimer($sender, $param) {
        $no_formulir = $this->Pengguna->getDataUser('no_formulir');        
        foreach($this->RepeaterS->Items as $v) {                
            $repeaterJawaban = $v->RepeaterJawaban->Items;             
            $idsoal = $v->txtIDSoal->Value;             
            $idjawaban='';
            foreach ($repeaterJawaban as $inputan) {                        
                if ($inputan->rdJawaban->Checked) {
                    $item=$inputan->rdJawaban->getNamingContainer();
                    $idjawaban = $v->RepeaterJawaban->DataKeys[$item->getItemIndex()];
                    $str = "UPDATE jawaban_ujian SET idjawaban = $idjawaban WHERE idsoal = $idsoal AND no_formulir = $no_formulir";
                    $this->DB->updateRecord($str);
                    break;
                }
            }            
        }                    
    }
    public function selesaiUjian($sender, $param) {            
        $no_formulir = $this->Pengguna->getDataUser('no_formulir');
        $this->simpanJawaban($this->RepeaterS, $no_formulir);
        $jawaban_benar = $this->DB->getCountRowsOfTable("jawaban_ujian ju LEFT JOIN jawaban j ON (j.idjawaban=ju.idjawaban) WHERE no_formulir='$no_formulir' AND ju.idjawaban!=0 AND status=1",'ju.idjawaban');        
        $jawaban_salah=$this->DB->getCountRowsOfTable("jawaban_ujian ju LEFT JOIN jawaban j ON (j.idjawaban=ju.idjawaban) WHERE no_formulir='$no_formulir' AND ju.idjawaban!=0 AND status=0",'ju.idjawaban');        
        $soal_tidak_terjawab=$this->DB->getCountRowsOfTable("jawaban_ujian WHERE idjawaban=0 AND no_formulir='$no_formulir'",'idjawaban');        
        $jumlah_soal = $jawaban_benar+$jawaban_salah+$soal_tidak_terjawab;                
        $nilai = $jawaban_benar > 0 ? ($jawaban_benar/$jumlah_soal)*100:0;
        
        $str = "UPDATE kartu_ujian SET tgl_selesai_ujian=NOW(),isfinish=1 WHERE no_formulir = $no_formulir";        
        $this->DB->query ('BEGIN');
        if ($this->DB->updateRecord($str)) {
            $str = "SELECT fp.kjur1,fp.kjur2,pp.kjur,pp.nilai FROM formulir_pendaftaran fp,peserta_ujian_pmb pup,passinggrade pp WHERE pup.no_formulir=fp.no_formulir AND pp.idjadwal_ujian=pup.idjadwal_ujian AND (pp.kjur=fp.kjur1 OR pp.kjur=fp.kjur2) AND fp.no_formulir = $no_formulir ORDER BY pp.kjur ASC";
            $this->DB->setFieldTable(array('kjur1', 'kjur2', 'kjur', 'nilai')); 
            $r = $this->DB->getRecord($str);                   
            $passing_grade_1=0;
            $passing_grade_2=0;
            if (isset($r[1])) {
                while (list($k, $v) = each($r)) {
                    if ($v['kjur1'] == $v['kjur']) {
                        $passing_grade_1 = $v['nilai'];
                    }
                    if ($v['kjur2'] == $v['kjur']) {
                        $passing_grade_2=$v['nilai'];
                    }
                }
            }
            $str= "REPLACE INTO nilai_ujian_masuk SET no_formulir = $no_formulir,jumlah_soal = $jumlah_soal,jawaban_benar = $jawaban_benar,jawaban_salah=$jawaban_salah,soal_tidak_terjawab=$soal_tidak_terjawab,passing_grade_1 = $passing_grade_1,passing_grade_2=$passing_grade_2,nilai = $nilai,ket_lulus=0,kjur=0";
            $this->DB->insertRecord($str);
            $this->DB->query('COMMIT');
            $this->redirect('SoalPMB', true);
        }else{
            $this->DB->query('ROLLBACK');
        }
        
    } 
}