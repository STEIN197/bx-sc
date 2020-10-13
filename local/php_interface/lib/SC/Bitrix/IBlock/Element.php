<?php
	namespace SC\Bitrix\IBlock;

	use CIBlockElement;
	use Exception;
	use SC\Bitrix\EntityDatabaseException;
	use SC\Bitrix\EntityNotFoundException;

	class Element extends Entity {

		use Parentable;
		use Propertiable;

		public function __construct(array $arFields = [], array $arProperties = []) {
			parent::__construct($arFields);
			$this->arProperties = [];
			$this->setProperties($arProperties);
		}

		public function save(): void {
			$celement = new CIBlockElement;
			if ($this->id) {
				$result = $celement->Update($this->id, $this->toArray());
			} else {
				$result = $celement->Add($this->toArray());
				if ($result)
					$this->setField('ID', $result);
			}
			if (!$result)
				throw new EntityDatabaseException($celement->LAST_ERROR);
		}

		public function delete(): void {
			if (!$this->id)
				return;
			if (CIBlockElement::delete($this->id)) {
				$this->id = null;
				unset($this->arFields['ID']);
			} else {
				throw new EntityDatabaseException('Cannot delete entity '.self::class." with ID '{$this->id}'");
			}
		}

		public function toArray(): array {
			return array_merge($this->getFields(), ['PROPERTY_VALUES' => $this->getProperties()]);
		}

		protected function fetchFields(): void {
			$this->arFields = CIBlockElement::GetByID($this->id)->GetNext(false, false);
			self::castArrayValuesType($this->arFields);
		}

		protected function fetchProperties(): void {
			$this->arProperties = CIBlockElement::GetByID($this->id)->GetNextElement()->GetProperties();
			foreach ($this->arProperties as &$arProp)
				$arProp = self::castValueType($arProp['VALUE']);
		}

		// TODO: Cache them?
		public function getParents(): array {
			global $DB;
			$q = "SELECT b_iblock_section.* FROM b_iblock_section_element LEFT JOIN b_iblock_section ON b_iblock_section_element.IBLOCK_SECTION_ID = b_iblock_section.ID WHERE IBLOCK_ELEMENT_ID = {$this->id}";
			$rs = $DB->Query($q);
			$result = [];
			while ($arFields = $rs->Fetch())
				$result[] = Section::fromArray($arFields);
			return $result;
		}

		// TODO: Do not do any queries here!
		public function setParents(array $parents): void {
			global $DB;
			$parentsToAdd = [];
			foreach ($parents as $parent) {
				$parent = Section::make($parent);
				if (!$this->parentExists($parent->getID()))
					$parentsToAdd[] = $parent->getID();
			}
			if ($parentsToAdd) {
				$q = "INSERT INTO b_iblock_section_element (IBLOCK_SECTION_ID, IBLOCK_ELEMENT_ID) VALUES ";
				$q .= join(', ', array_map(function($sectionID) {
					return "({$sectionID}, {$this->id})";
				}, $parentsToAdd));
				$DB->Query($q);
			}
		}

		// TODO: Do not do any queries here!
		public function deleteParents(array $parents): void {
			global $DB;
			$deleteIDs = [];
			foreach ($parents as $parent) {
				$parent = Section::make($parent);
				if (!$parent)
					throw new Exception('Passed parent is null');
				$deleteIDs[] = $parent->getID();
				if ($parent->getID() == $this->getField('IBLOCK_SECTION_ID'))
					$this->setParent(null);
			}
			$DB->Query("DELETE FROM b_iblock_section_element WHERE IBLOCK_SECTION_ID IN (".join(', ', $deleteIDs).") AND IBLOCK_ELEMENT_ID = {$this->id}");
		}

		private function parentExists(int $parentID): bool {
			global $DB;
			$rs = $DB->Query("SELECT COUNT(*) AS CNT FROM b_iblock_section_element WHERE IBLOCK_SECTION_ID = {$parentID} AND IBLOCK_ELEMENT_ID = {$this->id}")->Fetch();
			return $rs && $rs['CNT'] && $rs['CNT'] > 0;
		}

		public static function getList(array $arFilter = [], array $arOrder = [], ?array $arSelect = null, ?array $arNav = null): array {
			$arFilter = array_merge(['CHECK_PERMISSIONS' => 'N'], $arFilter);
			$rs = CIBlockElement::GetList($arOrder, $arFilter, false, $arNav, $arSelect);
			$result = [];
			while ($o = $rs->GetNextElement()) {
				$fields = $o->GetFields();
				$fields['PROPERTY_VALUES'] = [];
				$properties = $o->GetProperties();
				foreach ($properties as $code => $arProp)
					$fields['PROPERTY_VALUES'][$code] = $arProp['VALUE'];
				$result[] = $fields;
			}
			return $result;
		}

		public static function getByID(int $id): Element {
			$arFields = CIBlockElement::GetByID($id)->GetNext();
			if ($arFields)
				return self::fromArray($arFields);
			throw new EntityNotFoundException('Entity '.self::class." with ID '{$id}' is not found");
		}

		public static function fromArray(array $arFields): Element {
			$o = parent::fromArray($arFields);
			if (@$o->arFields['PROPERTY_VALUES']) {
				$o->arProperties = $o->arFields['PROPERTY_VALUES'];
				self::castArrayValuesType($o->arProperties);
				unset($o->arFields['PROPERTY_VALUES']);
			} elseif (@$o->arFields['PROPERTIES']) {
				$o->arProperties = [];
				foreach ($o->arFields as $arProp)
					$o->arProperties = self::castValueType($arProp['VALUE']);
				unset($o->arFields['PROPERTIES']);
			}
			return $o;
		}
	}
