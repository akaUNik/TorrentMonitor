<?php
require_once( dirname( __FILE__ ) . '/class/TransmissionRPC.class.php' );

// Settings
$torrentAddress = 'http://5.8.207.77:9999/transmission/rpc';
$torrentLogin = 'Diman';
$torrentPassword = '777qwerty15';

$rpc = new TransmissionRPC($torrentAddress, $torrentLogin, $torrentPassword);
//$rpc->debug = TRUE;
//$rpc->return_as_array = TRUE;

$toggleAltSpeed = false;
$getTorrents = true;


// out: "name|pct|blink|beep";
if ($getTorrents)
{
  $torrents = '';

  $ids = array();
  $fields = array('name', 'status', 'percentDone');
  $result = $rpc->get($ids, $fields);
  //print_r($result->arguments->torrents);
  foreach ($result->arguments->torrents as &$torrent)
  {
    if ($torrent->status == 4 || TRUE) //TR_STATUS_DOWNLOAD
      $torrents .= $torrent->name . '|' . ($torrent->percentDone * 100) . '%|1|1@';
  }
  $torrents = rtrim($torrents, '@');  //remove last char
  print($torrents);print("\n");
}

// Toggle alt speed mode
//function toggleAltSpeed()
if ($toggleAltSpeed)
{
  // Retrieve session variables
  $result = $rpc->sget();
  print($result->result); print("\n");
  print($result->arguments->alt_speed_enabled); print("\n");

  // Set session variable
  // $result->arguments->alt_speed_enabled == 1 ? 0 : 1
  $arguments = array(
    'alt_speed_enable' => false
  );
  $result = $rpc->sset($arguments);
  print_r($result);
}
?>
