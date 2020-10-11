<?php
	namespace SC\Bitrix\IBlock;

	use \CIBlockProperty;
	use \Exception;

	class Property extends Entity {

		public function __construct(array $arFields = []) {
			parent::__construct($arFields);
		}

		public function save(): void {
			$cproperty = new CIBlockProperty;
			if ($this->id) {
				$result = $cproperty->Update($this->id, $this->getFields());
			} else {
				$result = $cproperty->Add($this->getFields());
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
			$this->arFields = CIBlockProperty::GetByID($this->id)->GetNext(false, false);
		}

		public static function getList(array $arFilter, array $arOrder = ['SORT' => 'ASC'], ?array $arSelect = null, ?array $arNav = null): array {
			$rs = CIBlockProperty::GetList($arOrder, $arFilter);
			$result = [];
			while ($ar = $rs->GetNext(false, false))
				$result[$ar['CODE']] = $ar;
			return $result;
		}

		public static function getByID(int $id): ?self {
			$arFields = CIBlockProperty::GetByID($id)->GetNext(false, false);
			if ($arFields)
				return self::fromArray($arFields);
			throw new Exception("There is no property with ID '{$id}'");
		}
	}
