<?php
	namespace SC\Bitrix\IBlock;

	use CIBlockElement;
	use Exception;
	use SC\Bitrix\EntityDatabaseException;
	use SC\Bitrix\EntityNotFoundException;

	class Element extends Entity {

		use Parentable;
		use Propertiable;

		protected $arNewParents = [];
		protected $parentsFetched = false;
		private $arDeletedParents = [];
		private $arExistingParents = [];

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
			else
				$this->saveParents();
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
				$this->arExistingParents = $this->fetchParents();
			}
			return array_unique(array_merge($this->arNewParents, $this->arExistingParents));
		}

		public function setParents(array $parents): void {
			$this->arNewParents = array_unique(
				array_merge(
					$this->arNewParents,
					array_map(
						function($v) {
							return Section::make($v)->getID();
						},
						$parents
					),
					$this->getParents()
				)
			);
		}

		public function deleteParents(array $parents): void {
			$this->arDeletedParents = array_unique(
				array_merge(
					$this->arDeletedParents,
					array_map(
						function($v) {
							return Section::make($v)->getID();
						}, $parents
					)
				)
			);
		}

		public function saveParents(): void {
			global $DB;
			if ($this->id && !$this->parentsFetched) {
				$this->parentsFetched = true;
				$this->arExistingParents = $this->fetchParents();
			}
			$parentsToInsert = array_diff($this->arNewParents, $this->arExistingParents, $this->arDeletedParents);
			if (!empty($parentsToInsert)) {
				$q = "INSERT INTO b_iblock_section_element (IBLOCK_SECTION_ID, IBLOCK_ELEMENT_ID) VALUES ";
				$q .= join(', ', array_map(function($v) {
					return "($v, $this->id)";
				}, $parentsToInsert));
				$DB->Query($q);
			}
			// Не удалять основной раздел
			$this->arDeletedParents = array_filter($this->arDeletedParents, function($v) {
				return $this->getParent()->getID() != $v;
			});
			if (!empty($this->arDeletedParents)) {
				$q = "DELETE FROM b_iblock_section_element WHERE IBLOCK_ELEMENT_ID = {$this->id} AND IBLOCK_SECTION_ID IN ";
				$q .= '('.join(', ', $this->arDeletedParents).')';
				$DB->Query($q);
			}
			$this->arExistingParents = array_diff(
				array_unique(
					array_merge(
						$this->arNewParents,
						$this->arExistingParents
					)
				),
				$this->arDeletedParents
			);
			$this->arDeletedParents = $this->arNewParents = [];
		}

		private function fetchParents(): array {
			global $DB;
			$q = "SELECT IBLOCK_SECTION_ID FROM b_iblock_section_element WHERE IBLOCK_ELEMENT_ID = {$this->id}";
			$rs = $DB->Query($q);
			$result = [];
			while ($ar = $rs->Fetch())
				$result[] = $ar['IBLOCK_SECTION_ID'];
			return $result;
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
