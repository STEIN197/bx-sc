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

		public static function getByID(int $id, bool $onlyStub = false) {
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

		// TODO
		public function getElements(array $arFilter = [], array $arOrder = ['SORT' => 'ASC'], ?array $arSelect = null, ?array $arNav = null): array {}

		// TODO
		public function getSections(array $arFilter = [], array $arOrder = ['SORT' => 'ASC'], ?array $arSelect = null, ?array $arNav = null): array {}

		// TODO
		public function getDistinctValues($property, array $arFilter = null, bool $includeInactive = false): array {}
	}
