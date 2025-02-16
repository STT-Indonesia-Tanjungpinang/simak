<?php
prado::using ('Application.pagecontroller.mh.pembayaran.CPembayaranCutiSemesterGanjil');
class PembayaranCutiSemesterGanjil Extends CPembayaranCutiSemesterGanjil {
	public function onLoad($param) {
		parent::onLoad($param);
	}
	public function checkPembayaranSemesterLalu() { 
        $datamhs = $this->Pengguna->getDataUser();
		$tahun_masuk = $datamhs['tahun_masuk'];
		$semester_masuk = $datamhs['semester_masuk'];
		$ta=$_SESSION['currentPagePembayaranCutiSemesterGanjil']['ta'];			
		if ($tahun_masuk == $ta && $semester_masuk==1) {						
			return true;
		}else{								
			$ta=($ta == $tahun_masuk)?$tahun_masuk:$ta-1;																		
			$this->Finance->setDataMHS(array('no_formulir' => $datamhs['no_formulir']));
			$idkelas = $this->Finance->getKelasFromTransaksi($ta,2);
			$datamhs['idkelas'] = $idkelas===false?$datamhs['idkelas']:$idkelas;            
			if ($idkelas!='C') {				
				$this->Finance->setDataMHS(array('no_formulir' => $datamhs['no_formulir'],'nim' => $datamhs['nim'],'idkelas' => $datamhs['idkelas'],'tahun_masuk' => $tahun_masuk,'idsmt' =>2,'perpanjang' => $datamhs['perpanjang']));
			 	$totalbiaya=($tahun_masuk==$ta&&$semester_masuk==1)?$this->Finance->getTotalBiayaMhsPeriodePembayaran ():$this->Finance->getTotalBiayaMhsPeriodePembayaran ('lama');				
				$this->Finance->setDataMHS($datamhs);
				$totalbayar = $this->Finance->getTotalBayarMhs($ta,2);				
                $sisa=$totalbiaya-$totalbayar;                
                $datadulang = $this->Finance->getDataDulang(2, $ta);
                if ($sisa>0 && $datadulang['k_status'] != 'C') {
					$sisa=$this->Finance->toRupiah($sisa);
					$tasmt="T.A ".$this->DMaster->getNamaTA($ta).' semester '.$this->setup->getSemester(2);
					throw new Exception ('Mahasiswa a.n '.$datamhs['nama_mhs']." Memiliki tunggakan sebesar ($sisa) pada $tasmt, harap untuk dilunasi terlebih dahulu.");
				}
			}
		}		
	}
}