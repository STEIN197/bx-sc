<?php
	require 'vendor/autoload.php';

	AddEventHandler('kda.importexcel', 'OnEndImport', [SC\EventHandler::class, 'onEndImport']);
