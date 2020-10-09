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
				$result = $csection->Update($this->id, array_merge($this->arFields ?: [], $this->arProperties ?: []));
			} else {
				$result = $csection->Add(array_merge($this->arFields ?: [], $this->arProperties ?: []));
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
		public function getDistinctValues(array $properties, array $arFilter = null, bool $includeInactive = false): ?array {
			global $DB;
			if (!$this->getID())
				return null;
			foreach ($properties as &$property) {
				$oProp = Property::make($property);
				if (!$oProp && is_string($property))
					$oProp = Property::wrap($this->getIBlock()->getProperty($property));
				$property = $oProp;
			}
			$q = $this->getDistinctValuesQuery($properties, $arFilter, $includeInactive);
			$result = [];
			$rs = $DB->Query($q);
			while ($ar = $rs->Fetch())
				$result[] = $ar;
			natsort($result);
			return $result;
		}

		public function getDistinctValuesQuery(array $properties, ?array $arFilter = null, bool $includeInactive = false): string {
			$clauseSelect = [];
			$clauseFrom = 'b_iblock_element';
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
				foreach ($properties as $oProp) {
					if ($oProp->isNumeric())
						$clauseSelect[] = "TRIM(TRAILING '.' FROM TRIM(TRAILING '0' FROM prop_{$oProp->getField('CODE')}.VALUE_NUM)) AS {$oProp->getField('CODE')}";
					else
						$clauseSelect[] = "prop_{$oProp->getField('CODE')}.VALUE AS {$oProp->getField('CODE')}";
					$clauseFrom .= " LEFT JOIN b_iblock_element_property AS prop_{$oProp->getField('CODE')} ON b_iblock_element.ID = prop_{$oProp->getField('CODE')}.IBLOCK_ELEMENT_ID";
				}
				if ($arFilter) {
					foreach ($arFilter as $code => $value) {
						$arProp = $arProperties[$code];
						$clauseFrom .= " LEFT JOIN b_iblock_element_property AS filter_{$code} ON b_iblock_element.ID = filter_{$code}.IBLOCK_ELEMENT_ID";
						$clauseWhere[] = "filter_{$code}.IBLOCK_PROPERTY_ID = {$arProp['ID']}";
						if ($arProp['PROPERTY_TYPE'] === 'N')
							$clauseWhere[] = "filter_{$code}.VALUE_NUM = {$value}";
						else
							$clauseWhere[] = "filter_{$code}.VALUE = '{$value}'";
					}
				}
			// Свойства в отдельной таблице
			} else {
				foreach ($properties as $oProp) {
					if ($oProp->isMultiple()) {
						$valuesTableName = "b_iblock_element_prop_m{$iblock->getID()}";
						if ($oProp->isNumeric()) {
							$clauseSelect[] = "TRIM(TRAILING '.' FROM TRIM(TRAILING '0' FROM prop_{$oProp->getField('CODE')}.VALUE_NUM)) AS {$oProp->getField('CODE')}";
						} else {
							$clauseSelect[] = "prop_{$oProp->getField('CODE')}.VALUE AS {$oProp->getField('CODE')}";
						}
					} else {
						$valuesTableName = "b_iblock_element_prop_s{$iblock->getID()}";
						if ($oProp->isNumeric()) {
							$clauseSelect[] = "TRIM(TRAILING '.' FROM TRIM(TRAILING '0' FROM prop_{$oProp->getField('CODE')}.PROPERTY_{$oProp->getID()})) AS {$oProp->getField('CODE')}";
						} else {
							$clauseSelect[] = "prop_{$oProp->getField('CODE')}.PROPERTY_{$oProp->getID()} AS {$oProp->getField('CODE')}";
						}
					}
					$clauseFrom .= " LEFT JOIN {$valuesTableName} AS prop_{$oProp->getField('CODE')} ON b_iblock_element.ID = prop_{$oProp->getField('CODE')}.IBLOCK_ELEMENT_ID";
				}
				if ($arFilter) {
					foreach ($arFilter as $code => $value) {
						$arProp = $arProperties[$code];
						if ($arProp['MULTIPLE'] === 'Y') {
							$clauseFrom .= " LEFT JOIN b_iblock_element_prop_m{$iblock->getID()} AS property_{$code} ON b_iblock_element.ID = property_{$code}.IBLOCK_ELEMENT_ID";
							$clauseWhere[] = "filter_{$code}.IBLOCK_PROPERTY_ID = {$arProp['ID']}";
							if ($arProp['PROPERTY_TYPE'] === 'N')
								$clauseWhere[] = "filter_{$code}.VALUE_NUM = {$value}";
							else
								$clauseWhere[] = "filter_{$code}.VALUE = '{$value}'";
						} else {
							if ($arProp['PROPERTY_TYPE'] === 'N')
								$clauseWhere[] = "b_iblock_element_prop_s{$iblock->getID()}.PROPERTY_{$arProp['ID']} = {$value}";
							else
								$clauseWhere[] = "b_iblock_element_prop_s{$iblock->getID()}.PROPERTY_{$arProp['ID']} = '{$value}'";
						}
					}
				}
			}
			$clauseSelect = join(', ', $clauseSelect);
			$clauseFrom .= " LEFT JOIN b_iblock_section_element ON b_iblock_element.ID = b_iblock_section_element.IBLOCK_ELEMENT_ID";
			$clauseWhere = join(' AND ', $clauseWhere);
			return "SELECT DISTINCT {$clauseSelect} FROM {$clauseFrom} WHERE {$clauseWhere}";
		}

		public function getElements(array $arFilter = [], array $arOrder = ['SORT' => 'ASC'], ?array $arSelect = null, ?array $arNav = null): array {
			return Element::getList(array_merge($arFilter, ['IBLOCK_ID' => $this->getField('IBLOCK_ID'), 'SECTION_ID' => $this->id]), $arOrder, $arSelect, $arNav);
		}

		public function getSections(array $arFilter = [], array $arOrder = ['SORT' => 'ASC'], ?array $arSelect = null, ?array $arNav = null): array {
			return Section::getList(array_merge($arFilter, ['IBLOCK_ID' => $this->getField('IBLOCK_ID'), 'SECTION_ID' => $this->id]), $arOrder, $arSelect, $arNav);
		}

		public function getSubsections(array $arFilter = [], array $arOrder = ['SORT' => 'ASC'], ?array $arSelect = null, ?array $arNav = null): array {
			return Section::getList(array_merge($arFilter, [
				'IBLOCK_ID' => $this->getField('IBLOCK_ID'),
				'>LEFT_MARGIN' => $this->getField('LEFT_MARGIN'),
				'<RIGHT_MARGIN' => $this->getField('RIGHT_MARGIN')
			]), $arOrder, $arSelect, $arNav);
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
	}
