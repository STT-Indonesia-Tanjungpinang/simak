<?php
class MainPageAPI extends MainPage {
	/**     
	 * show page log aktivitas user 
	 */
	public $showLogAktivitasUser=false;
	public function onLoad ($param) {		
		parent::onLoad($param);				
		if (!$this->IsPostBack&&!$this->IsCallBack) {	           
		}
	}           
}