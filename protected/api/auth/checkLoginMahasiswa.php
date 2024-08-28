<?php
prado::using('Application.api.BaseWS');
class CheckLoginMahasiswa extends BaseWS {	
	public function getJsonContent() {
		try {
			$this->validate ();
			$data=$_POST;
			if (!isset($data['nim'])) {
				$this->payload['status']='30';
				throw new Exception ("Proses Login telah berhasil, namun ada error yaitu data POST nim tidak ada !!!");
			}
			if (empty($data['password'])) {
				$this->payload['status']='30';
				throw new Exception ("Proses Login telah berhasil, namun ada error yaitu data password kosong !!!");
			}
			$nim=addslashes($data['nim']);
			$password=addslashes($data['password']);;
            
            $payload="data nim ($nim) dan password ($password) udah sampai di server makasih";
            $message="username atau password salah";
            $this->payload['payload']=$payload;							
            $this->payload['message']=$message;
		}catch (Exception $e) {
			$this->payload['message'] = $e->getMessage();
		}
		$this->Pengguna->insertNewActivity ('json=check_login_mahasiswa',"melakukan request api terhadap method check_login_mahasiswa, outputnya: ".$this->payload['message']);
		return $this->payload;
	
	}
}