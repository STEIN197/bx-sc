<?php
	namespace SC\Bitrix\IBlock;

	use CIBlockProperty;
	use Exception;
	use SC\Bitrix\EntityDatabaseException;

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
					$this->setField('ID', $result);
			}
			if (!$result)
				throw new EntityDatabaseException($cproperty->LAST_ERROR);
		}

		public function delete(): void {
			if (!$this->id)
				return;
			if (CIBlockProperty::delete($this->id)) {
				$this->id = null;
				unset($this->arFields['ID']);
			} else {
				throw new EntityDatabaseException('Cannot delete entity '.self::class." with ID '{$this->id}'");
			}
		}

		public function isNumeric(): bool {
			$type = $this->getField('PROPERTY_TYPE');
			if (!$type)
				throw new Exception('Property does not have type field');
			return $type === 'N';
		}

		public function isMultiple(): bool {
			$multiple = $this->getField('MULTIPLE');
			if (!$multiple)
				throw new Exception('Property does not have multiplicity field');
			return $multiple === 'Y';
		}

		protected function fetchFields(): void {
			$this->arFields = self::castArrayValuesType(CIBlockProperty::GetByID($this->id)->GetNext(false, false));
		}

		public static function getList(array $arFilter, array $arOrder = [], ?array $arSelect = null, ?array $arNav = null): array {
			$arFilter = array_merge(['CHECK_PERMISSIONS' => 'N'], $arFilter);
			$rs = CIBlockProperty::GetList($arOrder, $arFilter);
			$result = [];
			while ($ar = $rs->GetNext(false, false))
				$result[$ar['CODE']] = $ar;
			return $result;
		}

		public static function getByID(int $id): self {
			$arFields = CIBlockProperty::GetByID($id)->GetNext(false, false);
			if ($arFields)
				return self::fromArray($arFields);
			throw new EntityNotFoundException(null, 'Entity '.self::class." with ID '{$id}' is not found");
		}
	}
