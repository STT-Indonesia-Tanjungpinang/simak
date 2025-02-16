<?php
prado::using ('Application.MainPageM');
class CDetailKRS extends MainPageM 
{
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
  public function onLoad($param) 
  {
    parent::onLoad($param);	
    $this->showSubMenuAkademikPerkuliahan = true;
    $this->showKRS = true;           
    $this->createObj('KRS');
    if (!$this->IsPostBack && !$this->IsCallback) {	            
      $this->tbCmbOutputReport->DataSource = $this->setup->getOutputFileType();
      $this->tbCmbOutputReport->Text= $_SESSION['outputreport'];
      $this->tbCmbOutputReport->DataBind();
        
      $this->populateData();				
      $this->lblModulHeader->Text = $this->getInfoToolbar();            
        
    }				
  }
  public function getDataMHS($idx) 
  {		        
    return $this->KRS->getDataMHS($idx);
  }
  public function getInfoToolbar() 
  {                
    $ta = $this->DMaster->getNamaTA($this->Page->KRS->DataKRS['krs']['tahun']);
    $semester = $this->setup->getSemester($this->Page->KRS->DataKRS['krs']['idsmt']);
    $text = "TA $ta Semester $semester";
    return $text;
  }	
  public function itemBound($sender, $param)
  {
    $item = $param->Item;
    if ($item->ItemType === 'Item' || $item->ItemType === 'AlternatingItem')
    {
      if ($item->DataItem['batal']) 
      {
        $item->cmbKelas->Enabled = false;
        CDetailKRS::$totalSKSBatal+=$item->DataItem['sks'];
        CDetailKRS::$jumlahMatkulBatal+=1;
      }
      else
      {
        $idkrsmatkul = $item->DataItem['idkrsmatkul'];
        $idpenyelenggaraan = $item->DataItem['idpenyelenggaraan'];
        $idkelas = $_SESSION['currentPageKRS']['DataMHS']['kelas_dulang'];
        $str = "SELECT km.idkelas_mhs,km.nama_kelas,vpp.nama_dosen,vpp.nidn,km.idruangkelas FROM kelas_mhs km JOIN v_pengampu_penyelenggaraan vpp ON (km.idpengampu_penyelenggaraan=vpp.idpengampu_penyelenggaraan) WHERE vpp.idpenyelenggaraan = $idpenyelenggaraan AND km.idkelas='$idkelas'  ORDER BY hari ASC,idkelas ASC,nama_dosen ASC";            
        $this->DB->setFieldTable(array('idkelas_mhs', 'nama_kelas', 'nama_dosen', 'nidn', 'idruangkelas'));
        $r = $this->DB->getRecord($str);	
        
        $str = "SELECT idkelas_mhs FROM kelas_mhs_detail WHERE idkrsmatkul = $idkrsmatkul";            
        $this->DB->setFieldTable(array('idkelas_mhs'));
        $r_selected = $this->DB->getRecord($str);
        
        if (isset($r_selected[1])) {
          $idkelas_mhs_selected=$r_selected[1]['idkelas_mhs'];
          $result = array();
        }else{
          $idkelas_mhs_selected='none';
          $result = array('none' => ' ');
        }      
        while (list($k, $v) = each($r)) {    
          $idkelas_mhs = $v['idkelas_mhs'];
          $jumlah_peserta_kelas = $this->DB->getCountRowsOfTable ("kelas_mhs_detail WHERE idkelas_mhs = $idkelas_mhs",'idkelas_mhs');
          $kapasitas=(int)$this->DMaster->getKapasitasRuangKelas($v['idruangkelas']);
          $keterangan=($jumlah_peserta_kelas <= $kapasitas) ? '': ' [PENUH]';
          $result[$idkelas_mhs] = $this->DMaster->getNamaKelasByID($idkelas).'-'.chr($v['nama_kelas'] + 64) . ' ['.$v['nidn'].']'.$keterangan;   
        }
        $item->cmbKelas->DataSource = $result;            
        $item->cmbKelas->DataBind();        
        $item->cmbKelas->Enabled=!$this->DB->checkRecordIsExist('idkrsmatkul', 'nilai_matakuliah', $idkrsmatkul);
        $item->cmbKelas->Text = $idkelas_mhs_selected;
        
        CDetailKRS::$totalSKS+=$item->DataItem['sks'];
        CDetailKRS::$jumlahMatkul+=1;
      }
    }
  }
  protected function populateData() 
  {
    try 
    {			
      $idkrs = addslashes($this->request['id']);            				
      $str = "SELECT vdm.no_formulir,vdm.nim,vdm.nirm,vdm.nama_mhs,vdm.jk,vdm.tempat_lahir,vdm.tanggal_lahir,vdm.kjur,vdm.nama_ps,vdm.idkonsentrasi,k.nama_konsentrasi,vdm.tahun_masuk,vdm.semester_masuk,vdm.iddosen_wali,vdm.idkelas,vdm.k_status,sm.n_status AS status,krs.idsmt,krs.tahun,krs.tasmt,krs.sah,vdm.photo_profile FROM krs LEFT JOIN v_datamhs vdm ON (krs.nim=vdm.nim) LEFT JOIN konsentrasi k ON (vdm.idkonsentrasi=k.idkonsentrasi) LEFT JOIN status_mhs sm ON (vdm.k_status=sm.k_status) WHERE krs.idkrs='$idkrs'";
      $this->DB->setFieldTable(array('no_formulir', 'nim', 'nirm', 'nama_mhs', 'jk', 'tempat_lahir', 'tanggal_lahir', 'kjur', 'nama_ps', 'idkonsentrasi', 'nama_konsentrasi', 'tahun_masuk', 'semester_masuk', 'iddosen_wali', 'idkelas', 'k_status', 'status', 'idsmt', 'tahun', 'tasmt', 'sah', 'photo_profile'));
      $r = $this->DB->getRecord($str);	           
      $datamhs = $r[1];
      
      if (!isset($r[1])) {
        $_SESSION['currentPageKRS']['DataKRS'] = array();
        throw new Exception("KRS dengan ID ($idkrs) tidak terdaftar.");
      }  
      
      $datamhs['iddata_konversi'] = $this->KRS->isMhsPindahan($datamhs['nim'],true);            
      $this->KRS->setDataMHS($datamhs);
      $kelas = $this->KRS->getKelasMhs();																	            
      $datamhs['nkelas']=($kelas['nkelas']== '') ? 'Belum ada':$kelas['nkelas'];			                    
      $datamhs['nama_konsentrasi']=($datamhs['idkonsentrasi'] == 0) ? '-' : $datamhs['nama_konsentrasi'];
      
      $nama_dosen = $this->DMaster->getNamaDosenWaliByID($datamhs['iddosen_wali']);				                    
      $datamhs['nama_dosen'] = $nama_dosen;
      
      $datadulang = $this->KRS->getDataDulang($datamhs['idsmt'], $datamhs['tahun']);
      $datamhs['kelas_dulang'] = $datadulang['idkelas'];
      
      $_SESSION['currentPageKRS']['DataMHS'] = $datamhs;
      $this->KRS->setDataMHS($datamhs);
      
      $this->KRS->getKRS($_SESSION['ta'], $_SESSION['semester']);
      
      $_SESSION['currentPageKRS']['DataKRS'] = $this->KRS->DataKRS;
      $this->btnTambah->Enabled=!$this->KRS->DataKRS['krs']['sah'];
      
      $this->RepeaterS->DataSource = $this->KRS->DataKRS['matakuliah'];
      $this->RepeaterS->dataBind();
    }
    catch (Exception $e) 
    {
      $this->idProcess = 'view';	
      $this->errorMessage->Text = $e->getMessage();	
    }

  }		
  public function prosesKelas($sender, $param)
  {
    $idkelas_mhs = $sender->Text;
    $idkrsmatkul = $this->getDataKeyField($sender, $this->RepeaterS);
    $this->DB->query('BEGIN');
    if ($idkelas_mhs=='none')
    {
      $this->DB->deleteRecord("kelas_mhs_detail WHERE idkrsmatkul = $idkrsmatkul");
      $this->DB->deleteRecord("kuesioner_jawaban WHERE idkrsmatkul = $idkrsmatkul");
      $this->DB->updateRecord("UPDATE nilai_matakuliah SET telah_isi_kuesioner=0,tanggal_isi_kuesioner=NULL WHERE idkrsmatkul = $idkrsmatkul");
      
      $str = "UPDATE kelas_mhs SET synced=0,sync_msg=null WHERE idkelas_mhs = $idkelas_mhs";
      $this->DB->updateRecord($str);

      $this->DB->query('COMMIT');
      $this->redirect('perkuliahan.DetailKRS', true, array('id' => $_SESSION['currentPageKRS']['DataKRS']['krs']['idkrs']));
    }
    else
    {
      $jumlah_peserta_kelas = $this->DB->getCountRowsOfTable ("kelas_mhs_detail WHERE idkelas_mhs = $idkelas_mhs",'idkelas_mhs');
      $str = "SELECT kapasitas FROM kelas_mhs km,ruangkelas rk WHERE rk.idruangkelas=km.idruangkelas AND idkelas_mhs = $idkelas_mhs";
      $this->DB->setFieldTable(array('kapasitas'));
      $result=$this->DB->getRecord($str);
      $kapasitas = $result[1]['kapasitas'];
      // if ($jumlah_peserta_kelas <= $kapasitas)
      // {
        if ($this->DB->checkRecordIsExist('idkrsmatkul', 'kelas_mhs_detail', $idkrsmatkul))
        {
          $this->DB->updateRecord("UPDATE kelas_mhs_detail SET idkelas_mhs = $idkelas_mhs WHERE idkrsmatkul = $idkrsmatkul");
          $this->DB->deleteRecord("kuesioner_jawaban WHERE idkrsmatkul = $idkrsmatkul");
          $this->DB->updateRecord("UPDATE nilai_matakuliah SET telah_isi_kuesioner=0,tanggal_isi_kuesioner=NULL WHERE idkrsmatkul = $idkrsmatkul");
        }
        else
        {
          $this->DB->insertRecord("INSERT INTO kelas_mhs_detail SET idkelas_mhs = $idkelas_mhs,idkrsmatkul = $idkrsmatkul");
        }
        
        $str = "UPDATE kelas_mhs SET synced=0,sync_msg=null WHERE idkelas_mhs = $idkelas_mhs";
        $this->DB->updateRecord($str);

        $this->DB->query('COMMIT');
        $this->redirect('perkuliahan.DetailKRS', true, array('id' => $_SESSION['currentPageKRS']['DataKRS']['krs']['idkrs']));
      // }
      // else
      // {
      //   $this->modalMessageError->show();
      //   $this->lblContentMessageError->Text = "Tidak bisa bergabung dengan kelas ini, karena kalau ditambah dengan Anda akan melampau kapasitas kelas ($kapasitas). Silahkan Refresh Web Browser Anda.";					
      // }
    }
  }
  public function tambahKRS($sender, $param)
  {
    $this->createObj('Nilai');
    $datakrs = $_SESSION['currentPageKRS']['DataKRS'];
    $idsmt = $datakrs['krs']['idsmt'];
    $tahun = $datakrs['krs']['tahun'];        
    $this->Nilai->setDataMHS($_SESSION['currentPageKRS']['DataMHS']);
    if ($idsmt==3) {
      $this->createObj('Finance');
      $this->Finance->setDataMHS($_SESSION['currentPageKRS']['DataMHS']);
      $maxSKs = $this->Finance->getSKSFromSP($tahun, $idsmt);
      $this->Nilai->getKHSBeforeCurrentSemester($tahun, $idsmt);
      $datakrs['krs']['ipstasmtbefore'] = $this->Nilai->getIPS();
    }else{
      $datadulangbefore=$this->Nilai->getDataDulangBeforeCurrentSemester($idsmt, $tahun);
      if ($datadulangbefore['k_status']=='C') {
        $maxSKs = $this->setup->getSettingValue('jumlah_sks_krs_setelah_cuti');                
        $datakrs['krs']['ipstasmtbefore'] = 'N.A (Status Cuti)';
      }else{
        $maxSKs = $this->Nilai->getMaxSKS($tahun, $idsmt);
        $datakrs['krs']['ipstasmtbefore'] = $this->Nilai->getIPS();
      }
    }
    $datakrs['krs']['maxSKS'] = $maxSKS;     
    $_SESSION['currentPageKRS']['DataKRS'] = $datakrs;
    $this->redirect ('perkuliahan.TambahKRS', true);
 
    /*try {
      $this->DB->query('BEGIN');
      $str = "SELECT nim FROM v_krsmhs WHERE idpenyelenggaraan = 667";            
      $this->DB->setFieldTable(array('nim'));
      $r = $this->DB->getRecord($str);
      while (list($k, $v)=each ($r)) {            
        $nim = $v['nim'];
        $str1 = "SELECT idkrs FROM krs WHERE nim LIKE '$nim' AND idsmt=1 AND tahun='2019'";            
        $this->DB->setFieldTable(array('idkrs'));
        $r1 = $this->DB->getRecord($str1);
        if (isset($r1[1])) {
          $idkrs = $r1[1]['idkrs'];
          $this->DB->insertRecord("REPLACE INTO krsmatkul SET idpenyelenggaraan=858,idkrs = $idkrs,batal=0");
          $str2 = "SELECT idkrsmatkul FROM krsmatkul WHERE idkrs = $idkrs AND idpenyelenggaraan=858";            
          $this->DB->setFieldTable(array('idkrsmatkul'));
          $r2 = $this->DB->getRecord($str2);                     
          if (isset($r2[1])) { 
            $idkrsmatkul = $r2[1]['idkrsmatkul'];
            $this->DB->insertRecord("REPLACE INTO kelas_mhs_detail SET idkelas_mhs=1241,idkrsmatkul = $idkrsmatkul");
          }
        }
        
      }   
      $this->DB->query('COMMIT');
      echo "SELESAI";
    } catch (Exception $e) {
      echo 'Caught exception: ',  $e->getMessage(), "\n";
    }*/      
  }
  public function closeDetailKRS($sender, $param)
  { 
    unset($_SESSION['currentPageKRS']);
    $this->redirect ('perkuliahan.KRS', true);
  }
  public function printKRS($sender, $param)
  {
    $this->createObj('reportkrs');
    $this->linkOutput->Text = '';
    $this->linkOutput->NavigateUrl='#';
    switch($_SESSION['outputreport']) {
      case 'summarypdf':
        $messageprintout = "Mohon maaf Print out pada mode summary pdf tidak kami support.";                
      break;
      case 'summaryexcel':
        $messageprintout = "Mohon maaf Print out pada mode summary excel tidak kami support.";                
      break;
      case 'excel2007':
        $messageprintout = "Mohon maaf Print out pada mode excel 2007 belum kami support.";                
      break;
      case 'pdf':                
        $messageprintout='';                
        $tahun = $_SESSION['ta'];
        $semester = $_SESSION['semester'];
        $nama_tahun = $this->DMaster->getNamaTA($tahun);
        $nama_semester = $this->setup->getSemester($semester);

        $dataReport = $_SESSION['currentPageKRS']['DataMHS'];
        $dataReport['krs'] = $_SESSION['currentPageKRS']['DataKRS']['krs'];        
        $dataReport['matakuliah'] = $_SESSION['currentPageKRS']['DataKRS']['matakuliah'];        
        $dataReport['nama_tahun'] = $nama_tahun;
        $dataReport['nama_semester'] = $nama_semester;        
        
        $kaprodi = $this->KRS->getKetuaPRODI($dataReport['kjur']);                  
        $dataReport['nama_kaprodi'] = $kaprodi['nama_dosen'];
        $dataReport['jabfung_kaprodi'] = $kaprodi['nama_jabatan'];
        $dataReport['nipy_kaprodi'] = $kaprodi['nipy'];
        
        $dataReport['linkoutput'] = $this->linkOutput;                 
        $this->report->setDataReport($dataReport); 
        $this->report->setMode($_SESSION['outputreport']);
        $this->report->printKRS();				

        
      break;
    }
    $this->lblMessagePrintout->Text = $messageprintout;
    $this->lblPrintout->Text = "Kartu Rencana Studi T.A $nama_tahun Semester $nama_semester";
    $this->modalPrintOut->show();
  }
}