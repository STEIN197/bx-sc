<?php

	namespace SC\Bitrix;

	final class Options {

		private static $instance;
		
		private $options = [];
		private $iblockID;

		private function __construct() {
			$this->iblockID = $iblockID;
			$this->setup();
			self::$instance = $this;
		}

		private function setup(): void {
			if (!\CModule::includeModule('iblock'))
				throw new \Exception('Can\'t load module \'iblock\'');
			$arElements = Element::getList([
				'ACTIVE' => 'Y',
				'IBLOCK_ID' => $this->iblockID,
				'SECTION_ID' => false
			]);
			foreach ($arElements as &$element) {
				$element['PREVIEW_PICTURE'] = \CFile::GetFileArray($element['PREVIEW_PICTURE']);
				$element['DETAIL_PICTURE'] = \CFile::GetFileArray($element['DETAIL_PICTURE']);
				$this->options[$element['CODE']] = $element;
			}
		}
		
		public function &get(?string $key = null): ?array {
			if ($key)
				return @$this->options[$key] ?: null;
			return $this->options;
		}

		public static function getInstance(): self {
			return self::$instance;
		}

		public static function init(int $iblockID): void {
			self::$instance = new self($iblockID);
		}
	}
