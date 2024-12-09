<?php
prado::using ('Application.MainPageF');
class Pendaftaran extends MainPageF {
	public function onLoad($param) {		
		parent::onLoad($param);	         
		$this->createObj('Dmaster');
		if (!$this->IsPostBack&&!$this->IsCallBack) { 
		    
		    $this->lblModulHeader->Text = $this->setup->getSettingValue('default_tahun_pendaftaran');
		    $daftar_prodi=$this->DMaster->getListProgramStudi(2);
		    $daftar_prodi['none'] = 'PILIH PROGRAM STUDI 1';
		    $this->cmbAddKjur1->DataSource=$daftar_prodi;
		    $this->cmbAddKjur1->dataBind();
		    $this->cmbAddKjur2->Enabled=false;
		    
		    $daftar_kelas=$this->DMaster->getListKelas();
		    $daftar_kelas['none'] = 'PILIH KELAS';
		    $this->cmbAddKelas->DataSource=$daftar_kelas;
		    $this->cmbAddKelas->DataBind();
		}
	}    
	public function changePs($sender, $param) {
        if ($sender->Text == 'none') {
            $this->cmbAddKjur2->Enabled=false;
            $this->cmbAddKjur2->Text='none';
        }else{
            $daftar_prodi=$this->DMaster->getListProgramStudi(2);
            $daftar_prodi['none'] = 'PILIH PROGRAM STUDI 2';
            $jurusan=$this->DMaster->removeKjur($daftar_prodi, $sender->Text);
            $this->cmbAddKjur2->Enabled=true;       
            $this->cmbAddKjur2->DataSource=$jurusan;
            $this->cmbAddKjur2->dataBind();
        }	    
	}
	public function checkEmail ($sender, $param) {
	    $id=$sender->getId ();
	    $this->idProcess = ($id=='editEmail')?'edit':'add';
	    $email_mhs=addslashes($param->Value);
	    try {
	        if ($email_mhs != '') {
                if ($this->DB->checkRecordIsExist('email','profiles_mahasiswa', $email_mhs)) {
                    throw new Exception ("Email ($email_mhs) sudah tidak tersedia. Silahkan ganti dengan yang lain.");
                }
                if ($this->DB->checkRecordIsExist('email','formulir_pendaftaran_temp', $email_mhs)) {
                    throw new Exception ("Email ($email_mhs) sudah tidak tersedia. Silahkan ganti dengan yang lain.");
                }
	        }
	    }catch (Exception $e) {
	        $param->IsValid=false;
	        $sender->ErrorMessage=$e->getMessage();
	    }
	}
	public function saveData ($sender, $param) {
	    if ($this->IsValid) {
	        $nama_mhs=addslashes(strtoupper(trim($this->txtAddNamaMhs->Text)));
	        $tempat_lahir=addslashes(strtoupper(trim($this->txtAddTempatLahir->Text)));
	        $tgl_lahir=date ('Y-m-d', $this->txtAddTanggalLahir->TimeStamp);
	        $jk=$this->rdAddPria->Checked===true?'L':'P';
	        $telp_hp=addslashes($this->txtAddNoTelpHP->Text);
	        $email=addslashes($this->txtAddEmail->Text);
	        $kjur1=$this->cmbAddKjur1->Text;
			$kjur2=($this->cmbAddKjur2->Text)> 0 ? $this->cmbAddKjur1->Text : 0;	        
	        $idkelas=$this->cmbAddKelas->Text;
	        $data=$this->Pengguna->createHashPassword($this->txtPassword1->Text);
	        $salt=$data['salt'];
	        $password=$data['password']; 
	        $ta=$this->setup->getSettingValue('default_tahun_pendaftaran');
	        $idsmt=1;
	        $no_pendaftaran=$ta.mt_rand(1000,9999).$idsmt;
			//input ke transaksi keuangan
			$str = "SELECT pin.no_formulir FROM pin LEFT JOIN formulir_pendaftaran fp ON (fp.no_formulir=pin.no_formulir) LEFT JOIN peserta_ujian_pmb pup ON (pup.no_formulir=pin.no_formulir) WHERE pin.tahun_masuk=$ta AND pin.idkelas='$idkelas' AND fp.no_formulir IS NULL AND pup.no_formulir IS NULL LIMIT 1";
			$this->DB->setFieldTable(array('no_formulir'));
			$form = $this->DB->getRecord($str);
			
			$no_formulir=$form[1]['no_formulir'];
			$no_transaksi='10'.$ta.$idsmt.mt_rand(10000,99999);
			$no_faktur=$ta.$no_transaksi;        
			$userid=$no_formulir;

			$this->DB->query ('BEGIN');
			$str = "INSERT INTO transaksi SET no_transaksi=$no_transaksi,no_faktur='$no_faktur',kjur=0,tahun='$ta',idsmt='$idsmt',idkelas='$idkelas',no_formulir='$no_formulir',nim=0,tanggal=NOW(),jumlah_sks=0,disc=0,userid='$userid',date_added=NOW(),date_modified=NOW()";
			if ($this->DB->insertRecord($str)) {
				$str = "SELECT idkombi,SUM(dibayarkan) AS sudah_dibayar FROM v_transaksi WHERE no_formulir=$no_formulir AND tahun=$ta AND idsmt=$idsmt AND commited=1 GROUP BY idkombi ORDER BY idkombi+1 ASC";
				$this->DB->setFieldTable(array('idkombi','sudah_dibayar'));
				$d=$this->DB->getRecord($str);

				$sudah_dibayarkan=array();
				while (list($o, $p)=each($d)) {            
					$sudah_dibayarkan[$p['idkombi']]=$p['sudah_dibayar'];
				}
				$str = "SELECT k.idkombi,kpt.biaya FROM kombi_per_ta kpt,kombi k WHERE k.idkombi=kpt.idkombi AND tahun=$ta AND idsmt=$idsmt AND kpt.idkelas='$idkelas' AND k.idkombi=1 ORDER BY periode_pembayaran,nama_kombi ASC";
				$this->DB->setFieldTable(array('idkombi','biaya'));
				$r=$this->DB->getRecord($str);

				while (list($k, $v)=each($r)) {
					$biaya=$v['biaya'];
					$idkombi=$v['idkombi'];
					$sisa_bayar=isset($sudah_dibayarkan[$idkombi])?$biaya-$sudah_dibayarkan[$idkombi]:$biaya;
					$str = "INSERT INTO transaksi_detail (idtransaksi_detail,no_transaksi,idkombi,dibayarkan,jumlah_sks) VALUES(NULL, $no_transaksi, $idkombi, $sisa_bayar,0)";
					$this->DB->insertRecord($str);
				}
				// input ke formulir pendaftaran 
				$str ="INSERT INTO formulir_pendaftaran SET 
					no_formulir='$no_formulir',
					nama_mhs='$nama_mhs',
					tempat_lahir='$tempat_lahir',
					tanggal_lahir='$tgl_lahir',
					jk='$jk',
					idagama=0,
					nama_ibu_kandung='-',
					idwarga='WNI',
					nik='-',
					idstatus='TIDAK_BEKERJA',
					alamat_kantor='-',
					alamat_rumah='-',
					kelurahan='-',
					kecamatan='-',
					telp_kantor='-',
					telp_rumah='-',
					telp_hp='-',
					idjp=0,
					pendidikan_terakhir='3',
					jurusan='-',
					kota='-',
					provinsi='-',
					tahun_pa=$ta,
					jenis_slta='SMU',
					asal_slta='-',
					status_slta='SWASTA',
					nomor_ijazah='-',
					kjur1='$kjur1',
					kjur2='$kjur2',
					waktu_mendaftar=NOW(),
					ta=$ta,
					idsmt=$idsmt,
					idkelas='$idkelas',
					daftar_via='WEB'";
				$this->DB->insertRecord($str);
				
				$photo_profile='resources/photomhs/no_photo.png';
                $userpassword=md5($this->txtPassword1->Text);
                $str = "INSERT INTO profiles_mahasiswa (idprofile,no_formulir,nim,email,userpassword,theme,photo_profile) VALUES (NULL, $no_formulir,0,'$email','$userpassword','cube','$photo_profile')";
                $this->DB->insertRecord($str);
                $ket="Input Via WEB";
                $userid=$no_formulir;
                $str = 'INSERT INTO bipend (idbipend,tahun,no_faktur,tgl_bayar,no_formulir,gelombang,dibayarkan,ket,userid) VALUES ';
                echo $str .= "(NULL, $ta,'$no_formulir',NOW(),'$no_formulir','1','$sisa_bayar','$ket','$userid')";				
				$this->DB->insertRecord($str);
				

				//input ke formulir pendaftaran temp
			 	$str  = "INSERT INTO formulir_pendaftaran_temp SET no_pendaftaran='$no_pendaftaran',no_formulir=$no_formulir,nama_mhs='$nama_mhs',tempat_lahir='$tempat_lahir',tanggal_lahir='$tgl_lahir',jk='$jk',email='$email',telp_hp='$telp_hp',kjur1='$kjur1',kjur2='$kjur2',idkelas='$idkelas',ta='$ta',idsmt='$idsmt',salt='$salt',userpassword='$password',waktu_mendaftar=NOW(),file_bukti_bayar=''";
				$this->DB->insertRecord($str);
				
				// membuat jadwal untuk mahasiswa ini
				$str = "INSERT INTO jadwal_ujian_pmb SET idjadwal_ujian=NULL,tahun_masuk=$ta,idsmt=$idsmt,nama_kegiatan='Ujian Calon Mahasiswa Baru $nama_mhs ($no_formulir)',tanggal_ujian='$ta-01-01',jam_mulai='00:00',jam_akhir='23:59',tanggal_akhir_daftar='$ta-12-31',idruangkelas='0',date_added=NOW(),date_modified=NOW(),status=1";
				$this->DB->insertRecord($str);
				$idjadwal_ujian=$this->DB->getLastInsertID();

				$str = "INSERT INTO peserta_ujian_pmb SET idpeserta_ujian=NULL,no_formulir=$no_formulir,idjadwal_ujian=$idjadwal_ujian,date_added=NOW()";
				$this->DB->insertRecord($str);
			
				$this->DB->query('COMMIT');
			}else{
				$this->DB->query('ROLLBACK');
			}
			
	        $this->redirect('PendaftaranSuccess',false,array('id'=>$no_pendaftaran));
	    }
	}
}