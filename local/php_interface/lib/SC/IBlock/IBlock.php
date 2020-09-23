<?php
	namespace SC\IBlock;

	use \CIBlock;
	use \CIBlockProperty;
	use \Exception;

	class IBlock extends Entity {

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
				$this->id = $result;
			}
			if (!$result)
				throw new Exception($ciblock->LAST_ERROR);
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
			$this->arProperties = [];
			$rs = CIBlockProperty::GetList(
				array(
					'SORT' => 'ASC',
				), array(
					'IBLOCK_ID' => $this->id,
					'ACTIVE' => 'Y'
				)
			);
			while ($ar = $rs->GetNext())
				$this->arProperties[$ar['CODE']] = $ar;
		}

		public static function getByID(int $id): ?self {
			if (!CIBlock::GetByID($id)->GetNext())
				return null;
			$iblock = new self;
			$iblock->id = $id;
			return $iblock;
		}
	}
