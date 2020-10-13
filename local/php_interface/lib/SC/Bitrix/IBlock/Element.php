<?php
	namespace SC\Bitrix\IBlock;

	use CIBlockElement;
	use Exception;
	use SC\Bitrix\EntityDatabaseException;
	use SC\Bitrix\EntityNotFoundException;

	class Element extends Entity {

		use Parentable;
		use Propertiable;

		protected $arParents = [];
		protected $parentsFetched = false;
		private $arParentIDsToDelete = [];

		public function __construct(array $arFields = [], array $arProperties = []) {
			parent::__construct($arFields);
			$this->arProperties = [];
			$this->setProperties($arProperties);
		}

		// TODO: Conditionaly save parents!
		public function save(): void {
			$celement = new CIBlockElement;
			if ($this->id) {
				$result = $celement->Update($this->id, $this->toArray());
			} else {
				$result = $celement->Add($this->toArray());
				if ($result)
					$this->setField('ID', $result);
				$this->propertiesFetched = true;
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

		protected function fetchFields(): array {
			return CIBlockElement::GetByID($this->id)->GetNext(false, false) ?: [];
		}

		protected function fetchProperties(): array {
			$arProperties = CIBlockElement::GetByID($this->id)->GetNextElement(false, false)->GetProperties();
			foreach ($arProperties as &$arProp)
				$arProp = $arProp['VALUE'];
			return $arProperties;
		}

		public function getParents(): array {
			global $DB;
			if ($this->id && !$this->parentsFetched) {
				$this->parentsFetched = true;
				$q = "SELECT IBLOCK_SECTION_ID FROM b_iblock_section_element WHERE IBLOCK_ELEMENT_ID = {$this->id}";
				$rs = $DB->Query($q);
				while ($ar = $rs->Fetch())
					$this->arParents[$ar['IBLOCK_SECTION_ID']] = Section::stubFromID((int) $ar['IBLOCK_SECTION_ID']);
			}
			return $this->arParents;
		}

		public function setParents(array $parents): void {
			$parents = array_map(function($v) {
				return Section::make($v);
			}, $parents);
			foreach ($parents as $section)
				$this->arParents[$section->getID()] = $section;
		}

		public function deleteParents(array $parents): void {
			$this->arParentIDsToDelete = array_merge($this->arParentIDsToDelete, array_map(function($v) {
				return Section::make($v)->getID();
			}, $parents));
		}

		private function parentExists(int $parentID): bool {
			global $DB;
			$rs = $DB->Query("SELECT COUNT(*) AS CNT FROM b_iblock_section_element WHERE IBLOCK_SECTION_ID = {$parentID} AND IBLOCK_ELEMENT_ID = {$this->id}")->Fetch();
			return $rs && $rs['CNT'] && intval($rs['CNT']) > 0;
		}

		public static function getList(array $arFilter = [], array $arOrder = [], ?array $arSelect = null, ?array $arNav = null): array {
			$arFilter = array_merge(['CHECK_PERMISSIONS' => 'N'], $arFilter);
			$rs = CIBlockElement::GetList($arOrder, $arFilter, false, $arNav, $arSelect);
			$result = [];
			while ($o = $rs->GetNextElement(false, false)) {
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
			$arFields = CIBlockElement::GetByID($id)->GetNext(false, false);
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
				$o->propertiesFetched = true;
			} elseif (@$o->arFields['PROPERTIES']) {
				$o->arProperties = [];
				foreach ($o->arFields as $arProp)
					$o->arProperties = self::castValueType($arProp['VALUE']);
				unset($o->arFields['PROPERTIES']);
				$o->propertiesFetched = true;
			}
			return $o;
		}
	}
