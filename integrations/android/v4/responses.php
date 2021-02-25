<?php


function return_error($error_string)
{

  $logfile = 'log-'.date('Y-m-d').'.txt';

  // file_put_contents('log.txt', $error_string , FILE_APPEND | LOCK_EX);
  header("Content-Type: application/json");
  $arr = array('error' => True, 'message' => $error_string);
  echo json_encode($arr);
  file_put_contents("error-".$logfile, $error_string."\n\n" , FILE_APPEND | LOCK_EX);
  die();
}

function return_success($success_string)
{
  // file_put_contents('log.txt', $success_string , FILE_APPEND | LOCK_EX);
  header("Content-Type: application/json");
  $arr = array('error' => False, 'message' => $success_string);
  echo json_encode($arr);
  die();
}

?>
