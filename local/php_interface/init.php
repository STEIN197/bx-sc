<?php
	require 'vendor/autoload.php';

	AddEventHandler('kda.importexcel', 'OnEndImport', [SC\Bitrix\EventHandler::class, 'onEndImport']);
