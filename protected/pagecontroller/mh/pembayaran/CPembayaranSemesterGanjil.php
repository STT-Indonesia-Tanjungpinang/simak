<?php
prado::using ('Application.MainPageMHS');
class CPembayaranSemesterGanjil Extends MainPageMHS {		
	public static $TotalSudahBayar=0;
    public static $KewajibanMahasiswa=0;
	public function onLoad($param) {
		parent::onLoad($param);			
        $this->showPembayaranSemesterGanjil=true;                
        $this->createObj('Finance');
		if (!$this->IsPostBack&&!$this->IsCallBack) {
            if (!isset($_SESSION['currentPagePembayaranSemesterGanjil'])||$_SESSION['currentPagePembayaranSemesterGanjil']['page_name']!='mh.pembayaran.PembayaranSemesterGanjil') {
				$_SESSION['currentPagePembayaranSemesterGanjil']=array('page_name'=>'mh.pembayaran.PembayaranSemesterGanjil','page_num'=>0,'ta'=>$_SESSION['ta'],'no_transaksi'=>'none');												
			}
			$this->setInfoToolbar();
			$this->tbCmbTA->DataSource=$this->DMaster->removeIdFromArray($this->DMaster->getListTA($this->Pengguna->getDataUser('tahun_masuk')),'none');;
            $this->tbCmbTA->Text=$_SESSION['currentPagePembayaranSemesterGanjil']['ta'];
            $this->tbCmbTA->dataBind();
			try {
				$datamhs = $this->Pengguna->getDataUser();
				$datamhs['idsmt']=1;
				$datamhs['ta']=$_SESSION['currentPagePembayaranSemesterGanjil']['ta'];  
				
				if ($datamhs['tahun_masuk'] == $datamhs['ta'] && $datamhs['semester_masuk']==1) {	
					$nim=$datamhs['nim'];
                    throw new Exception ("NIM ($nim) adalah seorang Mahasiswa baru, mohon diproses di Pembayaran->Mahasiswa Baru.");
                }
                $this->checkPembayaranSemesterLalu ();
				$this->Finance->setDataMHS($datamhs);
				CPembayaranSemesterGanjil::$KewajibanMahasiswa=$this->Finance->getTotalBiayaMhsPeriodePembayaran ('lama');
				$this->populateTransaksi();
			}catch (Exception $ex) {
                $this->idProcess='view';	
                $this->errorMessage->Text=$ex->getMessage();
            }  
            
		}	
	}	
    public function setInfoToolbar() {        
        $ta=$this->DMaster->getNamaTA($_SESSION['currentPagePembayaranSemesterGanjil']['ta']);        		
		$this->labelModuleHeader->Text="T.A $ta";        
	}
    public function changeTbTA ($sender,$param) {				
		$_SESSION['currentPagePembayaranSemesterGanjil']['ta']=$this->tbCmbTA->Text;
		$this->redirect('pembayaran.PembayaranSemesterGanjil',true); 
	}	
	public function setDataBound($sender,$param) {				
		$item=$param->Item;
		if ($item->ItemType==='Item' || $item->ItemType==='AlternatingItem') {			
			if ($item->DataItem['commited']) {
                $item->btnDeleteFromRepeater->Enabled=false;				
                $item->btnEditFromRepeater->Enabled=false;				
			}else{
                $item->btnDeleteFromRepeater->Attributes->onclick="if(!confirm('Apakah Anda ingin menghapus Transaksi ini?')) return false;";
            }
            CPembayaranSemesterGanjil::$TotalSudahBayar+=$item->DataItem['total'];
		}		
	}		
	public function populateTransaksi() {
		$datamhs = $this->Pengguna->getDataUser();		
		$datamhs['idsmt']=1;
		$datamhs['ta']=$_SESSION['currentPagePembayaranSemesterGanjil']['ta'];

		$idsmt=$datamhs['idsmt'];
        $nim=$datamhs['nim'];
        $tahun=$datamhs['ta'];
        
        $kjur=$datamhs['kjur'];
        $str = "SELECT no_transaksi,no_faktur,tanggal,commited,date_added FROM transaksi WHERE tahun='$tahun' AND idsmt='$idsmt' AND nim='$nim' AND kjur='$kjur'";
        $this->DB->setFieldTable(array('no_transaksi','no_faktur','tanggal','commited','date_added'));
        $r=$this->DB->getRecord($str);
        $result=array();
        while (list($k,$v)=each($r)) {
            $no_transaksi=$v['no_transaksi'];
            $v['total']=$this->DB->getSumRowsOfTable('dibayarkan',"transaksi_detail WHERE no_transaksi=$no_transaksi");
            $result[$k]=$v;
        }
        $this->ListTransactionRepeater->DataSource=$result;
        $this->ListTransactionRepeater->dataBind();        
	}	
	public function addTransaction ($sender,$param) {
		$datamhs=$this->Pengguna->getDataUser();    
		$datamhs['idsmt']=$datamhs['semester_masuk'];
        $this->Finance->setDataMHS($datamhs);
        if ($_SESSION['currentPagePembayaranSemesterGanjil']['no_transaksi'] == 'none') {
            $no_formulir=$datamhs['no_formulir'];
            $nim=$datamhs['nim'];
            $ta=$_SESSION['currentPagePembayaranSemesterGanjil']['ta'];    
            $tahun_masuk=$datamhs['tahun_masuk'];
            $idsmt=1;
            if ($this->Finance->getLunasPembayaran($ta,$idsmt)) {
                $this->lblContentMessageError->Text='Tidak bisa menambah Transaksi baru karena sudah lunas.';
                $this->modalMessageError->show();
            }elseif($this->DB->checkRecordIsExist('nim','transaksi',$nim," AND tahun='$ta' AND idsmt='$idsmt' AND commited=0")) {
                $this->lblContentMessageError->Text='Tidak bisa menambah Transaksi baru karena ada transaksi yang belum di Commit.';
                $this->modalMessageError->show();
            }else{
                $no_transaksi='10'.$ta.$idsmt.mt_rand(10000,99999);
                $no_faktur=$ta.$no_transaksi;
                $ps=$datamhs['kjur'];                
                $idkelas=$datamhs['idkelas'];
                $userid=$this->Pengguna->getDataUser('userid');

                $this->DB->query ('BEGIN');
                $str = "INSERT INTO transaksi SET no_transaksi=$no_transaksi,no_faktur='$no_faktur',kjur='$ps',tahun='$ta',idsmt='$idsmt',idkelas='$idkelas',no_formulir='$no_formulir',nim='$nim',jumlah_sks=0,disc=0,tanggal=NOW(),userid='$userid',date_added=NOW(),date_modified=NOW()";
                if ($this->DB->insertRecord($str)) {
                    $str = "SELECT idkombi,SUM(dibayarkan) AS sudah_dibayar FROM v_transaksi WHERE nim=$nim AND tahun=$ta AND idsmt=$idsmt AND commited=1 GROUP BY idkombi ORDER BY idkombi+1 ASC";
                    $this->DB->setFieldTable(array('idkombi','sudah_dibayar'));
                    $d=$this->DB->getRecord($str);

                    $sudah_dibayarkan=array();
                    while (list($o,$p)=each($d)) {            
                        $sudah_dibayarkan[$p['idkombi']]=$p['sudah_dibayar'];
                    }
                    $str = "SELECT k.idkombi,kpt.biaya FROM kombi_per_ta kpt,kombi k WHERE  k.idkombi=kpt.idkombi AND tahun=$tahun_masuk AND kpt.idkelas='$idkelas' AND idsmt=$idsmt AND periode_pembayaran='semesteran' ORDER BY periode_pembayaran,nama_kombi ASC";
                    $this->DB->setFieldTable(array('idkombi','biaya'));
                    $r=$this->DB->getRecord($str);

                    while (list($k,$v)=each($r)) {
                        $biaya=$v['biaya'];
                        $idkombi=$v['idkombi'];
                        $sisa_bayar=isset($sudah_dibayarkan[$idkombi]) ? $biaya-$sudah_dibayarkan[$idkombi]:$biaya;
                        $str = "INSERT INTO transaksi_detail SET idtransaksi_detail=NULL,no_transaksi=$no_transaksi,idkombi=$idkombi,dibayarkan=$sisa_bayar,jumlah_sks=0";
                        $this->DB->insertRecord($str);
                    }                   
                    $this->DB->query('COMMIT');
                    $_SESSION['currentPagePembayaranSemesterGanjil']['no_transaksi']=$no_transaksi;            
                    $this->redirect('pembayaran.TransaksiPembayaranSemesterGanjil',true);        
                }else{
                    $this->DB->query('ROLLBACK');
                }           
            }
        }else{            
            $this->redirect('pembayaran.TransaksiPembayaranSemesterGanjil',true); 
        }
    }
    public function editRecord ($sender,$param) {	        
        $datamhs=$this->Pengguna->getDataUser();    
        if ($_SESSION['currentPagePembayaranSemesterGanjil']['no_transaksi'] == 'none') {
            $no_transaksi=$this->getDataKeyField($sender,$this->ListTransactionRepeater);		
            $_SESSION['currentPagePembayaranSemesterGanjil']['no_transaksi']=$no_transaksi;
        }	
		$this->redirect('pembayaran.TransaksiPembayaranSemesterGanjil',true);
	}	
    public function deleteRecord ($sender,$param) {	
        $datamhs=$this->Pengguna->getDataUser();  
        $nim=$datamhs['nim'];
		$no_transaksi=$this->getDataKeyField($sender,$this->ListTransactionRepeater);		
		$this->DB->deleteRecord("transaksi WHERE no_transaksi='$no_transaksi'");		
		$this->redirect('pembayaran.PembayaranSemesterGanjil',true);
	}	
    
}