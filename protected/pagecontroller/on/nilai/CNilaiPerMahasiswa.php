<?php
prado::using ('Application.MainPageON');
class CNilaiPerMahasiswa extends MainPageON {	
  public $pnlInputNim = false;
  public $pnlInputPenyelenggaraan = false;
  public function onLoad($param) {
    parent::onLoad($param);	
    $this->createObj('Nilai');
    $this->showSubMenuAkademikNilai = true;
    $this->showNilaiPerMahasiswa=true;
    if (!$this->IsPostBack && !$this->IsCallback)
    {
      if (!isset($_SESSION['currentPageNilaiPerMahasiswa']) || $_SESSION['currentPageNilaiPerMahasiswa']['page_name'] != 'on.nilai.NilaiPerMahasiswa')
      {
        $_SESSION['currentPageNilaiPerMahasiswa'] = array('page_name' => 'on.nilai.NilaiPerMahasiswa', 'DataMHS' =>array(), 'semester' => $_SESSION['semester'],'ta' => $_SESSION['ta']);												
      }
      $nim = addslashes($this->request['id']);       
      try 
      {     
        $str = "SELECT vdm.no_formulir,vdm.nim,vdm.nirm,vdm.nama_mhs,vdm.jk,vdm.tempat_lahir,vdm.tanggal_lahir,vdm.kjur,vdm.nama_ps,vdm.idkonsentrasi,k.nama_konsentrasi,vdm.tahun_masuk,iddosen_wali,vdm.k_status,sm.n_status AS status,vdm.idkelas,ke.nkelas,photo_profile FROM v_datamhs vdm LEFT JOIN konsentrasi k ON (vdm.idkonsentrasi=k.idkonsentrasi) LEFT JOIN status_mhs sm ON (vdm.k_status=sm.k_status) LEFT JOIN kelas ke ON (vdm.idkelas=ke.idkelas) WHERE vdm.nim='$nim'";
        $this->DB->setFieldTable(array('no_formulir', 'nim', 'nirm', 'nama_mhs', 'jk', 'tempat_lahir', 'tanggal_lahir', 'kjur', 'nama_ps', 'idkonsentrasi', 'nama_konsentrasi', 'tahun_masuk', 'iddosen_wali', 'k_status', 'status', 'idkelas', 'nkelas', 'photo_profile'));
        $r = $this->DB->getRecord($str);	           
        if (!isset($r[1]))
        {
          unset($_SESSION['currentPageNilaiPerMahasiswa']);
          throw new Exception ("<div class=\"alert alert-danger alert-styled-left alert-bordered\">
            <span class=\"text-semibold\">Peringatan!</span>
            Mahasiswa Dengan NIM ($nim) tidak terdaftar di Portal
          </div>");
        }
        $datamhs = $r[1];
        $this->Nilai->setDatamHS($datamhs);
        $datamhs['iddata_konversi'] = $this->Nilai->isMhsPindahan($nim, true);
        
        $kelas = $this->Nilai->getKelasMhs();                
        $datamhs['nkelas']=($kelas['nkelas']== '') ? 'Belum ada':$kelas['nkelas'];			                    
        $datamhs['nama_konsentrasi']=($datamhs['idkonsentrasi'] == 0) ? '-' : $datamhs['nama_konsentrasi'];

        $nama_dosen = $this->DMaster->getNamaDosenWaliByID($datamhs['iddosen_wali']);				                    
        $datamhs['nama_dosen'] = $nama_dosen;                
        
        $this->Nilai->setDatamHS($datamhs);
        $_SESSION['currentPageNilaiPerMahasiswa']['DataMHS'] = $datamhs;
        
        $ta = $_SESSION['currentPageNilaiPerMahasiswa']['ta'];
        $this->tbCmbTA->DataSource = $this->DMaster->removeIdFromArray($this->DMaster->getListTA($this->Pengguna->getDataUser('tahun_masuk')), 'none');
        $this->tbCmbTA->Text = $ta;
        $this->tbCmbTA->dataBind();			
        
        $idsmt = $_SESSION['currentPageNilaiPerMahasiswa']['semester'];
        $semester = $this->DMaster->removeIdFromArray($this->setup->getSemester(), 'none');  				
        $this->tbCmbSemester->DataSource = $semester;
        $this->tbCmbSemester->Text = $idsmt;
        $this->tbCmbSemester->dataBind();
        
        $this->setInfoToolbar();
        
        $this->populateData();
        
      }
      catch (Exception $ex)
      {
        $this->idProcess = 'view';
        $this->errormessage->Text=  empty($nim) ? '': $ex->getMessage();
      }
    }
  }	
  public function setInfoToolbar() {                
    $ta = $this->DMaster->getNamaTA($_SESSION['currentPageNilaiPerMahasiswa']['ta']);		
    $semester = $this->setup->getSemester($_SESSION['currentPageNilaiPerMahasiswa']['semester']);		
    $this->lblModulHeader->Text = "T.A $ta Semester $semester";        
  }
  public function cekNIM($sender, $param) {		
    $nim = addslashes($param->Value);		
    if ($nim != '') 
    {
      try 
      {
        $str = "SELECT nim,kjur FROM register_mahasiswa WHERE nim='$nim'";
        $this->DB->setFieldTable(array('nim', 'kjur'));
        $r = $this->DB->getRecord($str);
        if (!isset($r[1])) {                                   
          throw new Exception ("NIM ($nim) tidak terdaftar di Portal, silahkan ganti dengan yang lain.");		
        }   
        $kjur = $this->Pengguna->getDataUser('kjur');
        if ($kjur > 0) {
          $kjur_mhs = $r[1]['kjur'];
          if ($kjur != $kjur_mhs){
            throw new Exception ("Anda tidak berhak mengakses data mahasiswa dengan NIM ($nim).");		
          } 
        }
      }catch (Exception $e) {
        $param->IsValid = false;
        $sender->ErrorMessage = $e->getMessage();
      }	
    }	
  }
  public function GO($sender, $param){
    if ($this->IsValid) {
      $nim = addslashes($this->txtAddNIM->Text);
      $this->redirect('nilai.NilaiPerMahasiswa', true, array('id' => $nim));
    }
  }
  public function getDataMHS($idx) {		        
    return $this->Nilai->getDataMHS($idx);
  }
  public function changeTbTA($sender, $param) {
    $_SESSION['currentPageNilaiPerMahasiswa']['ta'] = $this->tbCmbTA->Text;	
    $nim = $_SESSION['currentPageNilaiPerMahasiswa']['DataMHS']['nim'];
    $this->redirect('nilai.NilaiPerMahasiswa', true, array('id' => $nim));        
  }	
  public function changeTbSemester($sender, $param) {
    $_SESSION['currentPageNilaiPerMahasiswa']['semester'] = $this->tbCmbSemester->Text;		
    $nim = $_SESSION['currentPageNilaiPerMahasiswa']['DataMHS']['nim'];
    $this->redirect('nilai.NilaiPerMahasiswa', true, array('id' => $nim));
  }
  
  public function populateData() {
    $ta = $_SESSION['currentPageNilaiPerMahasiswa']['ta'];
    $idsmt = $_SESSION['currentPageNilaiPerMahasiswa']['semester'];
    $nim = $_SESSION['currentPageNilaiPerMahasiswa']['DataMHS']['nim'];
    
    $str = "SELECT vkm.idkrsmatkul,nm.idnilai,vkm.kmatkul,vkm.nmatkul,vkm.sks,vkm.nidn,vkm.nama_dosen,nm.n_kuan,nm.n_kual,nm.userid_input,nm.tanggal_input,nm.tanggal_modif,nm.bydosen,nm.ket,vkm.batal,vkm.sah FROM v_krsmhs vkm LEFT JOIN nilai_matakuliah nm ON vkm.idkrsmatkul=nm.idkrsmatkul WHERE nim='$nim' AND tahun='$ta' AND idsmt='$idsmt' AND sah=1";
    $this->DB->setFieldTable(array('idkrsmatkul', 'idnilai', 'kmatkul', 'nmatkul', 'sks', 'nidn', 'nama_dosen', 'n_kuan', 'n_kual', 'userid_input', 'tanggal_input', 'tanggal_modif', 'bydosen', 'ket', 'batal', 'sah'));	
    
    $r = $this->DB->getRecord($str);
    $result = array();       
    while (list($k, $v) = each($r)) {
      $v['kmatkul'] = $this->Nilai->getKMatkul($v['kmatkul']);
      if ($v['userid_input'] == 0) {
        $v['tanggal_input'] = '-';
        $v['tanggal_modif'] = '-';
      }else{
        $v['tanggal_input'] = $this->TGL->tanggal ('j F Y', $v['tanggal_input']);
        $v['tanggal_modif'] = $this->TGL->tanggal ('j F Y', $v['tanggal_modif']);
      }
      $result[$k] = $v;
    }
    $this->RepeaterS->DataSource = $result;
    $this->RepeaterS->dataBind();	
  }
  public function setData($sender, $param) {
    $item = $param->Item;	
    if ($item->ItemType === 'Item' || $item->ItemType === 'AlternatingItem') 
    {					
      if ($item->DataItem['batal'])
      {
        $item->literalKet->Text = 'Dibatalkan oleh dosen wali.';	
        $item->txtNilaiAngka->Enabled = false;
        $item->cmbNilai->Enabled = false;		
      }
      else 
      {
        $item->literalKet->Text = '-';
        $item->txtNilaiAngka->Text = $item->DataItem['n_kuan'];
        $item->cmbNilai->Text = $item->DataItem['n_kual'];
        $item->nilai_sebelumnya->Value = $item->DataItem['n_kual'];
        $bool = $item->DataItem['bydosen'] == true ? true : true;
        $item->txtNilaiAngka->Enabled = $bool;
        $item->cmbNilai->Enabled = $bool;
      }				
    }
  }
  public function saveData($sender, $param) 
  {
    if ($this->IsValid)
    {
      $this->createObj('log');
      $repeater = $this->RepeaterS;            	
      $userid = $this->Pengguna->getDataUser('userid');	
      $nim = $_SESSION['currentPageNilaiPerMahasiswa']['DataMHS']['nim'];	
      foreach ($repeater->Items as $inputan)
      {	
        if ($inputan->cmbNilai->Enabled) 
        {
          $idkrsmatkul = $inputan->idkrsmatkul->Value;		
          $infomatkul = $this->Nilai->getInfoMatkul($idkrsmatkul, 'krsmatkul');
          $kmatkul = $infomatkul['kmatkul'];
          $nmatkul = $infomatkul['nmatkul'];
          $n_kuan = addslashes($inputan->txtNilaiAngka->Text) > 0 ? $inputan->txtNilaiAngka->Text : 0;
          $n_kual = $inputan->cmbNilai->Text;				
          $nilai_sebelumnya = $inputan->nilai_sebelumnya->Value;				
          if ($nilai_sebelumnya == '' && $n_kual != 'none') 
          {//insert					
            if (!$this->DB->checkRecordIsExist('idkrsmatkul', 'nilai_matakuliah', $idkrsmatkul)) 
            {
              $str = "INSERT INTO nilai_matakuliah SET idnilai=NULL,idkrsmatkul = $idkrsmatkul,persentase_quiz=0,persentase_tugas=0,persentase_uts=0,persentase_uas=0,persentase_absen=0,nilai_quiz=0,nilai_tugas=0,nilai_uts=0,nilai_uas=0,nilai_absen=0,n_kuan = $n_kuan,n_kual='$n_kual',userid_input=$userid,userid_modif=$userid,tanggal_input=NOW(),tanggal_modif=NOW(),bydosen=0,ket='',telah_isi_kuesioner=0,tanggal_isi_kuesioner=CURDATE()";				
              $this->DB->insertRecord($str);

              $extra = "idkrsmatkul=$idkrsmatkul";
              $this->Log->insertLogIntoNilaiMatakuliah($nim, $kmatkul, $nmatkul, 'input', $n_kual, $nilai_sebelumnya, $extra);
            }
          }
          else if($n_kual != 'none' && $n_kual != $nilai_sebelumnya)
          {//update										
            $str = "UPDATE nilai_matakuliah SET n_kuan='$n_kuan',n_kual='$n_kual',userid_modif='$userid',tanggal_modif=NOW(),ket='dari $nilai_sebelumnya menjadi $n_kual' WHERE idkrsmatkul='$idkrsmatkul'";				
            $this->DB->updateRecord($str);
            
            $extra = "idkrsmatkul=$idkrsmatkul";
            $this->Log->insertLogIntoNilaiMatakuliah($nim, $kmatkul, $nmatkul, 'update', $n_kual, $nilai_sebelumnya, $extra);
          }
          else if($nilai_sebelumnya != '' && $n_kual == 'none')
          {//delete
            $str = "nilai_matakuliah WHERE idkrsmatkul='$idkrsmatkul'";	
            $this->DB->deleteRecord($str);

            $extra = "idkrsmatkul=$idkrsmatkul";
            $this->Log->insertLogIntoNilaiMatakuliah($nim, $kmatkul, $nmatkul, 'delete', $n_kual, $nilai_sebelumnya, $extra);
          }				
        }
      }
      $nim = $_SESSION['currentPageNilaiPerMahasiswa']['DataMHS']['nim'];
      $this->redirect('nilai.NilaiPerMahasiswa', true, array('id' => $nim));
    }		
  }	
  public function closeDetail($sender, $param) {
    unset($_SESSION['currentPageNilaiFinal']['DataMHS']);
    $this->redirect('nilai.NilaiPerMahasiswa', true);
  }
}