<?php
	$_SERVER["DOCUMENT_ROOT"] = dirname(dirname(__DIR__));
	define("LANGUAGE_ID", "ru");
	define("NO_KEEP_STATISTIC", true);
	define("NOT_CHECK_PERMISSIONS", true);
	define("LOG_FILENAME", 'php://stderr');
	require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';
	class PhpunitFileExceptionHandlerLog extends Bitrix\Main\Diag\FileExceptionHandlerLog {
		public function write($exception, $logType) {
			$text = Bitrix\Main\Diag\ExceptionHandlerFormatter::format($exception, false, $this->level);
			$msg = date("Y-m-d H:i:s")." - Host: ".$_SERVER["HTTP_HOST"]." - ".static::logTypeToString($logType)." - ".$text."\n";
			fwrite(STDERR, $msg);
		}
	}
	
	$handler = new PhpunitFileExceptionHandlerLog;
	
	$bitrixExceptionHandler = \Bitrix\Main\Application::getInstance()->getExceptionHandler();
	
	$reflection = new \ReflectionClass($bitrixExceptionHandler);
	$property = $reflection->getProperty('handlerLog');
	$property->setAccessible(true);
	$property->setValue($bitrixExceptionHandler, $handler);
