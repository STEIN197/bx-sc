<?php
	namespace SC\Bitrix\IBlock;

	use CIBlock;
	use Exception;
	use SC\Bitrix\EntityDatabaseException;
	use SC\Bitrix\EntityNotFoundException;

	class IBlock extends Entity implements EntityContainer {

		use Propertiable;

		public function __construct(array $arFields = [], array $arProperties = []) {
			parent::__construct($arFields);
			$this->setProperties($arProperties);
		}

		public function getIBlock(): IBlock {
			return $this;
		}

		public function save(): void {
			$ciblock = new CIBlock;
			if ($this->id) {
				$result = $ciblock->Update($this->id, $this->getFields());
			} else {
				$result = $ciblock->Add($this->getFields());
				if ($result)
					$this->id = $result;
			}
			if (!$result)
				throw new EntityDatabaseException($this, $ciblock->LAST_ERROR);
			$this->saveProperties();
		}

		public function delete(): void {
			if (!$this->id)
				return;
			if (CIBlock::delete($this->id)) {
				$this->id = null;
				unset($this->arFields['ID']);
			} else {
				throw new EntityDatabaseException($this, 'Cannot delete entity '.self::class." with ID '{$this->id}'");
			}
		}

		protected function fetchFields(): void {
			$this->arFields = CIBlock::GetByID($this->id)->GetNext(false, false);
		}

		protected function fetchProperties(): void {
			$this->arProperties = Property::getList([
				'IBLOCK_ID' => $this->id,
			]);
		}

		private function saveProperties(): void {
			$iblockID = $this->getID();
			foreach ($this->getProperties() as $prop) {
				$prop = Property::make($prop);
				$prop->setField('IBLOCK_ID', $iblockID);
				$prop->save();
			}
		}

		public static function getList(array $arFilter, array $arOrder = [], ?array $arSelect = [], ?array $arNav = null): array {
			$rs = CIBlock::GetList($arOrder, $arFilter);
			$result = [];
			while ($ar = $rs->GetNext(false, false))
				$result[] = $ar;
			return $result;
		}

		public static function getByID(int $id): IBlock {
			$arFields = CIBlock::GetByID($id)->GetNext(false, false);
			if ($arFields)
				return self::fromArray($arFields);
			throw new EntityNotFoundException(null, 'Entity '.self::class." with ID '{$id}' is not found");
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
