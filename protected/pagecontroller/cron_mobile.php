<?php
function request($api_call)
{
  $ch = curl_init();

  curl_setopt($ch, CURLOPT_URL, $api_call);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json'
  ]);

  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

  $response = curl_exec($ch);
  if($response === false)
  {
    $response = [
      'rc' => '99',
      'msg' => curl_error($ch),
      'payload' => null,
    ];
    
  }
  else
  {
    $result = json_decode($response, true);
    if(isset($result['rc']))
    {
      $response = [
        'rc' => $result['rc'],
        'msg' => $result['msg'],
        'payload' => null,
      ];
    }
    else
    {
      $response = [
        'rc' => '0',
        'msg' => curl_error($ch),
        'payload' => $result,
      ];
    }
    
  }

  // close cURL resource, and free up system resources
  curl_close($ch);
  return $response;
}

$host = '127.0.0.1';
$user = 'root';
$password = '12345678';
$db = 'simak_stti';

// $host = '127.0.0.1';
// $user = 'sttindon_sistem';
// $password = 'rE{ac^7kJ{w-';
// $db = 'sttindon_ps';

$url = "http://mostti.wanayasa.net:17180/links/api2";
$salt = 'S771';
$h = '11'; //saat development
try {
  $conn = new PDO("mysql:host=$host;port=3306;dbname=$db", $user, $password);
  // set the PDO error mode to exception
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  
  # Mahasiswa
  $q = $conn->query ("SELECT 
    a.nim,
    b.nama_mhs,
    b.jk,    
    b.tempat_lahir,
    b.tanggal_lahir,
    b.alamat_rumah,
    b.telp_hp,
    c.email,
    a.idsmt,
    a.tahun,
    a.kjur,
    a.k_status
  FROM register_mahasiswa AS a
  JOIN formulir_pendaftaran AS b ON a.no_formulir=b.no_formulir
  JOIN profiles_mahasiswa AS c ON c.no_formulir=b.no_formulir
  WHERE synced=0  
  LIMIT 3
  ");
  $q->setFetchMode(PDO::FETCH_ASSOC);
  $daftar_mhs = $q->fetchAll();
  
  foreach ($daftar_mhs as $v) 
  {    
    $nim = $v['nim'];       
     
    $sn = mt_rand(); //timestamp
    if ($h != '00')
    {
      $h = md5("$sn|$salt|$nim");
    }    
    
    $api_call = "$url/mhs/get.php?h=$h&sn=$sn&nim=$nim";
    $response = request($api_call);    echo "$api_call <br>";

    $nama_mhs = urlencode($v['nama_mhs']);
    $tempat_lahir = urlencode($v['tempat_lahir']);
    $tanggal_lahir = $v['tanggal_lahir'];
    $jk = $v['jk'];
    $telp_hp = $v['telp_hp'];
    $alamat_rumah = urlencode($v['alamat_rumah']);
    $email = $v['email'];
    $kjur = $v['kjur'];
    $masuk_tahun = $v['tahun'];
    $masuk_semester = $v['idsmt'];
    $idkelas = $v['k_status'];
    $k_status = $v['k_status'];
    
    if($response['rc'] == '0') //mahasiswa ini ada
    {
      $sn = mt_rand(); //timestamp
      if ($h != '00')
      {
        $h = md5("$sn|$salt|$nim");
      }    
      $api_call = "$url/mhs/upd.php?h=$h&sn=$sn&nim=$nim&nama_mhs=$nama_mhs&tempat_lahir=$tempat_lahir&tanggal_lahir=$tanggal_lahir&jk=$jk&telp_hp=$telp_hp&alamat_rumah=$alamat_rumah&email=$email&kjur=$kjur&masuk_tahun=$masuk_tahun&masuk_semester=$masuk_semester&idkelas=$idkelas&k_status=$k_status";      
      $result = request($api_call);      echo "$api_call <br>";
    }
    else if ($response['rc'] == '99')
    {
      $result['msg'] = $response['msg'];
    }
    else
    { 
      $sn = mt_rand(); //timestamp
      if ($h != '00')
      {
        $h = md5("$sn|$salt|$nim");
      }    
      $api_call = "$url/mhs/add.php?h=$h&sn=$sn&nim=$nim&nama_mhs=$nama_mhs&tempat_lahir=$tempat_lahir&tanggal_lahir=$tanggal_lahir&jk=$jk&telp_hp=$telp_hp&alamat_rumah=$alamat_rumah&email=$email&kjur=$kjur&masuk_tahun=$masuk_tahun&masuk_semester=$masuk_semester&idkelas=$idkelas&k_status=$k_status";      
      $result = request($api_call);      echo "$api_call <br>";
    }
    
    $synced = $result['rc'] == '0' ? 1 : -1;
    $sql = "UPDATE register_mahasiswa SET synced=:synced,sync_msg=:sync_msg WHERE nim=$nim";
    $query = $conn->prepare($sql);
    
    $query->bindvalue(':synced', $synced);    
    $query->bindvalue(':sync_msg', $result['msg']);    
    $query->execute();
  }
   #krs
   $q = $conn->query ("SELECT 
   idkrs,
   tgl_krs,
   no_krs,
   nim,
   idsmt,
   tahun,
   tasmt,
   sah,
   tgl_disahkan
 FROM krs  
 WHERE idkrs=9101  
 LIMIT 3
 ");
 $q->setFetchMode(PDO::FETCH_ASSOC);
 $daftar_krs = $q->fetchAll();
 
 foreach ($daftar_krs as $v) 
 {    
   $idkrs = $v['idkrs'];
   $nim = $v['nim'];
   $no_krs = $v['no_krs'];
   $tgl_krs = $v['tgl_krs'];
   $tahun = $v['tahun'];
   $idsmt = $v['idsmt'];
   $sah = $v['sah'];

   $sn = mt_rand(); //timestamp    
   if ($h != '00')
   {
     $h = md5("$sn|$salt|$idkrs");
   }    
   $api_call = "$url/krs/get.php?h=$h&sn=$sn&idkrs=$idkrs";
   $response = request($api_call); echo "$api_call <br>";

   if($response['rc'] == '0') //krs ini ada
   {
     $sn = mt_rand(); //timestamp
     if ($h != '00')
     {
       $h = md5("$sn|$salt|$idkrs");
     }    
     $api_call = "$url/krs/upd.php?h=$h&sn=$sn&idkrs=$idkrs&nim=$nim&no_krs=$no_krs&tgl_krs=$tgl_krs&tahun=$tahun&idsmt=$idsmt&sah=$sah";      
     $result = request($api_call);      echo "$api_call <br>";
   }
   else if ($response['rc'] == '99')
   {
     $result['msg'] = $response['msg'];
   }
   else
   { 
     $sn = mt_rand(); //timestamp
     if ($h != '00')
     {
       $h = md5("$sn|$salt|$idkrs");
     }    
     $api_call = "$url/krs/add.php?h=$h&sn=$sn&idkrs=$idkrs&nim=$nim&no_krs=$no_krs&tgl_krs=$tgl_krs&tahun=$tahun&idsmt=$idsmt&sah=$sah";
     $result = request($api_call);    echo "$api_call <br>";
   }

   $q = $conn->query ("SELECT 
       idkrsmatkul,
       idpenyelenggaraan,
       kmatkul,
       batal        
     FROM v_krsmhs 
     WHERE idkrs='$idkrs'
   ");
   $q->setFetchMode(PDO::FETCH_ASSOC);
   $daftar_krs_matkul = $q->fetchAll();

   foreach($daftar_krs_matkul as $krsmatkul)
   {
     $idkrsmatkul = $krsmatkul['idkrsmatkul'];    
     $kmatkul = $krsmatkul['kmatkul'];
     $idpenyelenggaraan = $krsmatkul['idpenyelenggaraan'];
     $batal = $krsmatkul['batal'];

     $sn = mt_rand(); //timestamp      
     if ($h != '00')
     {
       $h = md5("$sn|$salt|$idkrsmatkul");
     }    
     $api_call = "$url/kmk/get.php?h=$h&sn=$sn&idkrsmatkul=$idkrsmatkul";
     $response = request($api_call); echo "$api_call <br>";
     
     if($response['rc'] == '0') //kmatkul ini ada
     {
       $sn = mt_rand(); //timestamp        
       if ($h != '00')
       {
         $h = md5("$sn|$salt|$idkrsmatkul");
       }    
       $api_call = "$url/kmk/upd.php?h=$h&sn=$sn&idkrs=$idkrs&idkrsmatkul=$idkrsmatkul&kmatkul=$kmatkul&idpenyelenggaraan=$idpenyelenggaraan&batal=$batal";      
       $response = request($api_call);      echo "$api_call <br>";
     }
     else if ($response['rc'] == '99')
     {
       $result['msg'] = "saat insert krsmatkul ($idkrsmatkul) dari idkrs $idkrs ada error " . $response['msg'];
     }
     else
     { 
       $sn = mt_rand(); //timestamp
       if ($h != '00')
       {
         $h = md5("$sn|$salt|$idkrsmatkul");
       }    
       $api_call = "$url/kmk/add.php?h=$h&sn=$sn&idkrs=$idkrs&idkrsmatkul=$idkrsmatkul&kmatkul=$kmatkul&idpenyelenggaraan=$idpenyelenggaraan&batal=$batal";
       $response = request($api_call);      echo "$api_call <br>";
     }
   }

   $synced = $result['rc'] == '0' ? 1 : -1;
   $sql = "UPDATE krs SET synced=:synced,sync_msg=:sync_msg WHERE idkrs=$idkrs";
   $query = $conn->prepare($sql);
   
   $query->bindvalue(':synced', $synced);    
   $query->bindvalue(':sync_msg', $result['msg']);    
   $query->execute();
 }
  #jadwal kuliah
  $q = $conn->query ("SELECT 
    a.idkelas_mhs,
    b.tahun,
    b.idsmt,
    b.kjur,
    b.kmatkul,
    a.idkelas,
    b.nidn,
    a.nama_kelas,
    a.hari,    
    a.nama_kelas,
    a.hari,
    a.jam_masuk,
    a.jam_keluar,
    a.idpengampu_penyelenggaraan,
    b.idpenyelenggaraan    
  FROM kelas_mhs AS a  
  JOIN v_pengampu_penyelenggaraan AS b ON a.idpengampu_penyelenggaraan=b.idpengampu_penyelenggaraan  
  WHERE synced=0
  LIMIT 3
  ");
  $q->setFetchMode(PDO::FETCH_ASSOC);
  $daftar_jadwal = $q->fetchAll();
  
  foreach($daftar_jadwal as $v)
  {
    $idkelas_mhs = $v['idkelas_mhs'];
    $tahun = $v['tahun'];
    $idsmt = $v['idsmt'];
    $kjur = $v['kjur'];
    $kmatkul = $v['kmatkul'];
    $idkelas = $v['idkelas'];
    $nidn = $v['nidn'];
    $nama_kelas = $v['nama_kelas'];
    $hari = $v['hari'];
    $jam_masuk = $v['jam_masuk'];
    $jam_keluar = $v['jam_keluar'];

    $sn = mt_rand(); //timestamp    
    if ($h != '00')
    {
      $h = md5("$sn|$salt|$idkelas_mhs");
    }    
    $api_call = "$url/jad/get.php?h=$h&sn=$sn&idkelas_mhs=$idkelas_mhs";
    $response = request($api_call); echo "$api_call <br>";
    
    if($response['rc'] == '0') //kelas mhs ini ada
    {
      $sn = mt_rand(); //timestamp      
      if ($h != '00')
      {
        $h = md5("$sn|$salt|$idkelas_mhs");
      }    
      $api_call = "$url/jad/upd.php?h=$h&sn=$sn&idkelas_mhs=$idkelas_mhs&tahun=$tahun&idsmt=$idsmt&kjur=$kjur&kmatkul=$kmatkul&idkelas=$idkelas&nidn=$nidn&nama_kelas=$nama_kelas&hari=$hari&jam_masuk=$jam_masuk&jam_keluar=$jam_keluar";
      
      $result = request($api_call);      echo "$api_call <br>";
    }
    else if ($response['rc'] == '99')
    {
      $result['msg'] = $response['msg'];
    }
    else
    { 
      $sn = mt_rand(); //timestamp
      if ($h != '00')
      {
        $h = md5("$sn|$salt|$idkelas_mhs");
      }    
      $api_call = "$url/jad/add.php?h=$h&sn=$sn&idkelas_mhs=$idkelas_mhs&tahun=$tahun&idsmt=$idsmt&kjur=$kjur&kmatkul=$kmatkul&idkelas=$idkelas&nidn=$nidn&nama_kelas=$nama_kelas&hari=$hari&jam_masuk=$jam_masuk&jam_keluar=$jam_keluar";
      $result = request($api_call);    echo "$api_call <br>";
    }

    $q = $conn->query ("SELECT 
        idkelas_mhs_detail,
        idkelas_mhs,
        idkrsmatkul  
      FROM kelas_mhs_detail 
      WHERE idkelas_mhs='$idkelas_mhs'
    ");
    $q->setFetchMode(PDO::FETCH_ASSOC);
    $daftar_peserta_kelas = $q->fetchAll();

    foreach ($daftar_peserta_kelas as $v) 
    {
      $idkelas_mhs_detail = $v['idkelas_mhs_detail'];
      $idkelas_mhs = $v['idkelas_mhs'];
      $idkrsmatkul = $v['idkrsmatkul'];
      
      $sn = mt_rand(); //timestamp      
      if ($h != '00')
      {
        $h = md5("$sn|$salt|$idkelas_mhs_detail");
      }    
      $api_call = "$url/kls/get.php?h=$h&sn=$sn&idkelas_mhs_detail=$idkelas_mhs_detail";
      $response = request($api_call); echo "$api_call <br>";      
      if($response['rc'] == '0') //id kelas mhs ini ada
      {
        $sn = mt_rand(); //timestamp
        if ($h != '00')
        {
          $h = md5("$sn|$salt|$idkelas_mhs_detail");
        }    
        $api_call = "$url/kls/upd.php?h=$h&sn=$sn&idkelas_mhs_detail=$idkelas_mhs_detail&idkelas_mhs=$idkelas_mhs&idkrsmatkul=$idkrsmatkul";
        $result = request($api_call);      echo "$api_call <br>";
      }
      else if ($response['rc'] == '99')
      {
        $result['msg'] = $response['msg'];
      }
      else
      { 
        $sn = mt_rand(); //timestamp        
        if ($h != '00')
        {
          $h = md5("$sn|$salt|$idkelas_mhs_detail");
        }    
        $api_call = "$url/kls/add.php?h=$h&sn=$sn&idkelas_mhs_detail=$idkelas_mhs_detail&idkelas_mhs=$idkelas_mhs&idkrsmatkul=$idkrsmatkul";
        $result = request($api_call);    echo "$api_call <br>";
      }
    }

    $synced = $result['rc'] == '0' ? 1 : -1;
    $sql = "UPDATE kelas_mhs SET synced=:synced,sync_msg=:sync_msg WHERE idkelas_mhs=$idkelas_mhs";
    $query = $conn->prepare($sql);
    
    $query->bindvalue(':synced', $synced);    
    $query->bindvalue(':sync_msg', $result['msg']);    
    $query->execute();
  } 
}
catch(PDOException $e)
{
  echo "Connection failed: " . $e->getMessage();
}