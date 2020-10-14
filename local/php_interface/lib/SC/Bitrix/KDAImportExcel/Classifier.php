<?php
	namespace SC\Bitrix\KDAImportExcel;

	use SC\Bitrix\IBlock\IBlock;
	use SC\Bitrix\IBlock\Section;
	use SC\Bitrix\IBlock\Property;
	use SC\Bitrix\IBlock\Element;
	use SC\Bitrix\Util;
	use SC\Bitrix\EntityCreationException;
	use Exception;
	use ReflectionClass;

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
				try {
					$oProperty = Property::make($property);
				} catch (EntityCreationException $ex) {
					$oProperty = Property::fromArray($this->iblock->getProperty($property));
				}
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
			$properties = [];
			foreach ($config['properties'] as $property) {
				try {
					$oProperty = Property::make($property);
				} catch (EntityCreationException $ex) {
					$oProperty = Property::fromArray($this->iblock->getProperty($property));
				}
				$properties[$oProperty->getField('CODE')] = $oProperty;
			}
			$config['properties'] = $properties;
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

		public function execute(): void {
			$this->createSections();
			$this->distributeElements();
		}

		private function createSections(): void {
			global $DB;
			foreach ($this->arSections as $id => &$ar) {
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
				$ar['existingSections'] = $this->getExistingSections((int) $id);
			}
		}

		private function distributeElements(): void {
			global $DB;
			foreach ($this->retrieveElements() as $arElement) {
				$element = Element::fromArray($arElement);
				foreach ($this->arSections as $id => $ar) {
					$propValues = [];
					foreach ($ar['config']['properties'] as $property) {
						$propValues[] = $element->getProperty($property->getField('CODE'))['VALUE'];
					}
					if (@$ar['config']['callbacks']['createCode']) {
						$valueCode = $ar['config']['callbacks']['createCode'](...$propValues);
					} elseif (sizeof($propValues) === 1) {
						$valueCode = array_values($ar['config']['properties'])[0]->getField('CODE');
						// $elProperties = $element->getProperties();
						// $valueCode = $elProperties[$valueCode];
						$valueCode = $element->getProperty($valueCode);
						$valueCode = $valueCode ? Util::translit($valueCode) : null;
					} else {
						throw new Exception('createCode callback not specified for multiple distinct');
					}
					if ($valueCode)
						$element->setParents([$ar['existingSections'][$valueCode]]);
				}
				// $element->saveParents();
				$element->save();
				$propValues = [];
				$mainExistingSections = $this->arSections[(string) $this->mainSection['section']->getID()]['existingSections'];
				foreach ($this->mainSection['config']['properties'] as $property) {
					$propValues[] = $element->getProperty($property->getField('CODE'))['VALUE'];
				}
				if (@$this->mainSection['config']['callbacks']['createCode']) {
					$valueCode = $this->mainSection['config']['callbacks']['createCode'](...$propValues);
				} elseif (sizeof($propValues) === 1) {
					$valueCode = array_values($this->mainSection['config']['properties'])[0]->getField('CODE');
					$valueCode = $element->getProperty($valueCode);
					$valueCode = $valueCode ? Util::translit($valueCode) : null;
				} else {
					throw new Exception('createCode callback not specified for multiple distinct');
				}
				if ($valueCode) {
					$DB->Query("UPDATE b_iblock_element SET IBLOCK_SECTION_ID = {$mainExistingSections[$valueCode]} WHERE ID = {$element->getID()}");
				}
			}
			if ($this->elementSource === self::ELEMENT_SOURCE_SECTION)
				$DB->Query("DELETE FROM b_iblock_section_element WHERE IBLOCK_SECTION_ID = {$this->elementSourceSection->getID()}");
		}

		private function retrieveElements(): array {
			$arFilter = [];
			if ($this->elementSource !== self::ELEMENT_SOURCE_ALL)
				$arFilter['SECTION_ID'] = $this->elementSource === self::ELEMENT_SOURCE_ROOT ? false : $this->elementSourceSection->getID();
			return $this->iblock->getElements($arFilter, []);
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