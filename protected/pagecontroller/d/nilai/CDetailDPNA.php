<?php
prado::using ('Application.MainPageD');
class CDetailDPNA extends MainPageD {    
   	public function onLoad($param) {
		parent::onLoad($param);							
		$this->showSubMenuAkademikNilai=true;
        $this->showDPNA=true;    
        $this->createObj('Akademik');        
        $this->createObj('Nilai');        
        
		if (!$this->IsPostback&&!$this->IsCallback) {
            $this->tbCmbOutputReport->DataSource=$this->setup->getOutputFileType();
            $this->tbCmbOutputReport->Text= $_SESSION['outputreport'];
            $this->tbCmbOutputReport->DataBind();            
            try {
                $idkelas_mhs=addslashes($this->request['id']);
                $this->Demik->getInfoKelas($idkelas_mhs);                
                if (!isset($this->Demik->InfoKelas['idkelas_mhs'])) {                                                
                    throw new Exception ("Kelas Mahasiswa dengan id ($idkelas_mhs) tidak terdaftar.");		
                }     
                $infokelas=$this->Demik->InfoKelas;
                $this->Demik->InfoKelas['nama_ps']=$_SESSION['daftar_jurusan'][$infokelas['kjur']];
                $this->Demik->InfoKelas['ta']=$this->DMaster->getNamaTA($infokelas['tahun']);
                $this->Demik->InfoKelas['nama_semester']=$this->setup->getSemester($infokelas['idsmt']);                
                $this->Demik->InfoKelas['namakelas']=$this->DMaster->getNamaKelasByID($infokelas['idkelas']).'-'.chr($infokelas['nama_kelas']+64);
                $this->Demik->InfoKelas['hari']=$this->TGL->getNamaHari($infokelas['hari']);

                $str = "SELECT d.nidn AS nidn_dosen_pengajar,CONCAT(d.gelar_depan,' ',d.nama_dosen,' ',d.gelar_belakang) AS nama_dosen_pengajar,d.idjabatan AS idjabatan_dosen_pengajar FROM kelas_mhs km,pengampu_penyelenggaraan pp,dosen d WHERE km.idpengampu_penyelenggaraan=pp.idpengampu_penyelenggaraan AND d.iddosen=pp.iddosen AND idkelas_mhs=$idkelas_mhs";
                $this->DB->setFieldTable(array('nidn_dosen_pengajar','nama_dosen_pengajar','idjabatan_dosen_pengajar'));
                $r=$this->DB->getRecord($str);
                $datakelas=$r[1];
                $this->Demik->InfoKelas['nidn_dosen_pengajar']=$datakelas['nidn_dosen_pengajar'];
                $this->Demik->InfoKelas['nama_dosen_pengajar']=$datakelas['nama_dosen_pengajar'];
                $this->Demik->InfoKelas['idjabatan_dosen_pengajar']=$datakelas['idjabatan_dosen_pengajar'];                            
                $this->Demik->InfoKelas['nama_jabatan_dosen_pengajar']=$this->DMaster->getNamaJabfungByID($datakelas['idjabatan_dosen_pengajar']);
                
                $_SESSION['currentPageDPNA']['DataDPNA']=$this->Demik->InfoKelas;
                $this->populateData();	             
            } catch (Exception $ex) {
                $this->idProcess='view';	
                $this->errorMessage->Text=$ex->getMessage();
            }
		}
	}    
	protected function populateData() {	
        $idkelas_mhs=$_SESSION['currentPageDPNA']['DataDPNA']['idkelas_mhs'];
        $str = "SELECT vdm.nim,vdm.nirm,vdm.nama_mhs,vdm.jk,n.n_kuan,n.n_kual FROM kelas_mhs_detail kmd LEFT JOIN nilai_matakuliah n ON (n.idkrsmatkul=kmd.idkrsmatkul) JOIN v_krsmhs vkm ON (vkm.idkrsmatkul=kmd.idkrsmatkul) JOIN v_datamhs vdm ON (vkm.nim=vdm.nim) WHERE  kmd.idkelas_mhs=$idkelas_mhs AND vkm.sah=1 AND vkm.batal=0 ORDER BY vdm.nama_mhs ASC";        
        $this->DB->setFieldTable(array('nim','nirm','nama_mhs','jk','n_kuan','n_kual'));
        $r=$this->DB->getRecord($str);	           
        $result=array();
        $sks=$this->Demik->InfoKelas['sks'];
        while (list($k,$v)=each($r)) {
            $n_kuan='-';
            $n_kual='-';
            $am='-';
            $hm='-';
            if ($v['n_kual']!= '') {
                $n_kuan=$v['n_kuan'];
                $n_kual=$v['n_kual'];
                $am=$this->Nilai->getAngkaMutu($v['n_kual']);
                $hm=$am*$sks;
            }
            $v['n_kuan']=$n_kuan;
            $v['n_kual']=$n_kual;
            $v['am']=$am;
            $v['hm']=$hm;
            $result[$k]=$v;
        }
        $this->RepeaterS->DataSource=$result;
        $this->RepeaterS->dataBind();	                
	}
	public function printOut ($sender,$param) {	    
        $this->Demik->InfoKelas=$_SESSION['currentPageDPNA']['DataDPNA'];
        $dataReport=$_SESSION['currentPageDPNA']['DataDPNA'];             
        $this->createObj('reportnilai');
        $this->linkOutput->Text='';
        $this->linkOutput->NavigateUrl='#';
        switch ($_SESSION['outputreport']) {
            case  'summarypdf' :
                $messageprintout="Mohon maaf Print out pada mode summary pdf tidak kami support.";                
            break;
            case  'summaryexcel' :
                $messageprintout="Mohon maaf Print out pada mode summary excel tidak kami support.";                
            break;
            case 'pdf' :
                       
                $nama_matakuliah=$dataReport['nmatkul'];
                
                $messageprintout="Matakuliah $nama_matakuliah";
                $dataReport['nama_pt_alias']=$this->setup->getSettingValue('nama_pt_alias');
                $dataReport['nama_jabatan_dpna']=$this->setup->getSettingValue('nama_jabatan_dpna');
                $dataReport['nama_penandatangan_dpna']=$this->setup->getSettingValue('nama_penandatangan_dpna');
                $dataReport['jabfung_penandatangan_dpna']=$this->setup->getSettingValue('jabfung_penandatangan_dpna');
                $dataReport['nidn_penandatangan_dpna']=$this->setup->getSettingValue('nidn_penandatangan_dpna');

                $dataReport['linkoutput']=$this->linkOutput; 
                $this->report->setDataReport($dataReport); 
                $this->report->setMode($_SESSION['outputreport']);

                $this->report->printDPNA($this->Nilai,true);
                
            break;
            case  'excel2007' :
                $nama_matakuliah=$dataReport['nmatkul'];
                
                $messageprintout="Matakuliah $nama_matakuliah";

                $dataReport['nama_pt_alias']=$this->setup->getSettingValue('nama_pt_alias');
                $dataReport['nama_jabatan_dpna']=$this->setup->getSettingValue('nama_jabatan_dpna');
                $dataReport['nama_penandatangan_dpna']=$this->setup->getSettingValue('nama_penandatangan_dpna');
                $dataReport['jabfung_penandatangan_dpna']=$this->setup->getSettingValue('jabfung_penandatangan_dpna');
                $dataReport['nidn_penandatangan_dpna']=$this->setup->getSettingValue('nidn_penandatangan_dpna');

                $dataReport['linkoutput']=$this->linkOutput; 
                $this->report->setDataReport($dataReport); 
                $this->report->setMode($_SESSION['outputreport']);

                $this->report->printDPNA($this->Nilai,true);
            break;
        }        
        $this->lblMessagePrintout->Text=$messageprintout;
        $this->lblPrintout->Text='Daftar Peserta dan Nilai Akhir';
        $this->modalPrintOut->show();
	}
}