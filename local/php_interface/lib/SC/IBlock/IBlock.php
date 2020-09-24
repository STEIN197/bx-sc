<?php
	namespace SC\IBlock;

	use \CIBlock;
	use \Exception;

	class IBlock extends Entity implements EntityContainer {

		use Propertiable;

		public function __construct(?array $arFields = null, ?array $arProperties = null) {
			$this->arFields = $arFields;
			$this->arProperties = $arProperties;
		}

		public function getIBlock(): ?IBlock {
			return $this;
		}

		public function save(): void {
			$ciblock = new CIBlock;
			if ($this->id) {
				$result = $ciblock->Update($this->id, $this->arFields);
			} else {
				$result = $ciblock->Add($this->arFields);
				if ($result)
					$this->id = $result;
			}
			if (!$result)
				throw new Exception($ciblock->LAST_ERROR);
			$this->saveProperties();
		}

		public function delete(): void {
			if (!$this->id)
				return;
			if (CIBlock::delete($this->id)) {
				$this->id = null;
				unset($this->arFields['ID']);
			} else {
				throw new Exception;
			}
		}

		protected function fetchFields(): void {
			$this->arFields = CIBlock::GetByID($this->id)->GetNext();
		}

		protected function fetchProperties(): void {
			$this->arProperties = Property::getList([
				'IBLOCK_ID' => $this->id,
			]);
		}

		private function saveProperties(): void {
			$arPropertyCodes = array_keys($this->arProperties);
			$arExistingProperties = Property::getList([
				'IBLOCK_ID' => $this->id,
				'CODE' => $arPropertyCodes
			]);
			foreach ($arExistingProperties as $key => $arProperty)
				Property::wrap($arProperty)->save();
			$arNewPropertyCodes = array_diff($arPropertyCodes, array_keys($arExistingProperties));
			foreach ($arNewPropertyCodes as $code)
				(new Property($this->arProperties[$code]))->save();
		}

		public static function getList(array $arFilter, array $arOrder = ['SORT' => 'ASC'], ?array $arSelect = [], ?array $arNav = null): array {
			$rs = CIBlock::GetList($arOrder, $arFilter);
			$result = [];
			while ($ar = $rs->GetNext())
				$result[] = $ar;
			return $result;
		}

		public static function getByID(int $id, bool $onlyStub = false): ?IBlock {
			$o = null;
			if ($onlyStub) {
				$o = new self;
				$o->id = $id;
			} else {
				$arFields = CIBlock::GetByID($id)->GetNext();
				if ($arFields)
					$o = self::wrap($arFields);
			}
			return $o;
		}

		public function getElements(array $arFilter = [], array $arOrder = ['SORT' => 'ASC'], ?array $arSelect = null, ?array $arNav = null): array {
			return Element::getList(array_merge($arFilter, ['IBLOCK_ID' => $this->id]), $arOrder, $arSelect, $arNav);
		}

		public function getSections(array $arFilter = [], array $arOrder = ['SORT' => 'ASC'], ?array $arSelect = null, ?array $arNav = null): array {
			return Section::getList(array_merge($arFilter, ['IBLOCK_ID' => $this->id]), $arOrder, $arSelect, $arNav);
		}

		public function getDistinctValues($property, ?array $arFilter = null, bool $includeInactive = false): ?array {
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

		protected function getDistinctValuesQuery(Property $property, ?array $arFilter = null, bool $includeInactive = false): string {
			$isMultiple = $property->getField('MULTIPLE') === 'Y';
			$isNum = $property->getField('PROPERTY_TYPE') === 'N';
			$clauseSelect = $clauseFrom = $clauseWhere = '';
			$clauseWhere = ["b_iblock_element.IBLOCK_ID = {$this->getID()}"];
			// Свойства в общей таблице
			if ($this->getField('VERSION') == 1) {
				if ($isNum) {
					$clauseSelect = "DISTINCT TRIM(TRAILING '.' FROM TRIM(TRAILING '0' FROM b_iblock_element_property.VALUE_NUM)) AS VALUE";
				} else {
					$clauseSelect = "DISTINCT b_iblock_element_property.VALUE";
				}
				$clauseFrom = "b_iblock_element LEFT JOIN b_iblock_element_property ON b_iblock_element.ID = b_iblock_element_property.IBLOCK_ELEMENT_ID";
				$clauseWhere[] = "b_iblock_element_property.VALUE IS NOT NULL";
				if ($arFilter) {
					foreach ($arFilter as $code => $value) {
						$arProp = $this->getProperty($code);
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
					$valuesTableName = "b_iblock_element_prop_m{$this->getID()}";
					if ($isNum) {
						$clauseSelect = "DISTINCT TRIM(TRAILING '.' FROM TRIM(TRAILING '0' FROM {$valuesTableName}.VALUE_NUM)) AS VALUE";
					} else {
						$clauseSelect = "DISTINCT {$valuesTableName}.VALUE";
					}
					$clauseWhere[] = "{$valuesTableName}.VALUE IS NOT NULL";
				} else {
					$valuesTableName = "b_iblock_element_prop_s{$this->getID()}";
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
						$arProp = $this->getProperty($code);
						if ($arProp['MULTIPLE'] === 'Y') {
							$clauseFrom .= " LEFT JOIN b_iblock_element_prop_m{$this->getID()} AS property_{$code} ON b_iblock_element.ID = property_{$code}.IBLOCK_ELEMENT_ID";
							$clauseWhere[] = "property_{$code}.IBLOCK_PROPERTY_ID = {$arProp['ID']}";
							if ($arProp['PROPERTY_TYPE'] === 'N')
								$clauseWhere[] = "property_{$code}.VALUE_NUM = {$value}";
							else
								$clauseWhere[] = "property_{$code}.VALUE = '{$value}'";
						} else {
							if ($arProp['PROPERTY_TYPE'] === 'N')
								$clauseWhere[] = "b_iblock_element_prop_s{$this->getID()}.PROPERTY_{$arProp['ID']} = {$value}";
							else
								$clauseWhere[] = "b_iblock_element_prop_s{$this->getID()}.PROPERTY_{$arProp['ID']} = '{$value}'";
						}
					}
				}
			}
			$clauseWhere = join(' AND ', $clauseWhere);
			return "SELECT {$clauseSelect} FROM {$clauseFrom} WHERE {$clauseWhere}";
		}
	}
