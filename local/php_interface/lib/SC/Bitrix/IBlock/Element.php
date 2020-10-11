<?php
	namespace SC\Bitrix\IBlock;

	use \CIBlockElement;
	use \CFile;
	use \Exception;

	class Element extends Entity {

		use Parentable;
		use Propertiable;

		public function __construct(array $arFields = [], array $arProperties = []) {
			parent::__construct($arFields);
			$this->arProperties = $arProperties;
			self::castArrayValuesType($this->arProperties);
		}

		public function save(): void {
			$celement = new CIBlockElement;
			$arFields = array_merge($this->getFields(), ['PROPERTY_VALUES' => $this->getProperties()]);
			if ($this->id) {
				$result = $celement->Update($this->id, $arFields);
			} else {
				$result = $celement->Add($arFields);
				if ($result)
					$this->id = $result;
			}
			if (!$result)
				throw new Exception($celement->LAST_ERROR);
		}

		public function delete(): void {
			if (!$this->id)
				return;
			if (CIBlockElement::delete($this->id)) {
				$this->id = null;
				unset($this->arFields['ID']);
			} else {
				throw new Exception;
			}
		}

		protected function fetchFields(): void {
			$this->arFields = CIBlockElement::GetByID($this->id)->GetNext(false, false);
		}

		protected function fetchProperties(): void {
			$this->arProperties = CIBlockElement::GetByID($this->id)->GetNextElement()->GetProperties();
			foreach ($this->arProperties as &$arProp)
				$arProp = $arProp['VALUE'];
		}

		public function getParents(): array {
			global $DB;
			$q = "SELECT b_iblock_section.* FROM b_iblock_section_element LEFT JOIN b_iblock_section ON b_iblock_section_element.IBLOCK_SECTION_ID = b_iblock_section.ID WHERE IBLOCK_ELEMENT_ID = {$this->id}";
			$rs = $DB->Query($q);
			$result = [];
			while ($arFields = $rs->Fetch())
				$result[] = Section::fromArray($arFields);
			return $result;
		}

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

		public static function getList(array $arFilter, array $arOrder = ['SORT' => 'ASC'], ?array $arSelect = null, ?array $arNav = null): array {
			$rs = CIBlockElement::GetList($arOrder, $arFilter, false, $arNav, $arSelect);
			$result = [];
			while ($o = $rs->GetNextElement()) {
				$f = $o->GetFields();
				$f['PROPERTIES'] = $o->GetProperties();
				$result[] = $f;
			}
			return $result;
		}

		public static function getByID(int $id): Element {
			$arFields = CIBlockElement::GetByID($id)->GetNext();
			if ($arFields)
				return self::fromArray($arFields);
			throw new Exception("There is no element with ID '{$id}'");
		}

		public static function fromArray(array $arFields): Element {
			$o = parent::fromArray($arFields);
			$o->arProperties = @$o->arFields['PROPERTIES'];
			unset($o->arFields['PROPERTIES']);
			foreach ($o->arProperties as &$arProp)
				$arProp = $arProp['VALUE'];
			return $o;
		}
	}
