<?php
prado::using ('Application.pagecontroller.k.perkuliahan.CKUM');
class KUM extends CKUM {    
	public function onLoad($param) {		
		parent::onLoad($param);		                                   
    }
	public function printOutR ($sender, $param) {
        $idkrs=$this->getDataKeyField($sender, $this->RepeaterS);
        $dataidkrs[$idkrs]=$idkrs;
        $this->createObj('reportkrs');
        $this->linkOutput->Text='';
        $this->linkOutput->NavigateUrl='#';
        
        switch ($_SESSION['outputreport']) {
            case  'summarypdf' :
                $messageprintout="Mohon maaf Print out pada mode summary pdf tidak kami support.";                
            break;
            case  'summaryexcel' :
                $messageprintout="Mohon maaf Print out pada mode summary excel tidak kami support.";                
            break;
            case  'excel2007' :
                $messageprintout="Mohon maaf Print out pada mode excel belum kami support.";                 
            break;
            case  'pdf' :
                $messageprintout="";
                $str = "SELECT krs.idkrs,vdm.no_formulir,vdm.nim,vdm.nirm,vdm.nama_mhs,vdm.jk,vdm.tempat_lahir,vdm.tanggal_lahir,vdm.kjur,vdm.nama_ps,vdm.idkonsentrasi,k.nama_konsentrasi,vdm.tahun_masuk,vdm.semester_masuk,iddosen_wali,d.idkelas,d.k_status,krs.idsmt,krs.tahun,krs.tasmt,krs.sah FROM krs JOIN dulang d ON (d.nim=krs.nim) LEFT JOIN v_datamhs vdm ON (krs.nim=vdm.nim) LEFT JOIN konsentrasi k ON (vdm.idkonsentrasi=k.idkonsentrasi) WHERE krs.idkrs='$idkrs'";
                $this->DB->setFieldTable(array('idkrs','no_formulir','nim','nirm','nama_mhs','jk','tempat_lahir','tanggal_lahir','kjur','nama_ps','idkonsentrasi','nama_konsentrasi','tahun_masuk','semester_masuk','iddosen_wali','idkelas','k_status','idsmt','tahun','tasmt','sah'));
                $r=$this->DB->getRecord($str);	           
                $dataReport=$r[1];
                
                $dataReport['nama_ps']=$_SESSION['daftar_jurusan'][$dataReport['kjur']];                
                $nama_tahun = $this->DMaster->getNamaTA($dataReport['tahun']);   
                $nama_semester = $this->setup->getSemester($dataReport['idsmt']);
                $dataReport['nama_tahun']=$nama_tahun; 
                $dataReport['nama_semester']=$nama_semester;
                
                $nama_dosen=$this->DMaster->getNamaDosenWaliByID($dataReport['iddosen_wali']);				                    
                $dataReport['nama_dosen']=$nama_dosen;
                
                $kaprodi=$this->KRS->getKetuaPRODI($dataReport['kjur']);
                $dataReport['nama_kaprodi']=$kaprodi['nama_dosen'];
                $dataReport['jabfung_kaprodi']=$kaprodi['nama_jabatan'];
                $dataReport['nidn_kaprodi']=$kaprodi['nidn'];
                
                $dataReport['linkoutput']=$this->linkOutput;
                $this->report->setDataReport($dataReport); 
                $this->report->setMode($_SESSION['outputreport']);
                
                $this->printKUM($_SESSION['currentPageKUM']['jenisujian'], $dataidkrs, $this->KRS, $this->DMaster);                
            break;
        }
        $this->lblMessagePrintout->Text = $messageprintout;
        $this->lblPrintout->Text='Kartu Ujian Mahasiswa';
        $this->modalPrintOut->show();
    }
	/**
     * digunakan untuk memprint Kartu ujian mahasiswa
     * @param type $jenisujian UTS atau UAS
     * @param type $dataidkrs 
     * @param type $objKRS objek KRS
     * @param type $objDMaster objek data master
     */
    public function printKUM ($jenisujian, $dataidkrs, $objKRS, $objDMaster) {
        
        switch ($this->report->getDriver()) {
            case 'excel2003' :               
            case 'excel2007' :                
//                $this->printOut("kum_$nim");
            break;
            case 'summarypdf' :
                $this->setMode('pdf');
                $rpt=$this->report->rpt;
                
                $rpt->setTitle('Kartu Ujian Mahasiswa');
				$rpt->setSubject('Kartu Ujian Mahasiswa');
                
                while (list($idkrs, $value)=each($dataidkrs)) {                    
                    $rpt->AddPage();
                    $this->setHeaderPT();
                    
                    $row=$this->currentRow;
                    $row+=6;
                    $rpt->SetFont ('helvetica','B',12);	
                    $rpt->setXY(3, $row);			
                    $kartu=($jenisujian=='uts')?'KARTU UJIAN TENGAH SEMESTER (UTS)':'KARTU UJIAN AKHIR SEMESTER (UAS)';
                    $rpt->Cell(0, $row, $kartu,0,0,'C');
                    
                    $str = "SELECT krs.idkrs,vdm.no_formulir,vdm.nim,vdm.nirm,vdm.nama_mhs,vdm.jk,vdm.tempat_lahir,vdm.tanggal_lahir,vdm.kjur,vdm.nama_ps,vdm.idkonsentrasi,k.nama_konsentrasi,vdm.tahun_masuk,vdm.semester_masuk,iddosen_wali,d.idkelas,d.k_status,krs.idsmt,krs.tahun,krs.tasmt,krs.sah FROM krs JOIN dulang d ON (d.nim=krs.nim) LEFT JOIN v_datamhs vdm ON (krs.nim=vdm.nim) LEFT JOIN konsentrasi k ON (vdm.idkonsentrasi=k.idkonsentrasi) WHERE krs.idkrs='$idkrs'";
                    $this->db->setFieldTable(array('idkrs','no_formulir','nim','nirm','nama_mhs','jk','tempat_lahir','tanggal_lahir','kjur','nama_ps','idkonsentrasi','nama_konsentrasi','tahun_masuk','semester_masuk','iddosen_wali','idkelas','k_status','idsmt','tahun','tasmt','sah'));
                    $r=$this->db->getRecord($str);	           
                    $dataReport=$r[1];

                    $dataReport['nama_ps']=$_SESSION['daftar_jurusan'][$dataReport['kjur']];                
                    $nama_tahun = $objDMaster->getNamaTA($dataReport['tahun']);   
                    $nama_semester = $this->setup->getSemester($dataReport['idsmt']);
                    $dataReport['nama_tahun']=$nama_tahun; 
                    $dataReport['nama_semester']=$nama_semester;

                    $nama_dosen=$objDMaster->getNamaDosenWaliByID($dataReport['iddosen_wali']);				                    
                    $dataReport['nama_dosen']=$nama_dosen;

                    $kaprodi=$objKRS->getKetuaPRODI($dataReport['kjur']);
                    $dataReport['nama_kaprodi']=$kaprodi['nama_dosen'];
                    $dataReport['jabfung_kaprodi']=$kaprodi['nama_jabatan'];
                    $dataReport['nidn_kaprodi']=$kaprodi['nidn'];
                    $row+=6;
                    $rpt->SetFont ('helvetica','B',8);	
                    $rpt->setXY(3, $row);			
                    $rpt->Cell(0, $row,'NIM');
                    $rpt->SetFont ('helvetica','',8);
                    $rpt->setXY(38, $row);			
                    $rpt->Cell(0, $row,': '.$dataReport['nama_mhs'].' ('.$dataReport['jk'].')');
                    $rpt->SetFont ('helvetica','B',8);	
                    $rpt->setXY(105, $row);			
                    $rpt->Cell(0, $row,'Semester');
                    $rpt->SetFont ('helvetica','',8);
                    $rpt->setXY(130, $row);			
                    $rpt->Cell(0, $row,': '.$dataReport['nama_ps'].' / S-1');
                    $row+=3;
                    $rpt->setXY(3, $row);			
                    $rpt->SetFont ('helvetica','B',8);	
                    $rpt->Cell(0, $row,'Nama');
                    $rpt->SetFont ('helvetica','',8);
                    $rpt->setXY(38, $row);			
                    $rpt->Cell(0, $row,': '.$dataReport['nama_dosen']);				
                    $rpt->SetFont ('helvetica','B',8);	
                    $rpt->setXY(105, $row);			
                    $rpt->Cell(0, $row,'T.A');
                    $rpt->SetFont ('helvetica','',8);
                    $rpt->setXY(130, $row);			
                    $rpt->Cell(0, $row,': '.$dataReport['nim']);
                    $row+=3;
                    $rpt->setXY(3, $row);			
                    $rpt->SetFont ('helvetica','B',8);	
                    $rpt->Cell(0, $row,'Program Studi');				
                    $rpt->SetFont ('helvetica','',8);
                    $rpt->setXY(38, $row);			
                    $rpt->Cell(0, $row,": $nama_semester / $nama_tahun");				
                    $rpt->SetFont ('helvetica','B',8);	
                    $rpt->setXY(105, $row);			
                    $rpt->Cell(0, $row,'NIRM');
                    $rpt->SetFont ('helvetica','',8);
                    $rpt->setXY(130, $row);			
                    $rpt->Cell(0, $row,': '.$dataReport['nirm']);			

                    $row+=20;
                    $rpt->SetFont ('helvetica','B',8);
                    $rpt->setXY(3, $row);			
                    $rpt->Cell(8, 5, 'NO', 1, 0, 'C');				
                    $rpt->Cell(15, 5, 'KODE', 1, 0, 'C');								
                    $rpt->Cell(70, 5, 'MATAKULIAH', 1, 0, 'C');							
                    $rpt->Cell(8, 5, 'SKS', 1, 0, 'C');				
                    $rpt->Cell(8, 5, 'SMT', 1, 0, 'C');				
                    $rpt->Cell(60, 5, 'PENGAWAS', 1, 0, 'C');						
                    $rpt->Cell(15, 5, 'TGL', 1, 0, 'C');
                    $rpt->Cell(15, 5, 'TTD', 1, 0, 'C');

                    $daftar_matkul=$objKRS->getDetailKRS($idkrs);
                    $totalSks=0;
                    $row+=5;				
                    $rpt->SetFont ('helvetica','',8);
                    while (list($k, $v)=each($daftar_matkul)) {
                        $rpt->setXY(3, $row);	
                        $rpt->Cell(8, 5, $v['no'], 1, 0, 'C');				
                        $rpt->Cell(15, 5, $v['kmatkul'], 1, 0, 'C');		
                        $flag='';
                        if ($jenisujian=='uas') {
                            $idkrsmatkul=$v['idkrsmatkul'];
                            $str = "kbm_detail WHERE idkrsmatkul='$idkrsmatkul' AND kehadiran='hadir'";										
                            $flag=' *';
                            if ($totalpertemuan=$this->db->getCountRowsOfTable($str,'idkrsmatkul')>=1) {
                                $minimal=round(($totalpertemuan/14)*100);													
                                $flag=$minimal<75?'*':'';
                            }
                        }					
                        $rpt->Cell(70, 5, $v['nmatkul'].$flag , 1, 0, 'L');							
                        $rpt->Cell(8, 5, $v['sks'], 1, 0, 'C');				
                        $rpt->Cell(8, 5, $v['semester'], 1, 0, 'C');				
                        $rpt->Cell(60, 5, '', 1, 0, 'L');						
                        $rpt->Cell(15, 5, '', 1, 0, 'C');
                        $rpt->Cell(15, 5, '', 1, 0, 'C');					
                        $totalSks+=$v['sks'];
                        $row+=5;
                    }
					$row+=3;
					$rpt->setXY(3, $row);	
					$rpt->Cell(8, 5, 'TOTAL SKS', 1, 0, 'C');				
					$rpt->Cell(15, 5, $totalSks, 1, 0, 'C');						
					
                    $row+=3;
                    $rpt->setXY(3, $row);	
                    $rpt->SetFont ('helvetica','',6);
                    $rpt->Cell(70, 5, 'Catatan : Tanda "*" memiliki arti absensi Mahasiswa kurang dari 75%.' , 0, 0, 'L');	

                    $row+=5;
                    $rpt->SetFont ('helvetica','B',8);
                    $rpt->setXY(120, $row);			
                    $rpt->Cell(80, 5, 'Mengetahui,',0,0,'L');				

                    $row+=5;
                    $rpt->setXY(120, $row);			
                    $tanggal=$this->TGL->tanggal('j F Y');				
                    $rpt->Cell(80, 5, "Tanjungpinang, $tanggal",0,0,'L');

                    $row+=5;				
                    $rpt->setXY(120, $row);			
                    $rpt->Cell(80, 5, 'Ketua Program Studi '.$dataReport['nama_ps'],0,0,'L');								

                    $row+=20;				
                    $rpt->setXY(120, $row);			
                    $rpt->Cell(80, 5, $dataReport['nama_kaprodi'],0,0,'L');

                    $row+=5;							
                    $rpt->setXY(120, $row);
                    $nama_jabatan=$dataReport['jabfung_kaprodi'];
                    $nidn=$dataReport['nidn_kaprodi'];
                    $rpt->Cell(80, 5, "$nama_jabatan NIDN : $nidn",0,0,'L');
                    
                }
                $this->printOut("kum");
            break;
            case 'pdf' :
                $rpt=$this->report->rpt;
                $rpt->AddPage();
                $this->report->setHeaderPT();
                 
                $nim = $this->report->dataReport['nim'];
                $nama_tahun=$this->report->dataReport['nama_tahun'];
                $nama_semester=$this->report->dataReport['nama_semester'];
        
                $rpt->setTitle('Kartu Ujian Mahasiswa');
				$rpt->setSubject('Kartu Ujian Mahasiswa');
                
                
                $row=$this->report->currentRow;
				$row+=6;
				$rpt->SetFont ('helvetica','B',12);	
				$rpt->setXY(3, $row);			
                $kartu=' KARTU STUDI MAHASISWA (KSM)';
				$rpt->Cell(0, $row, $kartu,0,0,'C');
				
				$row+=3;
				$rpt->setXY(3, $row);	
				$rpt->Cell(0, $row,'UJIAN TENGAH  SEMESTER DAN UJIAN AKHIR SEMESTER',0,0,'C');
				
				$row+=6;
				$rpt->SetFont ('helvetica','B',8);	
				$rpt->setXY(6, $row);			
				$rpt->Cell(0, $row,'Nim');
				$rpt->SetFont ('helvetica','',8);
				$rpt->setXY(38, $row);			
				$rpt->Cell(0, $row,': '.$this->report->dataReport['nim']);
				$rpt->SetFont ('helvetica','B',8);	
				$rpt->setXY(105, $row);			
				$rpt->Cell(0, $row,'Semester');
				$rpt->SetFont ('helvetica','',8);
				$rpt->setXY(130, $row);			
				$rpt->Cell(0, $row,': '."$nama_semester");
				$row+=3;
				$rpt->setXY(6, $row);			
				$rpt->SetFont ('helvetica','B',8);	
				$rpt->Cell(0, $row,'Nama');
				$rpt->SetFont ('helvetica','',8);
				$rpt->setXY(38, $row);			
				$rpt->Cell(0, $row,': '.$this->report->dataReport['nama_mhs']);				
				$rpt->SetFont ('helvetica','B',8);	
				$rpt->setXY(105, $row);			
				$rpt->Cell(0, $row,'T.A');
				$rpt->SetFont ('helvetica','',8);
				$rpt->setXY(130, $row);			
				$rpt->Cell(0, $row,': '."$nama_tahun");
				$row+=3;
				$rpt->setXY(6, $row);			
				$rpt->SetFont ('helvetica','B',8);	
				$rpt->Cell(0, $row,'Program Studi');				
				$rpt->SetFont ('helvetica','',8);
				$rpt->setXY(38, $row);			
				$rpt->Cell(0, $row,': '.$this->report->dataReport['nama_ps']);						
				
                $row+=20;
				$rpt->SetFont ('helvetica','B',8);
				$rpt->setXY(6, $row);			
				$rpt->Cell(8, 10, 'NO', 1, 0, 'C');				
				$rpt->Cell(15, 10, 'KODE', 1, 0, 'C');								
				$rpt->Cell(70, 10, 'MATAKULIAH', 1, 0, 'C');							
				$rpt->Cell(8, 10, 'SKS', 1, 0, 'C');							
				$rpt->Cell(60, 5, 'PARAF PENGAWAS', 1, 0, 'C');
				$row+=5;
				$rpt->SetFont ('helvetica','B',8);
				$rpt->setXY(107, $row);															
				$rpt->Cell(30, 5, 'UTS', 1, 0, 'C');				
                $rpt->Cell(30, 5, 'UAS', 1, 0, 'C');
				
                $daftar_matkul=$objKRS->getDetailKRS($this->report->dataReport['idkrs']);
				$totalSks=0;
				$row+=5;				
				$rpt->SetFont ('helvetica','',8);
                while (list($k, $v)=each($daftar_matkul)) {
                    $rpt->setXY(6, $row);	
					$rpt->Cell(8, 8, $v['no'], 1, 0, 'C');				
					$rpt->Cell(15, 8, $v['kmatkul'], 1, 0, 'C');		
					$flag='';
					if ($jenisujian=='uas') {
						$idkrsmatkul=$v['idkrsmatkul'];
						$str = "kbm_detail WHERE idkrsmatkul='$idkrsmatkul' AND kehadiran='hadir'";										
						$flag=' *';
						if ($totalpertemuan=$this->db->getCountRowsOfTable($str,'idkrsmatkul')>=1) {
							$minimal=round(($totalpertemuan/14)*100);													
							$flag=$minimal<75?'*':'';
						}
					}					
					$rpt->Cell(70, 8, $v['nmatkul'].$flag , 1, 0, 'L');							
					$rpt->Cell(8, 8, $v['sks'], 1, 0, 'C');							
					$rpt->Cell(30, 8, '', 1, 0, 'L');	
					$rpt->Cell(30, 8, '', 1, 0, 'L');						
					$totalSks+=$v['sks'];
					$row+=8;
				}
				$row+=0;
				$rpt->setXY(6, $row);	
				$rpt->Cell(93, 5, 'TOTAL SKS', 1, 0, 'C');				
				$rpt->Cell(8, 5, $totalSks, 1, 0, 'C');	
				
                $row+=6;
				$rpt->setXY(3, $row);	
				$rpt->SetFont ('helvetica','',8);
				$rpt->Cell(70, 5, 'Catatan : - KSM ini sah apabila telah ditandatangani oleh Mahasiswa dan Bagian Akademik serta telah dibubuhi stempel ' , 0, 0, 'L');	
				$row+=3;
				$rpt->setXY(17, $row);	
				$rpt->SetFont ('helvetica','',8);
				$rpt->Cell(70, 5, 'dan sudah di verifikasi oleh bagian keuangan.' , 0, 0, 'L');
				$row+=3;
				$rpt->setXY(15, $row);	
				$rpt->SetFont ('helvetica','',8);
				$rpt->Cell(70, 5, '- KSM merupakan bukti sah pengambilan mata kuliah & syarat untuk mengikuti Ujian Tengah Semester (UTS)' , 0, 0, 'L');
				$row+=3;
				$rpt->setXY(15, $row);	
				$rpt->SetFont ('helvetica','',8);
				$rpt->Cell(70, 5, '  dan Ujian Akhir Semester (UAS) serta menjadi syarat pedaftaran semester berikutnya.' , 0, 0, 'L');

												
				$row+=10;
				$rpt->setXY(15, $row);
				$rpt->Cell(70, 5, "Mahasiswa",0,0,'C');				
				$tanggal=$this->TGL->tanggal('j F Y');				
				$rpt->Cell(80, 5, "Tanjungpinang, $tanggal",0,0,'C');
				
				$row+=5;				
				$rpt->setXY(85, $row);			
				$rpt->Cell(80, 5, 'Pembantu Ketua I',0,0,'C');								
				
				$row+=20;				
				$rpt->setXY(15, $row);	
				$rpt->Cell(70, 5, $this->report->dataReport['nama_mhs'],0,0,'C');
				$rpt->Cell(80, 5, 'Aggry Saputra, S.T., M.T',0,0,'C');

				$row+=10;				
				$rpt->setXY(30, $row);	
				$rpt->Cell(50, 5, 'VERIFIKASI UTS','T,L,R',0,'C');
				$rpt->setXY(100, $row);
				$rpt->Cell(50, 5, 'VERIFIKASI UAS','T,L,R',0,'C');			
				$rpt->setXY(30, $row);	
				$rpt->Cell(50, 20, '','B,L,R',0,'C');
				$rpt->setXY(100, $row);
				$rpt->Cell(50, 20, '','B,L,R',0,'C');
				
                $this->report->printOut("kum_$nim");
            break;
        }
        $this->report->setLink($this->report->dataReport['linkoutput'],"Kartu Ujian Mahasiswa T.A $nama_tahun Semester $nama_semester");
    }
}
?>