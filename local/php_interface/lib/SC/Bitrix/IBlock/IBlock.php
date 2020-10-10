<?php
	namespace SC\Bitrix\IBlock;

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
				Property::fromArray($arProperty)->save();
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
					$o = self::fromArray($arFields);
			}
			return $o;
		}

		public function getElements(array $arFilter = [], array $arOrder = ['SORT' => 'ASC'], ?array $arSelect = null, ?array $arNav = null): array {
			return Element::getList(array_merge($arFilter, ['IBLOCK_ID' => $this->id]), $arOrder, $arSelect, $arNav);
		}

		public function getSections(array $arFilter = [], array $arOrder = ['SORT' => 'ASC'], ?array $arSelect = null, ?array $arNav = null): array {
			return Section::getList(array_merge($arFilter, ['IBLOCK_ID' => $this->id]), $arOrder, $arSelect, $arNav);
		}

		public function getDistinctValues(array $properties, ?array $arFilter = null, bool $includeInactive = false): ?array {
			global $DB;
			if (!$this->getID())
				return null;
			foreach ($properties as &$property) {
				$oProp = Property::make($property);
				if (!$oProp && is_string($property))
					$oProp = Property::fromArray($this->getProperty($property));
				$property = $oProp;
			}
			$q = $this->getDistinctValuesQuery($properties, $arFilter, $includeInactive);
			$result = [];
			$rs = $DB->Query($q);
			while ($ar = $rs->Fetch())
				$result[] = $ar['VALUE'];
			natsort($result);
			return $result;
		}

		public function getDistinctValuesQuery(array $properties, ?array $arFilter = null, bool $includeInactive = false): string {
			$clauseSelect = [];
			$clauseFrom = 'b_iblock_element';
			$clauseWhere = [
				"b_iblock_element.IBLOCK_ID = {$this->getField('ID')}"
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
			$clauseWhere = join(' AND ', $clauseWhere);
			return "SELECT DISTINCT {$clauseSelect} FROM {$clauseFrom} WHERE {$clauseWhere}";
		}
	}
