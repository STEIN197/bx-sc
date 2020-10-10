<?php
	require 'vendor/autoload.php';

	AddEventHandler('kda.importexcel', 'OnEndImport', [SC\KDAImportExcel\EventHandler::class, 'onEndImport']);
