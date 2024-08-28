<?php
prado::using('Application.api.BaseWS');
class getTransaction extends BaseWS {	
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
			$tipe_transaksi=substr($no_transaksi, 0,2);
			switch ($tipe_transaksi) {
				case 10: //bayar biasa
					$str = "SELECT t.no_transaksi,t.no_faktur,t.kjur,t.tahun,t.idsmt,t.idkelas,k.nkelas,t.no_formulir,fp.nama_mhs,t.nim,t.disc,t.commited,t.tanggal,t.date_added FROM transaksi t LEFT JOIN formulir_pendaftaran fp ON (fp.no_formulir=t.no_formulir) LEFT JOIN kelas k ON (k.idkelas=t.idkelas) WHERE t.no_transaksi='$no_transaksi'";
					$this->DB->setFieldTable(array('no_transaksi','no_faktur','kjur','tahun','idsmt','idkelas','nkelas','no_formulir','nama_mhs','nim','disc','commited','tanggal','date_added'));		
					$r=$this->DB->getRecord($str);						
					if (isset($r[1])) {
						$result=$r[1];
						$payload['no_formulir']=$result['no_formulir'];
						$payload['nim']=$result['nim'];
						if ($result['nama_mhs'] == '' || $result['nim'] == '' || $result['kjur'] == 0) {
							$no_formulir=$payload['no_formulir'];
							$str = "SELECT nama_mhs FROM formulir_pendaftaran_temp WHERE no_formulir='$no_formulir'";
							$this->DB->setFieldTable(array('nama_mhs'));		
							$r=$this->DB->getRecord($str);	
							$result['nama_mhs']=isset($r[1])?$r[1]['nama_mhs']:'';		
							$keterangan='MAHASISWA BARU';
						}else{
							$keterangan='MAHASISWA LAMA';
						}
						$payload['nama_mhs']=$result['nama_mhs'];
						$payload['no_transaksi']=$result['no_transaksi'];
						$payload['no_faktur']=$result['no_faktur'];
						$payload['kjur']=$result['kjur'];
						$payload['tahun']=$result['tahun'];
						$payload['idsmt']=$result['idsmt'];						
						$payload['idkelas']=$result['idkelas'];		
						$this->createObj('dmaster');
						$payload['nama_prodi']=$payload['kjur'] > 0 ? $this->DMaster->getNamaProgramStudiByID($payload['kjur']) : '';
						$payload['semester']=$this->semester[$result['idsmt']];	
						$payload['nama_kelas']=$result['nkelas'];
						$this->createObj('Finance');	
						$totaltagihan = $this->Finance->getTotalTagihanByNoTransaksi($no_transaksi);			
						$jumlah = $result['disc']>0?($totaltagihan*($result['disc']/100)):$totaltagihan;			
						$payload['totaltagihan']=$jumlah;
						$payload['commited']=$result['commited'];
                        $payload['keterangan']=$keterangan;
                        if ($result['commited']==1) {
                            $this->payload['status']='88';
                            $message="Login=1, transaksi ($no_transaksi) sudah pernah dilakukan";
                        }else{
                            $this->payload['status']='00';
                            $message="Login=1, data transaksi dengan nomor ($no_transaksi) berhasil diperoleh !!!";
                        }
                        
						$this->payload['payload']=$payload;							
						$this->payload['message']=$message;
					}else{
						$this->payload['status']='04';
						throw new Exception ("Proses Login telah berhasil, namun data transaksi dengan nomor ($no_transaksi) tidak ada di database !!!");
					}			
				break;
				case 11: //bayar cuti
					$str = "SELECT t.no_transaksi,t.no_faktur,t.tahun,t.idsmt,vdm.no_formulir,t.nim,vdm.nama_mhs,vdm.kjur,vdm.nama_ps,vdm.idkelas,k.nkelas AS nama_kelas,t.dibayarkan AS totaltagihan,t.commited,t.tanggal,t.date_added FROM transaksi_cuti t JOIN v_datamhs vdm ON (vdm.nim=t.nim) JOIN kelas k ON (k.idkelas=vdm.idkelas)  WHERE t.no_transaksi='$no_transaksi'";
					$this->DB->setFieldTable(array('no_transaksi','no_faktur','tahun','idsmt','no_formulir','nim','nama_mhs','kjur','nama_ps','idkelas','nama_kelas','totaltagihan','commited','tanggal','date_added'));		
					$r=$this->DB->getRecord($str);						
					if (isset($r[1])) {
						$payload=$r[1];
                        if ($payload['commited']==1) {
                            $this->payload['status']='88';
                            $message="Login=1, transaksi ($no_transaksi) sudah pernah dilakukan";
                        }else{
                            $this->payload['status']='00';
                            $message="Login=1, data transaksi dengan nomor ($no_transaksi) berhasil diperoleh !!!";
						}			
						$this->createObj('dmaster');
						$payload['nama_prodi']=$payload['nama_ps'];		
						$payload['semester']=$this->semester[$payload['idsmt']];		
						$payload['keterangan']='CUTI';		
						$this->payload['payload']=$payload;							
						$this->payload['message']=$message;
					}else{
						$this->payload['status']='04';
						throw new Exception ("Proses Login telah berhasil, namun data transaksi cuti dengan nomor ($no_transaksi) tidak ada di database !!!");
					}		
				break;
				default :
					$this->payload['status']='30';
					throw new Exception ("Proses Login telah berhasil, namun ada error yaitu tipe_transaksi tidak dikenal.");
			}
		}catch (Exception $e) {
			$this->payload['message'] = $e->getMessage();
		}
		// $this->Pengguna->insertNewActivity ('json=get_transaction',"melakukan request api terhadap method get_transaction, outputnya: ".$this->payload['message']);
		return $this->payload;
	
	}
}