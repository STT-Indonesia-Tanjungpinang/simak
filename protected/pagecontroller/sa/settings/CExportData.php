<?php
prado::using ('Application.MainPageSA');
class CExportData extends MainPageSA {    
    public function onLoad($param) {        
        parent::onLoad($param);                     
        $this->showVariable=true;       
        if (!$this->IsPostBack&&!$this->IsCallBack) {              
            if (!isset($_SESSION['currentPageExportData'])||$_SESSION['currentPageExportData']['page_name']!='sa.settings.ExportData') {
                $_SESSION['currentPageExportData']=array('page_name'=>'sa.settings.ExportData','page_num'=>0);                                              
            }            
        }
    }     
    public function cekNIM ($sender,$param) {     
        $nim=addslashes($param->Value);     
        if ($nim != '') {
            try {
                $str = "SELECT nim FROM v_datamhs vdm  WHERE vdm.nim='$nim'";
                $this->DB->setFieldTable(array('nim'));
                $r=$this->DB->getRecord($str);             
                if (!isset($r[1])) {
                    throw new Exception ("Mahasiswa Dengan NIM ($nim) tidak terdaftar di Portal.");
                }
            }catch (Exception $e) {
                $param->IsValid=false;
                $sender->ErrorMessage=$e->getMessage();
            }   
        }   
    }
    public function exportData ($sender,$param) {
        if ($this->IsValid) {
            try {
                switch ($sender->getId()) {
                    case 'btnSaveExportPerMHS' :
                        $nim=addslashes($this->txtNIM->Text);
                        $this->exportDataMHS($nim);
                    break;
                }   
            }catch (Exception $e) {
                echo $e->getMessage();
            }
            
        }
    }
    private function buildSqlInsert ($tablename,$clausa='') {
        $result=$this->DB->query('SELECT * FROM '.$tablename.$clausa);        
        $fields_amount  =   $result->field_count;  
        $rows_num=$this->DB->getCountRowsOfTable ($tablename.$clausa);     
        $res            =   $this->DB->query('SHOW CREATE TABLE '.$tablename); 
        $TableMLine     =   $res->fetch_row();
        $content='';
        for ($i = 0, $st_counter = 0; $i < $fields_amount;   $i++, $st_counter=0) {
            while($row = $result->fetch_row())  { //when started (and every after 100 command cycle):
                if ($st_counter%100 == 0 || $st_counter == 0 )  
                {
                        $content .= "INSERT INTO ".$tablename." VALUES";
                }
                $content .= "\n(";
                for($j=0; $j<$fields_amount; $j++)  
                { 
                    $row[$j] = str_replace("\n","\\n", addslashes($row[$j]) ); 
                    if (isset($row[$j]))
                    {
                        $content .= '"'.addslashes($row[$j]).'"' ; 
                    }
                    else 
                    {   
                        $content .= '""';
                    }     
                    if ($j<($fields_amount-1))
                    {
                            $content.= ',';
                    }      
                }
                $content .=")";
                //every after 100 command cycle [or at last line] ....p.s. but should be inserted 1 cycle eariler
                if ( (($st_counter+1)%100==0 && $st_counter!=0) || $st_counter+1==$rows_num) 
                {   
                    $content .= ";";
                } 
                else 
                {
                    $content .= ",";
                } 
                $st_counter=$st_counter+1;
            }
        } 
        return $content .="\n\n";
    }
    private function exportDataMHS ($value,$mode='nim') {
        switch ($mode) {
            case 'nim' :               
                $str = "SELECT no_formulir,nim,nirm FROM register_mahasiswa WHERE nim='$value'";
                $this->DB->setFieldTable(array('no_formulir','nim','nirm'));
                $r=$this->DB->getRecord($str);

                $no_formulir=$r[1]['no_formulir']; 
                $nim=$r[1]['nim'];    
                $nirm=$r[1]['nirm'];                
                $filename=BASEPATH."exported/sql/$value.sql";
                $fp = fopen ($filename,'w');
                $sql='';
                if ($fp) {
                    $sql = $sql."--\n";
                    $sql =$sql. "-- Table: formulir_pendaftaran\n";
                    $sql =$sql. "--\n";
                    $sql =$sql.$this->buildSqlInsert('formulir_pendaftaran'," WHERE no_formulir='$no_formulir'");  
                    
                    $sql =$sql. "--\n";
                    $sql =$sql. "-- Table: bipend\n";
                    $sql =$sql. "--\n";
                    $sql =$sql.$this->buildSqlInsert('bipend'," WHERE no_formulir='$no_formulir'");  

                    $sql = $sql."--\n";
                    $sql =$sql. "-- Table: peserta_ujian_pmb\n";
                    $sql =$sql. "--\n";
                    $sql =$sql.$this->buildSqlInsert('peserta_ujian_pmb'," WHERE no_formulir='$no_formulir'");

                    $sql = $sql."--\n";
                    $sql =$sql. "-- Table: kartu_ujian\n";
                    $sql =$sql. "--\n";
                    $sql =$sql.$this->buildSqlInsert('kartu_ujian'," WHERE no_formulir='$no_formulir'"); 

                    $sql = $sql."--\n";
                    $sql =$sql. "-- Table: jawaban_ujian\n";
                    $sql =$sql. "--\n";
                    $sql =$sql.$this->buildSqlInsert('jawaban_ujian'," WHERE no_formulir='$no_formulir'");

                    $sql = $sql."--\n";
                    $sql =$sql. "-- Table: nilai_ujian_masuk\n";
                    $sql =$sql. "--\n";
                    $sql =$sql.$this->buildSqlInsert('nilai_ujian_masuk'," WHERE no_formulir='$no_formulir'");

                    $sql =$sql."--\n";
                    $sql =$sql. "-- Table: register_mahasiswa\n";
                    $sql =$sql. "--\n";
                    $sql =$sql.$this->buildSqlInsert('register_mahasiswa'," WHERE no_formulir='$no_formulir'");                           

                    $sql =$sql."--\n";
                    $sql =$sql. "-- Table: profiles_mahasiswa\n";
                    $sql =$sql. "--\n";
                    $sql=$sql.$this->buildSqlInsert('profiles_mahasiswa'," WHERE no_formulir='$no_formulir'");  

                    $sql =$sql."--\n";
                    $sql =$sql. "-- Table: data_konversi\n";
                    $sql =$sql. "--\n";
                    $sql=$sql.$this->buildSqlInsert('data_konversi'," WHERE nim='$nim'");  

                    $sql =$sql."--\n";
                    $sql =$sql. "-- Table: nilai_konversi\n";
                    $sql =$sql. "--\n";

                    $str = "SELECT iddata_konversi FROM data_konversi WHERE nim='$nim'";
                    $r=$this->DB->query($str);
                    $row=$r->fetch_assoc();
                    $iddata_konversi=isset($row['iddata_konversi'])?$row['iddata_konversi']:'';
                    $sql =$sql.$this->buildSqlInsert('nilai_konversi2'," WHERE iddata_konversi='$iddata_konversi'");

                    $sql =$sql."--\n";
                    $sql =$sql. "-- Table: dulang\n";
                    $sql =$sql. "--\n";
                    $sql=$sql.$this->buildSqlInsert('dulang'," WHERE nim='$nim'"); 

                    $sql = $sql."--\n";
                    $sql =$sql. "-- Table: krs\n";
                    $sql =$sql. "--\n";
                    $sql =$sql.$this->buildSqlInsert('krs'," WHERE nim='$nim'");

                    $sql = $sql."--\n";
                    $sql =$sql. "-- Table: krsmatkul\n";
                    $sql =$sql. "--\n";
                    $str = "SELECT idkrs FROM krs WHERE nim='$nim'";
                    $r=$this->DB->query($str);
                    while ($row=$r->fetch_assoc()) {           
                        $idkrs=$row['idkrs'];
                        $sql =$sql.$this->buildSqlInsert('krsmatkul'," WHERE idkrs='$idkrs'");
                    }   
                    
                    $sql = $sql."--\n";
                    $sql =$sql. "-- Table: kbmdetail\n";
                    $sql =$sql. "--\n";
                    $str = "SELECT km.idkrsmatkul FROM krs k, krsmatkul km,kbm_detail kbm WHERE k.idkrs=km.idkrs AND km.idkrsmatkul=kbm.idkrsmatkul AND k.nim='$nim'";
                    $r=$this->DB->query($str);
                    while ($row=$r->fetch_assoc()) {           
                        $idkrsmatkul=$row['idkrsmatkul'];
                        $sql =$sql.$this->buildSqlInsert('kbm_detail'," WHERE idkrsmatkul='$idkrsmatkul'");
                    }

                    $sql = $sql."--\n";
                    $sql =$sql. "-- Table: kelas_mhs_detail\n";
                    $sql =$sql. "--\n";
                    $str = "SELECT km.idkrsmatkul FROM krs k, krsmatkul km,kelas_mhs_detail kmd WHERE k.idkrs=km.idkrs AND km.idkrsmatkul=kmd.idkrsmatkul AND k.nim='$nim'";
                    $r=$this->DB->query($str);
                    while ($row=$r->fetch_assoc()) {           
                        $idkrsmatkul=$row['idkrsmatkul'];
                        $sql =$sql.$this->buildSqlInsert('kelas_mhs_detail'," WHERE idkrsmatkul='$idkrsmatkul'");
                    }

                    $sql = $sql."--\n";
                    $sql =$sql. "-- Table: kuesioner_jawaban\n";
                    $sql =$sql. "--\n";
                    $str = "SELECT km.idkrsmatkul FROM krs k, krsmatkul km,kuesioner_jawaban kj WHERE k.idkrs=km.idkrs AND km.idkrsmatkul=kj.idkrsmatkul AND k.nim='$nim'";
                    $r=$this->DB->query($str);
                    while ($row=$r->fetch_assoc()) {           
                        $idkrsmatkul=$row['idkrsmatkul'];
                        $sql =$sql.$this->buildSqlInsert('kuesioner_jawaban'," WHERE idkrsmatkul='$idkrsmatkul'");
                    }   

                    $sql =$sql."--\n";
                    $sql =$sql. "-- Table: gantinim\n";
                    $sql =$sql. "--\n";
                    $sql=$sql.$this->buildSqlInsert('gantinim'," WHERE nim_lama='$nim'"); 

                    $sql =$sql."--\n";
                    $sql =$sql. "-- Table: gantinirm\n";
                    $sql =$sql. "--\n";
                    $sql=$sql.$this->buildSqlInsert('gantinirm'," WHERE nirm_lama='$nirm'"); 

                    $sql =$sql."--\n";
                    $sql =$sql. "-- Table: jadwalsidang\n";
                    $sql =$sql. "--\n";
                    $sql=$sql.$this->buildSqlInsert('jadwalsidang'," WHERE nim='$nim'"); 
                    
                    $sql = $sql."--\n";
                    $sql =$sql. "-- Table: nilai_absensi\n";
                    $sql =$sql. "--\n";
                    $str = "SELECT km.idkrsmatkul FROM krs k, krsmatkul km,nilai_absensi na WHERE k.idkrs=km.idkrs AND km.idkrsmatkul=na.idkrsmatkul AND k.nim='$nim'";
                    $r=$this->DB->query($str);
                    while ($row=$r->fetch_assoc()) {           
                        $idkrsmatkul=$row['idkrsmatkul'];
                        $sql =$sql.$this->buildSqlInsert('nilai_absensi'," WHERE idkrsmatkul='$idkrsmatkul'");
                    }

                    $sql = $sql."--\n";
                    $sql =$sql. "-- Table: nilai_imported\n";
                    $sql =$sql. "--\n";
                    $str = "SELECT km.idkrsmatkul FROM krs k, krsmatkul km,nilai_imported ni WHERE k.idkrs=km.idkrs AND km.idkrsmatkul=ni.idkrsmatkul AND k.nim='$nim'";
                    $r=$this->DB->query($str);
                    while ($row=$r->fetch_assoc()) {           
                        $idkrsmatkul=$row['idkrsmatkul'];
                        $sql =$sql.$this->buildSqlInsert('nilai_imported'," WHERE idkrsmatkul='$idkrsmatkul'");
                    }

                    $sql = $sql."--\n";
                    $sql =$sql. "-- Table: nilai_matakuliah\n";
                    $sql =$sql. "--\n";
                    $str = "SELECT km.idkrsmatkul FROM krs k, krsmatkul km,nilai_matakuliah nm WHERE k.idkrs=km.idkrs AND km.idkrsmatkul=nm.idkrsmatkul AND k.nim='$nim'";
                    $r=$this->DB->query($str);
                    while ($row=$r->fetch_assoc()) {           
                        $idkrsmatkul=$row['idkrsmatkul'];
                        $sql =$sql.$this->buildSqlInsert('nilai_matakuliah'," WHERE idkrsmatkul='$idkrsmatkul'");
                    }

                    $sql = $sql."--\n";
                    $sql =$sql. "-- Table: nilai_quiz\n";
                    $sql =$sql. "--\n";
                    $str = "SELECT km.idkrsmatkul FROM krs k, krsmatkul km,nilai_quiz nq WHERE k.idkrs=km.idkrs AND km.idkrsmatkul=nq.idkrsmatkul AND k.nim='$nim'";
                    $r=$this->DB->query($str);
                    while ($row=$r->fetch_assoc()) {           
                        $idkrsmatkul=$row['idkrsmatkul'];
                        $sql =$sql.$this->buildSqlInsert('nilai_quiz'," WHERE idkrsmatkul='$idkrsmatkul'");
                    }

                    $sql = $sql."--\n";
                    $sql =$sql. "-- Table: nilai_uas\n";
                    $sql =$sql. "--\n";
                    $str = "SELECT km.idkrsmatkul FROM krs k, krsmatkul km,nilai_uas nu WHERE k.idkrs=km.idkrs AND km.idkrsmatkul=nu.idkrsmatkul AND k.nim='$nim'";
                    $r=$this->DB->query($str);
                    while ($row=$r->fetch_assoc()) {           
                        $idkrsmatkul=$row['idkrsmatkul'];
                        $sql =$sql.$this->buildSqlInsert('nilai_uas'," WHERE idkrsmatkul='$idkrsmatkul'");
                    }

                    $sql = $sql."--\n";
                    $sql =$sql. "-- Table: nilai_uts\n";
                    $sql =$sql. "--\n";
                    $str = "SELECT km.idkrsmatkul FROM krs k, krsmatkul km,nilai_uts nu WHERE k.idkrs=km.idkrs AND km.idkrsmatkul=nu.idkrsmatkul AND k.nim='$nim'";
                    $r=$this->DB->query($str);
                    while ($row=$r->fetch_assoc()) {           
                        $idkrsmatkul=$row['idkrsmatkul'];
                        $sql =$sql.$this->buildSqlInsert('nilai_uts'," WHERE idkrsmatkul='$idkrsmatkul'");
                    }

                    $sql = $sql."--\n";
                    $sql =$sql. "-- Table: pendaftaran_konsentrasi\n";
                    $sql =$sql. "--\n";
                    $sql =$sql.$this->buildSqlInsert('pendaftaran_konsentrasi'," WHERE nim='$nim'");

                    $sql = $sql."--\n";
                    $sql =$sql. "-- Table: perpanjangan_studi\n";
                    $sql =$sql. "--\n";
                    $sql =$sql.$this->buildSqlInsert('perpanjangan_studi'," WHERE nim='$nim'");

                    $sql = $sql."--\n";
                    $sql =$sql. "-- Table: pindahkelas\n";
                    $sql =$sql. "--\n";
                    $sql =$sql.$this->buildSqlInsert('pindahkelas'," WHERE nim='$nim'");

                    $sql = $sql."--\n";
                    $sql =$sql. "-- Table: pkrs\n";
                    $sql =$sql. "--\n";
                    $sql =$sql.$this->buildSqlInsert('pkrs'," WHERE nim='$nim'");

                    $sql = $sql."--\n";
                    $sql =$sql. "-- Table: profiles_ortu\n";
                    $sql =$sql. "--\n";
                    $sql =$sql.$this->buildSqlInsert('profiles_ortu'," WHERE nim='$nim'");

                    $sql = $sql."--\n";
                    $sql =$sql. "-- Table: rekap_laporan_pembayaran_per_semester\n";
                    $sql =$sql. "--\n";
                    $sql =$sql.$this->buildSqlInsert('rekap_laporan_pembayaran_per_semester'," WHERE nim='$nim'");

                    $sql = $sql."--\n";
                    $sql =$sql. "-- Table: rekap_status_mahasiswa\n";
                    $sql =$sql. "--\n";
                    $sql =$sql.$this->buildSqlInsert('rekap_status_mahasiswa'," WHERE nim='$nim'");

                    $sql = $sql."--\n";
                    $sql =$sql. "-- Table: transaksi\n";
                    $sql =$sql. "--\n";
                    $sql =$sql.$this->buildSqlInsert('transaksi'," WHERE no_formulir='$no_formulir'");

                    $sql = $sql."--\n";
                    $sql =$sql. "-- Table: transaksi_detail\n";
                    $sql =$sql. "--\n";
                    $str = "SELECT no_transaksi FROM transaksi WHERE no_formulir='$no_formulir'";
                    $r=$this->DB->query($str);
                    while ($row=$r->fetch_assoc()) {           
                        $no_transaksi=$row['no_transaksi'];
                        $sql =$sql.$this->buildSqlInsert('transaksi_detail'," WHERE no_transaksi='$no_transaksi'");
                    }

                    $sql = $sql."--\n";
                    $sql =$sql. "-- Table: transaksi_api\n";
                    $sql =$sql. "--\n";
                    $sql =$sql.$this->buildSqlInsert('transaksi_api'," WHERE no_formulir='$no_formulir'");

                    $sql = $sql."--\n";
                    $sql =$sql. "-- Table: transaksi_sp\n";
                    $sql =$sql. "--\n";
                    $sql =$sql.$this->buildSqlInsert('transaksi_sp'," WHERE nim='$nim'");

                    $sql = $sql."--\n";
                    $sql =$sql. "-- Table: transaksi_cuti\n";
                    $sql =$sql. "--\n";
                    $sql =$sql.$this->buildSqlInsert('transaksi_cuti'," WHERE nim='$nim'");

                    $sql = $sql."--\n";
                    $sql =$sql. "-- Table: transkrip_asli\n";
                    $sql =$sql. "--\n";
                    $sql =$sql.$this->buildSqlInsert('transkrip_asli'," WHERE nim='$nim'");

                    $sql = $sql."--\n";
                    $sql =$sql. "-- Table: transkrip_asli_detail\n";
                    $sql =$sql. "--\n";
                    $sql =$sql.$this->buildSqlInsert('transkrip_asli_detail'," WHERE nim='$nim'");
                    
                    $sql = $sql."--\n";
                    $sql =$sql. "-- Table: tweets\n";
                    $sql =$sql. "--\n";
                    $sql =$sql.$this->buildSqlInsert('tweets'," WHERE userid='$nim'");

                    $sql = $sql."--\n";
                    $sql =$sql. "-- Table: tweetscomment\n";
                    $sql =$sql. "--\n";
                    $sql =$sql.$this->buildSqlInsert('tweetscomment'," WHERE userid='$nim'");

                    fwrite($fp,$str);
                    $this->txtOutput->Text=$sql;
                    fclose($fp);
                }else{
                    throw new Exception("File ($filename) open failed.");
                }                       
                
            break;
            default :
                throw new Exception ('Mode Export data berdasarkan mahasiswa hanya tersedia (nim|no_formulir|nirm');
        }
    }

}
// class CExportData extends MainPageSA {    
// 	public function onLoad($param) {		
// 		parent::onLoad($param);				        
// 		$this->showVariable=true;       
// 		if (!$this->IsPostBack&&!$this->IsCallBack) {	           
//             if (!isset($_SESSION['currentPageExportData'])||$_SESSION['currentPageExportData']['page_name']!='sa.settings.ExportData') {
// 				$_SESSION['currentPageExportData']=array('page_name'=>'sa.settings.ExportData','page_num'=>0);												
// 			}            
// 		}
// 	}     
//     public function cekNIM ($sender,$param) {     
//         $nim=addslashes($param->Value);     
//         if ($nim != '') {
//             try {
//                 $str = "SELECT nim FROM v_datamhs vdm  WHERE vdm.nim='$nim'";
//                 $this->DB->setFieldTable(array('nim'));
//                 $r=$this->DB->getRecord($str);             
//                 if (!isset($r[1])) {
//                     throw new Exception ("Mahasiswa Dengan NIM ($nim) tidak terdaftar di Portal.");
//                 }
//             }catch (Exception $e) {
//                 $param->IsValid=false;
//                 $sender->ErrorMessage=$e->getMessage();
//             }   
//         }   
//     }
//     public function exportData ($sender,$param) {
//         if ($this->IsValid) {
//             switch ($sender->getId()) {
//                 case 'btnSaveExportNIM' :
//                     $nim=addslashes($this->txtNIM->Text);
//                     $str = "";
//                 break;
//             }   
//         }
//     }


//     function Export_Database($host,$user,$pass,$name,  $tables=false, $backup_name=false )     {
//         $mysqli = new mysqli($host,$user,$pass,$name); 
//         $mysqli->select_db($name); 
//         $mysqli->query("SET NAMES 'utf8'");

//         $queryTables    = $mysqli->query('SHOW TABLES'); 
//         while($row = $queryTables->fetch_row()) 
//         { 
//             $target_tables[] = $row[0]; 
//         }   
//         if($tables !== false) 
//         { 
//             $target_tables = array_intersect( $target_tables, $tables); 
//         }
//         foreach($target_tables as $table)
//         {
//             $result         =   $mysqli->query('SELECT * FROM '.$table);  
//             $fields_amount  =   $result->field_count;  
//             $rows_num=$mysqli->affected_rows;     
//             $res            =   $mysqli->query('SHOW CREATE TABLE '.$table); 
//             $TableMLine     =   $res->fetch_row();
//             $content        = (!isset($content) ?  '' : $content) . "\n\n".$TableMLine[1].";\n\n";

            // for ($i = 0, $st_counter = 0; $i < $fields_amount;   $i++, $st_counter=0) 
            // {
            //     while($row = $result->fetch_row())  
            //     { //when started (and every after 100 command cycle):
            //         if ($st_counter%100 == 0 || $st_counter == 0 )  
            //         {
            //                 $content .= "\nINSERT INTO ".$table." VALUES";
            //         }
            //         $content .= "\n(";
            //         for($j=0; $j<$fields_amount; $j++)  
            //         { 
            //             $row[$j] = str_replace("\n","\\n", addslashes($row[$j]) ); 
            //             if (isset($row[$j]))
            //             {
            //                 $content .= '"'.$row[$j].'"' ; 
            //             }
            //             else 
            //             {   
            //                 $content .= '""';
            //             }     
            //             if ($j<($fields_amount-1))
            //             {
            //                     $content.= ',';
            //             }      
            //         }
            //         $content .=")";
            //         //every after 100 command cycle [or at last line] ....p.s. but should be inserted 1 cycle eariler
            //         if ( (($st_counter+1)%100==0 && $st_counter!=0) || $st_counter+1==$rows_num) 
            //         {   
            //             $content .= ";";
            //         } 
            //         else 
            //         {
            //             $content .= ",";
            //         } 
            //         $st_counter=$st_counter+1;
            //     }
            // } $content .="\n\n\n";
        // }
//         //$backup_name = $backup_name ? $backup_name : $name."___(".date('H-i-s')."_".date('d-m-Y').")__rand".rand(1,11111111).".sql";
//         $backup_name = $backup_name ? $backup_name : $name.".sql";
//         header('Content-Type: application/octet-stream');   
//         header("Content-Transfer-Encoding: Binary"); 
//         header("Content-disposition: attachment; filename=\"".$backup_name."\"");  
//         echo $content; exit;
//     }
// }