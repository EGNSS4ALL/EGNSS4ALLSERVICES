<?php

function get_client_ip() {
  $ipaddress = NULL;
  if (getenv('HTTP_CLIENT_IP'))
      $ipaddress = getenv('HTTP_CLIENT_IP');
  else if(getenv('HTTP_X_FORWARDED_FOR'))
      $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
  else if(getenv('HTTP_X_FORWARDED'))
      $ipaddress = getenv('HTTP_X_FORWARDED');
  else if(getenv('HTTP_FORWARDED_FOR'))
      $ipaddress = getenv('HTTP_FORWARDED_FOR');
  else if(getenv('HTTP_FORWARDED'))
     $ipaddress = getenv('HTTP_FORWARDED');
  else if(getenv('REMOTE_ADDR'))
      $ipaddress = getenv('REMOTE_ADDR');
  
  return $ipaddress;
}

function get_user ($login,$pswd) {    
  $sql = "SELECT id,name,surname,identification_number,email,vat FROM user WHERE login = '".addslashes($login)."' AND pswd = '".addslashes(sha1($pswd))."'";
  $res = mysqli_query($GLOBALS["mysqli_spoj"], $sql);
  if($rec = $res->fetch_assoc()) {
    $user = array();
    $user['id'] = $rec['id'];
    $user['name'] = $rec['name'];
    $user['surname'] = $rec['surname'];
    $user['identification_number'] = $rec['identification_number'];
    $user['email'] = $rec['email'];
    $user['vat'] = $rec['vat']; 
    return $user;    
  }    
  return false;
}

function get_number_of_photos ($task_id) {
  $number = 0;
  
  $sql = "SELECT COUNT(*) AS pocet FROM photo WHERE task_id = '".addslashes($task_id)."'";
  $res = mysqli_query($GLOBALS["mysqli_spoj"], $sql);
  if($rec = $res->fetch_assoc()) {
    $number = $rec['pocet'];
  }
  
  return $number;
}

function get_task_status ($task_id) {
  $status = '';
  $sql = "SELECT status FROM task WHERE id = '".addslashes($task_id)."'";
  $res = mysqli_query($GLOBALS["mysqli_spoj"], $sql);
  if($rec = $res->fetch_assoc()) {
    $status = $rec['status'];
  }
  
  return $status;
}

function get_tasks ($uid) {    
  $tasks = array();
  $sql = "SELECT t.id
                ,t.status
                ,t.name
                ,t.text
                ,t.text_returned
                ,t.date_created
                ,t.task_due_date
                ,t.note
                ,(SELECT COUNT(*) FROM photo WHERE task_id = t.id AND flg_deleted = 0) AS number_of_photos 
                ,IF((SELECT count(*) FROM task_flag tf WHERE task_id = t.id AND flag_id = 1) > 0,'1','0') AS flag_valid
                ,IF((SELECT count(*) FROM task_flag tf WHERE task_id = t.id AND flag_id = 2) > 0,'1','0') AS flag_invalid
          FROM task t LEFT JOIN status_sortorder ss ON t.status = ss.status 
          WHERE t.user_id = '".addslashes($uid)."' 
            AND t.flg_deleted = 0
          ORDER BY ss.sortorder";
  $res = mysqli_query($GLOBALS["mysqli_spoj"], $sql);
  while($rec = $res->fetch_assoc()) {
    $task = array();
    $task['id'] = $rec['id'];
    $task['status'] = $rec['status'];
    $task['name'] = $rec['name'];
    $task['text'] = $rec['text'];
    $task['text_returned'] = $rec['text_returned'];
    $task['date_created'] = $rec['date_created'];
    $task['task_due_date'] = $rec['task_due_date'];
    $task['note'] = $rec['note']; 
    //$task['number_of_photos'] = get_number_of_photos ($rec['id']);
    $task['number_of_photos'] = $rec['number_of_photos'];
    $task['flag_valid'] = $rec['flag_valid'];
    $task['flag_invalid'] = $rec['flag_invalid'];
    
    $task['photos_ids'] = array();
    $sql2 = "SELECT id 
               FROM photo
              WHERE task_id = '".addslashes($task['id'])."' 
                AND flg_deleted = 0";
    $res2 = mysqli_query($GLOBALS["mysqli_spoj"], $sql2);
    while($rec2 = $res2->fetch_assoc()) {
      $task['photos_ids'][] = $rec2['id'];
    }
    
    $tasks[] = $task;   
  }  
  return $tasks;
}

function set_task_status ($task_id,$status,$note) {
  $output = array();
  
  $note = "'".addslashes($note)."'";
  if ($note == "''") $note = 'NULL';
  
  $sql = "UPDATE task SET status = '".addslashes($status)."', note = $note, timestamp = utc_timestamp() WHERE id = '".addslashes($task_id)."'";
  if ($res = mysqli_query($GLOBALS["mysqli_spoj"], $sql)) {
    $output['status'] = 'ok';
    $output['error_msg'] = NULL;
  } else {         
    $output['status'] = 'error';
    $output['error_msg'] = mysqli_error($GLOBALS["mysqli_spoj"]);
  }
  return $output;
}  

function set_photos ($photos,$user_id,$task_id) {
  $status = array();        
    
  $status['status'] = 'ok';
  $status['error_msg'] = NULL;
  
  $task_id = "'".addslashes($task_id)."'";
  if ($task_id == "''") $task_id = 'NULL';
  
  $sql = 'START TRANSACTION';
  mysqli_query($GLOBALS["mysqli_spoj"], $sql);

  if (is_array($photos)) {
    foreach ($photos as $photo) {
      $note = "'".addslashes($photo['note'])."'";
      if ($note == "''") $note = 'NULL';
      
      $lat = "'".addslashes($photo['lat'])."'";
      if ($lat == "''") $lat = 'NULL';
      
      $lng = "'".addslashes($photo['lng'])."'";
      if ($lng == "''") $lng = 'NULL';
      
      $centroidLat = "'".addslashes($photo['centroidLat'])."'";
      if ($centroidLat == "''") $centroidLat = 'NULL';
      
      $centroidLng = "'".addslashes($photo['centroidLng'])."'";
      if ($centroidLng == "''") $centroidLng = 'NULL';
      
      $altitude = "'".addslashes($photo['altitude'])."'";
      if ($altitude == "''") $altitude = 'NULL';
      
      $bearing = "'".addslashes($photo['bearing'])."'";
      if ($bearing == "''") $bearing = 'NULL';
      
      $magnetic_azimuth = "'".addslashes($photo['magnetic_azimuth'])."'";
      if ($magnetic_azimuth == "''") $magnetic_azimuth = 'NULL';
      
      $photo_heading = "'".addslashes($photo['photo_heading'])."'";
      if ($photo_heading == "''") $photo_heading = 'NULL';
      
      $pitch = "'".addslashes($photo['pitch'])."'";
      if ($pitch == "''") $pitch = 'NULL';
      
      $roll = "'".addslashes($photo['roll'])."'";
      if ($roll == "''") $roll = 'NULL';
      
      $photo_angle = "'".addslashes($photo['photo_angle'])."'";
      if ($photo_angle == "''") $photo_angle = 'NULL';
      
      $orientation = "'".addslashes($photo['orientation'])."'";
      if ($orientation == "''") $orientation = 'NULL';
      
      $horizontal_view_angle = "'".addslashes($photo['horizontalViewAngle'])."'";
      if ($horizontal_view_angle == "''") $horizontal_view_angle = 'NULL';
      
      $vertical_view_angle = "'".addslashes($photo['verticalViewAngle'])."'";
      if ($vertical_view_angle == "''") $vertical_view_angle = 'NULL';
      
      $accuracy = "'".addslashes($photo['accuracy'])."'";
      if ($accuracy == "''") $accuracy = 'NULL';
      
      $device_manufacture = "'".addslashes($photo['deviceManufacture'])."'";
      if ($device_manufacture == "''") $device_manufacture = 'NULL';
      
      $device_model = "'".addslashes($photo['deviceModel'])."'";
      if ($device_model == "''") $device_model = 'NULL'; 
      
      $device_platform = "'".addslashes($photo['devicePlatform'])."'";
      if ($device_platform == "''") $device_platform = 'NULL';
      
      $device_version = "'".addslashes($photo['deviceVersion'])."'";
      if ($device_version == "''") $device_version = 'NULL';
      
      if ($photo['satsInfo']) $sats_info = json_encode($photo['satsInfo']);
      $sats_info = "'".addslashes($sats_info)."'";
      if ($sats_info == "''") $sats_info = 'NULL';
      
      $extra_sat_count = "'".addslashes($photo['extraSatCount'])."'";
      if ($extra_sat_count == "''") $extra_sat_count = 'NULL';
      
      $nmea_msg = "'".addslashes($photo['NMEAMessage'])."'";
      if ($nmea_msg == "''") $nmea_msg = 'NULL';
      
      if ($photo['networkInfo']) $network_info = json_encode($photo['networkInfo']);
      $network_info = "'".addslashes($network_info)."'";
      if ($network_info == "''") $network_info = 'NULL';       
             
      if ($photo['created']) $created = gmdate('Y-m-d H:i:s', strtotime($photo['created']));
      $created = "'".addslashes($created)."'";
      if ($created == "''") $created = 'NULL';
      
      $digest = "'".addslashes($photo['digest'])."'";
      if ($digest == "''") $digest = 'NULL'; 
      
      $nmea_location_json = '';
      $nmea_distance = '';
      
      // --- NMEA process BEGIN ---
      if ($photo['devicePlatform'] == 'Android') {    
        
        $nmea_location = get_coordinates_from_nmea($photo['NMEAMessage']); 
        if ($nmea_location) {
          $nmea_location_json = json_encode($nmea_location);
          $nmea_distance = get_distance_from_coordinates($photo['lat'],$photo['lng'],$nmea_location['lat'],$nmea_location['lon']); 
        } 
      } 
      // --- NMEA process END ---    

      $nmea_location_json = "'".addslashes($nmea_location_json)."'";
      if ($nmea_location_json == "''") $nmea_location_json = 'NULL'; 
      
      $nmea_distance = "'".addslashes($nmea_distance)."'";
      if ($nmea_distance == "''") $nmea_distance = 'NULL'; 
      
      // --- EKF BEGIN---       
      $efkLatGpsL1 = "'".addslashes($photo['efkLatGpsL1'])."'";
      if ($efkLatGpsL1 == "''") $efkLatGpsL1 = 'NULL';
      
      $efkLngGpsL1 = "'".addslashes($photo['efkLngGpsL1'])."'";
      if ($efkLngGpsL1 == "''") $efkLngGpsL1 = 'NULL';
      
      $efkAltGpsL1 = "'".addslashes($photo['efkAltGpsL1'])."'";
      if ($efkAltGpsL1 == "''") $efkAltGpsL1 = 'NULL';
      
      $efkTimeGpsL1 = ($photo['efkTimeGpsL1']) ? gmdate('Y-m-d H:i:s', strtotime($photo['efkTimeGpsL1'])) : '';     
      $efkTimeGpsL1 = "'".addslashes($efkTimeGpsL1)."'";
      if ($efkTimeGpsL1 == "''") $efkTimeGpsL1 = 'NULL';
      
      $efkLatGpsL5 = "'".addslashes($photo['efkLatGpsL5'])."'";
      if ($efkLatGpsL5 == "''") $efkLatGpsL5 = 'NULL';
      
      $efkLngGpsL5 = "'".addslashes($photo['efkLngGpsL5'])."'";
      if ($efkLngGpsL5 == "''") $efkLngGpsL5 = 'NULL';
      
      $efkAltGpsL5 = "'".addslashes($photo['efkAltGpsL5'])."'";
      if ($efkAltGpsL5 == "''") $efkAltGpsL5 = 'NULL';
      
      $efkTimeGpsL5 = ($photo['efkTimeGpsL5']) ? gmdate('Y-m-d H:i:s', strtotime($photo['efkTimeGpsL5'])) : '';  
      $efkTimeGpsL5 = "'".addslashes($efkTimeGpsL5)."'";
      if ($efkTimeGpsL5 == "''") $efkTimeGpsL5 = 'NULL';
      
      $efkLatGpsIf = "'".addslashes($photo['efkLatGpsIf'])."'";
      if ($efkLatGpsIf == "''") $efkLatGpsIf = 'NULL';
      
      $efkLngGpsIf = "'".addslashes($photo['efkLngGpsIf'])."'";
      if ($efkLngGpsIf == "''") $efkLngGpsIf = 'NULL';
      
      $efkAltGpsIf = "'".addslashes($photo['efkAltGpsIf'])."'";
      if ($efkAltGpsIf == "''") $efkAltGpsIf = 'NULL';
      
      $efkTimeGpsIf = ($photo['efkTimeGpsIf']) ? gmdate('Y-m-d H:i:s', strtotime($photo['efkTimeGpsIf'])) : '';  
      $efkTimeGpsIf = "'".addslashes($efkTimeGpsIf)."'";
      if ($efkTimeGpsIf == "''") $efkTimeGpsIf = 'NULL';
      
      $efkLatGalE1 = "'".addslashes($photo['efkLatGalE1'])."'";
      if ($efkLatGalE1 == "''") $efkLatGalE1 = 'NULL';
      
      $efkLngGalE1 = "'".addslashes($photo['efkLngGalE1'])."'";
      if ($efkLngGalE1 == "''") $efkLngGalE1 = 'NULL';
      
      $efkAltGalE1 = "'".addslashes($photo['efkAltGalE1'])."'";
      if ($efkAltGalE1 == "''") $efkAltGalE1 = 'NULL';
      
      $efkTimeGalE1 = ($photo['efkTimeGalE1']) ? gmdate('Y-m-d H:i:s', strtotime($photo['efkTimeGalE1'])) : '';  
      $efkTimeGalE1 = "'".addslashes($efkTimeGalE1)."'";
      if ($efkTimeGalE1 == "''") $efkTimeGalE1 = 'NULL';
      
      $efkLatGalE5 = "'".addslashes($photo['efkLatGalE5'])."'";
      if ($efkLatGalE5 == "''") $efkLatGalE5 = 'NULL';
      
      $efkLngGalE5 = "'".addslashes($photo['efkLngGalE5'])."'";
      if ($efkLngGalE5 == "''") $efkLngGalE5 = 'NULL';
      
      $efkAltGalE5 = "'".addslashes($photo['efkAltGalE5'])."'";
      if ($efkAltGalE5 == "''") $efkAltGalE5 = 'NULL';
      
      $efkTimeGalE5 = ($photo['efkTimeGalE5']) ? gmdate('Y-m-d H:i:s', strtotime($photo['efkTimeGalE5'])) : '';   
      $efkTimeGalE5 = "'".addslashes($efkTimeGalE5)."'";
      if ($efkTimeGalE5 == "''") $efkTimeGalE5 = 'NULL';
      
      $efkLatGalIf = "'".addslashes($photo['efkLatGalIf'])."'";
      if ($efkLatGalIf == "''") $efkLatGalIf = 'NULL';
      
      $efkLngGalIf = "'".addslashes($photo['efkLngGalIf'])."'";
      if ($efkLngGalIf == "''") $efkLngGalIf = 'NULL';
      
      $efkAltGalIf = "'".addslashes($photo['efkAtlGalIf'])."'";
      if ($efkAltGalIf == "''") $efkAltGalIf = 'NULL';
      
      $efkTimeGalIf = ($photo['efkTimeGalIf']) ? gmdate('Y-m-d H:i:s', strtotime($photo['efkTimeGalIf'])) : '';   
      $efkTimeGalIf = "'".addslashes($efkTimeGalIf)."'";
      if ($efkTimeGalIf == "''") $efkTimeGalIf = 'NULL';
      // --- EKF END ---  
      
      $sql_path = "SELECT id FROM photo WHERE digest = $digest";
      $res_path = mysqli_query($GLOBALS["mysqli_spoj"], $sql_path);
      if(!($rec_path = $res_path->fetch_assoc())) {               
        $sql = "INSERT INTO photo (task_id, user_id, note, lat, lng, centroidLat, centroidLng, altitude, bearing, magnetic_azimuth, photo_heading, pitch, roll, photo_angle, orientation, horizontal_view_angle, vertical_view_angle, accuracy, created, device_manufacture, device_model, device_platform, device_version, sats_info, extra_sat_count, nmea_msg, network_info, timestamp, digest, nmea_location, nmea_distance, efkLatGpsL1, efkLngGpsL1, efkAltGpsL1, efkTimeGpsL1, efkLatGpsL5, efkLngGpsL5, efkAltGpsL5, efkTimeGpsL5, efkLatGpsIf, efkLngGpsIf, efkAltGpsIf, efkTimeGpsIf, efkLatGalE1, efkLngGalE1, efkAltGalE1, efkTimeGalE1, efkLatGalE5, efkLngGalE5, efkAltGalE5, efkTimeGalE5, efkLatGalIf, efkLngGalIf, efkAltGalIf, efkTimeGalIf) 
                VALUES ($task_id,'".addslashes($user_id)."',$note,$lat,$lng,$centroidLat,$centroidLng,$altitude,$bearing,$magnetic_azimuth,$photo_heading,$pitch,$roll,$photo_angle,$orientation,$horizontal_view_angle,$vertical_view_angle,$accuracy,$created,$device_manufacture,$device_model,$device_platform,$device_version,$sats_info,$extra_sat_count,$nmea_msg,$network_info,utc_timestamp(),$digest,$nmea_location_json,$nmea_distance,$efkLatGpsL1,$efkLngGpsL1,$efkAltGpsL1,$efkTimeGpsL1,$efkLatGpsL5,$efkLngGpsL5,$efkAltGpsL5,$efkTimeGpsL5,$efkLatGpsIf,$efkLngGpsIf,$efkAltGpsIf,$efkTimeGpsIf,$efkLatGalE1,$efkLngGalE1,$efkAltGalE1,$efkTimeGalE1,$efkLatGalE5,$efkLngGalE5,$efkAltGalE5,$efkTimeGalE5,$efkLatGalIf,$efkLngGalIf,$efkAltGalIf,$efkTimeGalIf)";            
        if (mysqli_query($GLOBALS["mysqli_spoj"], $sql)) {
          $id = mysqli_insert_id($GLOBALS["mysqli_spoj"]);
          
          if ($photo['photo']) {
            $sql_path = "SELECT pa_id FROM user WHERE id = '".addslashes($user_id)."'";
            $res_path = mysqli_query($GLOBALS["mysqli_spoj"], $sql_path);
            if($rec_path = $res_path->fetch_assoc()) {  
              $pa_id = $rec_path['pa_id'];
              
              $data = base64_decode($photo['photo']);
              /*
              $data = '';
              $b64_array = str_split($photo['photo'],4096);
              foreach ($b64_array as $b64_part) {
                $data .= base64_decode($b64_part);
              }
              */
              
              $image_name = 'image_'.$id.'.jpeg';
              $path = 'photos4all/'.$pa_id.'/'.$user_id.'/';
              create_directories ('../photos4all',$pa_id,$user_id);
              file_put_contents('../'.$path.$image_name, $data);
              
              $hash = hash('sha256', 'bfb576892e43b763731a1596c428987893b2e76ce1be10f733_'.hash('sha256',$data).'_'.$photo['created'].'_'.$user_id);
              $flg_original = 0;
              if ($hash == $photo['digest']) $flg_original = 1; 
              
              $sql = "UPDATE photo SET path = '".addslashes($path)."',file_name = '".addslashes($image_name)."', timestamp = utc_timestamp(), flg_original = $flg_original WHERE id = $id";
              if (!mysqli_query($GLOBALS["mysqli_spoj"], $sql)) {
                $status['status'] = 'error';
                $status['error_msg'] = mysqli_error($GLOBALS["mysqli_spoj"]);   
              }             
            }         
          }        
        } else {
          $status['status'] = 'error';
          $status['error_msg'] = mysqli_error($GLOBALS["mysqli_spoj"]);
        }
      } 
    }
  } 
    
  if ($status['status'] == 'ok') {
    $sql = 'COMMIT';
    mysqli_query($GLOBALS["mysqli_spoj"], $sql);  
  } else {
    $sql = 'ROLLBACK';
    mysqli_query($GLOBALS["mysqli_spoj"], $sql);
  }
  
  return $status;       
}

function set_photo ($photo,$user_id,$task_id) {
  $status = array();   
    
  $status['status'] = 'ok';
  $status['error_msg'] = NULL;
  
  $task_id = "'".addslashes($task_id)."'";
  if ($task_id == "''") $task_id = 'NULL';
  
  $sql = 'START TRANSACTION';
  mysqli_query($GLOBALS["mysqli_spoj"], $sql);

  $note = "'".addslashes($photo['note'])."'";
  if ($note == "''") $note = 'NULL';
  
  $lat = "'".addslashes($photo['lat'])."'";
  if ($lat == "''") $lat = 'NULL';
  
  $lng = "'".addslashes($photo['lng'])."'";
  if ($lng == "''") $lng = 'NULL';
  
  $centroidLat = "'".addslashes($photo['centroidLat'])."'";
  if ($centroidLat == "''") $centroidLat = 'NULL';
  
  $centroidLng = "'".addslashes($photo['centroidLng'])."'";
  if ($centroidLng == "''") $centroidLng = 'NULL';
  
  $altitude = "'".addslashes($photo['altitude'])."'";
  if ($altitude == "''") $altitude = 'NULL';
  
  $bearing = "'".addslashes($photo['bearing'])."'";
  if ($bearing == "''") $bearing = 'NULL';
  
  $magnetic_azimuth = "'".addslashes($photo['magnetic_azimuth'])."'";
  if ($magnetic_azimuth == "''") $magnetic_azimuth = 'NULL';
  
  $photo_heading = "'".addslashes($photo['photo_heading'])."'";
  if ($photo_heading == "''") $photo_heading = 'NULL';
  
  $pitch = "'".addslashes($photo['pitch'])."'";
  if ($pitch == "''") $pitch = 'NULL';
  
  $roll = "'".addslashes($photo['roll'])."'";
  if ($roll == "''") $roll = 'NULL';
  
  $photo_angle = "'".addslashes($photo['photo_angle'])."'";
  if ($photo_angle == "''") $photo_angle = 'NULL';
  
  $orientation = "'".addslashes($photo['orientation'])."'";
  if ($orientation == "''") $orientation = 'NULL';
  
  $horizontal_view_angle = "'".addslashes($photo['horizontalViewAngle'])."'";
  if ($horizontal_view_angle == "''") $horizontal_view_angle = 'NULL';
  
  $vertical_view_angle = "'".addslashes($photo['verticalViewAngle'])."'";
  if ($vertical_view_angle == "''") $vertical_view_angle = 'NULL';
  
  $accuracy = "'".addslashes($photo['accuracy'])."'";
  if ($accuracy == "''") $accuracy = 'NULL';
  
  $device_manufacture = "'".addslashes($photo['deviceManufacture'])."'";
  if ($device_manufacture == "''") $device_manufacture = 'NULL';
  
  $device_model = "'".addslashes($photo['deviceModel'])."'";
  if ($device_model == "''") $device_model = 'NULL'; 
  
  $device_platform = "'".addslashes($photo['devicePlatform'])."'";
  if ($device_platform == "''") $device_platform = 'NULL';
  
  $device_version = "'".addslashes($photo['deviceVersion'])."'";
  if ($device_version == "''") $device_version = 'NULL';
  
  if ($photo['satsInfo']) $sats_info = json_encode($photo['satsInfo']);
  $sats_info = "'".addslashes($sats_info)."'";
  if ($sats_info == "''") $sats_info = 'NULL';
  
  $extra_sat_count = "'".addslashes($photo['extraSatCount'])."'";
  if ($extra_sat_count == "''") $extra_sat_count = 'NULL';
  
  $nmea_msg = "'".addslashes($photo['NMEAMessage'])."'";
  if ($nmea_msg == "''") $nmea_msg = 'NULL';
  
  if ($photo['networkInfo']) $network_info = json_encode($photo['networkInfo']);
  $network_info = "'".addslashes($network_info)."'";
  if ($network_info == "''") $network_info = 'NULL';       
         
  if ($photo['created']) $created = gmdate('Y-m-d H:i:s', strtotime($photo['created']));
  $created = "'".addslashes($created)."'";
  if ($created == "''") $created = 'NULL';
  
  $digest = "'".addslashes($photo['digest'])."'";
  if ($digest == "''") $digest = 'NULL'; 
  
  $nmea_location_json = '';
  $nmea_distance = '';
  
  // --- NMEA process ---
  if ($photo['devicePlatform'] == 'Android') {   
    $nmea_location = get_coordinates_from_nmea($photo['NMEAMessage']); 
    if ($nmea_location) {
      $nmea_location_json = json_encode($nmea_location);
      $nmea_distance = get_distance_from_coordinates($photo['lat'],$photo['lng'],$nmea_location['lat'],$nmea_location['lon']);         
    } 
  } 
  // --- NMEA process ---
  
  $nmea_location_json = "'".addslashes($nmea_location_json)."'";
  if ($nmea_location_json == "''") $nmea_location_json = 'NULL'; 
  
  $nmea_distance = "'".addslashes($nmea_distance)."'";
  if ($nmea_distance == "''") $nmea_distance = 'NULL'; 
  
  // --- EKF BEGIN---       
  $efkLatGpsL1 = "'".addslashes($photo['efkLatGpsL1'])."'";
  if ($efkLatGpsL1 == "''") $efkLatGpsL1 = 'NULL';
  
  $efkLngGpsL1 = "'".addslashes($photo['efkLngGpsL1'])."'";
  if ($efkLngGpsL1 == "''") $efkLngGpsL1 = 'NULL';
  
  $efkAltGpsL1 = "'".addslashes($photo['efkAltGpsL1'])."'";
  if ($efkAltGpsL1 == "''") $efkAltGpsL1 = 'NULL';
  
  $efkTimeGpsL1 = ($photo['efkTimeGpsL1']) ? gmdate('Y-m-d H:i:s', strtotime($photo['efkTimeGpsL1'])) : '';     
  $efkTimeGpsL1 = "'".addslashes($efkTimeGpsL1)."'";
  if ($efkTimeGpsL1 == "''") $efkTimeGpsL1 = 'NULL';
  
  $efkLatGpsL5 = "'".addslashes($photo['efkLatGpsL5'])."'";
  if ($efkLatGpsL5 == "''") $efkLatGpsL5 = 'NULL';
  
  $efkLngGpsL5 = "'".addslashes($photo['efkLngGpsL5'])."'";
  if ($efkLngGpsL5 == "''") $efkLngGpsL5 = 'NULL';
  
  $efkAltGpsL5 = "'".addslashes($photo['efkAltGpsL5'])."'";
  if ($efkAltGpsL5 == "''") $efkAltGpsL5 = 'NULL';
  
  $efkTimeGpsL5 = ($photo['efkTimeGpsL5']) ? gmdate('Y-m-d H:i:s', strtotime($photo['efkTimeGpsL5'])) : '';  
  $efkTimeGpsL5 = "'".addslashes($efkTimeGpsL5)."'";
  if ($efkTimeGpsL5 == "''") $efkTimeGpsL5 = 'NULL';
  
  $efkLatGpsIf = "'".addslashes($photo['efkLatGpsIf'])."'";
  if ($efkLatGpsIf == "''") $efkLatGpsIf = 'NULL';
  
  $efkLngGpsIf = "'".addslashes($photo['efkLngGpsIf'])."'";
  if ($efkLngGpsIf == "''") $efkLngGpsIf = 'NULL';
  
  $efkAltGpsIf = "'".addslashes($photo['efkAltGpsIf'])."'";
  if ($efkAltGpsIf == "''") $efkAltGpsIf = 'NULL';
  
  $efkTimeGpsIf = ($photo['efkTimeGpsIf']) ? gmdate('Y-m-d H:i:s', strtotime($photo['efkTimeGpsIf'])) : '';  
  $efkTimeGpsIf = "'".addslashes($efkTimeGpsIf)."'";
  if ($efkTimeGpsIf == "''") $efkTimeGpsIf = 'NULL';
  
  $efkLatGalE1 = "'".addslashes($photo['efkLatGalE1'])."'";
  if ($efkLatGalE1 == "''") $efkLatGalE1 = 'NULL';
  
  $efkLngGalE1 = "'".addslashes($photo['efkLngGalE1'])."'";
  if ($efkLngGalE1 == "''") $efkLngGalE1 = 'NULL';
  
  $efkAltGalE1 = "'".addslashes($photo['efkAltGalE1'])."'";
  if ($efkAltGalE1 == "''") $efkAltGalE1 = 'NULL';
  
  $efkTimeGalE1 = ($photo['efkTimeGalE1']) ? gmdate('Y-m-d H:i:s', strtotime($photo['efkTimeGalE1'])) : '';  
  $efkTimeGalE1 = "'".addslashes($efkTimeGalE1)."'";
  if ($efkTimeGalE1 == "''") $efkTimeGalE1 = 'NULL';
  
  $efkLatGalE5 = "'".addslashes($photo['efkLatGalE5'])."'";
  if ($efkLatGalE5 == "''") $efkLatGalE5 = 'NULL';
  
  $efkLngGalE5 = "'".addslashes($photo['efkLngGalE5'])."'";
  if ($efkLngGalE5 == "''") $efkLngGalE5 = 'NULL';
  
  $efkAltGalE5 = "'".addslashes($photo['efkAltGalE5'])."'";
  if ($efkAltGalE5 == "''") $efkAltGalE5 = 'NULL';
  
  $efkTimeGalE5 = ($photo['efkTimeGalE5']) ? gmdate('Y-m-d H:i:s', strtotime($photo['efkTimeGalE5'])) : '';   
  $efkTimeGalE5 = "'".addslashes($efkTimeGalE5)."'";
  if ($efkTimeGalE5 == "''") $efkTimeGalE5 = 'NULL';
  
  $efkLatGalIf = "'".addslashes($photo['efkLatGalIf'])."'";
  if ($efkLatGalIf == "''") $efkLatGalIf = 'NULL';
  
  $efkLngGalIf = "'".addslashes($photo['efkLngGalIf'])."'";
  if ($efkLngGalIf == "''") $efkLngGalIf = 'NULL';
  
  $efkAltGalIf = "'".addslashes($photo['efkAtlGalIf'])."'";
  if ($efkAltGalIf == "''") $efkAltGalIf = 'NULL';
  
  $efkTimeGalIf = ($photo['efkTimeGalIf']) ? gmdate('Y-m-d H:i:s', strtotime($photo['efkTimeGalIf'])) : '';   
  $efkTimeGalIf = "'".addslashes($efkTimeGalIf)."'";
  if ($efkTimeGalIf == "''") $efkTimeGalIf = 'NULL';
  // --- EKF END ---  
    
  $sql_path = "SELECT id FROM photo WHERE digest = $digest";
  $res_path = mysqli_query($GLOBALS["mysqli_spoj"], $sql_path);
  if(!($rec_path = $res_path->fetch_assoc())) {               
    $sql = "INSERT INTO photo (task_id, user_id, note, lat, lng, centroidLat, centroidLng, altitude, bearing, magnetic_azimuth, photo_heading, pitch, roll, photo_angle, orientation, horizontal_view_angle, vertical_view_angle, accuracy, created, device_manufacture, device_model, device_platform, device_version, sats_info, extra_sat_count, nmea_msg, network_info, timestamp, digest, nmea_location, nmea_distance, efkLatGpsL1, efkLngGpsL1, efkAltGpsL1, efkTimeGpsL1, efkLatGpsL5, efkLngGpsL5, efkAltGpsL5, efkTimeGpsL5, efkLatGpsIf, efkLngGpsIf, efkAltGpsIf, efkTimeGpsIf, efkLatGalE1, efkLngGalE1, efkAltGalE1, efkTimeGalE1, efkLatGalE5, efkLngGalE5, efkAltGalE5, efkTimeGalE5, efkLatGalIf, efkLngGalIf, efkAltGalIf, efkTimeGalIf) 
            VALUES ($task_id,'".addslashes($user_id)."',$note,$lat,$lng,$centroidLat,$centroidLng,$altitude,$bearing,$magnetic_azimuth,$photo_heading,$pitch,$roll,$photo_angle,$orientation,$horizontal_view_angle,$vertical_view_angle,$accuracy,$created,$device_manufacture,$device_model,$device_platform,$device_version,$sats_info,$extra_sat_count,$nmea_msg,$network_info,utc_timestamp(),$digest,$nmea_location_json,$nmea_distance,$efkLatGpsL1,$efkLngGpsL1,$efkAltGpsL1,$efkTimeGpsL1,$efkLatGpsL5,$efkLngGpsL5,$efkAltGpsL5,$efkTimeGpsL5,$efkLatGpsIf,$efkLngGpsIf,$efkAltGpsIf,$efkTimeGpsIf,$efkLatGalE1,$efkLngGalE1,$efkAltGalE1,$efkTimeGalE1,$efkLatGalE5,$efkLngGalE5,$efkAltGalE5,$efkTimeGalE5,$efkLatGalIf,$efkLngGalIf,$efkAltGalIf,$efkTimeGalIf)";            
    if (mysqli_query($GLOBALS["mysqli_spoj"], $sql)) {
      $id = mysqli_insert_id($GLOBALS["mysqli_spoj"]);
      $status['photo_id'] = $id;
      
      if ($photo['photo']) {
        $sql_path = "SELECT pa_id FROM user WHERE id = '".addslashes($user_id)."'";
        $res_path = mysqli_query($GLOBALS["mysqli_spoj"], $sql_path);
        if($rec_path = $res_path->fetch_assoc()) {  
          $pa_id = $rec_path['pa_id'];
          
          $data = str_replace(' ','+',($photo['photo']));
          $data = base64_decode($data);
                    
          $image_name = 'image_'.$id.'.jpeg';
          $path = 'photos4all/'.$pa_id.'/'.$user_id.'/';
          create_directories ('../photos4all',$pa_id,$user_id);
          file_put_contents('../'.$path.$image_name, $data);
          
          $hash = hash('sha256', 'bfb576892e43b763731a1596c428987893b2e76ce1be10f733_'.hash('sha256',$data).'_'.$photo['created'].'_'.$user_id);
          $flg_original = 0;
          if ($hash == $photo['digest']) $flg_original = 1; 
          
          $sql = "UPDATE photo SET path = '".addslashes($path)."',file_name = '".addslashes($image_name)."', timestamp = utc_timestamp(), flg_original = $flg_original WHERE id = $id";
          if (!mysqli_query($GLOBALS["mysqli_spoj"], $sql)) {
            $status['status'] = 'error';
            $status['error_msg'] = mysqli_error($GLOBALS["mysqli_spoj"]);   
          }             
        }         
      }        
    } else {
      $status['status'] = 'error';
      $status['error_msg'] = mysqli_error($GLOBALS["mysqli_spoj"]);
    }
  } 
    
    
  if ($status['status'] == 'ok') {
    $sql = 'COMMIT';
    mysqli_query($GLOBALS["mysqli_spoj"], $sql);
  } else {
    $sql = 'ROLLBACK';
    mysqli_query($GLOBALS["mysqli_spoj"], $sql);
    unset($status['photo_id']);
  }
  
  return $status;       
}

function set_path ($user_id,$name,$start,$end,$area,$device_manufacture,$device_model,$device_platform,$device_version,$points) {
  $status = array();  
  
  $path_id = NULL;
  $points_count = 0;
    
  $status['status'] = 'ok';
  $status['error_msg'] = NULL;
  
  $user_id = "'".addslashes($user_id)."'";
  if ($user_id == "''") $user_id = 'NULL';
  
  $name = "'".addslashes($name)."'";
  if ($name == "''") $name = 'NULL';
  
  $device_manufacture = "'".addslashes($device_manufacture)."'";
  if ($device_manufacture == "''") $device_manufacture = 'NULL';
  
  $device_model = "'".addslashes($device_model)."'";
  if ($device_model == "''") $device_model = 'NULL';
  
  $device_platform = "'".addslashes($device_platform)."'";
  if ($device_platform == "''") $device_platform = 'NULL';
  
  $device_version = "'".addslashes($device_version)."'";
  if ($device_version == "''") $device_version = 'NULL';
  
  $start = "'".addslashes($start)."'";
  if ($start == "''") $start = 'NULL';
  
  $end = "'".addslashes($end)."'";
  if ($end == "''") $end = 'NULL';
  
  $area = "'".addslashes($area)."'";
  if ($area == "''") $area = '0';
  
  $sql = 'START TRANSACTION';
  mysqli_query($GLOBALS["mysqli_spoj"], $sql);
  
  $sql = "INSERT INTO path (user_id, name, start, end, area, device_manufacture, device_model, device_platform, device_version) 
          VALUES ($user_id,$name,$start,$end,$area,$device_manufacture,$device_model,$device_platform,$device_version)";            
  if (mysqli_query($GLOBALS["mysqli_spoj"], $sql)) {
    $path_id = mysqli_insert_id($GLOBALS["mysqli_spoj"]);
    
    if (is_array($points)) {
      foreach ($points as $point) {
        $lat = "'".addslashes($point['lat'])."'";
        if ($lat == "''") $lat = 'NULL';
        
        $lng = "'".addslashes($point['lng'])."'";
        if ($lng == "''") $lng = 'NULL';  
        
        $altitude = "'".addslashes($point['altitude'])."'";
        if ($altitude == "''") $altitude = 'NULL'; 
        
        $accuracy = "'".addslashes($point['accuracy'])."'";
        if ($accuracy == "''") $accuracy = 'NULL';          
               
        if ($point['created']) $created = gmdate('Y-m-d H:i:s', strtotime($point['created']));
        $created = "'".addslashes($created)."'";
        if ($created == "''") $created = 'NULL';  
        
        $sql = "INSERT INTO path_point (path_id, lat, lng, altitude, accuracy, created) 
                VALUES ($path_id,$lat,$lng,$altitude,$accuracy,$created)";            
        if (!mysqli_query($GLOBALS["mysqli_spoj"], $sql)) {
          $status['status'] = 'error';
          $status['error_msg'] = mysqli_error($GLOBALS["mysqli_spoj"]);
        } else {
          $points_count++;
        }        
      }
    }
        
  } else {
    $status['status'] = 'error';
    $status['error_msg'] = mysqli_error($GLOBALS["mysqli_spoj"]);
  } 
  
  if ($points_count == 0) {
    $status['status'] = 'error';
    $status['error_msg'] = "no points in the path";
  }  
    
  if ($status['status'] == 'ok') {
    $sql = 'COMMIT';
    mysqli_query($GLOBALS["mysqli_spoj"], $sql);
    $status['path_id'] = $path_id;
  } else {
    $sql = 'ROLLBACK';
    mysqli_query($GLOBALS["mysqli_spoj"], $sql);
    $status['path_id'] = NULL;
  }
  
  return $status;       
}

function write_log ($status,$error_msg) {
  $status = "'".addslashes($status)."'";
  if ($status == "''") $status = 'NULL';
        
  $error_msg = "'".addslashes($error_msg)."'";
  if ($error_msg == "''") $error_msg = 'NULL';
  
  $sql = "INSERT INTO comm_log (status, error_msg, timestamp) VALUES ($status,$error_msg,utc_timestamp())";
  mysqli_query($GLOBALS["mysqli_spoj"], $sql);
}

function create_directories ($photo_dir,$paid,$uid) {
  if(!is_dir($photo_dir)){
    mkdir($photo_dir, 0777, true);
  }
  if(!is_dir($photo_dir.'/'.$paid)){
    mkdir($photo_dir.'/'.$paid, 0777, true);
  }
  if(!is_dir($photo_dir.'/'.$paid.'/'.$uid)){
    mkdir($photo_dir.'/'.$paid.'/'.$uid, 0777, true);
  }  
}

function get_task_photos ($task_id,$user_id) {
  $output = array();
  
  if ($task_id) {
    $sql = "SELECT note,lat,lng,photo_heading,created,path,file_name,digest FROM photo WHERE flg_deleted = 0 AND task_id = '".addslashes($task_id)."'";
  } elseif ($user_id) {
    $sql = "SELECT note,lat,lng,photo_heading,created,path,file_name,digest FROM photo WHERE flg_deleted = 0 AND task_id IS NULL AND user_id = '".addslashes($user_id)."'";
  } else {
    return $output;
  }

  $res = mysqli_query($GLOBALS["mysqli_spoj"], $sql);
  while($rec = $res->fetch_assoc()) { 
    $out = array();  
       
    $out['note'] = $rec['note'];
    $out['lat'] = $rec['lat'];
    $out['lng'] = $rec['lng'];
    $out['photo_heading'] = $rec['photo_heading'];
    $out['created'] = $rec['created'];
    
    $file = NULL;
    if (file_exists('../'.$rec['path'].$rec['file_name']) ) {
      $file = file_get_contents('../'.$rec['path'].$rec['file_name']);
    }
    $out['photo'] = base64_encode($file);
    $out['digest'] = $rec['digest'];
    
    $output[] = $out;
  } 
  
  return $output;
}

function set_network_location_and_distance ($photo_id, $location_json, $distance) {
  $location_json = "'".addslashes($location_json)."'";
  if ($location_json == "''") $location_json = 'NULL';
  
  $distance = "'".addslashes($distance)."'";
  if ($distance == "''") $distance = 'NULL';
  
  $sql = "UPDATE photo SET network_location = $location_json, distance = $distance WHERE id = '".addslashes($photo_id)."'";
  $res = mysqli_query($GLOBALS["mysqli_spoj"], $sql);
}

function get_location ($info) {
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, "https://eu1.unwiredlabs.com/v2/process.php");
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);  
  curl_setopt($curl, CURLOPT_POST, 1);
  curl_setopt($curl, CURLOPT_POSTFIELDS, $info);
  $contents = curl_exec($curl);         
  curl_close($curl);
  
  return $contents;
}

function get_balance ($token) {
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, "https://us1.unwiredlabs.com/v2/balance.php?token=".$token);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);  
  $contents = curl_exec($curl);         
  curl_close($curl);
  
  return $contents;
}

function get_distance_from_coordinates($a_lat, $a_lng, $b_lat, $b_lng) {
		
	$const_p = pi() / 180;
  $const_r = 12742;
  
  $a = 0.5 - cos(($b_lat - $a_lat) * $const_p) / 2 + cos($a_lat * $const_p) * cos($b_lat * $const_p) * (1 - cos(($b_lon - $a_lon) * $const_p)) / 2;
  
  $distance = $const_r * sin(sqrt($a));
  
  return $distance * 1000;		
}

function get_shapes ($max_lat,$min_lat,$max_lng,$min_lng) {   
  $output = array();
  
  $max_lat = addslashes($max_lat);
  $min_lat = addslashes($min_lat);
  $max_lng = addslashes($max_lng);
  $min_lng = addslashes($min_lng);
  
  $sql = "SELECT identificator,wgs_geometry FROM land WHERE wgs_min_lat < '$max_lat' AND wgs_max_lat > '$min_lat' AND wgs_min_lng < '$max_lng' AND wgs_max_lng > '$min_lng'";
  //echo ($sql);
  $res = mysqli_query($GLOBALS["mysqli_spoj"], $sql);
  while($rec = $res->fetch_assoc()) {
    $out = array();  
    
    $out['identificator'] = $rec['identificator'];
    $out['wgs_geometry'] = $rec['wgs_geometry'];
    
    $output[] = $out;
  }
  
  return $output;  
}

function check_task_photos ($task_id) {
  $check = false;
  
  $task_id = addslashes($task_id);
   
  $sql = "SELECT COUNT(1) AS pocet FROM photo WHERE task_id = '$task_id'";
  $res = mysqli_query($GLOBALS["mysqli_spoj"], $sql);
  if($rec = $res->fetch_assoc()) { 
    if ($rec['pocet'] > 0) $check = true;
  }    
  
  return $check;
}

function get_paths ($user_id) {
  $output = array();
  
  $sql = "SELECT id,user_id,name,start,end,area,device_manufacture,device_model,device_platform,device_version FROM path WHERE flg_deleted = 0 AND user_id = '".addslashes($user_id)."'";
  $res = mysqli_query($GLOBALS["mysqli_spoj"], $sql);
  while($rec = $res->fetch_assoc()) { 
    $out = array();  
       
    $out['id'] = $rec['id'];
    $out['name'] = $rec['name'];
    $out['start'] = $rec['start'];
    $out['end'] = $rec['end'];
    $out['area'] = $rec['area'];
    $out['device_manufacture'] = $rec['device_manufacture'];
    $out['device_model'] = $rec['device_model'];
    $out['device_platform'] = $rec['device_platform'];
    $out['device_version'] = $rec['device_version'];
    $out['points'] = array();
    
    $sql2 = "SELECT id,lat,lng,altitude,accuracy,created FROM path_point WHERE path_id = '".addslashes($rec['id'])."'";
    $res2 = mysqli_query($GLOBALS["mysqli_spoj"], $sql2);
    while($rec2 = $res2->fetch_assoc()) {
      $out2 = array();  
       
      $out2['id'] = $rec2['id'];
      $out2['lat'] = $rec2['lat'];
      $out2['lng'] = $rec2['lng'];
      $out2['altitude'] = $rec2['altitude'];
      $out2['accuracy'] = $rec2['accuracy'];
      $out2['created'] = $rec2['created'];
      
      $out['points'][] = $out2;      
    }
    
    $output[] = $out;
  } 
  
  return $output;
}

function get_coordinates_from_nmea ($nmea_msg) {  
  $record = '';
  $p1 = strrpos($nmea_msg,'RMC');
  if ($p1 !== false) {
    $p2 = strpos($nmea_msg,'*',$p1);
    $record = substr($nmea_msg,$p1-3,$p2-$p1+6);
    
    $record_array = explode(',',$record);      
    $nmea_location = array();
    
    if ($record_array[3] && $record_array[5]) {
      $nmea_location['lat'] = substr($record_array[3],0,2) + substr($record_array[3],2) / 60;
      if ($record_array[4] == 'S') $record_array[3] = -$record_array[3];
      $nmea_location['lon'] = substr($record_array[5],0,3) + substr($record_array[5],3) / 60;
      if ($record_array[6] == 'W') $record_array[5] = -$record_array[5];     
      
      return $nmea_location;  
    }    
  }
  
  return false;
}

function get_unassigned_photos_ids ($uid) {    
  $ids = array();
  $sql = "SELECT id
          FROM photo 
          WHERE user_id = '".addslashes($uid)."'
            AND flg_deleted = 0 
            AND task_id IS NULL";
  $res = mysqli_query($GLOBALS["mysqli_spoj"], $sql);
  while($rec = $res->fetch_assoc()) {
    $ids[] = $rec['id'];   
  }  
  return $ids;
}

function get_photo ($photo_id) {
  $output = array();
  
  $sql = "SELECT note,lat,lng,photo_heading,created,path,file_name,digest FROM photo WHERE flg_deleted = 0 AND id = '".addslashes($photo_id)."'";
  $res = mysqli_query($GLOBALS["mysqli_spoj"], $sql);
  if($rec = $res->fetch_assoc()) { 
    $output['note'] = $rec['note'];
    $output['lat'] = $rec['lat'];
    $output['lng'] = $rec['lng'];
    $output['photo_heading'] = $rec['photo_heading'];
    $output['created'] = $rec['created'];
    
    $file = NULL;
    if (file_exists('../'.$rec['path'].$rec['file_name']) ) {
      $file = file_get_contents('../'.$rec['path'].$rec['file_name']);
    }
    $output['photo'] = base64_encode($file);
    $output['digest'] = $rec['digest'];     
  } 
  
  return $output;
}
 
//Created for the GSA in 2020-2021. Project management: SpaceTec Partners, software development: www.foxcom.eu
?>