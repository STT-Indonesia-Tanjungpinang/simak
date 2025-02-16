<?php

class MainPageSA extends MainPage 
{
	/**     
	 * show page sub menu lembaga [dmaster]
	 */
	public $showSubMenuLembaga = false;        
	/**
	 * show page dosen [datamaster lembaga]
	 */
	public $showDosen = false;
	/**
	 * show page dosen wali [datamaster lembaga]
	 */
	public $showDosenWali = false;
	/**     
	 * show page program studi [dmaster lembaga]
	 */
	public $showProdi = false;    
	/**
	 * show sub menu [dmaster perkuliahan]
	 */
	public $showSubMenuDMasterPerkuliahan = false;    
	/**     
	 * show page kurikulum [dmaster lembaga]
	 */
	public $showKurikulum = false;    
	/**     
	 * show page tahun akademik [dmaster perkuliahan]
	 */
	public $showTA = false;
	/**
	 * show sub menu [pembayaran]
	 */
	public $showSubMenuPembayaran = false;  
	/**
	 * show page pembayaran mahasiswa baru
	 */
	public $showPembayaranMahasiswaBaru = false;  
	/**     
	 * show page pembayaran semester Ganjil [pembayaran]
	 */
	public $showPembayaranSemesterGanjil = false;
	/**     
	 * show page pembayaran semester Genap [pembayaran]
	 */
	public $showPembayaranSemesterGenap = false;
	/**
	 * show page penyelenggaraan [perkuliahan]
	 */
	public $showPenyelenggaraan = false;
	/**
	 * show page KRS Kelas Ekstension [perkuliahan]
	 */
	public $showKRSEkstension = false;
	/**
	 * show page Peserta matakuliah [perkuliahan]
	 */
	public $showPesertaMatakuliah = false;
	/**     
	 * show page variable [setting variable]
	 */
	public $showVariable = false;        
	/**     
	 * show sub menu setting akademik[setting]
	 */
	public $showSubMenuSettingAkademik = false; 
	/**     
	 * show sub menu setting sistem[setting]
	 */
	public $showSubMenuSettingSistem = false;
	/**     
	 * show page user super admin [setting sistem]
	 */
	public $showUserSA = false;
	/**     
	 * show page user Manajemen [setting sistem]
	 */
	public $showUserManajemen = false;
	/**     
	 * show page user keuangan [setting sistem]
	 */
	public $showUserKeuangan = false;
	/**     
	 * show page user dosen [setting sistem]
	 */
	public $showUserDosen = false;
	/**     
	 * show page user operator nilai [setting sistem]
	 */
	public $showUserON = false;
	/**     
	 * show page user verifikator nilai [setting sistem]
	 */
	public $showUserVN = false;
	/**     
	 * show page user checker nilai [setting sistem]
	 */
	public $showUserCN = false;
	/**     
	 * show page user API [setting sistem]
	 */
	public $showAPI = false;
	/**     
	 * show page cache [setting sistem]
	 */
	public $showCache = false;    
	 /**
	 * show page export data[setting]
	 */
	public $showExportData = false;
	public function onLoad($param)
	{		
		parent::onLoad($param);				
		if (!$this->IsPostBack&&!$this->IsCallBack)
		{	           
		}
	}           
}