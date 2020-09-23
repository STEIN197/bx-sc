<?php
	namespace SC\IBlock;

	use \CIBlock;
	use \CIBlockSection;
	use \Exception;

	class Section extends TreeEntity {

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
		public function getDistinctValues(array $arProperty, bool $includeInactive = true, array $arFilter = null): array {
			global $DB;
			$result = [];
			$q = $this->getDistinctValuesQuery($arProperty, $includeInactive, $arFilter);
			$rs = $DB->Query($q);
			while ($ar = $rs->Fetch())
				$result[] = $ar['VALUE'];
			natsort($result);
			return $result;
		}

		public function getElements(?array $arOrder = ['SORT' => 'ASC'], ?array $arFilter = null, ?array $arGroupBy = null, ?array $arNavStartParams = null, ?array $arSelect = null): array {
			$arFilter = array_merge($arFilter, [
				'IBLOCK_ID' => $this->getFields()['IBLOCK_ID'],
				'SECTION_ID' => $this->getFields()['ID'],
			]);
			$rs = CIBlockElement::GetList($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelect);
			$result = [];
			while ($o = $rs->GetNextElement()) {
				$f = $o->GetFields();
				$f['PROPERTIES'] = $o->GetProperties();
				$result[] = $f;
			}
			return $result;
		}

		public function getDistinctValuesQuery(array $arProperty, bool $includeInactive = true, array $arFilter = null): string {
			$arIBlock = $this->getIBlock()->getFields();
			$arSection = $this->getFields();
			// Свойства в общей таблице
			$clauseWhere = 'b_iblock_section_element.IBLOCK_SECTION_ID IN ('.join(',', $this->getSubsectionsIDs()).')';
			if ($arIBlock['VERSION'] == 1) {
				if ($arProperty['PROPERTY_TYPE'] === 'N')
					$clauseSelect = "DISTINCT TRIM(TRAILING '.' FROM TRIM(TRAILING '0' FROM b_iblock_element_property.VALUE_NUM)) AS VALUE";
				else
					$clauseSelect = "DISTINCT b_iblock_element_property.VALUE";
				$clauseFrom = "b_iblock_section_element LEFT JOIN b_iblock_element ON b_iblock_section_element.IBLOCK_ELEMENT_ID = b_iblock_element.ID LEFT JOIN b_iblock_element_property ON b_iblock_section_element.IBLOCK_ELEMENT_ID = b_iblock_element_property.IBLOCK_ELEMENT_ID";
				$clauseWhere .= " AND VALUE IS NOT NULL";
				if (!$includeInactive)
					$clauseWhere .= ' AND b_iblock_element.ACTIVE = \'Y\'';
				// TODO: Реализовать $arFilter
			// Свойства в отдельной таблице
			} else {
				if ($arProperty['MULTIPLE'] === 'Y') {
					if ($arProperty['PROPERTY_TYPE'] === 'N')
						$clauseSelect = "DISTINCT TRIM(TRAILING '.' FROM TRIM(TRAILING '0' FROM b_iblock_element_prop_m{$arIBlock['ID']}.VALUE_NUM)) AS VALUE";
					else
						$clauseSelect = "DISTINCT b_iblock_element_prop_m{$arIBlock['ID']}.VALUE";
					$clauseFrom = "b_iblock_section_element LEFT JOIN b_iblock_element ON b_iblock_section_element.IBLOCK_ELEMENT_ID = b_iblock_element.ID LEFT JOIN b_iblock_element_prop_m{$arIBlock['ID']} ON b_iblock_section_element.IBLOCK_ELEMENT_ID = b_iblock_element_prop_m{$arIBlock['ID']}.IBLOCK_ELEMENT_ID";
					$clauseWhere .= " AND b_iblock_element_prop_m{$arIBlock['ID']}.VALUE IS NOT NULL AND b_iblock_element_prop_m{$arIBlock['ID']}.IBLOCK_PROPERTY_ID = {$arProperty['ID']}";
					if ($arFilter) {
						$clauseFrom .= " LEFT JOIN b_iblock_element_prop_s{$arIBlock['ID']} ON b_iblock_section_element.IBLOCK_ELEMENT_ID = b_iblock_element_prop_s{$arIBlock['ID']}.IBLOCK_ELEMENT_ID";
					}
				} else {
					if ($arProperty['PROPERTY_TYPE'] === 'N')
						$clauseSelect = "DISTINCT TRIM(TRAILING '.' FROM TRIM(TRAILING '0' FROM PROPERTY_{$arProperty['ID']})) AS VALUE";
					else
						$clauseSelect = "DISTINCT PROPERTY_{$arProperty['ID']} AS VALUE";
					$clauseFrom = "b_iblock_element_prop_s{$arIBlock['ID']} RIGHT JOIN b_iblock_section_element USING(IBLOCK_ELEMENT_ID)";
					$clauseWhere .= " AND PROPERTY_{$arProperty['ID']} IS NOT NULL";
				}
				if ($arFilter) {
					$arProperties = $this->getIBlock()->getProperties();
					foreach ($arFilter as $code => $value) {
						$clauseWhere .= " AND PROPERTY_{$arProperties[$code]['ID']} = ".($arProperties[$code]['PROPERTY_TYPE'] === 'N' ? $value : "'{$value}'");
					}
				}
			}
			return "SELECT {$clauseSelect} FROM {$clauseFrom} WHERE {$clauseWhere}";
		}

		private function getSubsectionsIDs(): array {
			$arIBlock = $this->getIBlock()->getFields();
			$arSection = $this->getFields();
			$rsTree = CIBlockSection::GetList(
				array(), array(
					'IBLOCK_ID' => $arIBlock['ID'],
					'ACTIVE' => 'Y',
					'>=LEFT_MARGIN' => $arSection['LEFT_MARGIN'],
					'<=RIGHT_MARGIN' => $arSection['RIGHT_MARGIN']
				), false, array(
					'ID'
				)
			);
			$result = [];
			while ($ar = $rsTree->GetNext())
				$result[] = $ar['ID'];
			return $result;
		}

		public static function getByID(int $id): ?self {
			if (!CIBlockSection::GetByID($id)->GetNext())
				return null;
			$section = new self;
			$section->id = $id;
			return $section;
		}
	}
