<?php
prado::using ('Application.MainPageMHS');
class CKRS extends MainPageMHS {
  /**
  * total SKS
  */
  static $totalSKS=0;
   /**
  * total SKS Batal
  */
  public static $totalSKSBatal=0;	
  /**
  * jumlah matakuliah
  */
  static $jumlahMatkul=0;	
   /**
  * total Matakuliah Batal
  */
  public static $jumlahMatkulBatal=0;
  public function onLoad($param) {
    parent::onLoad($param);	
    $this->showSubMenuAkademikPerkuliahan=true;
    $this->showKRS = true;   
  
    $this->createObj('KRS');
    if (!$this->IsPostBack && !$this->IsCallback)
    {	
      if (!isset($_SESSION['currentPageKRS'])||$_SESSION['currentPageKRS']['page_name']!='mh.perkuliahan.KRS')
      {
        $_SESSION['currentPageKRS']=array('page_name'=>'mh.perkuliahan.KRS','page_num'=>0,'DataKRS'=>array());
      } 
      $this->lblModulHeader->Text=$this->getInfoToolbar();
      
      $this->tbCmbTA->DataSource=$this->DMaster->removeIdFromArray($this->DMaster->getListTA($this->Pengguna->getDataUser('tahun_masuk')),'none');
      $this->tbCmbTA->Text=$_SESSION['ta'];
      $this->tbCmbTA->dataBind();			
      
      $semester=$this->DMaster->removeIdFromArray($this->setup->getSemester(),'none');  				
      $this->tbCmbSemester->DataSource=$semester;
      $this->tbCmbSemester->Text=$_SESSION['semester'];
      $this->tbCmbSemester->dataBind();
      
      $this->tbCmbOutputReport->DataSource=$this->setup->getOutputFileType();
      $this->tbCmbOutputReport->Text= $_SESSION['outputreport'];
      $this->tbCmbOutputReport->DataBind();
      
      $this->populateData();				
        
    }				
  }
  public function getInfoToolbar()
  {                
    $ta = $this->DMaster->getNamaTA($_SESSION['ta']);
    $semester=$this->setup->getSemester($_SESSION['semester']);
    $text="TA $ta Semester $semester";
    return $text;
  }
  public function changeTbTA ($sender,$param) {
    $_SESSION['ta']=$this->tbCmbTA->Text;		
    $this->redirect('perkuliahan.KRS',true);        
  }	
  public function changeTbSemester ($sender,$param) {
    $_SESSION['semester']=$this->tbCmbSemester->Text;		
    $this->redirect('perkuliahan.KRS',true);
  }	
  public function itemBound ($sender,$param) {
    $item=$param->Item;
    if ($item->ItemType === 'Item' || $item->ItemType === 'AlternatingItem') {    
      if ($item->DataItem['batal']) {
        $item->cmbKelas->Enabled=false;
        CKRS::$totalSKSBatal+=$item->DataItem['sks'];
        CKRS::$jumlahMatkulBatal+=1;
      }else{
        $idkrsmatkul = $item->DataItem['idkrsmatkul'];
        $idpenyelenggaraan=$item->DataItem['idpenyelenggaraan'];
        $idkelas=$_SESSION['currentPageKRS']['DataKRS']['krs']['kelas_dulang'];
        $str = "SELECT km.idkelas_mhs,km.nama_kelas,vpp.nama_dosen,vpp.nidn,km.idruangkelas FROM kelas_mhs km JOIN v_pengampu_penyelenggaraan vpp ON (km.idpengampu_penyelenggaraan=vpp.idpengampu_penyelenggaraan) WHERE vpp.idpenyelenggaraan=$idpenyelenggaraan AND km.idkelas='$idkelas'  ORDER BY hari ASC,idkelas ASC,nama_dosen ASC";            
        $this->DB->setFieldTable(array('idkelas_mhs','nama_kelas','nama_dosen','nidn','idruangkelas'));
        $r = $this->DB->getRecord($str);
        
        $str = "SELECT idkelas_mhs FROM kelas_mhs_detail WHERE idkrsmatkul = $idkrsmatkul";            
        $this->DB->setFieldTable(array('idkelas_mhs'));
        $r_selected = $this->DB->getRecord($str);
        
        if (isset($r_selected[1])) {
          $idkelas_mhs_selected=$r_selected[1]['idkelas_mhs'];
          $result = array();
        }else{
          $idkelas_mhs_selected='none';
          $result = array('none'=>' ');
        }                
        while (list($k,$v)=each($r)) { 
          $idkelas_mhs=$v['idkelas_mhs'];
          $jumlah_peserta_kelas = $this->DB->getCountRowsOfTable ("kelas_mhs_detail WHERE idkelas_mhs=$idkelas_mhs",'idkelas_mhs');
          $kapasitas=(int)$this->DMaster->getKapasitasRuangKelas($v['idruangkelas']);
          $keterangan=($jumlah_peserta_kelas <= $kapasitas) ? '' : ' [PENUH]';
          $result[$idkelas_mhs]=$this->DMaster->getNamaKelasByID($idkelas).'-'.chr($v['nama_kelas']+64) . ' ['.$v['nidn'].']'.$keterangan;   
        }                
        $item->cmbKelas->DataSOurce=$result;            
        $item->cmbKelas->DataBind();        
        $item->cmbKelas->Enabled=!$this->DB->checkRecordIsExist('idkrsmatkul','nilai_matakuliah',$idkrsmatkul);
        $item->cmbKelas->Text=$idkelas_mhs_selected;

        $datamhs=$this->Pengguna->getDataUser(); 
        $kelas=$datamhs['idkelas'];
        if($kelas=="A"){
          $i = "403"; 
        }else if($kelas=="B"){
          $i = "406";   
        }else if($kelas=="C"){
          $i = "404";   
        }else if($kelas=="D"){
          $i = "405";   
        }		
        $str_lock_kelas = "SELECT value FROM setting WHERE setting_id='$i'";   
        $this->DB->setFieldTable(array('value'));
        $r_lock_kelas = $this->DB->getRecord($str_lock_kelas);
        $value_lock_kelas=$r_lock_kelas[1]['value'];
        if($value_lock_kelas=="1" && $idkelas_mhs_selected!="none"){
          $item->cmbKelas->Enabled=false;
        }
        
        CKRS::$totalSKS+=$item->DataItem['sks'];
        CKRS::$jumlahMatkul+=1;
      }
    }
  }
  protected function populateData () {
    try {			
      $datamhs=$this->Pengguna->getDataUser();  
      $this->KRS->setDataMHS($datamhs);
      $datakrs=$this->KRS->getKRS($_SESSION['ta'],$_SESSION['semester']);           
      if (isset($datakrs['krs']['idkrs'])) {
        $datadulang=$this->KRS->getDataDulang($datakrs['krs']['idsmt'],$datakrs['krs']['tahun']);
        $datakrs['krs']['kelas_dulang']=$datadulang['idkelas'];               
      }                        
      $_SESSION['currentPageKRS']['DataKRS']=$datakrs;
      $this->RepeaterS->DataSource=$this->KRS->DataKRS['matakuliah'];
      $this->RepeaterS->dataBind();
    }catch (Exception $e) {
      $this->idProcess = 'view';	
      $this->errorMessage->Text=$e->getMessage();	
    }

  }	
   
  public function prosesKelas ($sender,$param) {
    $idkelas_mhs=$sender->Text;
    $idkrsmatkul = $this->getDataKeyField($sender, $this->RepeaterS);
    $this->DB->query('BEGIN');
    if ($idkelas_mhs=='none') 
    {
      $this->DB->deleteRecord("kelas_mhs_detail WHERE idkrsmatkul = $idkrsmatkul");
      $this->DB->deleteRecord("kuesioner_jawaban WHERE idkrsmatkul = $idkrsmatkul");
      $this->DB->updateRecord("UPDATE nilai_matakuliah SET telah_isi_kuesioner=0,tanggal_isi_kuesioner='' WHERE idkrsmatkul = $idkrsmatkul");
      
      $str = "UPDATE kelas_mhs SET synced=0,sync_msg=null WHERE idkelas_mhs=$idkelas_mhs";
      $this->DB->updateRecord($str);

      $this->DB->query('COMMIT');
      $this->redirect('perkuliahan.KRS', true); 
    }
    else
    {
      $jumlah_peserta_kelas = $this->DB->getCountRowsOfTable ("kelas_mhs_detail WHERE idkelas_mhs=$idkelas_mhs",'idkelas_mhs');
      $str = "SELECT kapasitas FROM kelas_mhs km,ruangkelas rk WHERE rk.idruangkelas=km.idruangkelas AND idkelas_mhs=$idkelas_mhs";
      $this->DB->setFieldTable(array('kapasitas'));
      $result=$this->DB->getRecord($str);
      $kapasitas=$result[1]['kapasitas'];
      //if ($jumlah_peserta_kelas <= $kapasitas) {
        if ($this->DB->checkRecordIsExist('idkrsmatkul','kelas_mhs_detail',$idkrsmatkul)) 
        {
          $this->DB->updateRecord("UPDATE kelas_mhs_detail SET idkelas_mhs=$idkelas_mhs WHERE idkrsmatkul = $idkrsmatkul");
          $this->DB->deleteRecord("kuesioner_jawaban WHERE idkrsmatkul = $idkrsmatkul");
          $this->DB->updateRecord("UPDATE nilai_matakuliah SET telah_isi_kuesioner=0,tanggal_isi_kuesioner='' WHERE idkrsmatkul = $idkrsmatkul");
        }
        else //masukan peserta ke kelas
        {
          $this->DB->insertRecord("INSERT INTO kelas_mhs_detail SET idkelas_mhs=$idkelas_mhs,idkrsmatkul = $idkrsmatkul");
        }

        $str = "UPDATE kelas_mhs SET synced=0,sync_msg=null WHERE idkelas_mhs=$idkelas_mhs";
        $this->DB->updateRecord($str);

        $this->DB->query('COMMIT');
        $this->redirect('perkuliahan.KRS', true);
      //}else{
      //    $this->modalMessageError->show();
      //    $this->lblContentMessageError->Text="Tidak bisa bergabung dengan kelas ini, karena kalau ditambah dengan Anda akan melampau kapasitas kelas ($kapasitas). Silahkan Refresh Web Browser Anda.";					
      //}
    }
  }
  public function printKRS ($sender,$param) {
    $this->createObj('reportkrs');        
    $messageprintout='';   

    $this->linkOutput->Text='';
    $this->linkOutput->NavigateUrl='#';

    $dataReport=$this->Pengguna->getDataUser();
    $tahun=$_SESSION['ta'];
    $semester=$_SESSION['semester'];
    $nama_tahun = $this->DMaster->getNamaTA($tahun);
    $nama_semester = $this->setup->getSemester($semester);

    $idkrs=$_SESSION['currentPageKRS']['DataKRS']['krs']['idkrs'];
    $str = "kelas_mhs_detail kmd JOIN krsmatkul km ON (kmd.idkrsmatkul=km.idkrsmatkul) WHERE idkrs='$idkrs'";
    $jumlah_kelas=$this->DB->getCountRowsOfTable($str,'kmd.idkrsmatkul');
    $jumlah_matkul = $_SESSION['currentPageKRS']['DataKRS']['krs']['jumlah_sah'];	        
    if ($jumlah_kelas >= $jumlah_matkul) {
      switch ($_SESSION['outputreport']) {
        case  'summarypdf' :
          $messageprintout="Mohon maaf Print out pada mode summary pdf tidak kami support.";                
        break;
        case  'summaryexcel' :
          $messageprintout="Mohon maaf Print out pada mode summary excel tidak kami support.";                
        break;
        case  'excel2007' :
          $messageprintout="Mohon maaf Print out pada mode excel 2007 belum kami support.";                
        break;
        case  'pdf' :                                
          $dataReport['krs']=$_SESSION['currentPageKRS']['DataKRS']['krs'];        
          $dataReport['matakuliah']=$_SESSION['currentPageKRS']['DataKRS']['matakuliah'];        
          $dataReport['nama_tahun']=$nama_tahun;
          $dataReport['nama_semester']=$nama_semester;        
          
          $kaprodi=$this->KRS->getKetuaPRODI($dataReport['kjur']);                  
          $dataReport['nama_kaprodi']=$kaprodi['nama_dosen'];
          $dataReport['jabfung_kaprodi']=$kaprodi['nama_jabatan'];
          $dataReport['nipy_kaprodi']=$kaprodi['nipy'];
          
          $dataReport['linkoutput']=$this->linkOutput;                 
          $this->report->setDataReport($dataReport); 
          $this->report->setMode($_SESSION['outputreport']);
          $this->report->printKRS();				
          
        break;
      }
      $this->lblMessagePrintout->Text=$messageprintout;
      $this->lblPrintout->Text="Kartu Rencana Studi T.A $nama_tahun Semester $nama_semester";
      $this->modalPrintOut->show();
    }else{
      $this->modalMessageError->show();
      $this->lblContentMessageError->Text="Mohon untuk mengisi seluruh kelas terlebih dahulu.";
    }       
     
  }
  
  public function printKSM ($sender,$param) {
    $this->createObj('reportkrs');        
    $messageprintout='';   

    $this->linkOutput->Text='';
    $this->linkOutput->NavigateUrl='#';

    $dataReport=$this->Pengguna->getDataUser();
    $tahun=$_SESSION['ta'];
    $semester=$_SESSION['semester'];
    $nama_tahun = $this->DMaster->getNamaTA($tahun);
    $nama_semester = $this->setup->getSemester($semester);

    $idkrs=$_SESSION['currentPageKRS']['DataKRS']['krs']['idkrs'];
    $dataidkrs[$idkrs]=$idkrs;

    if ($this->DB->checkRecordIsExist('idkrs','siuas_pembayaran_belum_lunas',$idkrs)==false) {
      $messageprintout="Mohon maaf, anda tidak bisa mencetak KSM karena administrasi, Silahkan hubungi bagian keuangan.";
    }else{
      switch ($_SESSION['outputreport']) {
        case  'summarypdf' :
          $messageprintout="Mohon maaf Print out pada mode summary pdf tidak kami support.";                
        break;
        case  'summaryexcel' :
          $messageprintout="Mohon maaf Print out pada mode summary excel tidak kami support.";                
        break;
        case  'excel2007' :
          $messageprintout="Mohon maaf Print out pada mode excel 2007 belum kami support.";                
        break;
        case  'pdf' :                                
              
          $messageprintout="";
          $str = "SELECT krs.idkrs,vdm.no_formulir,vdm.nim,vdm.nirm,vdm.nama_mhs,vdm.jk,vdm.tempat_lahir,vdm.tanggal_lahir,vdm.kjur,vdm.nama_ps,vdm.idkonsentrasi,k.nama_konsentrasi,vdm.tahun_masuk,vdm.semester_masuk,iddosen_wali,d.idkelas,d.k_status,krs.idsmt,krs.tahun,krs.tasmt,krs.sah FROM krs JOIN dulang d ON (d.nim=krs.nim) LEFT JOIN v_datamhs vdm ON (krs.nim=vdm.nim) LEFT JOIN konsentrasi k ON (vdm.idkonsentrasi=k.idkonsentrasi) WHERE krs.idkrs='$idkrs'";
          $this->DB->setFieldTable(array('idkrs','no_formulir','nim','nirm','nama_mhs','jk','tempat_lahir','tanggal_lahir','kjur','nama_ps','idkonsentrasi','nama_konsentrasi','tahun_masuk','semester_masuk','iddosen_wali','idkelas','k_status','idsmt','tahun','tasmt','sah'));
          $r=$this->DB->getRecord($str);	           
          $dataReport=$r[1];
          
          $dataReport['nama_tahun']=$nama_tahun; 
          $dataReport['nama_semester']=$nama_semester;
          
          $nama_dosen=$this->DMaster->getNamaDosenWaliByID($dataReport['iddosen_wali']);				                    
          $dataReport['nama_dosen']=$nama_dosen;
          
          $kaprodi=$this->KRS->getKetuaPRODI($dataReport['kjur']);
          $dataReport['nama_kaprodi']=$kaprodi['nama_dosen'];
          $dataReport['jabfung_kaprodi']=$kaprodi['nama_jabatan'];
          $dataReport['nidn_kaprodi']=$kaprodi['nidn'];
          
          $dataReport['linkoutput']=$this->linkOutput;
          $this->report->setDataReport($dataReport); 
          $this->report->setMode($_SESSION['outputreport']);
          
          $this->printKUM("",$dataidkrs,$this->KRS,$this->DMaster);  
        
        break;
      }
    }
    $this->lblMessagePrintout->Text=$messageprintout;
    $this->lblPrintout->Text="Kartu Studi Mahasiswa T.A $nama_tahun Semester $nama_semester";
    $this->modalPrintOut->show();
  }	
  
  /**
   * digunakan untuk memprint Kartu ujian mahasiswa
   * @param type $jenisujian UTS atau UAS
   * @param type $dataidkrs 
   * @param type $objKRS objek KRS
   * @param type $objDMaster objek data master
   */
  public function printKUM ($jenisujian,$dataidkrs,$objKRS,$objDMaster) {
    
    switch ($this->report->getDriver()) {
      case 'excel2003' :               
      case 'excel2007' :                
      break;
      case 'summarypdf' :
        $this->setMode('pdf');
        $rpt=$this->report->rpt;
        
        $rpt->setTitle('Kartu Ujian Mahasiswa');
        $rpt->setSubject('Kartu Ujian Mahasiswa');
        
        while (list($idkrs,$value)=each($dataidkrs)) {                    
          $rpt->AddPage();
          $this->setHeaderPT();
          
          $row=$this->currentRow;
          $row+=6;
          $rpt->SetFont ('helvetica','B',12);	
          $rpt->setXY(3,$row);			
          $kartu=($jenisujian=='uts')?'KARTU UJIAN TENGAH SEMESTER (UTS)':'KARTU UJIAN AKHIR SEMESTER (UAS)';
          $rpt->Cell(0,$row,$kartu,0,0,'C');
          
          $str = "SELECT krs.idkrs,vdm.no_formulir,vdm.nim,vdm.nirm,vdm.nama_mhs,vdm.jk,vdm.tempat_lahir,vdm.tanggal_lahir,vdm.kjur,vdm.nama_ps,vdm.idkonsentrasi,k.nama_konsentrasi,vdm.tahun_masuk,vdm.semester_masuk,iddosen_wali,d.idkelas,d.k_status,krs.idsmt,krs.tahun,krs.tasmt,krs.sah FROM krs JOIN dulang d ON (d.nim=krs.nim) LEFT JOIN v_datamhs vdm ON (krs.nim=vdm.nim) LEFT JOIN konsentrasi k ON (vdm.idkonsentrasi=k.idkonsentrasi) WHERE krs.idkrs='$idkrs'";
          $this->db->setFieldTable(array('idkrs','no_formulir','nim','nirm','nama_mhs','jk','tempat_lahir','tanggal_lahir','kjur','nama_ps','idkonsentrasi','nama_konsentrasi','tahun_masuk','semester_masuk','iddosen_wali','idkelas','k_status','idsmt','tahun','tasmt','sah'));
          $r=$this->db->getRecord($str);	           
          $dataReport=$r[1];

          $dataReport['nama_ps']=$_SESSION['daftar_jurusan'][$dataReport['kjur']];                
          $nama_tahun = $objDMaster->getNamaTA($dataReport['tahun']);   
          $nama_semester = $this->setup->getSemester($dataReport['idsmt']);
          $dataReport['nama_tahun']=$nama_tahun; 
          $dataReport['nama_semester']=$nama_semester;

          $nama_dosen=$objDMaster->getNamaDosenWaliByID($dataReport['iddosen_wali']);				                    
          $dataReport['nama_dosen']=$nama_dosen;

          $kaprodi=$objKRS->getKetuaPRODI($dataReport['kjur']);
          $dataReport['nama_kaprodi']=$kaprodi['nama_dosen'];
          $dataReport['jabfung_kaprodi']=$kaprodi['nama_jabatan'];
          $dataReport['nidn_kaprodi']=$kaprodi['nidn'];
          $row+=6;
          $rpt->SetFont ('helvetica','B',8);	
          $rpt->setXY(3,$row);			
          $rpt->Cell(0,$row,'NIM');
          $rpt->SetFont ('helvetica','',8);
          $rpt->setXY(38,$row);			
          $rpt->Cell(0,$row,': '.$dataReport['nama_mhs'].' ('.$dataReport['jk'].')');
          $rpt->SetFont ('helvetica','B',8);	
          $rpt->setXY(105,$row);			
          $rpt->Cell(0,$row,'Semester');
          $rpt->SetFont ('helvetica','',8);
          $rpt->setXY(130,$row);			
          $rpt->Cell(0,$row,': '.$dataReport['nama_ps'].' / S-1');
          $row+=3;
          $rpt->setXY(3,$row);			
          $rpt->SetFont ('helvetica','B',8);	
          $rpt->Cell(0,$row,'Nama');
          $rpt->SetFont ('helvetica','',8);
          $rpt->setXY(38,$row);			
          $rpt->Cell(0,$row,': '.$dataReport['nama_dosen']);				
          $rpt->SetFont ('helvetica','B',8);	
          $rpt->setXY(105,$row);			
          $rpt->Cell(0,$row,'T.A');
          $rpt->SetFont ('helvetica','',8);
          $rpt->setXY(130,$row);			
          $rpt->Cell(0,$row,': '.$dataReport['nim']);
          $row+=3;
          $rpt->setXY(3,$row);			
          $rpt->SetFont ('helvetica','B',8);	
          $rpt->Cell(0,$row,'Program Studi');				
          $rpt->SetFont ('helvetica','',8);
          $rpt->setXY(38,$row);			
          $rpt->Cell(0,$row,": $nama_semester / $nama_tahun");				
          $rpt->SetFont ('helvetica','B',8);	
          $rpt->setXY(105,$row);			
          $rpt->Cell(0,$row,'NIRM');
          $rpt->SetFont ('helvetica','',8);
          $rpt->setXY(130,$row);			
          $rpt->Cell(0,$row,': '.$dataReport['nirm']);			

          $row+=20;
          $rpt->SetFont ('helvetica','B',8);
          $rpt->setXY(3,$row);			
          $rpt->Cell(8, 5, 'NO', 1, 0, 'C');				
          $rpt->Cell(15, 5, 'KODE', 1, 0, 'C');								
          $rpt->Cell(70, 5, 'MATAKULIAH', 1, 0, 'C');							
          $rpt->Cell(8, 5, 'SKS', 1, 0, 'C');				
          $rpt->Cell(8, 5, 'SMT', 1, 0, 'C');				
          $rpt->Cell(60, 5, 'PENGAWAS', 1, 0, 'C');						
          $rpt->Cell(15, 5, 'TGL', 1, 0, 'C');
          $rpt->Cell(15, 5, 'TTD', 1, 0, 'C');

          $daftar_matkul = $objKRS->getDetailKRS($idkrs);
          $totalSks=0;
          $row+=5;				
          $rpt->SetFont ('helvetica','',8);
          while (list($k,$v)=each($daftar_matkul)) {
            $rpt->setXY(3,$row);	
            $rpt->Cell(8, 5, $v['no'], 1, 0, 'C');				
            $rpt->Cell(15, 5, $v['kmatkul'], 1, 0, 'C');		
            $flag='';
            if ($jenisujian=='uas') {
              $idkrsmatkul = $v['idkrsmatkul'];
              $str = "kbm_detail WHERE idkrsmatkul='$idkrsmatkul' AND kehadiran='hadir'";										
              $flag=' *';
              if ($totalpertemuan=$this->db->getCountRowsOfTable($str,'idkrsmatkul')>=1) {
                $minimal=round(($totalpertemuan/14)*100);													
                $flag=$minimal<75?'*':'';
              }
            }					
            $rpt->Cell(70, 5, $v['nmatkul'].$flag , 1, 0, 'L');							
            $rpt->Cell(8, 5, $v['sks'], 1, 0, 'C');				
            $rpt->Cell(8, 5, $v['semester'], 1, 0, 'C');				
            $rpt->Cell(60, 5, '', 1, 0, 'L');						
            $rpt->Cell(15, 5, '', 1, 0, 'C');
            $rpt->Cell(15, 5, '', 1, 0, 'C');					
            $totalSks+=$v['sks'];
            $row+=5;
          }
          $row+=3;
          $rpt->setXY(3,$row);	
          $rpt->Cell(8, 5, 'TOTAL SKS', 1, 0, 'C');				
          $rpt->Cell(15, 5, $totalSks, 1, 0, 'C');						
          
          $row+=3;
          $rpt->setXY(3,$row);	
          $rpt->SetFont ('helvetica','',6);
          $rpt->Cell(70, 5, 'Catatan : Tanda "*" memiliki arti absensi Mahasiswa kurang dari 75%.' , 0, 0, 'L');	

          $row+=5;
          $rpt->SetFont ('helvetica','B',8);
          $rpt->setXY(120,$row);			
          $rpt->Cell(80, 5, 'Mengetahui,',0,0,'L');				

          $row+=5;
          $rpt->setXY(120,$row);			
          $tanggal = $this->TGL->tanggal('j F Y');				
          $rpt->Cell(80, 5, "Tanjungpinang, $tanggal",0,0,'L');

          $row+=5;				
          $rpt->setXY(120,$row);			
          $rpt->Cell(80, 5, 'Ketua Program Studi '.$dataReport['nama_ps'],0,0,'L');								

          $row+=20;				
          $rpt->setXY(120,$row);			
          $rpt->Cell(80, 5, $dataReport['nama_kaprodi'],0,0,'L');

          $row+=5;							
          $rpt->setXY(120,$row);
          $nama_jabatan=$dataReport['jabfung_kaprodi'];
          $nidn=$dataReport['nidn_kaprodi'];
          $rpt->Cell(80, 5, "$nama_jabatan NIDN : $nidn",0,0,'L');
          
        }
        $this->printOut("kum");
      break;
      case 'pdf' :
        $rpt=$this->report->rpt;
        $rpt->AddPage();
        $this->report->setHeaderPT();
         
        $nim=$this->report->dataReport['nim'];
        $nama_tahun=$this->report->dataReport['nama_tahun'];
        $nama_semester=$this->report->dataReport['nama_semester'];
    
        $rpt->setTitle('Kartu Ujian Mahasiswa');
        $rpt->setSubject('Kartu Ujian Mahasiswa');
        
        
        $row=$this->report->currentRow;
        $row+=6;
        $rpt->SetFont ('helvetica','B',12);	
        $rpt->setXY(3,$row);			
        $kartu=' KARTU STUDI MAHASISWA (KSM)';
        $rpt->Cell(0,$row,$kartu,0,0,'C');
        
        $row+=3;
        $rpt->setXY(3,$row);	
        $rpt->Cell(0,$row,'UJIAN TENGAH  SEMESTER DAN UJIAN AKHIR SEMESTER',0,0,'C');
        
        $row+=6;
        $rpt->SetFont ('helvetica','B',8);	
        $rpt->setXY(6,$row);			
        $rpt->Cell(0,$row,'Nim');
        $rpt->SetFont ('helvetica','',8);
        $rpt->setXY(38,$row);			
        $rpt->Cell(0,$row,': '.$this->report->dataReport['nim']);
        $rpt->SetFont ('helvetica','B',8);	
        $rpt->setXY(105,$row);			
        $rpt->Cell(0,$row,'Semester');
        $rpt->SetFont ('helvetica','',8);
        $rpt->setXY(130,$row);			
        $rpt->Cell(0,$row,': '."$nama_semester");
        $row+=3;
        $rpt->setXY(6,$row);			
        $rpt->SetFont ('helvetica','B',8);	
        $rpt->Cell(0,$row,'Nama');
        $rpt->SetFont ('helvetica','',8);
        $rpt->setXY(38,$row);			
        $rpt->Cell(0,$row,': '.$this->report->dataReport['nama_mhs']);				
        $rpt->SetFont ('helvetica','B',8);	
        $rpt->setXY(105,$row);			
        $rpt->Cell(0,$row,'T.A');
        $rpt->SetFont ('helvetica','',8);
        $rpt->setXY(130,$row);			
        $rpt->Cell(0,$row,': '."$nama_tahun");
        $row+=3;
        $rpt->setXY(6,$row);			
        $rpt->SetFont ('helvetica','B',8);	
        $rpt->Cell(0,$row,'Program Studi');				
        $rpt->SetFont ('helvetica','',8);
        $rpt->setXY(38,$row);			
        $rpt->Cell(0,$row,': '.$this->report->dataReport['nama_ps']);						
        
        $row+=20;
        $rpt->SetFont ('helvetica','B',8);
        $rpt->setXY(6,$row);			
        $rpt->Cell(8, 10, 'NO', 1, 0, 'C');				
        $rpt->Cell(15, 10, 'KODE', 1, 0, 'C');								
        $rpt->Cell(70, 10, 'MATAKULIAH', 1, 0, 'C');							
        $rpt->Cell(8, 10, 'SKS', 1, 0, 'C');							
        $rpt->Cell(60, 5, 'PARAF PENGAWAS', 1, 0, 'C');
        $row+=5;
        $rpt->SetFont ('helvetica','B',8);
        $rpt->setXY(107,$row);															
        $rpt->Cell(30, 5, 'UTS', 1, 0, 'C');				
        $rpt->Cell(30, 5, 'UAS', 1, 0, 'C');
        
        $daftar_matkul = $objKRS->getDetailKRS($this->report->dataReport['idkrs']);
        $totalSks=0;
        $row+=5;				
        $rpt->SetFont ('helvetica','',8);
        while (list($k,$v)=each($daftar_matkul)) {
          $rpt->setXY(6,$row);	
          $rpt->Cell(8, 8, $v['no'], 1, 0, 'C');				
          $rpt->Cell(15, 8, $v['kmatkul'], 1, 0, 'C');		
          $flag='';
          if ($jenisujian=='uas') {
            $idkrsmatkul = $v['idkrsmatkul'];
            $str = "kbm_detail WHERE idkrsmatkul='$idkrsmatkul' AND kehadiran='hadir'";										
            $flag=' *';
            if ($totalpertemuan=$this->db->getCountRowsOfTable($str,'idkrsmatkul')>=1) {
              $minimal=round(($totalpertemuan/14)*100);													
              $flag=$minimal<75?'*':'';
            }
          }					
          $rpt->Cell(70, 8, $v['nmatkul'].$flag , 1, 0, 'L');							
          $rpt->Cell(8, 8, $v['sks'], 1, 0, 'C');							
          $rpt->Cell(30, 8, '', 1, 0, 'L');	
          $rpt->Cell(30, 8, '', 1, 0, 'L');						
          $totalSks+=$v['sks'];
          $row+=8;
        }
        $row+=0;
        $rpt->setXY(6,$row);	
        $rpt->Cell(93, 5, 'TOTAL SKS', 1, 0, 'C');				
        $rpt->Cell(8, 5, $totalSks, 1, 0, 'C');	
        
        $row+=6;
        $rpt->setXY(3,$row);	
        $rpt->SetFont ('helvetica','',8);
        $rpt->Cell(70, 5, 'Catatan : - KSM ini sah apabila telah ditandatangani oleh Mahasiswa dan Bagian Akademik serta telah dibubuhi stempel ' , 0, 0, 'L');	
        $row+=3;
        $rpt->setXY(17,$row);	
        $rpt->SetFont ('helvetica','',8);
        $rpt->Cell(70, 5, 'dan sudah di verifikasi oleh bagian keuangan.' , 0, 0, 'L');
        $row+=3;
        $rpt->setXY(15,$row);	
        $rpt->SetFont ('helvetica','',8);
        $rpt->Cell(70, 5, '- KSM merupakan bukti sah pengambilan mata kuliah & syarat untuk mengikuti Ujian Tengah Semester (UTS)' , 0, 0, 'L');
        $row+=3;
        $rpt->setXY(15,$row);	
        $rpt->SetFont ('helvetica','',8);
        $rpt->Cell(70, 5, '  dan Ujian Akhir Semester (UAS) serta menjadi syarat pedaftaran semester berikutnya.' , 0, 0, 'L');

                        
        $row+=10;
        $rpt->setXY(15,$row);
        $rpt->Cell(70, 5, "Mahasiswa",0,0,'C');				
        $tanggal = $this->TGL->tanggal('j F Y');				
        $rpt->Cell(80, 5, "Tanjungpinang, $tanggal",0,0,'C');
        
        $row+=5;				
        $rpt->setXY(85,$row);			
        $rpt->Cell(80, 5, 'Pembantu Ketua I',0,0,'C');								
        
        $row+=20;				
        $rpt->setXY(15,$row);	
        $rpt->Cell(70, 5, $this->report->dataReport['nama_mhs'],0,0,'C');
        $rpt->Cell(80, 5, 'Ade Winarni, M.T',0,0,'C');

        $row+=10;				
        $rpt->setXY(30,$row);	
        $rpt->Cell(50, 5, 'VERIFIKASI UTS','T,L,R',0,'C');
        $rpt->setXY(100,$row);
        $rpt->Cell(50, 5, 'VERIFIKASI UAS','T,L,R',0,'C');			
        $rpt->setXY(30,$row);	
        $rpt->Cell(50, 20, '','B,L,R',0,'C');
        $rpt->setXY(100,$row);
        $rpt->Cell(50, 20, '','B,L,R',0,'C');
        
        $this->report->printOut("kum_$nim");
      break;
    }
    $this->report->setLink($this->report->dataReport['linkoutput'],"Kartu Studi Mahasiswa T.A $nama_tahun Semester $nama_semester");
  }	
}