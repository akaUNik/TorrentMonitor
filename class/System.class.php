<?php
require_once 'TransmissionRPC.class.php';
class Sys
{
	//проверяем есть ли интернет
	public static function checkInternet()
	{
		$page = file_get_contents('http://ya.ru');
		if (preg_match('/<title>Яндекс<\/title>/', $page))
			return TRUE;
		else
			return FALSE;
	}

	//проверяем есть ли конфигурационный файл
	public static function checkConfigExist()
	{
		$dir = dirname(__FILE__);
		$dir = str_replace('class', '', $dir);
		if (file_exists($dir.'/config.php'))
			return TRUE;
		else
			return FALSE;
	}

	//проверяем правильно ли заполнен конфигурационный файл
	public static function checkConfig()
	{
		$dir = dirname(__FILE__).'/../';
		include_once $dir.'config.php';

		$confArray = Config::$confArray;
		foreach ($confArray as $key => $val)
		{
			if (empty($val))
				return FALSE;
		}
		return TRUE;
	}

	//проверяем установлено ли расширение CURL
	public static function checkCurl()
	{
		if (in_array('curl', get_loaded_extensions()))
			return TRUE;
		else
			return FALSE;
	}

	//проверяем есть ли на конце пути /
	public static function checkPath($path)
	{
		if (substr($path, -1) == '/')
			$path = $path;
		else
			$path = $path.'/';
		return $path;
	}

	//проверка на возхможность записи в директорию
	public static function checkWriteToPath($path)
	{
		return is_writable($path);
	}

	//версия системы
	public static function version()
	{
		return '1.1';
	}

	//проверка обновлений системы
	public static function checkUpdate()
	{
	    $opts = stream_context_create(array(
    		'http' => array(
    			'timeout' => 1
    			)
    		));

        $xmlstr = @file_get_contents('http://korphome.ru/torrent_monitor/version.xml', false, $opts);
        $xml = @simplexml_load_string($xmlstr);

		if (false !== $xml)
		{
			if (Sys::version() < $xml->current_version)
				return TRUE;
			else
				return FALSE;
		}
	}

	// обёртка для file_get_contents, для более удобного использования
	public static function getUrlContent($param = null)
    {
    	if (is_array($param))
    	{
			$url = $param['url'];
			$url = str_replace('https://', 'http://', $url); //для lostfilm.tv

			$opts = array(
			  'http' => array(
				'method' => $param['type'],
				'header' =>
				  	'Host: ' . parse_url($url, PHP_URL_HOST) . PHP_EOL .
			  		'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:26.0) Gecko/20100101 Firefox/26.0' . PHP_EOL .
					//	'Accept: */*' . PHP_EOL .
					'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8' . PHP_EOL .
					'Accept-Language: en-US,en;q=0.5' . PHP_EOL .
					'Accept-Encoding: deflate' . PHP_EOL .
					'Cache-Control: max-age=0',
				'max_redirects' => 20
			)
			);

			if (isset($param['referer']))
    			$opts['http']['header'] .= PHP_EOL . 'Referer: ' . $param['referer'];

    		if (isset($param['cookie']))
    			$opts['http']['header'] .= PHP_EOL . 'Cookie: ' . $param['cookie'];

			$opts['http']['header'] .= PHP_EOL . 'Connection: keep-alive';

    		if (isset($param['postfields']))
    		{
				$opts['http']['header'] .=  PHP_EOL . 'Content-type: application/x-www-form-urlencoded';
				$opts['http']['header'] .=  PHP_EOL . 'Content-Length: ' . strlen($param['postfields']);
				$opts['http']['content'] = $param['postfields'];
    		}

			// ssl
			// if (parse_url($url, PHP_URL_SCHEME) == 'https')
			// {
			// 	$opts['ssl'] => array(
      //   	'verify_peer'       => true,
      //   	'allow_self_signed'=> true
			// 	);
			// }

			$context = stream_context_create($opts);

			// Открываем файл с помощью установленных выше HTTP-заголовков
			$result = file_get_contents($url, false, $context);

			if ($result == FALSE) {
				echo 'file_get_contents failed!!!' . PHP_EOL;
				print 'URL: ' . $url . PHP_EOL;
				//print_r($opts);
				//print PHP_EOL;
				print_r($http_response_header);
			}

    		if (isset($param['convert']))
    			$result = iconv($param['convert'][0], $param['convert'][1], $result);

			// debug
			// print 'URL: ' . $url . PHP_EOL;
			// print_r($opts);
			// print '===========================================================' . PHP_EOL;
			// print 'result: ' . strlen($result) . PHP_EOL;
			// print_r($http_response_header);
			// file_put_contents('debug-' . time() . '.html', $result);
			// print '===========================================================' . PHP_EOL;

			// если необходимы еще заголовки
			if (isset($param['header']))
				return implode("\r\n", $http_response_header) . PHP_EOL . $result;
			else
				return $result;
    	}
    }

    //Проверяем доступность трекера
    public static function checkavAilability($tracker)
    {
		$page = Sys::getUrlContent(
            array(
                'type'           => 'GET',
                'header'         => 1,
                'returntransfer' => 1,
                'url'            => $tracker,
            )
		);

		if (preg_match('/HTTP\/1\.1 200 OK/', $page))
			return true;
		else
			return false;
    }

	//Получаем заголовок страницы
	public static function getHeader($url)
	{
		$Purl = parse_url($url);
		$tracker = $Purl['host'];
		$tracker = preg_replace('/www\./', '', $tracker);

		if ($tracker == 'rutor.org')
			$tracker = 'new-rutor.org';

		$forumPage = Sys::getUrlContent(
            array(
                'type'           => 'GET',
                'returntransfer' => 1,
                'url'            => $url,
            )
		);

		if ($tracker == 'rustorka.com')
		{
		    $dir = str_replace('class', '', dirname(__FILE__));
		    $engineFile = $dir.'trackers/'.$tracker.'.engine.php';
			if (file_exists($engineFile))
			{
				Database::clearWarnings('system');

				$functionEngine = include_once $engineFile;
				$class = explode('.', $tracker);
				$class = $class[0];
				$functionClass = str_replace('-', '', $class);
			}

    		$cookie = Database::getCookie($tracker);
    		$exucution = FALSE;
    		if (call_user_func($functionClass.'::checkCookie', $cookie))
    		{
    			$sess_cookie = $cookie;
    			//запускам процесс выполнения
    			$exucution = TRUE;
    		}
    		else
    		{
        		$sess_cookie = call_user_func($functionClass.'::getCookie', $tracker);
        		//запускам процесс выполнения
    			$exucution = TRUE;
            }

    		if ($exucution)
    		{
    			//получаем страницу для парсинга
                $forumPage = Sys::getUrlContent(
                	array(
                		'type'           => 'POST',
                		'header'         => 0,
                		'returntransfer' => 1,
                		'url'            => $url,
                		'cookie'         => $sess_cookie,
                		'sendHeader'     => array('Host' => $tracker, 'Content-length' => strlen($sess_cookie)),
                		'convert'        => array('windows-1251', 'utf-8//IGNORE'),
                	)
                );
            }
		}

		if ($tracker != 'new-rutor.org' && $tracker != 'casstudio.tv' && $tracker != 'torrents.net.ua' && $tracker != 'rustorka.com' && $tracker != 'tr.anidub.com')
			$forumPage = iconv('windows-1251', 'utf-8//IGNORE', $forumPage);

		if ($tracker == 'tr.anidub.com')
			$tracker = 'anidub.com';
		//preg_match('/<title>(.*)<\/title>/is', $forumPage, $array);
		preg_match('/<title>(.*)<\/title>/', $forumPage, $array);

		if ( ! empty($array[1]))
		{
			if ($tracker == 'anidub.com')
				$name = substr($array[1], 0, -23);
			elseif ($tracker == 'casstudio.tv')
				$name = substr($array[1], 48);
			elseif ($tracker == 'kinozal.tv')
				$name = substr($array[1], 0, -22);
			elseif ($tracker == 'nnm-club.me')
				$name = substr($array[1], 0, -20);
			elseif ($tracker == 'rutracker.org')
				$name = substr($array[1], 0, -34);
			elseif ($tracker == 'new-rutor.org')
				$name = substr($array[1], 17);
            elseif ($tracker == 'tracker.0day.kiev.ua')
				$name = substr($array[1], 6, -67);
            elseif ($tracker == 'torrents.net.ua')
				$name = substr($array[1], 0, -96);
            elseif ($tracker == 'pornolab.net')
                $name = substr($array[1], 0, -16);
            elseif ($tracker == 'rustorka.com')
                $name = substr($array[1], 0, -111);
            else
                $name = $array[1];
		}
		else
			$name = 'Неизвестный';
		return $name;
	}

	//добавляем в torrent-клиент
	public static function addToClient($id, $path, $hash, $tracker, $message, $date_str)
	{
        $torrentClient = Database::getSetting('torrentClient');
        $dir = dirname(__FILE__).'/';
        include_once $dir.$torrentClient.'.class.php';
        $server = Database::getSetting('serverAddress');
        $url = $server.$path;
        $dir = str_replace('class/', '', $dir);
        $url = str_replace($dir, '', $url);
        $status = call_user_func($torrentClient.'::addNew', $id, $url, $hash, $tracker);
        if ($status['status'])
        {
            Database::deleteFromTemp($id);
            return ' И добавлен в torrent-клиент.';
        }
        else
        {
            Database::saveToTemp($id, $url, $hash, $tracker, $message, $date_str);
            Errors::setWarnings($torrentClient, $status['msg']);
            return ' Но не добавлен в torrent-клиент и сохраненён.';
        }
	}

	//сохраняем torrent файл
	public static function saveTorrent($tracker, $name, $torrent, $id, $hash, $message, $date_str)
	{

			// Transmission
			try
			{
				#получаем настройки из базы
				$settings = Database::getAllSetting();
				foreach ($settings as $row)
				{
					extract($row);
				}

				$rpc = new TransmissionRPC($torrentAddress, $torrentLogin, $torrentPassword);
				// Add a torrent using the raw torrent data
				$result = $rpc->add_metainfo($torrent);
				print_r($result);
			} catch (Exception $e) {
				//die('[ERROR] ' . $e->getMessage() . PHP_EOL);
				print '[ERROR] ' . $e->getMessage() . PHP_EOL;
			}
      //отправляем уведомлении о новом торренте
      Notification::sendNotification('notification', $date_str, $tracker, $message, $name);

	}

	//преобразуем месяц из числового в текстовый
	public static function dateNumToString($date)
	{
	    $monthes_num = array('/10/', '/11/', '/12/', '/0?1/', '/0?2/', '/0?3/', '/0?4/', '/0?5/', '/0?6/', '/0?7/', '/0?8/', '/0?9/');
	    $monthes_ru = array('Окт', 'Ноя', 'Дек', 'Янв', 'Фев', 'Мар', 'Апр', 'Мая', 'Июн', 'Июл', 'Авг', 'Сен');
	    $month = preg_replace($monthes_num, $monthes_ru, $date);

	    return $month;
	}

	//преобразуем месяц из текстового в числовый
	public static function dateStringToNum($date)
	{
	    $monthes = array('/янв|Янв|Jan/i', '/фев|Фев|Feb/i', '/мар|Мар|Mar/i', '/апр|Апр|Apr/i', '/мая|май|Мая|мая|May/i', '/июн|Июн|Jun/i', '/июл|Июл|Jul/i', '/авг|Авг|Aug/i', '/сен|Сен|Sep/i', '/окт|Окт|Oct/i', '/ноя|Ноя|Nov/i', '/дек|Дек|Dec/i');
	    $monthes_num = array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12');
	    $month = preg_replace($monthes, $monthes_num, $date);

	    return $month;
	}

	//записываем время последнего запуска системы
	public static function lastStart()
	{
        $dir = dirname(__FILE__);
		$dir = str_replace('class', '', $dir);
		$date = date('d-m-Y H:i:s');
		file_put_contents($dir.'/laststart.txt', $date);
	}

	//проверяем что файл является torrent-файлом (ну пытаемся)
	public static function checkTorrentFile($torrent)
    {
        if (strlen($torrent) > 100)
        {
            if (preg_match('/announce/', $torrent))
                return TRUE;
            else
                return FALSE;
        }
        else
            return FALSE;
    }

    //получаем важные новости и кладём в БД
    public static function getNews()
    {
        // //получаем страницу
        // $page = Sys::getUrlContent(
        // 	array(
        // 		'type'           => 'GET',
        // 		'returntransfer' => 1,
        // 		'url'            => 'http://korphome.ru/torrent_monitor/news.xml',
        // 	)
        // );
        // //читаем xml
        // $page = @simplexml_load_string($page);
        // for ($i=0; $i<count($page->news); $i++)
        // {
        //     if ( ! Database::checkNewsExist($page->news[$i]->id))
        //         Database::insertNews($page->news[$i]->id, $page->news[$i]->text);
        // }
    }
}
?>
