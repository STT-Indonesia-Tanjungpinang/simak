<?php
prado::using ('Application.MainPageMHS');
class CTransaksiPembayaranSemesterGanjil Extends MainPageMHS {	
    public static $TotalKomponenBiaya=0;
    public static $TotalSudahDibayarkan=0;
    public static $TotalJumlahBayar=0;
	public function onLoad($param) {
		parent::onLoad($param);				
        $this->showPembayaranSemesterGanjil=true;                
        $this->createObj('Finance');
		if (!$this->IsPostBack && !$this->IsCallback) {            
            try {                
                $datamhs = $this->Pengguna->getDataUser();                
                if (!isset($_SESSION['currentPagePembayaranSemesterGanjil']['no_transaksi']) || $_SESSION['currentPagePembayaranSemesterGanjil']['no_transaksi'] == 'none') {              
                    throw new Exception ("Tidak ada data No. Transaksi di Sesi ini");		
                }  
                $this->setInfoToolbar();
                $this->Finance->setDataMHS($datamhs);
                $no_transaksi = $_SESSION['currentPagePembayaranSemesterGanjil']['no_transaksi'];
                $str = "SELECT no_faktur,tanggal FROM transaksi WHERE no_transaksi = $no_transaksi";
                $this->DB->setFieldTable(array('no_faktur', 'tanggal'));
                $d=$this->DB->getRecord($str);
                $this->hiddennofaktur->Value = $d[1]['no_faktur'];
                $this->txtAddNomorFaktur->Text = $d[1]['no_faktur'];
                $this->cmbAddTanggalFaktur->Text = $this->TGL->tanggal('d-m-Y', $d[1]['tanggal']);
                $this->populateData();
            }catch (Exception $ex) {
                $this->idProcess = 'view';	
                $this->errorMessage->Text = $ex->getMessage();
            }      
		}	
    }
    public function setInfoToolbar() {        
        $ta = $this->DMaster->getNamaTA($_SESSION['currentPagePembayaranSemesterGanjil']['ta']);        		
		$this->labelModuleHeader->Text = "T.A $ta";        
	}    
    public function populateData() {
        $datamhs = $this->Pengguna->getDataUser();
        $no_transaksi = $_SESSION['currentPagePembayaranSemesterGanjil']['no_transaksi'];
        $no_formulir = $datamhs['no_formulir'];
        $ta = $_SESSION['currentPagePembayaranSemesterGanjil']['ta'];             
        $tahun_masuk = $datamhs['tahun_masuk'];
        $idsmt=1;     
        $kelas = $datamhs['idkelas'];                
        
        $str = "SELECT idkombi,SUM(dibayarkan) AS sudah_dibayar FROM v_transaksi WHERE no_formulir = $no_formulir AND tahun = $ta AND idsmt=$idsmt AND commited=1 GROUP BY idkombi ORDER BY idkombi+1 ASC";
        $this->DB->setFieldTable(array('idkombi', 'sudah_dibayar'));
        $d=$this->DB->getRecord($str);
        
        $sudah_dibayarkan=array();
        while (list($o, $p) = each($d)) {            
            $sudah_dibayarkan[$p['idkombi']] = $p['sudah_dibayar'];
        }
        
        $str = "SELECT idkombi,dibayarkan FROM transaksi_detail WHERE no_transaksi = $no_transaksi ORDER BY idkombi+1 ASC";
        $this->DB->setFieldTable(array('idkombi', 'dibayarkan'));
        $k = $this->DB->getRecord($str);
        
        $belum_komit=array();
        while (list($m, $n) = each($k)) {              
            $belum_komit[$n['idkombi']] = $n['dibayarkan'];
        }
        
        $str = "SELECT k.idkombi,k.nama_kombi,kpt.biaya FROM kombi_per_ta kpt,kombi k WHERE  k.idkombi=kpt.idkombi AND tahun = $tahun_masuk AND idsmt=$idsmt AND kpt.idkelas='$kelas' AND periode_pembayaran='semesteran' ORDER BY periode_pembayaran,nama_kombi ASC";
        $this->DB->setFieldTable(array('idkombi', 'nama_kombi', 'biaya'));
        $r = $this->DB->getRecord($str);
        
        while (list($k, $v) = each($r)) {
            $biaya = $v['biaya'];
            $idkombi = $v['idkombi'];
            $sudah_dibayar=isset($sudah_dibayarkan[$idkombi])?$sudah_dibayarkan[$idkombi]:0;
            if ($sudah_dibayar <=$biaya) {
                $v['biaya_alias'] = $this->Finance->toRupiah($biaya);
                $v['nama_kombi']=  strtoupper($v['nama_kombi']);            
                $v['sudah_dibayar'] = $sudah_dibayar;
                $v['sudah_dibayar_alias'] = $this->Finance->toRupiah($sudah_dibayar);  
                $jumlah_bayar = $belum_komit[$idkombi];                
                $v['jumlah_bayar'] = $jumlah_bayar;
                $v['jumlah_bayar_alias'] = $this->Finance->toRupiah($jumlah_bayar);
                $result[$k] = $v;
            }            
        }		
        $this->GridS->DataSource = $result;
		$this->GridS->dataBind();
        
    }
	public function editItem($sender, $param) {                   
        $this->GridS->EditItemIndex=$param->Item->ItemIndex;
        $this->populateData();        
    }
    public function cancelItem($sender, $param) {                
        $this->GridS->EditItemIndex=-1;
        $this->populateData();        
    }		
    public function deleteItem($sender, $param) {                
        $id = $this->GridS->DataKeys[$param->Item->ItemIndex]; 
        $datamhs = $this->Pengguna->getDataUser();
        $no_transaksi = $_SESSION['currentPagePembayaranSemesterGanjil']['no_transaksi'];
        $this->DB->updateRecord("UPDATE transaksi_detail SET dibayarkan=0 WHERE idkombi = $id AND no_transaksi = $no_transaksi");
        $this->GridS->EditItemIndex=-1;
        $this->populateData();
    }  
    public function saveItem($sender, $param) {                        
        $item = $param->Item;
        $id = $this->GridS->DataKeys[$item->ItemIndex];   
        
        $datamhs = $this->Pengguna->getDataUser();
        $no_transaksi = $_SESSION['currentPagePembayaranSemesterGanjil']['no_transaksi'];
        $no_formulir = $datamhs['no_formulir'];
        $ta = $_SESSION['currentPagePembayaranSemesterGanjil']['ta'];        
        $tahun_masuk = $datamhs['tahun_masuk'];
        $idsmt=1;     
        $kelas = $datamhs['idkelas'];       
        
        
        $str = "SELECT SUM(dibayarkan) AS sudah_dibayar FROM v_transaksi WHERE no_formulir = $no_formulir AND tahun = $ta AND idsmt=$idsmt AND idkombi = $id AND commited=1";
        $this->DB->setFieldTable(array('sudah_dibayar'));
        $d=$this->DB->getRecord($str);
        $sudah_dibayar = $d[1]['sudah_dibayar'];
        
        
        $str = "SELECT biaya FROM kombi_per_ta kpt,kombi k WHERE  k.idkombi=kpt.idkombi AND tahun = $tahun_masuk AND idsmt=$idsmt AND kpt.idkelas='$kelas' AND kpt.idkombi = $id";
        $this->DB->setFieldTable(array('biaya'));
        $r = $this->DB->getRecord($str);
        $biaya = $r[1]['biaya'];
        
        $jumlah_bayar = $this->Finance->toInteger(addslashes($item->ColumnJumlahBayar->TextBox->Text));                         
        
        if (($jumlah_bayar+$sudah_dibayar) <= $biaya) {
            $str = "UPDATE transaksi_detail SET dibayarkan='$jumlah_bayar' WHERE no_transaksi = $no_transaksi AND idkombi = $id";
            $this->DB->updateRecord($str);       
        }
        $this->GridS->EditItemIndex=-1;
        $this->populateData();
    }
	public function checkNomorFaktur($sender, $param) {
		$this->idProcess = $sender->getId() == 'addNomorFaktur'?'add':'edit';
        $no_faktur = $param->Value;		
        if ($no_faktur != '') {
            try {
                if ($this->hiddennofaktur->Value != $no_faktur) {
                    if ($this->DB->checkRecordIsExist('no_faktur', 'transaksi', $no_faktur)) {                                
                        throw new Exception ("Nomor Faktur dari ($no_faktur) sudah tidak tersedia silahkan ganti dengan yang lain.");		
                    }
                }
            }catch (Exception $e) {
                $param->IsValid = false;
                $sender->ErrorMessage = $e->getMessage();
            }	
        }	
    }
    public function saveData($sender, $param) {
		if ($this->Page->isValid) {	
            $datamhs = $this->Pengguna->getDataUser();
            $no_transaksi = $_SESSION['currentPagePembayaranSemesterGanjil']['no_transaksi'];
            $nim = $datamhs['nim'];
            
            $no_faktur = addslashes($this->txtAddNomorFaktur->Text);            
            $tanggal=date('Y-m-d', $this->cmbAddTanggalFaktur->TimeStamp);
            
            $str = "UPDATE transaksi SET no_faktur='$no_faktur',tanggal='$tanggal',date_modified=NOW() WHERE no_transaksi='$no_transaksi'";
            $this->DB->updateRecord($str);
            $_SESSION['currentPagePembayaranSemesterGanjil']['no_transaksi'] = 'none';
            $this->redirect('pembayaran.PembayaranSemesterGanjil', true, array('id' => $nim));
        }
    }    
    public function closeTransaction($sender, $param) {
        $datamhs = $this->Pengguna->getDataUser(); 
        $nim = $datamhs['nim'];
        $_SESSION['currentPagePembayaranSemesterGanjil']['no_transaksi'] = 'none';
        $this->redirect('pembayaran.PembayaranSemesterGanjil', true);
    }
    public function cancelTrx($sender, $param) {	
        $datamhs = $this->Pengguna->getDataUser(); 
        $nim = $datamhs['nim'];
		$no_transaksi = $_SESSION['currentPagePembayaranSemesterGanjil']['no_transaksi'];		
		$this->DB->deleteRecord("transaksi WHERE no_transaksi='$no_transaksi'");
        $_SESSION['currentPagePembayaranSemesterGanjil']['no_transaksi'] = 'none';
		$this->redirect('pembayaran.PembayaranSemesterGanjil', true);
	}
    public function closeDetail($sender, $param) {
        $_SESSION['currentPagePembayaranSemesterGanjil']['no_transaksi'] = 'none';
        $this->redirect('pembayaran.PembayaranSemesterGanjil', true);
    }
}
class TotalPrice extends MainController
{   
	public function render($writer)
	{	
        $this->createObj('Finance');
        $writer->write($this->Finance->toRupiah(CTransaksiPembayaranSemesterGanjil::$TotalKomponenBiaya));	
	}
}
class TotalSudahDibayarkan extends MainController
{   
	public function render($writer)
	{	
        $this->createObj('Finance');
        $writer->write($this->Finance->toRupiah(CTransaksiPembayaranSemesterGanjil::$TotalSudahDibayarkan));	
	}
}
class TotalJumlahBayar extends MainController
{   
	public function render($writer)
	{	
        $this->createObj('Finance');
        $writer->write($this->Finance->toRupiah(CTransaksiPembayaranSemesterGanjil::$TotalJumlahBayar));	
	}
}