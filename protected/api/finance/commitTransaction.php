<?php
prado::using('Application.api.BaseWS');
class commitTransaction extends BaseWS {	
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
					if ($result['commited']) {
						$this->payload['status']='88';
						throw new Exception ("Proses Login telah berhasil, namun data transaksi dengan nomor ($no_transaksi) telah di Commit !!!. Bisa di rollback, beritahu ke bagian keuangan dengan menyebutkan nomor Transaksi ini ($no_transaksi)");
					}
					$this->createObj('Finance');
					$total_tagihan=$this->Finance->getTotalTagihanByNoTransaksi($no_transaksi);
					$this->DB->query('BEGIN');
					$this->DB->updateRecord("UPDATE transaksi SET commited=1,date_modified=NOW() WHERE no_transaksi='$no_transaksi'");
					if ($result['nama_mhs'] == '' && $result['nim'] == '' && $result['kjur'] == 0) {
						$str = "INSERT INTO transaksi_api (no_transaksi,no_faktur,kjur,tahun,idsmt,idkelas,no_formulir,nim,commited,tanggal,userid,total,date_added,date_modified) SELECT no_transaksi,no_faktur,kjur,tahun,idsmt,idkelas,no_formulir,nim,commited,tanggal,$userid,$total_tagihan,NOW(),NOW() FROM transaksi WHERE no_transaksi='$no_transaksi'";
						$this->DB->insertRecord($str);
						$this->DB->query('COMMIT'); //biaya pendaftaran
					}elseif ($result['nim'] == '') { 
						$str = "INSERT INTO transaksi_api (no_transaksi,no_faktur,kjur,tahun,idsmt,idkelas,no_formulir,nim,commited,tanggal,userid,total,date_added,date_modified) SELECT no_transaksi,no_faktur,kjur,tahun,idsmt,idkelas,no_formulir,nim,commited,tanggal,$userid,$total_tagihan,NOW(),NOW() FROM transaksi WHERE no_transaksi='$no_transaksi'";
						$this->DB->insertRecord($str);
						$this->DB->query('COMMIT'); //pembayaran mahasiswa baru
					}else{
						$this->createObj('Finance');
						$no_formulir=$result['no_formulir'];
						$nim=$result['nim'];
						$kjur=$result['kjur'];
						$ta=$result['tahun'];
						$idsmt=$result['idsmt'];
						$tahun_masuk=$result['tahun_masuk'];
						$semester_masuk=$result['semester_masuk'];
						$idkelas=$result['idkelas'];
						$k_status=$result['k_status'];
						$datamhs=array('no_formulir'=>$no_formulir,'nim'=>$nim,'kjur'=>$kjur,'tahun_masuk'=>$result['tahun_masuk'],'semester_masuk'=>$semester_masuk,'idkelas'=>$idkelas,'ta'=>$ta,'idsmt'=>$idsmt,'k_status'=>$k_status,'perpanjang'=>$result['perpanjang']);
						$this->Finance->setDataMHS($datamhs);
						$datadulang=$this->Finance->getDataDulang($idsmt,$ta);  
			            if (!isset($datadulang['iddulang'])) {	                
			                $bool=$this->Finance->getTresholdPembayaran($ta,$idsmt);						                                
			                if ($bool) {
			                    $tasmt=$ta.$idsmt;	                    
			                    $str = "INSERT INTO dulang (iddulang,nim,tahun,idsmt,tasmt,tanggal,idkelas,status_sebelumnya,k_status) VALUES (NULL,'$nim','$ta','$idsmt','$tasmt',NOW(),'$idkelas','$k_status','A')";
			                    $this->DB->insertRecord($str);
			                    
			                    $str = "UPDATE register_mahasiswa SET k_status='A' WHERE nim='$nim'";
			                    $this->DB->updateRecord($str);
			                }
			                               
			            }
			            $str = "INSERT INTO transaksi_api (no_transaksi,no_faktur,kjur,tahun,idsmt,idkelas,no_formulir,nim,commited,tanggal,userid,total,date_added,date_modified) SELECT no_transaksi,no_faktur,kjur,tahun,idsmt,idkelas,no_formulir,nim,commited,tanggal,$userid,$total_tagihan,NOW(),NOW() FROM transaksi WHERE no_transaksi='$no_transaksi'";
						$this->DB->insertRecord($str);

			            $this->DB->query('COMMIT'); 
					}
					$this->payload['status']='00';
					$this->payload['message']="Proses Login telah berhasil, data transaksi dengan nomor ($no_transaksi) berhasil di Commit !!!. Bisa di rollback, beritahu ke bagian keuangan dengan menyebutkan nomor Transaksi ini ($no_transaksi)";		
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
					if ($result['commited']) {
						$this->payload['status']='88';
						throw new Exception ("Proses Login telah berhasil, namun data transaksi dengan nomor ($no_transaksi) telah di Commit !!!. Bisa di rollback, beritahu ke bagian keuangan dengan menyebutkan nomor Transaksi ini ($no_transaksi)");
					}

					$this->DB->query('BEGIN');
		            $str = "UPDATE transaksi_cuti SET commited=1,date_modified=NOW() WHERE no_transaksi=$no_transaksi";
		            $this->DB->updateRecord($str);

		            $this->createObj('Finance');
		            $no_formulir=$result['no_formulir'];
					$nim=$result['nim'];
					$kjur=$result['kjur'];
					$ta=$result['tahun'];
					$idsmt=$result['idsmt'];
					$tahun_masuk=$result['tahun_masuk'];
					$semester_masuk=$result['semester_masuk'];
					$idkelas=$result['idkelas'];
					$k_status=$result['k_status'];
					$datamhs=array('no_formulir'=>$no_formulir,'nim'=>$nim,'kjur'=>$kjur,'tahun_masuk'=>$result['tahun_masuk'],'semester_masuk'=>$semester_masuk,'idkelas'=>$idkelas,'ta'=>$ta,'idsmt'=>$idsmt,'k_status'=>$k_status,'perpanjang'=>$result['perpanjang']);
					$this->Finance->setDataMHS($datamhs);
		            $datadulang=$this->Finance->getDataDulang($datamhs['ta'],$datamhs['idsmt']);		            
		            if (!isset($datadulang['iddulang'])) {		                
		                $tasmt=$ta.$idsmt;		                
		                $str = "INSERT INTO dulang (iddulang,nim,tahun,idsmt,tasmt,tanggal,idkelas,status_sebelumnya,k_status) VALUES (NULL,'$nim','$ta','$idsmt','$tasmt',NOW(),'$idkelas','$k_status','C')";
		                $this->DB->insertRecord($str);

		                $str = "UPDATE register_mahasiswa SET k_status='C' WHERE nim='$nim'";
		                $this->DB->updateRecord($str);
		            }
		            $str = "INSERT INTO transaksi_api (no_transaksi,no_faktur,tahun,idsmt,nim,commited,tanggal,userid,total,date_added,date_modified) SELECT no_transaksi,no_faktur,tahun,idsmt,nim,commited,tanggal,$userid,dibayarkan,date_added,date_modified FROM transaksi_cuti WHERE no_transaksi='$no_transaksi'";
					$this->DB->insertRecord($str);	
		            $this->DB->query('COMMIT');

					$this->payload['status']='00';
					$this->payload['message']="Proses Login telah berhasil, data transaksi CUTI dengan nomor ($no_transaksi) berhasil di Commit !!!. Bisa di rollback, beritahu ke bagian keuangan dengan menyebutkan nomor Transaksi ini ($no_transaksi)";		
				break;
				default :
					$this->payload['status']='30';
					throw new Exception ("Proses Login telah berhasil, namun ada error yaitu tipe_transaksi tidak dikenal. !!!");
			}
		}catch (Exception $e) {
			$this->payload['message'] = $e->getMessage();
		}	
		$this->Pengguna->insertNewActivity ('json=commit_transaction',"melakukan request api terhadap method commit_transaction, outputnya: ".addslashes($this->payload['message']));
		return $this->payload;
	}
}