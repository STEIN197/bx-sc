<?php
	namespace SC\KDAImportExcel;

	use SC\IBlock\IBlock;
	use SC\IBlock\Section;
	use SC\IBlock\Property;
	use SC\Util;
	use \Exception;
	use \ReflectionClass;

	// TODO: Добавить лимит на количество записей
	// TODO: Добавить внутреннюю классификацию типа /d/dxt/ => /57/57x4/ или /std/angle/ => /gost-12/90/
	// TODO: Автоматическая деактивация и активация разделов
	class Classifier {

		public const ELEMENT_SOURCE_ALL = 0;
		public const ELEMENT_SOURCE_ROOT = 1;
		public const ELEMENT_SOURCE_SECTION = 2; // TODO: Удалять все связки с этим разделом

		/** @var IBlock */
		private $iblock;
		/** @var array */
		private $arSections;
		/** @var array */
		private $mainSection;
		/** @var int */
		private $elementSource;
		/** @var Section */
		private $elementSourceSection;

		public function __construct($iblock) {
			$this->iblock = IBlock::make($iblock);
			if (!$this->iblock)
				throw new Exception('Cannot create make IBlock object');
		}

		/**
		 * @param array $config Содержит ключи 'properties', 'callbacks', 'child'
		 */
		public function add($section, array $config): void {
			$section = Section::make($section);
			if (!$section)
				throw new Exception('Unable to create section');
			$properties = [];
			foreach ($config['properties'] as $property) {
				$oProperty = Property::make($property);
				if (!$oProperty && is_string($property))
					$oProperty = Property::wrap($this->iblock->getProperty($property));
				if (!$oProperty)
					throw new Exception('Unable to create property');
				$properties[$oProperty->getField('CODE')] = $oProperty;
			}
			$config['properties'] = $properties;
			// if ($config['child']) {
			// 	$config['child'] = new self($this->iblock);
			// }
			$this->arSections[(string) $section->getID()] = [
				'section' => $section,
				'config' => $config
			];
		}

		public function addMain($section, array $config): void {
			$section = Section::make($section);
			$this->add($section, $config);
			$this->mainSection = [
				'section' => $section,
				'config' => $config
			];
		}

		/**
		 * @param int $source;
		 * @param Section|array|int $section
		 * @return void
		 */
		public function setElementSource(int $source, $section = null): void {
			self::checkSource($source);
			$this->elementSource = $source;
			if ($source === self::ELEMENT_SOURCE_SECTION) {
				$this->elementSourceSection = Section::make($section);
				if (!$this->elementSourceSection)
					throw new Exception('Unable to create section object as the source');
			}
		}

		// TODO
		public function execute(): void {
			// $arFilter = [];
			// if ($this->elementSource === self::ELEMENT_SOURCE_ROOT)
			// 	$arFilter['SECTION_ID'] = false;
			// elseif ($this->elementSource === self::ELEMENT_SOURCE_SECTION)
			// 	$arFilter['SECTION_ID'] = $this->elementSourceSection;
			// $arElements = $this->iblock->getElements($filter, []);
			foreach ($this->arSections as $id => $ar) {
				$arExistingSections = $this->getExistingSections((int) $id);
				$config = &$ar['config'];
				if ($this->elementSource === self::ELEMENT_SOURCE_SECTION)
					$arDistinctValues = $this->elementSourceSection->getDistinctValues($config['properties']);
				else
					$arDistinctValues = $this->iblock->getDistinctValues($config['properties']);
				foreach ($arDistinctValues as $row) {
					$rowUnkeyed = array_values($row);
					$isSingleValue = sizeof($row) === 1;
					if ($config['callbacks']['createCode']) {
						$valueCode = $config['callbacks']['createCode'](...$rowUnkeyed);
					} elseif ($isSingleValue) {
						$valueCode = Util::translit($rowUnkeyed[0]);
					} else {
						throw new Exception('createCode callback not specified for multiple distinct');
					}
					if (isset($arExistingSections[$valueCode]))
						continue;
					if ($config['callbacks']['createName']) {
						$valueName = $config['callbacks']['createName'](...$rowUnkeyed);
					} elseif ($isSingleValue) {
						$valueName = $rowUnkeyed[0];
					} else {
						throw new Exception('createName callback not specified for multiple distinct');
					}
					if ($config['callbacks']['sort'])
						$sort = $config['callbacks']['sort'](...$rowUnkeyed);
					elseif ($isSingleValue && array_values($config['properties'])[0]->isNumeric())
						$sort = $rowUnkeyed[0] * 100;
					else
						$sort = '';
					$oSection = new Section([
						'IBLOCK_ID' => $this->iblock->getID(),
						'IBLOCK_SECTION_ID' => $id,
						'NAME' => $valueName,
						'CODE' => $valueCode,
						'SORT' => $sort
					]);
					$oSection->save();
				}
			}
			$this->mainSection;
		}

		private function getExistingSections(int $parentSection): array {
			$arExisting = $this->iblock->getSections([
				'SECTION_ID' => $parentSection
			], [], [
				'CODE', 'ID'
			]);
			return array_combine(array_column($arExisting, 'CODE'), array_column($arExisting, 'ID'));
		}

		/**
		 * @param int $source
		 * @return void
		 * @throws Exception
		 */
		private static function checkSource(int $source): void {
			$ref = new ReflectionClass(__CLASS__);
			$sourceConstants = [];
			foreach ($ref->getConstants() as $constName => $constValue)
				if (strpos($constName, 'ELEMENT_SOURCE_') === 0)
					$sourceConstants[$constName] = $constValue;
			if (!in_array($source, $sourceConstants))
				throw new Exception("Unknown source const: {$source}");
		}
	}