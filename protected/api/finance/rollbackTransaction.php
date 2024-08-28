<?php
prado::using('Application.api.BaseWS');
class rollbackTransaction extends BaseWS {	
	public function getJsonContent() {		
		try {
			$this->validate ();
			$data=$_POST;
			if (!isset($data['no_transaksi'])) {
				$this->payload['status']='30';
				throw new Exception ("Proses Login telah berhasil, namun ada error yaitu data POST no_transaksi tidak ada !!!");
			}
			if (empty($data['no_transaksi'])) {
				$this->payload['status']='30';
				throw new Exception ("Proses Login telah berhasil, namun ada error yaitu data no_transaksi kosong !!!");
			}
			$no_transaksi=addslashes($data['no_transaksi']);
			$userid=$this->Pengguna->getDataUser('userid');
			$tipe_transaksi=substr($no_transaksi, 0,2);
			switch ($tipe_transaksi) {
				case 10: //bayar biasa
					$str = "SELECT t.no_transaksi,t.kjur,t.no_formulir,fp.nama_mhs,t.nim,t.tahun,t.idsmt,t.idkelas,rm.k_status,rm.perpanjang,fp.ta AS tahun_masuk,fp.idsmt AS semester_masuk,t.commited FROM transaksi t LEFT JOIN formulir_pendaftaran fp ON (fp.no_formulir=t.no_formulir) LEFT JOIN register_mahasiswa rm ON (t.no_formulir=rm.no_formulir) WHERE t.no_transaksi='$no_transaksi'";
					$this->DB->setFieldTable(array('no_transaksi','kjur','no_formulir','nama_mhs','nim','tahun','idsmt','idkelas','k_status','perpanjang','commited','tahun_masuk','semester_masuk'));		
					$r=$this->DB->getRecord($str);
					if (!isset($r[1])) {
						$this->payload['status']='04';
						throw new Exception ("Proses Login telah berhasil, namun transaksi dengan nomor ($no_transaksi) tidak ada di database !!!");				
					}

					$result=$r[1];		
					$this->DB->query('BEGIN');
					$this->DB->updateRecord("UPDATE transaksi SET commited=0,date_modified=NOW() WHERE no_transaksi='$no_transaksi'");
					if ($result['nama_mhs'] == '' && $result['nim'] == '' && $result['kjur'] == 0) {
						$str = "UPDATE transaksi_api SET commited=0,date_modified=NOW() WHERE no_transaksi='$no_transaksi'";
						$this->DB->updateRecord($str);
						$this->DB->query('COMMIT'); //biaya pendaftaran
					}elseif ($result['nim'] == '') { 
						$str = "UPDATE transaksi_api SET commited=0,date_modified=NOW() WHERE no_transaksi='$no_transaksi'";
						$this->DB->updateRecord($str);
						$this->DB->query('COMMIT'); //pembayaran mahasiswa baru
					}else{						
			            $str = "UPDATE transaksi_api SET commited=0,date_modified=NOW() WHERE no_transaksi='$no_transaksi'";
						$this->DB->updateRecord($str);
			            $this->DB->query('COMMIT'); 
					}
					$this->payload['status']='88';
					$this->payload['message']="Proses Login telah berhasil, data transaksi dengan nomor ($no_transaksi) berhasil di di Rollback !!!. ";		
				break;
				case 11: //bayar cuti
					$str = "SELECT t.no_transaksi,rm.kjur,rm.no_formulir,fp.nama_mhs,t.nim,t.tahun,t.idsmt,rm.idkelas,rm.k_status,rm.perpanjang,fp.ta AS tahun_masuk,fp.idsmt AS semester_masuk,t.commited FROM transaksi_cuti t LEFT JOIN register_mahasiswa rm ON (t.nim=rm.nim) LEFT JOIN formulir_pendaftaran fp ON (fp.no_formulir=rm.no_formulir) WHERE t.no_transaksi='$no_transaksi'";
					$this->DB->setFieldTable(array('no_transaksi','kjur','no_formulir','nama_mhs','nim','tahun','idsmt','idkelas','k_status','perpanjang','commited','tahun_masuk','semester_masuk'));		
					$r=$this->DB->getRecord($str);
					if (!isset($r[1])) {
						$this->payload['status']='04';
						throw new Exception ("Proses Login telah berhasil, namun transaksi dengan nomor ($no_transaksi) tidak ada di database !!!");				
					}
					$result=$r[1];
					
		            $str = "UPDATE transaksi_cuti SET commited=0,date_modified=NOW() WHERE no_transaksi=$no_transaksi";
		            $this->DB->updateRecord($str);
		            
		            $str = "UPDATE transaksi_api SET commited=0,date_modified=NOW() WHERE no_transaksi='$no_transaksi'";
					$this->DB->updateRecord($str);

		            $this->DB->query('COMMIT');

					$this->payload['status']='88';
					$this->payload['message']="Proses Login telah berhasil, data transaksi CUTI dengan nomor ($no_transaksi) berhasil di di Rollback !!!. ";		

				break;
				default :
					$this->payload['status']='30';
					throw new Exception ("Proses Login telah berhasil, namun ada error yaitu tipe_transaksi tidak dikenal. !!!");
			}
		}catch (Exception $e) {
			$this->payload['message'] = $e->getMessage();
		}	
		$this->Pengguna->insertNewActivity ('json=rollback_transaction',"melakukan request api terhadap method rollback_transaction, outputnya: ".$this->payload['message']);
		return $this->payload;
	}
}