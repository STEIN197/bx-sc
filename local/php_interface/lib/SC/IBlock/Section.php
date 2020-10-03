<?php
	namespace SC\IBlock;

	use \CIBlockSection;
	use \Exception;

	class Section extends Entity implements EntityContainer {

		use Parentable;
		use Propertiable;

		protected $iblock;

		public function __construct(?array $arFields = null, ?array $arProperties = null) {
			$this->arFields = $arFields;
			$this->arProperties = $arProperties;
		}

		public function save(): void {
			$csection = new CIBlockSection;
			if ($this->id) {
				$result = $csection->Update($this->id, array_merge($this->arFields, $this->arProperties));
			} else {
				$result = $csection->Add(array_merge($this->arFields, $this->arProperties));
				$this->id = $result;
			}
			if (!$result)
				throw new Exception($csection->LAST_ERROR);
		}

		public function delete(): void {
			if (!$this->id)
				return;
			if (CIBlockSection::delete($this->id)) {
				$this->id = null;
				unset($this->arFields['ID']);
			} else {
				throw new Exception;
			}
		}

		protected function fetchFields(): void {
			$this->arFields = CIBlockSection::GetByID($this->id)->GetNext();
			$this->arFields['PICTURE'] = \CFile::GetFileArray($this->arFields['PICTURE']);
		}

		protected function fetchProperties(): void {
			$this->arProperties = CIBlockSection::GetList(
				array(), array(
					'IBLOCK_ID' => $this->getIBlock()->getID(),
					'ID' => $this->id
				), false, array(
					'UF_*'
				)
			)->GetNext();
		}

		public function getIBlock(): IBlock {
			if (!$this->iblock)
				$this->iblock = IBlock::getByID($this->arFields['IBLOCK_ID']);
			return $this->iblock;
		}

		public function getParent(): ?Section {
			$parentID = $this->getFields()['IBLOCK_SECTION_ID'];
			return $parentID ? self::getByID($parentID) : null;
		}

		/**
		 * Получает все значения для данного раздела данного свойства.
		 * @param array $arProperty Для какого свойтсва получать значения.
		 * @param bool $includeInactive Включать в выборку неактивные элементы.
		 * @param array $arFilter Доп. фильтрация для выборки значений.
		 */
		public function getDistinctValues($property, array $arFilter = null, bool $includeInactive = false): ?array {
			global $DB;
			if (!$this->getID())
				return null;
			$property = Property::make($property);
			$q = $this->getDistinctValuesQuery($property, $arFilter, $includeInactive);
			$result = [];
			$rs = $DB->Query($q);
			while ($ar = $rs->Fetch())
				$result[] = $ar['VALUE'];
			natsort($result);
			return $result;
		}

		public function getDistinctValuesQuery(Property $property, ?array $arFilter = null, bool $includeInactive = false): string {
			$isMultiple = $property->getField('MULTIPLE') === 'Y';
			$isNum = $property->getField('PROPERTY_TYPE') === 'N';
			$clauseSelect = $clauseFrom = '';
			$clauseWhere = [
				"b_iblock_element.IBLOCK_ID = {$this->getField('IBLOCK_ID')}",
				"b_iblock_section_element.IBLOCK_SECTION_ID = {$this->getID()}"
			];
			if (!$includeInactive)
				$clauseWhere[] = "b_iblock_element.ACTIVE = 'Y'";
			$iblock = $this->getIBlock();
			$arProperties = $iblock->getProperties();
			// Свойства в общей таблице
			if ($iblock->getField('VERSION') == 1) {
				if ($isNum) {
					$clauseSelect = "DISTINCT TRIM(TRAILING '.' FROM TRIM(TRAILING '0' FROM b_iblock_element_property.VALUE_NUM)) AS VALUE";
				} else {
					$clauseSelect = "DISTINCT b_iblock_element_property.VALUE";
				}
				$clauseFrom = "b_iblock_element LEFT JOIN b_iblock_element_property ON b_iblock_element.ID = b_iblock_element_property.IBLOCK_ELEMENT_ID";
				$clauseWhere[] = "b_iblock_element_property.VALUE IS NOT NULL";
				if ($arFilter) {
					foreach ($arFilter as $code => $value) {
						$arProp = $arProperties[$code];
						$clauseFrom .= " LEFT JOIN b_iblock_element_property AS property_{$code} ON b_iblock_element.ID = property_{$code}.IBLOCK_ELEMENT_ID";
						$clauseWhere[] = "property_{$code}.IBLOCK_PROPERTY_ID = {$arProp['ID']}";
						if ($arProp['PROPERTY_TYPE'] === 'N')
							$clauseWhere[] = "property_{$code}.VALUE_NUM = {$value}";
						else
							$clauseWhere[] = "property_{$code}.VALUE = '{$value}'";
					}
				}
			// Свойства в отдельной таблице
			} else {
				if ($isMultiple) {
					$valuesTableName = "b_iblock_element_prop_m{$iblock->getID()}";
					if ($isNum) {
						$clauseSelect = "DISTINCT TRIM(TRAILING '.' FROM TRIM(TRAILING '0' FROM {$valuesTableName}.VALUE_NUM)) AS VALUE";
					} else {
						$clauseSelect = "DISTINCT {$valuesTableName}.VALUE";
					}
					$clauseWhere[] = "{$valuesTableName}.VALUE IS NOT NULL";
				} else {
					$valuesTableName = "b_iblock_element_prop_s{$iblock->getID()}";
					if ($isNum) {
						$clauseSelect = "DISTINCT TRIM(TRAILING '.' FROM TRIM(TRAILING '0' FROM {$valuesTableName}.PROPERTY_{$property->getID()})) AS VALUE";
					} else {
						$clauseSelect = "DISTINCT {$valuesTableName}.PROPERTY_{$property->getID()} AS VALUE";
					}
					$clauseWhere[] = "{$valuesTableName}.PROPERTY_{$property->getID()} IS NOT NULL";
				}
				$clauseFrom = "b_iblock_element LEFT JOIN {$valuesTableName} ON b_iblock_element.ID = {$valuesTableName}.IBLOCK_ELEMENT_ID";
				if ($arFilter) {
					foreach ($arFilter as $code => $value) {
						$arProp = $arProperties[$code];
						if ($arProp['MULTIPLE'] === 'Y') {
							$clauseFrom .= " LEFT JOIN b_iblock_element_prop_m{$iblock->getID()} AS property_{$code} ON b_iblock_element.ID = property_{$code}.IBLOCK_ELEMENT_ID";
							$clauseWhere[] = "property_{$code}.IBLOCK_PROPERTY_ID = {$arProp['ID']}";
							if ($arProp['PROPERTY_TYPE'] === 'N')
								$clauseWhere[] = "property_{$code}.VALUE_NUM = {$value}";
							else
								$clauseWhere[] = "property_{$code}.VALUE = '{$value}'";
						} else {
							if ($arProp['PROPERTY_TYPE'] === 'N')
								$clauseWhere[] = "b_iblock_element_prop_s{$iblock->getID()}.PROPERTY_{$arProp['ID']} = {$value}";
							else
								$clauseWhere[] = "b_iblock_element_prop_s{$iblock->getID()}.PROPERTY_{$arProp['ID']} = '{$value}'";
						}
					}
				}
			}
			$clauseFrom .= " LEFT JOIN b_iblock_section_element ON b_iblock_element.ID = b_iblock_section_element.IBLOCK_ELEMENT_ID";
			$clauseWhere = join(' AND ', $clauseWhere);
			return "SELECT {$clauseSelect} FROM {$clauseFrom} WHERE {$clauseWhere}";
		}

		public static function getList(array $arFilter, array $arOrder = ['SORT' => 'ASC'], ?array $arSelect = null, ?array $arNav = null): array {
			$rs = CIBlockSection::GetList($arOrder, $arFilter, false, $arSelect, $arNav);
			$result = [];
			while ($ar = $rs->GetNext())
				$result[] = $ar;
			return $result;
		}

		public static function getByID(int $id, bool $onlyStub = false): ?Section {
			$o = null;
			if ($onlyStub) {
				$o = new self;
				$o->id = $id;
			} else {
				$arFields = CIBlockSection::GetByID($id)->GetNext();
				if ($arFields)
					$o = self::wrap($arFields);
			}
			return $o;
		}

		public function getElements(array $arFilter = [], array $arOrder = ['SORT' => 'ASC'], ?array $arSelect = null, ?array $arNav = null): array {
			return Element::getList(array_merge($arFilter, ['IBLOCK_ID' => $this->getField('IBLOCK_ID'), 'SECTION_ID' => $this->id]), $arOrder, $arSelect, $arNav);
		}

		public function getSections(array $arFilter = [], array $arOrder = ['SORT' => 'ASC'], ?array $arSelect = null, ?array $arNav = null): array {
			return Section::getList(array_merge($arFilter, ['IBLOCK_ID' => $this->getField('IBLOCK_ID'), 'SECTION_ID' => $this->id]), $arOrder, $arSelect, $arNav);
		}
	}
