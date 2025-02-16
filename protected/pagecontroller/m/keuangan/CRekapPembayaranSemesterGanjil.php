<?php
prado::using ('Application.MainPageM');
class CRekapPembayaranSemesterGanjil Extends MainPageM {	
    public static $TotalJumlahMahasiswa=0;
    public static $TotalKewajiban=0;
    public static $TotalDibayarkan=0;
    
	public function onLoad($param) {
		parent::onLoad($param);				
        $this->showSubMenuRekapKeuangan=true;
        $this->showReportRekapPembayaranGanjil=true;                
        $this->createObj('Finance');
		if (!$this->IsPostBack && !$this->IsCallback) {
            if (!isset($_SESSION['currentPageRekapPembayaranSemesterGanjil']) || $_SESSION['currentPageRekapPembayaranSemesterGanjil']['page_name'] != 'm.keuangan.RekapPembayaranSemesterGanjil') {
				$_SESSION['currentPageRekapPembayaranSemesterGanjil'] = array('page_name' => 'm.keuangan.RekapPembayaranSemesterGanjil', 'page_num' => 0, 'search' => false,'semester' =>1,'kelas' => 'none');												
			}
            $_SESSION['currentPageRekapPembayaranSemesterGanjil']['search'] = false; 
            
            $daftar_ps = $this->DMaster->removeIdFromArray($_SESSION['daftar_jurusan'],'none');            
			$this->tbCmbPs->DataSource = $daftar_ps;
			$this->tbCmbPs->Text = $_SESSION['kjur'];			
			$this->tbCmbPs->dataBind();	            
            
            $this->tbCmbTA->DataSource = $this->DMaster->removeIdFromArray($this->DMaster->getListTA(), 'none');;
            $this->tbCmbTA->Text = $_SESSION['ta'];
            $this->tbCmbTA->dataBind();
            
            $tahun_masuk = $this->getAngkatan (false);	
			$this->tbCmbTahunMasuk->DataSource = $tahun_masuk	;					
			$this->tbCmbTahunMasuk->Text = $_SESSION['tahun_masuk'];						
			$this->tbCmbTahunMasuk->dataBind();
            
            
            $kelas = $this->DMaster->getListKelas();
            $kelas['none'] = 'All';
			$this->tbCmbKelas->DataSource = $kelas;
			$this->tbCmbKelas->Text = $_SESSION['currentPageRekapPembayaranSemesterGanjil']['kelas'];			
			$this->tbCmbKelas->dataBind();	
            
            $this->tbCmbOutputReport->DataSource = $this->setup->getOutputFileType();
            $this->tbCmbOutputReport->Text= $_SESSION['outputreport'];
            $this->tbCmbOutputReport->DataBind();
            
            $this->populateData();
            $this->populateSummary();
            $this->setInfoToolbar();
		}	
	}	
    public function setInfoToolbar() {                
        $kjur = $_SESSION['kjur'];        
		$ps = $_SESSION['daftar_jurusan'][$kjur];
        $ta = $this->DMaster->getNamaTA($_SESSION['ta']); 
        $tahun_masuk = $this->DMaster->getNamaTA($_SESSION['tahun_masuk']);
		$this->lblModulHeader->Text = "Program Studi $ps T.A $ta Tahun Masuk $tahun_masuk ";        
	}
    public function changeTbPs($sender, $param) {		
		$_SESSION['kjur'] = $this->tbCmbPs->Text;
        $this->setInfoToolbar();
		$this->populateData();
	}	
    public function changeTbTA($sender, $param) {				
		$_SESSION['ta'] = $this->tbCmbTA->Text;
        $this->setInfoToolbar();
		$this->populateData();
	}   
    public function changeTbTahunMasuk($sender, $param) {    				
		$_SESSION['tahun_masuk'] = $this->tbCmbTahunMasuk->Text;		        
		$this->setInfoToolbar(); 
        $this->populateData();
	}
    public function changeTbKelas($sender, $param) {				
		$_SESSION['currentPageRekapPembayaranSemesterGanjil']['kelas'] = $this->tbCmbKelas->Text;
        $this->setInfoToolbar(); 
		$this->populateData();
	}
	public function renderCallback($sender, $param) {
		$this->RepeaterS->render($param->NewWriter);	
	}	
	public function Page_Changed($sender, $param) {
		$_SESSION['currentPageRekapPembayaranSemesterGanjil']['page_num'] = $param->NewPageIndex;
		$this->populateData($_SESSION['currentPageRekapPembayaranSemesterGanjil']['search']);
	}	
    public function searchRecord($sender, $param) {
		$_SESSION['currentPageRekapPembayaranSemesterGanjil']['search']=true;
        $this->populateData($_SESSION['currentPageRekapPembayaranSemesterGanjil']['search']);
	} 
	public function populateData($search = false) {		
		$ta = $_SESSION['ta'];
        $tahun_masuk = $_SESSION['tahun_masuk'];     
		$semester = $_SESSION['currentPageRekapPembayaranSemesterGanjil']['semester'];
		$kjur = $_SESSION['kjur'];	
        
        $kelas = $_SESSION['currentPageRekapPembayaranSemesterGanjil']['kelas'];
        $str_kelas = $kelas == 'none'?'':" AND idkelas='$kelas'";
        $str = "SELECT idrekap,no_formulir,nim,nirm,nama_mhs,jk,n_kelas,dibayarkan,kewajiban,sisa FROM rekap_laporan_pembayaran_per_semester WHERE kjur='$kjur' AND tahun = $ta AND idsmt='$semester'$str_kelas AND tahun_masuk = $tahun_masuk";
        if ($search) {            
            $txtsearch = addslashes($this->txtKriteria->Text);
            switch($this->cmbKriteria->Text) {  
                case 'nim':
                    $clausa = "AND nim='$txtsearch'";
                    $jumlah_baris = $this->DB->getCountRowsOfTable ("rekap_laporan_pembayaran_per_semester WHERE kjur='$kjur' AND tahun = $ta AND idsmt='$semester'$str_kelas AND tahun_masuk = $tahun_masuk $clausa",'nim');
                    $str = "$str $clausa";
                break;
                case 'nirm':
                    $clausa = "AND nirm='$txtsearch'";
                    $jumlah_baris = $this->DB->getCountRowsOfTable ("rekap_laporan_pembayaran_per_semester WHERE kjur='$kjur' AND tahun = $ta AND idsmt='$semester'$str_kelas AND tahun_masuk = $tahun_masuk $clausa",'nim');
                    $str = "$str $clausa";
                break;
                case 'nama':
                    $clausa = "AND nama_mhs LIKE '%$txtsearch%'";
                    $jumlah_baris = $this->DB->getCountRowsOfTable ("rekap_laporan_pembayaran_per_semester WHERE kjur='$kjur' AND tahun = $ta AND idsmt='$semester'$str_kelas AND tahun_masuk = $tahun_masuk $clausa",'nim');
                    $str = "$str $clausa";
                break;
            }
        }else{
            $jumlah_baris = $this->DB->getCountRowsOfTable("rekap_laporan_pembayaran_per_semester WHERE kjur='$kjur' AND tahun = $ta AND idsmt='$semester'$str_kelas AND tahun_masuk = $tahun_masuk",'idrekap');
        }
        $this->RepeaterS->CurrentPageIndex = $_SESSION['currentPageRekapPembayaranSemesterGanjil']['page_num'];
		$this->RepeaterS->VirtualItemCount = $jumlah_baris;   
		$currentPage = $this->RepeaterS->CurrentPageIndex;
		$offset = $currentPage*$this->RepeaterS->PageSize;		
		$itemcount = $this->RepeaterS->VirtualItemCount;
		$limit = $this->RepeaterS->PageSize;
		if (($offset + $limit) > $itemcount) {
			$limit = $itemcount - $offset;
		}
		if ($limit < 0) {$offset=0;$limit=10;$_SESSION['currentPageRekapPembayaranSemesterGanjil']['page_num'] = 0;}
        $str = "$str ORDER BY idkelas ASC,nama_mhs ASC LIMIT $offset, $limit";				
        $this->DB->setFieldTable(array('idrekap', 'no_formulir', 'nim', 'nirm', 'nama_mhs', 'jk', 'n_kelas', 'dibayarkan', 'kewajiban', 'sisa'));
        $r = $this->DB->getRecord($str, $offset+1);	        
        $result = array();	
        
		while (list($k, $v) = each($r)) {
            $v['kewajiban'] = $this->Finance->toRupiah($v['kewajiban']);
			$v['dibayarkan'] = $this->Finance->toRupiah($v['dibayarkan']);													
			$v['sisa'] = $this->Finance->toRupiah($v['sisa']);
			$result[$k] = $v;
		}
        $this->RepeaterS->DataSource = $result;
		$this->RepeaterS->dataBind();     
        $this->paginationInfo->Text = $this->getInfoPaging($this->RepeaterS);
	}
    public function itemCreated($sender, $param) {
        $item = $param->Item;
		if ($item->ItemType === 'Item' || $item->ItemType === 'AlternatingItem') {
            CRekapPembayaranSemesterGanjil::$TotalKewajiban+=$item->DataItem['jumlah_kewajiban'];
            CRekapPembayaranSemesterGanjil::$TotalDibayarkan+=$item->DataItem['jumlah_dibayarkan']; 
            CRekapPembayaranSemesterGanjil::$TotalJumlahMahasiswa+=$item->DataItem['jumlah_mhs']; 
        }
    }
    public function populateSummary() {
        $ta = $_SESSION['ta'];   
		$semester = $_SESSION['currentPageRekapPembayaranSemesterGanjil']['semester'];
		$kjur = $_SESSION['kjur'];	
        $str = "SELECT tahun_masuk,idkelas,COUNT(nim) AS jumlah_mhs,SUM(kewajiban) AS jumlah_kewajiban,SUM(dibayarkan) AS jumlah_dibayarkan FROM `rekap_laporan_pembayaran_per_semester` WHERE kjur = $kjur AND tahun = $ta AND idsmt=$semester GROUP BY tahun_masuk,idkelas";
    
        $this->DB->setFieldTable(array('tahun_masuk', 'idkelas', 'jumlah_mhs', 'jumlah_kewajiban', 'jumlah_dibayarkan'));
        $r = $this->DB->getRecord($str);
        $this->RepeaterSummary->DataSource = $r;
        $this->RepeaterSummary->DataBind();
    }
    public function generateData($sender, $param) {
        $ta = $_SESSION['ta'];
        $tahun_masuk = $_SESSION['tahun_masuk'];     
		$semester = $_SESSION['currentPageRekapPembayaranSemesterGanjil']['semester'];
		$kjur = $_SESSION['kjur'];	
        
        $kelas = $_SESSION['currentPageRekapPembayaranSemesterGanjil']['kelas'];
        $str_kelas = $kelas == 'none'?'':" AND t2.idkelas='$kelas'";       
        
        $this->DB->deleteRecord("rekap_laporan_pembayaran_per_semester WHERE kjur='$kjur' AND tahun = $ta AND idsmt='$semester' AND tahun_masuk='$tahun_masuk'$str_kelas");
        $str = "SELECT fp.no_formulir,rm.nim,rm.nirm,fp.nama_mhs,fp.jk,fp.ta AS tahun_masuk,fp.idsmt AS semester_masuk,t2.idkelas FROM formulir_pendaftaran fp JOIN register_mahasiswa rm ON (rm.no_formulir=fp.no_formulir) JOIN (SELECT DISTINCT(nim) AS nim,idkelas FROM transaksi WHERE kjur='$kjur' AND tahun = $ta AND idsmt='$semester'$str_kelas) AS t2 ON (t2.nim=rm.nim) WHERE fp.ta = $tahun_masuk ORDER BY nim ASC,nama_mhs ASC";			
   		$this->DB->setFieldTable(array('no_formulir', 'nim', 'nirm', 'nama_mhs', 'jk', 'tahun_masuk', 'semester_masuk', 'idkelas'));
        $r = $this->DB->getRecord($str);    
        
        $komponen_biaya=array();
        $this->Finance->setDataMHS(array('tahun_masuk' => $tahun_masuk,'idsmt' => $semester,'idkelas' => 'A'));
        $komponen_biaya['A']['baru'] = $this->Finance->getTotalBiayaMhsPeriodePembayaran('baru');
        $komponen_biaya['A']['lama'] = $this->Finance->getTotalBiayaMhsPeriodePembayaran('lama');         
        
        $this->Finance->setDataMHS(array('tahun_masuk' => $tahun_masuk,'idsmt' => $semester,'idkelas' => 'B'));
        $komponen_biaya['B']['baru'] = $this->Finance->getTotalBiayaMhsPeriodePembayaran('baru');            
        $komponen_biaya['B']['lama'] = $this->Finance->getTotalBiayaMhsPeriodePembayaran('lama');       
        
        $this->Finance->setDataMHS(array('tahun_masuk' => $tahun_masuk,'idsmt' => $semester,'idkelas' => 'C'));
        $komponen_biaya['C']['baru'] = $this->Finance->getTotalBiayaMhsPeriodePembayaran('baru');            
        $komponen_biaya['C']['lama'] = $this->Finance->getTotalBiayaMhsPeriodePembayaran('lama');        
        
		while (list($k, $v) = each($r)) {
            $no_formulir = $v['no_formulir'];
			$nim = $v['nim'];
            $nirm=$v['nirm'];
            $nama_mhs = addslashes($v['nama_mhs']);
            $jk = $v['jk'];
            $tahun_masuk = $v['tahun_masuk'];
            $semester_masuk = $v['semester_masuk'];
            
            $idkelas = $v['idkelas'];  
            $n_kelas = $this->DMaster->getNamaKelasByID($idkelas);  
			$str2 = "SELECT SUM(dibayarkan) AS dibayarkan FROM transaksi t,transaksi_detail td WHERE td.no_transaksi=t.no_transaksi AND t.nim=$nim AND t.idsmt=$semester AND t.tahun = $ta AND t.commited=1";			
			$this->DB->setFieldTable(array('dibayarkan'));
			$r2=$this->DB->getRecord($str2);				
			$dibayarkan = $r2[1]['dibayarkan'];
            $kewajiban=($ta==$v['tahun_masuk'] && $v['semester_masuk'] == $semester) ? $komponen_biaya[$idkelas]['baru']:$komponen_biaya[$idkelas]['lama'];
            $sisa = $kewajiban-$dibayarkan;
            
            $str = "INSERT INTO rekap_laporan_pembayaran_per_semester SET no_formulir='$no_formulir', nim='$nim', nirm='$nirm', nama_mhs='$nama_mhs', jk='$jk', tahun_masuk = $tahun_masuk, semester_masuk = $semester_masuk, idkelas='$idkelas', n_kelas='$n_kelas', dibayarkan='$dibayarkan', kewajiban='$kewajiban', sisa='$sisa', tahun='$ta', idsmt='$semester', kjur='$kjur'";
			$this->DB->insertRecord($str);
            
		}
        $this->redirect('keuangan.RekapPembayaranSemesterGanjil', true);
    }
    public function refreshRecord($sender, $param) {
        $idrekap = $this->getDataKeyField($sender, $this->RepeaterS);
        $str = "SELECT nim,tahun_masuk,semester_masuk,idsmt,tahun,idkelas FROM rekap_laporan_pembayaran_per_semester WHERE idrekap = $idrekap";
        $this->DB->setFieldTable(array('nim', 'tahun_masuk', 'semester_masuk', 'idsmt', 'tahun', 'idkelas'));
        $r = $this->DB->getRecord($str);   
        $nim = $r[1]['nim'];
        $semester = $r[1]['idsmt'];
        $ta = $r[1]['tahun'];
        $this->Finance->setDataMHS(array('tahun_masuk' => $r[1]['tahun_masuk'],'idsmt' => $r[1]['semester_masuk'],'idkelas' => $r[1]['idkelas']));
        $kewajiban=($r[1]['tahun'] == $r[1]['tahun_masuk'] && $r[1]['semester_masuk'] == $r[1]['idsmt']) ?$this->Finance->getTotalBiayaMhsPeriodePembayaran('baru'):$this->Finance->getTotalBiayaMhsPeriodePembayaran('lama');
        $str2 = "SELECT SUM(dibayarkan) AS dibayarkan FROM transaksi t,transaksi_detail td WHERE td.no_transaksi=t.no_transaksi AND t.nim=$nim AND t.idsmt=$semester AND t.tahun = $ta AND t.commited=1";			
        $this->DB->setFieldTable(array('dibayarkan'));
        $r2=$this->DB->getRecord($str2);
        $dibayarkan = $r2[1]['dibayarkan'];
        $sisa = $kewajiban-$dibayarkan;
        $str = "UPDATE rekap_laporan_pembayaran_per_semester SET dibayarkan='$dibayarkan', kewajiban='$kewajiban', sisa='$sisa' WHERE idrekap = $idrekap";
        $this->DB->updateRecord($str);
        
        $this->redirect('keuangan.RekapPembayaranSemesterGanjil', true);
    }
	public function printOut($sender, $param) {	
        $this->createObj('reportfinance');
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
                $messageprintout = "";
                $dataReport['kjur'] = $_SESSION['kjur'];
                $dataReport['nama_ps'] = $_SESSION['daftar_jurusan'][$_SESSION['kjur']];
                $tahun = $_SESSION['ta'];                
                $tahun_masuk = $_SESSION['tahun_masuk'];                
                $nama_tahun = $this->DMaster->getNamaTA($tahun);
                
                $dataReport['ta'] = $tahun;                
                $dataReport['nama_tahun'] = $nama_tahun;                
                $dataReport['tahun_masuk'] = $tahun_masuk;                
                $dataReport['nama_tahun_masuk'] = $this->DMaster->getNamaTA($tahun_masuk);
                $dataReport['semester'] = $_SESSION['currentPageRekapPembayaranSemesterGanjil']['semester'];
                $dataReport['nama_semester'] = $this->setup->getSemester($_SESSION['currentPageRekapPembayaranSemesterGanjil']['semester']);
                
                $dataReport['kelas'] = $_SESSION['currentPageRekapPembayaranSemesterGanjil']['kelas'];
                $dataReport['linkoutput'] = $this->linkOutput;
                $this->report->setDataReport($dataReport); 
                $this->report->setMode($_SESSION['outputreport']);
                
                $this->report->printRekapPembayaranSemester($this->Finance, $this->DMaster); 
            break;
            case 'pdf':
                $messageprintout = "Mohon maaf Print out pada mode pdf belum kami support.";                
            break;
        }
        $this->lblMessagePrintout->Text = $messageprintout;
        $this->lblPrintout->Text = 'Rekapitulasi Pembayaran Semester Ganjil';
        $this->modalPrintOut->show();
    }
}