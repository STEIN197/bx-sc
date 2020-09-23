<?php

	namespace SC;

	use \CModule;
	use \CIBlockElement;
	use \CFile;

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
			CModule::includeModule('iblock');
			$rsElements = CIBlockElement::GetList(
				array(), array(
					'ACTIVE' => 'Y',
					'IBLOCK_ID' => $this->iblockID,
					'SECTION_ID' => false
				)
			);
			while ($elt = $rsElements->GetNextElement()) {
				$f = $elt->GetFields();
				$f['PREVIEW_PICTURE'] = CFile::GetFileArray($f['PREVIEW_PICTURE']);
				$f['DETAIL_PICTURE'] = CFile::GetFileArray($f['DETAIL_PICTURE']);
				$this->options[$f['CODE']] = $f;
				$this->options[$f['CODE']]['PROPERTIES'] = $elt->GetProperties();
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
