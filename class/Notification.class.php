<?php
require "class.php-prowl.php";

class Notification
{
	private static $errors;

	public static function findWarning()
    {
    	$trackersArray = Database::getTrackersList();
    	foreach ($trackersArray as $tracker)
    	{
    		$warningsCount = Database::getWarningsCount($tracker);
    		if ($warningsCount == 1)
    		{
    			$warningsArray = Database::getWarnings($tracker);
    			Notification::sendNotification('warning', $warningsArray['time'], $tracker, $warningsArray['reason']);
    		}
    	}
	}
	
	public static function send($settingEmail, $date, $tracker, $message, $header_message)
	{
        $headers = 'From: TorrentMonitor'."\r\n";
		$headers .= 'MIME-Version: 1.0'."\r\n";
		$headers .= 'Content-type: text/html; charset=utf-8'."\r\n";	
		$msg = 'Дата: '.$date.'<br>Трекер: '.$tracker.'<br>Сообщение: '.$message;
		
		mail($settingEmail, '=?UTF-8?B?'.base64_encode("TorrentMonitor: ".$header_message).'?=', $msg, $headers);
			
		// send to Prowl
		try {
			$api_key = Database::getSetting('prowlKey')
			$prowl = new Prowl();
			$prowl->setApiKey($api_key);
			//$prowl->setDebug(true);
	
			$application = "TorrentMonitor";
			$event = $header_message;
			$description = $msg;
			$url = $tracker;
			$priority = -1;
	
			$message = $prowl->add($application,$event,$priority,$description,$url);
			//echo var_dump($message).DEMO_EOL;		
		} catch (Exception $message) {
			echo "Failed: ".$message->getMessage().DEMO_EOL;
		}	
	}
	
	public static function sendNotification($type, $date, $tracker, $message)
	{
		if ($type == 'warning')
			$header_message = 'Предупреждение.';
		if ($type == 'notification')
			$header_message = 'Обновление.';

		$settingEmail = Database::getSetting('email');
		if ( ! empty($settingEmail))
		{
			if ($type == 'warning')
			{
				if (Database::getSetting('send_warning'))
					Notification::send($settingEmail, $date, $tracker, $message, $header_message);
			}
			if ($type == 'notification')
			{
				if (Database::getSetting('send'))
					Notification::send($settingEmail, $date, $tracker, $message, $header_message);
			}
		}
	}
}
?>