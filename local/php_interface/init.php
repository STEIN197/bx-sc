<?php
	require 'AutoloadMapping.php';
	$mapping = new AutoloadMapping(__DIR__.DIRECTORY_SEPARATOR.'lib');
	$mapping->registerClasses();
	interface delete_it {
		function getIBlock();
		function getParent();
		function getChildren();

		function getFields();
		function getProperties();
		function setFields();
		function setProperties();
		function getField();
		function getProperty();
		function setField();
		function setProperty();

		function getID(): int;
		function save();
		function delete();
		static function getList();
		static function castTypes(array $arFields);
		static function getByID(int $id);
		function getDistinctValues();
	}

