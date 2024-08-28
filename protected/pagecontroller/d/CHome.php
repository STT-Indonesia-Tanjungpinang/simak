<?php
prado::using ('Application.MainPageD');
class CHome extends MainPageD {
	public function onLoad($param) {		
		parent::onLoad($param);		            
		$this->showDashboard=true;      
		$this->createObj('Nilai');         
		if (!$this->IsPostBack&&!$this->IsCallBack) {   
			$this->populateData();
		}                
	}
	public function populateData() {
        $iddosen=$this->Pengguna->getDataUser('iddosen');		
        $str = "SELECT vpp.idpengampu_penyelenggaraan,km.idkelas_mhs,km.idkelas,km.nama_kelas,km.hari,km.jam_masuk,km.jam_keluar,vpp.kmatkul,vpp.nmatkul,vpp.sks,rk.namaruang,rk.kapasitas,vpp.tahun,vpp.idsmt FROM kelas_mhs km JOIN v_pengampu_penyelenggaraan vpp ON (km.idpengampu_penyelenggaraan=vpp.idpengampu_penyelenggaraan) LEFT JOIN ruangkelas rk ON (rk.idruangkelas=km.idruangkelas) WHERE vpp.iddosen=$iddosen AND tahun >= (tahun-3) ORDER BY hari ASC,idkelas ASC";
       			
		$this->DB->setFieldTable(array('idpengampu_penyelenggaraan','idkelas_mhs','kmatkul','nmatkul','sks','idkelas','nama_kelas','hari','jam_masuk','jam_keluar','namaruang','kapasitas','tahun','idsmt'));			
		$r=$this->DB->getRecord($str);	
        $result=array();
        while (list($k,$v)=each($r)) {            
            $v['namakelas']=$this->DMaster->getNamaKelasByID($v['idkelas']).'-'.chr($v['nama_kelas']+64);
            $v['jumlah_peserta_kelas']=$this->DB->getCountRowsOfTable('kelas_mhs_detail WHERE idkelas_mhs='.$v['idkelas_mhs'],'idkelas_mhs');
            $result[$k]=$v;
        }      
		$this->RepeaterS->DataSource=$result;
		$this->RepeaterS->dataBind();
        
	}
}