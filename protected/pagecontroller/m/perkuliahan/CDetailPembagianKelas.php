<?php
prado::using ('Application.MainPageM');
class CDetailPembagianKelas extends MainPageM {	
public function onLoad($param) {
    parent::onLoad($param);		
    $this->showSubMenuAkademikPerkuliahan = true;
    $this->showPembagianKelas=true;
    
    $this->createObj('Akademik');
    if (!$this->IsPostBack && !$this->IsCallback) {
      if (!isset($_SESSION['currentPagePembagianKelas']) || $_SESSION['currentPagePembagianKelas']['page_name'] != 'm.perkuliahan.PembagianKelas') {                
        $_SESSION['currentPagePembagianKelas'] = array('page_name' => 'm.perkuliahan.PembagianKelas', 'page_num' => 0, 'search' => false,'iddosen' => 'none', 'nama_hari' => 'none');												
      }
      $_SESSION['currentPagePembagianKelas']['search'] = false;
      
      $ta = $_SESSION['ta'];
      $idsmt = $_SESSION['semester'];
      $kjur = $_SESSION['kjur'];
      
      $str = "SELECT DISTINCT(iddosen),nidn,nama_dosen FROM v_pengampu_penyelenggaraan WHERE kjur = $kjur AND tahun = $ta AND idsmt=$idsmt";
      $this->DB->setFieldTable(array('iddosen', 'nidn', 'nama_dosen'));
      $r = $this->DB->getRecord($str);	
      $daftar_dosen=array('none' => 'Daftar Dosen');
      while (list($k, $v) = each($r)) { 
        $iddosen = $v['iddosen'];
        $daftar_dosen[$iddosen] = $v['nama_dosen'] . '['.$v['nidn'].']';
      }
      $iddosen = $_SESSION['currentPagePembagianKelas']['iddosen'];
      $this->cmbAddNamaDosen->DataSource = $daftar_dosen;			
      $this->cmbAddNamaDosen->Text = $iddosen;
      $this->cmbAddNamaDosen->dataBind();
      
      $this->tbCmbOutputReport->DataSource = $this->setup->getOutputFileType();
      $this->tbCmbOutputReport->Text= $_SESSION['outputreport'];
      $this->tbCmbOutputReport->DataBind();
      
      if ($_SESSION['currentPagePembagianKelas']['iddosen'] == 'none') {
        $this->cmbAddMatakuliah->Enabled = false;
        $this->cmbAddKelas->Enabled = false;
        $this->cmbAddHari->Enabled = false;
        $this->cmbAddJamMasuk->Enabled = false;
        $this->cmbAddJamKeluar->Enabled = false;                
        $this->cmbAddRuang->Enabled = false;
        $this->btnSave->Enabled = false;
        $this->RepeaterS->DataSource=array();
        $this->RepeaterS->dataBind();  
      }
      else
      {
        $str = "SELECT idpengampu_penyelenggaraan,kmatkul,nmatkul FROM v_pengampu_penyelenggaraan WHERE kjur = $kjur AND tahun = $ta AND idsmt=$idsmt AND iddosen = $iddosen";
        $this->DB->setFieldTable(array('idpengampu_penyelenggaraan', 'kmatkul', 'nmatkul'));
        $r = $this->DB->getRecord($str);	
        $daftar_matakuliah = array('none' => 'Daftar Matakuliah yang di Ampu');
        
        while (list($k, $v) = each($r)) {
          $kmatkul = $this->Demik->getKMatkul($v['kmatkul']);
          $daftar_matakuliah[$v['idpengampu_penyelenggaraan']] = '['.$kmatkul . '] '.$v['nmatkul'];
        }
        $this->cmbAddMatakuliah->DataSource = $daftar_matakuliah;
        $this->cmbAddMatakuliah->DataBind();
        
        $kelas = $this->DMaster->getListKelas();
        $kelas['none'] = 'Daftar Kelas';
        $this->cmbAddKelas->DataSource = $kelas;                
        $this->cmbAddKelas->dataBind();	
        
        //load hari 				
        $this->cmbAddHari->DataSource = $this->DMaster->removeIdFromArray($this->TGL->getNamaHari(), 'none');
        $this->cmbAddHari->dataBind();
        
        //load jam
        $jam_masuk_keluar['08:00'] = '08:00';    
        $jam_masuk_keluar['08:50'] = '08:50';
        $jam_masuk_keluar['09:40'] = '09:40';
        $jam_masuk_keluar['10:30'] = '10:30';
        $jam_masuk_keluar['11:10'] = '11:10';
        $jam_masuk_keluar['11:20'] = '11:20';
        $jam_masuk_keluar['12:00'] = '12:00';
        $jam_masuk_keluar['12:10'] = '12:10';
        $jam_masuk_keluar['12:50'] = '12:50';
        $jam_masuk_keluar['13:00'] = '13:00';
        $jam_masuk_keluar['13:40'] = '13:40';
        $jam_masuk_keluar['13:50'] = '13:50';
        $jam_masuk_keluar['14:40'] = '14:40';
        $jam_masuk_keluar['15:30'] = '15:30';
        $jam_masuk_keluar['18:15'] = '18:15';
        $jam_masuk_keluar['18:30'] = '18:30';
        $jam_masuk_keluar['19:10'] = '19:10';
        $jam_masuk_keluar['19:35'] = '19:35';
        $jam_masuk_keluar['19:50'] = '19:50';
        $jam_masuk_keluar['20:15'] = '20:15';
        $jam_masuk_keluar['20:30'] = '20:30';
        $jam_masuk_keluar['20:35'] = '20:35';
        $jam_masuk_keluar['21:10'] = '21:10';
        $jam_masuk_keluar['21:15'] = '21:15';
        $jam_masuk_keluar['21:50'] = '21:50';
        $jam_masuk_keluar['22:15'] = '22:15';
        $jam_masuk_keluar['22:30'] = '22:30';

        $this->cmbAddJamMasuk->DataSource = $jam_masuk_keluar;
        $this->cmbAddJamMasuk->dataBind();        

        $this->cmbAddJamKeluar->DataSource = $jam_masuk_keluar;    
        $this->cmbAddJamKeluar->dataBind();            
        
        //load kelas 				
        $this->cmbAddRuang->DataSource = $this->getLogic('DMaster')->getRuangKelas();
        $this->cmbAddRuang->dataBind();
        
        $this->populateData();
      }
      $this->lblModulHeader->Text = $this->getInfoToolbar();					
    }			
  }
  public function changeDosenPengampu($sender, $param) {
    $_SESSION['currentPagePembagianKelas']['iddosen'] = $this->cmbAddNamaDosen->Text;	 
    $this->redirect ('perkuliahan.DetailPembagianKelas', true);
  }	

  public function getInfoToolbar() {        
    $kjur = $_SESSION['kjur'];        
    $ps = $_SESSION['daftar_jurusan'][$kjur];
    $ta = $this->DMaster->getNamaTA($_SESSION['ta']);
    $semester = $this->setup->getSemester($_SESSION['semester']);
    $text = "Program Studi $ps TA $ta Semester $semester";
    return $text;
  }    
  public function populateData($search = false) {	
    $ta = $_SESSION['ta'];
    $idsmt = $_SESSION['semester'];
    $kjur = $_SESSION['kjur'];        
    $iddosen = $_SESSION['currentPagePembagianKelas']['iddosen'];
    $str = "SELECT km.idkelas_mhs,km.idkelas,km.nama_kelas,km.hari,km.jam_masuk,km.jam_keluar,vpp.kmatkul,vpp.nmatkul,vpp.sks,vpp.semester,vpp.nidn,rk.namaruang,rk.kapasitas FROM kelas_mhs km JOIN v_pengampu_penyelenggaraan vpp ON (km.idpengampu_penyelenggaraan=vpp.idpengampu_penyelenggaraan) LEFT JOIN ruangkelas rk ON (rk.idruangkelas=km.idruangkelas) WHERE idsmt='$idsmt' AND tahun='$ta' AND kjur='$kjur' AND vpp.iddosen = $iddosen ORDER BY idkelas ASC,nmatkul ASC,nama_dosen ASC";
    $this->DB->setFieldTable(array('idkelas_mhs', 'kmatkul', 'nmatkul', 'sks', 'semester', 'nidn', 'idkelas', 'nama_kelas', 'hari', 'jam_masuk', 'jam_keluar', 'namaruang', 'kapasitas'));
    $r = $this->DB->getRecord($str);	
    $result = array();
    while (list($k, $v) = each($r)) {  
      $kmatkul = $v['kmatkul'];
      $v['kode_matkul'] = $this->Demik->getKMatkul($kmatkul);     
      $v['namakelas'] = $this->DMaster->getNamaKelasByID($v['idkelas']).'-'.chr($v['nama_kelas'] + 64) . ' ['.$v['nidn'].']';
      $v['jumlah_peserta_kelas'] = $this->DB->getCountRowsOfTable('kelas_mhs_detail WHERE idkelas_mhs='.$v['idkelas_mhs'],'idkelas_mhs');
      $result[$k] = $v;
    }
    $this->RepeaterS->DataSource = $result;
    $this->RepeaterS->dataBind();           
  }
  
  public function saveData($sender, $param) {
    if ($this->IsValid) {
      $iddosen = $_SESSION['currentPagePembagianKelas']['iddosen'];
      if ($iddosen == 'none' || $iddosen== '') {
        $this->redirect('perkuliahan.DetailPembagianKelas', true);
      }else{
        $id_pengampu_penyelenggaraan = $this->cmbAddMatakuliah->Text;
        $idkelas = $this->cmbAddKelas->Text;
        $nama_kelas = $this->DB->getMaxOfRecord('nama_kelas',"kelas_mhs WHERE idkelas='$idkelas' AND idpengampu_penyelenggaraan = $id_pengampu_penyelenggaraan")+1;
        $hari = $this->cmbAddHari->Text;
        $jam_masuk=addslashes($this->cmbAddJamMasuk->Text);
        $jam_keluar = addslashes($this->cmbAddJamKeluar->Text);
        $ruangkelas = $this->cmbAddRuang->Text;

        $str = "INSERT INTO kelas_mhs (idkelas_mhs,idkelas,nama_kelas,hari,jam_masuk,jam_keluar,idpengampu_penyelenggaraan,idruangkelas) VALUES (NULL,'$idkelas', $nama_kelas,'$hari', '$jam_masuk', '$jam_keluar', $id_pengampu_penyelenggaraan,'$ruangkelas')";				
        $this->DB->insertRecord($str);
        $this->redirect('perkuliahan.DetailPembagianKelas', true);
      }
    }
  }
  public function editRecord($sender, $param) 
  {
    $this->idProcess = 'edit';
    $idkelas_mhs = $this->getDataKeyField($sender, $this->RepeaterS);
    $this->hiddenid->Value = $idkelas_mhs;
    $str = "SELECT pp.idpenyelenggaraan,km.hari,km.jam_masuk,km.jam_keluar,idruangkelas FROM pengampu_penyelenggaraan pp,kelas_mhs km WHERE pp.idpengampu_penyelenggaraan=km.idpengampu_penyelenggaraan AND km.idkelas_mhs = $idkelas_mhs";
    $this->DB->setFieldTable(array('idpenyelenggaraan', 'hari', 'jam_masuk', 'jam_keluar', 'idruangkelas'));
    $r = $this->DB->getRecord($str);
    
    //load pengampu
    $idpenyelenggaraan = $r[1]['idpenyelenggaraan'];
    $str = "SELECT idpengampu_penyelenggaraan,nidn,nama_dosen FROM v_pengampu_penyelenggaraan WHERE idpenyelenggaraan = $idpenyelenggaraan";
    $this->DB->setFieldTable(array('idpengampu_penyelenggaraan', 'nidn', 'nama_dosen'));
    $data_pengampu = $this->DB->getRecord($str);	
    while (list($k, $v) = each($data_pengampu)) { 
      $idpengampu_penyelenggaraan = $v['idpengampu_penyelenggaraan'];
      $daftar_dosen[$idpengampu_penyelenggaraan] = $v['nama_dosen'] . '['.$v['nidn'].']';
    }
    $this->cmbEditPengampu->DataSource = $daftar_dosen;
    $this->cmbEditPengampu->Text = $r[1]['idpengampu_penyelenggaraan'];
    $this->cmbEditPengampu->dataBind();
    
    //load hari 				
    $this->cmbEditHari->DataSource = $this->TGL->getNamaHari();
    $this->cmbEditHari->Text = $r[1]['hari'];
    $this->cmbEditHari->dataBind();
    
    //load jam
    $jam_masuk_keluar['08:00'] = '08:00';    
    $jam_masuk_keluar['08:50'] = '08:50';
    $jam_masuk_keluar['09:40'] = '09:40';
    $jam_masuk_keluar['10:30'] = '10:30';
    $jam_masuk_keluar['11:10'] = '11:10';
    $jam_masuk_keluar['11:20'] = '11:20';
    $jam_masuk_keluar['12:00'] = '12:00';
    $jam_masuk_keluar['12:10'] = '12:10';
    $jam_masuk_keluar['12:50'] = '12:50';
    $jam_masuk_keluar['13:00'] = '13:00';
    $jam_masuk_keluar['13:40'] = '13:40';
    $jam_masuk_keluar['13:50'] = '13:50';
    $jam_masuk_keluar['14:40'] = '14:40';
    $jam_masuk_keluar['15:30'] = '15:30';
    $jam_masuk_keluar['18:15'] = '18:15';
    $jam_masuk_keluar['18:30'] = '18:30';
    $jam_masuk_keluar['19:10'] = '19:10';
    $jam_masuk_keluar['19:35'] = '19:35';
    $jam_masuk_keluar['19:50'] = '19:50';
    $jam_masuk_keluar['20:15'] = '20:15';
    $jam_masuk_keluar['20:30'] = '20:30';
    $jam_masuk_keluar['20:35'] = '20:35';
    $jam_masuk_keluar['21:10'] = '21:10';
    $jam_masuk_keluar['21:15'] = '21:15';
    $jam_masuk_keluar['21:50'] = '21:50';
    $jam_masuk_keluar['22:15'] = '22:15';
    $jam_masuk_keluar['22:30'] = '22:30';

    $this->cmbEditJamMasuk->DataSource = $jam_masuk_keluar;
    $this->cmbEditJamMasuk->dataBind();
    $this->cmbEditJamMasuk->Text = $r[1]['jam_masuk'];

    $this->cmbEditJamKeluar->DataSource = $jam_masuk_keluar;    
    $this->cmbEditJamKeluar->dataBind();    
    $this->cmbEditJamKeluar->Text = $r[1]['jam_keluar'];
    
    //load kelas 
    $this->cmbEditRuang->DataSource = $this->getLogic('DMaster')->getRuangKelas();
    $this->cmbEditRuang->dataBind();       
    $this->cmbEditRuang->Text = $r[1]['idruangkelas'];
  }
  public function updateData($sender, $param) {
    if ($this->IsValid) {
      $idpengampu_penyelenggaraan = $this->cmbEditPengampu->Text;
      $idkelas_mhs = $this->hiddenid->Value;
      $hari = $this->cmbEditHari->Text;
      $jam_masuk=addslashes($this->cmbEditJamMasuk->Text);
      $jam_keluar = addslashes($this->cmbEditJamKeluar->Text);
      $ruangkelas = $this->cmbEditRuang->Text;
      
      $str = "UPDATE kelas_mhs SET idpengampu_penyelenggaraan = $idpengampu_penyelenggaraan,hari='$hari',jam_masuk='$jam_masuk',jam_keluar='$jam_keluar',idruangkelas='$ruangkelas' WHERE idkelas_mhs = $idkelas_mhs";		
      $this->DB->updateRecord($str);

      $str = "UPDATE kelas_mhs SET synced=0,sync_msg=null WHERE idkelas_mhs = $idkelas_mhs";
      $this->DB->updateRecord($str);

      $this->redirect('perkuliahan.DetailPembagianKelas', true);
    }
  }
  public function deleteRecord($sender, $param) {
    $idkelas_mhs = $this->getDataKeyField($sender, $this->RepeaterS);
    $jumlah_mhs = $this->DB->getCountRowsOfTable("kelas_mhs_detail WHERE idkelas_mhs = $idkelas_mhs",'idkrsmatkul');
    if ($jumlah_mhs > 0) 
    {
      $this->lblContentMessageError->Text = "Tidak bisa menghapus kelas karena masih ada $jumlah_mhs peserta. solusinya pindahkan dulu ke kelas lain";
      $this->modalMessageError->show();
    }
    else 
    {
      $this->DB->deleteRecord("kelas_mhs WHERE idkelas_mhs = $idkelas_mhs");
      $this->redirect('perkuliahan.DetailPembagianKelas', true);
    }
  }
  public function printOut($sender, $param) {		
    $this->createObj('reportakademik');
    $this->linkOutput->Text = '';
    $this->linkOutput->NavigateUrl='#';
    $idkelas_mhs = $this->getDataKeyField($sender, $this->RepeaterS);
    $dataReport = $this->Demik->getInfoKelas($idkelas_mhs);
    switch($_SESSION['outputreport']) {
      case 'summarypdf':
        $messageprintout = "Mohon maaf Print out pada mode summary pdf tidak kami support.";                
      break;
      case 'summaryexcel':
        $messageprintout = "Mohon maaf Print out pada mode summary excel tidak kami support.";                
      break;
      case 'excel2007':               
        $dataReport['namakelas'] = $this->DMaster->getNamaKelasByID($dataReport['idkelas']).'-'.chr($dataReport['nama_kelas'] + 64);
        $dataReport['hari'] = $this->Page->TGL->getNamaHari($dataReport['hari']);
        
        $dataReport['nama_prodi'] = $_SESSION['daftar_jurusan'][$dataReport['kjur']];
        $dataReport['nama_tahun'] = $this->DMaster->getNamaTA($dataReport['tahun']);
        $dataReport['nama_semester'] = $this->setup->getSemester($dataReport['idsmt']);               
        
        $dataReport['linkoutput'] = $this->linkOutput; 
        $this->report->setDataReport($dataReport); 
        $this->report->setMode($_SESSION['outputreport']);  
        
        $messageprintout = "Daftar Hadir Mahasiswa : <br/>";
        $this->report->printDaftarHadirMahasiswa();
      break;
      case 'pdf':
        $messageprintout = "Mohon maaf Print out pada mode excel pdf belum kami support.";
      break;
    }                
    $this->lblMessagePrintout->Text = $messageprintout;
    $this->lblPrintout->Text = 'Daftar Hadir Mahasiswa';
    $this->modalPrintOut->show();
  }
}