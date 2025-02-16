<?php
prado::using ('Application.MainPageDW');
class CDetailTranskripKRS extends MainPageDW {		
	public function onLoad($param) {
		parent::onLoad($param);							
		$this->showSubMenuAkademikNilai = true;
        $this->showTranskripKRS=true;    
        $this->createObj('Nilai');
        
		if (!$this->IsPostBack && !$this->IsCallback) {
            if (!isset($_SESSION['currentPageDetailTranskripKRS']) || $_SESSION['currentPageDetailTranskripKRS']['page_name'] != 'dw.nilai.DetailTranskripKRS') {
				$_SESSION['currentPageDetailTranskripKRS'] = array('page_name' => 'dw.nilai.DetailTranskripKRS', 'page_num' => 0, 'search' => false,'DataMHS' =>array());												                                               
			}  
            $this->tbCmbOutputReport->DataSource = $this->setup->getOutputFileType();
            $this->tbCmbOutputReport->Text= $_SESSION['outputreport'];
            $this->tbCmbOutputReport->DataBind();
			$this->populateData();	
		}
	}
    public function getDataMHS($idx) {		        
        return $this->Nilai->getDataMHS($idx);
    }
	protected function populateData() {		
        try {
            $nim = addslashes($this->request['id']);            				
            $str = "SELECT vdm.no_formulir,vdm.nim,vdm.nirm,vdm.nama_mhs,vdm.jk,vdm.tempat_lahir,vdm.tanggal_lahir,vdm.kjur,vdm.nama_ps,vdm.idkonsentrasi,k.nama_konsentrasi,vdm.tahun_masuk,iddosen_wali,idkelas,k_status FROM v_datamhs vdm LEFT JOIN konsentrasi k ON (vdm.idkonsentrasi=k.idkonsentrasi) WHERE nim='$nim'";
            $this->DB->setFieldTable(array('no_formulir', 'nim', 'nirm', 'nama_mhs', 'jk', 'tempat_lahir', 'tanggal_lahir', 'kjur', 'nama_ps', 'idkonsentrasi', 'nama_konsentrasi', 'tahun_masuk', 'iddosen_wali', 'idkelas', 'k_status'));
            $r = $this->DB->getRecord($str);				
            
            if (!isset($r[1])) {
                $_SESSION['currentPageDetailTranskripKRS']['DataMHS'] = array();
                throw new Exception("Mahasiswa dengan NIM ($nim) tidak terdaftar.");
            }
            $datamhs = $r[1];
            if ($datamhs['iddosen_wali']!=$this->iddosen_wali){
                $_SESSION['currentPageDetailTranskripKRS']['DataMHS'] = array();
                throw new Exception("Mahasiswa dengan NIM ($nim) dimiliki oleh Mahasiswa diluar perwalian Anda.");
            }
            $datamhs['nama_dosen'] = $this->DMaster->getNamaDosenWaliByID ($datamhs['iddosen_wali']);
            $datamhs['nkelas'] = $this->DMaster->getNamaKelasByID($datamhs['idkelas']);
            $datamhs['nama_konsentrasi']=($datamhs['idkonsentrasi'] == 0) ? '-' : $datamhs['nama_konsentrasi'];                    
            $datamhs['status'] = $this->DMaster->getNamaStatusMHSByID($datamhs['k_status']);
            $datamhs['iddata_konversi'] = $this->Nilai->isMhsPindahan($nim, true);
            $_SESSION['currentPageDetailTranskripKRS']['DataMHS'] = $datamhs;
            $this->Nilai->setDataMHS($datamhs);
            $transkrip = $this->Nilai->getTranskripFromKRS();            
            
            $this->RepeaterS->DataSource = $transkrip;
            $this->RepeaterS->dataBind();		
        } catch (Exception $ex) {
            $this->idProcess = 'view';	
			$this->errorMessage->Text = $ex->getMessage();
        }        
	}
	public function printOut($sender, $param) {	
        $this->createObj('reportnilai');          
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
                $messageprintout='Transkrip Sementara : ';
                $dataReport = $_SESSION['currentPageDetailTranskripKRS']['DataMHS'];  
                $dataReport['nama_pt_alias'] = $this->setup->getSettingValue('nama_pt_alias');
                $dataReport['nama_jabatan_transkrip'] = $this->setup->getSettingValue('nama_jabatan_transkrip');
                $dataReport['nama_penandatangan_transkrip'] = $this->setup->getSettingValue('nama_penandatangan_transkrip');
                $dataReport['jabfung_penandatangan_transkrip'] = $this->setup->getSettingValue('jabfung_penandatangan_transkrip');
                $dataReport['nipy_penandatangan_transkrip'] = $this->setup->getSettingValue('nipy_penandatangan_transkrip');

                $dataReport['linkoutput'] = $this->linkOutput; 
                $this->report->setDataReport($dataReport); 
                $this->report->setMode($_SESSION['outputreport']);
                $this->report->printTranskripKRS($this->Nilai,true);				
            break;
        }
        $this->lblMessagePrintout->Text = $messageprintout;
        $this->lblPrintout->Text = 'Transkrip KRS';
        $this->modalPrintOut->show();
	}
}