<?php
	require 'AutoloadMapping.php';
	$mapping = new AutoloadMapping(__DIR__.DIRECTORY_SEPARATOR.'lib');
	$mapping->registerClasses();
