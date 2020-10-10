<?php
	namespace SC\Bitrix\IBlock;

	use \CIBlockProperty;
	use \Exception;

	class Property extends Entity {

		public function __construct(?array $arFields = null) {
			$this->arFields = $arFields;
		}

		public function save(): void {
			$cproperty = new CIBlockProperty;
			if ($this->id) {
				$result = $cproperty->Update($this->id, $this->arFields);
			} else {
				$result = $cproperty->Add($this->arFields);
				if ($result)
					$this->id = $result;
			}
			if (!$result)
				throw new Exception($cproperty->LAST_ERROR);
		}

		public function delete(): void {
			if (!$this->id)
				return;
			if (CIBlockProperty::delete($this->id)) {
				$this->id = null;
				unset($this->arFields['ID']);
			} else {
				throw new Exception("Cannot delete property {$this}");
			}
		}

		public function isNumeric(): bool {
			$type = $this->getField('PROPERTY_TYPE');
			if (!$type)
				throw new Exception('Property does not have any type');
			return $type === 'N';
		}

		public function isMultiple(): bool {
			$multiple = $this->getField('MULTIPLE');
			if (!$multiple)
				throw new Exception('Property does not have multiplicity field');
			return $multiple === 'Y';
		}

		protected function fetchFields(): void {
			$this->arFields = CIBlockProperty::GetByID($this->id)->GetNext();
		}

		public static function getList(array $arFilter, array $arOrder = ['SORT' => 'ASC'], ?array $arSelect = null, ?array $arNav = null): array {
			$rs = CIBlockProperty::GetList($arOrder, $arFilter);
			$result = [];
			while ($ar = $rs->GetNext())
				$result[$ar['CODE']] = $ar;
			return $result;
		}

		public static function getByID(int $id, bool $onlyStub = false): ?self {
			$o = null;
			if ($onlyStub) {
				$o = new self;
				$o->id = $id;
			} else {
				$arFields = CIBlockProperty::GetByID($id)->GetNext();
				if ($arFields)
					$o = self::wrap($arFields);
			}
			return $o;
		}
	}
