<?php

	namespace SC;

	class Options {

		public const IBLOCK_ID = 2;
		private static $options = null;

		private static function setup(): void {
			\CModule::includeModule('iblock');
			$rsElements = \CIBlockElement::GetList(
				array(), array(
					'ACTIVE' => 'Y',
					'IBLOCK_ID' => self::IBLOCK_ID,
					'SECTION_ID' => false
				)
			);
			self::$options = [];
			while ($elt = $rsElements->GetNextElement()) {
				$f = $elt->GetFields();
				$f['PREVIEW_PICTURE'] = \CFile::GetFileArray($f['PREVIEW_PICTURE']);
				$f['DETAIL_PICTURE'] = \CFile::GetFileArray($f['DETAIL_PICTURE']);
				self::$options[$f['CODE']] = $f;
				self::$options[$f['CODE']]['PROPERTIES'] = $elt->GetProperties();
			}
		}
		
		public static function &get(?string $key = null): ?array {
			if (!self::$options)
				self::setup();
			if ($key)
				return @self::$options[$key] ?: null;
			return self::$options;
		}
	}
