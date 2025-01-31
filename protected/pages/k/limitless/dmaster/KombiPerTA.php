<?php
prado::using ('Application.pagecontroller.k.dmaster.CKombiPerTA');
class KombiPerTA Extends CKombiPerTA {	
	public function onLoad($param) {
		parent::onLoad($param);				
	}		
    public function itemCreated($sender, $param){
        $item = $param->Item;
        if($item->ItemType === 'EditItem') {   
            $item->ColumnBiaya->TextBox->CssClass='form-control';                                   
            $item->ColumnBiaya->TextBox->Width='150px'; 
            $item->ColumnBiaya->TextBox->Attributes->OnKeyUp="formatangka(this,false)";
            
            $item->EditColumn->UpdateButton->ClientSide->OnPreDispatch="Pace.stop();Pace.start();";                       
            $item->EditColumn->CancelButton->ClientSide->OnPreDispatch="Pace.stop();Pace.start();";                                   
            $item->DeleteColumn->Button->ClientSide->OnPreDispatch="Pace.stop();Pace.start();";
               
        }
        if($item->ItemType === 'Item' || $item->ItemType === 'AlternatingItem')  {                        
            $item->EditColumn->EditButton->ClientSide->OnPreDispatch="Pace.stop();Pace.start();";
            $item->EditColumn->EditButton->CssClass='btn btn-icon btn-xs';
            $item->EditColumn->EditButton->Attributes->Title='Ubah Biaya';
            
            $item->DeleteColumn->Button->ClientSide->OnPreDispatch="Pace.stop();Pace.start();";                
            $item->DeleteColumn->Button->CssClass="btn btn-icon btn-xs";                
            $item->DeleteColumn->Button->Attributes->Title='Reset Biaya';
            if ($item->DataItem['periode_pembayaran']=='SEMESTERAN') {                
                CKombiPerTA::$TotalBiaya+=$item->DataItem['biaya'];
                CKombiPerTA::$TotalKeseluruhan+=$item->DataItem['biaya']; 
            }else{
                CKombiPerTA::$TotalKeseluruhan+=$item->DataItem['biaya']; 
            }
            
        } 
        
    }
}	