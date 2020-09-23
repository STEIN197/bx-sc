<?php
	namespace SC\IBlock;

	interface Entity {

		final public function getFields(): array {
			return $this->fetchData('Fields');
		}

		final public function getProperties(): array {
			return $this->fetchData('Properties');
		}

		final public function setFields(array $arFields): void {
			$this->arFields = $arFields;
		}

		final public function setProperties(array $arProperties): void {
			$this->arProperties = $arProperties;
		}

		final public function getID(): ?int {
			return $this->id;
		}

		final private function fetchData(string $dataType): array {
			$data = &$this->{"ar{$dataType}"};
			if (!$data && $this->id) {
				$this->{"fetch{$dataType}"}();
				self::castTypes($data);
			}
			return $data;
		}

		// final public function getField(string $key) {}
		// final public function getProperty(string $key) {}
		// final public function setField(string $key, $value) {}
		// final public function setProperty(string $key, $value) {}

		abstract public function save();
		abstract public function delete();
		// abstract public function copy();
		abstract protected function fetchFields(): void;
		abstract protected function fetchProperties(): void;

		public static function castTypes(array &$arFields): void {
			foreach ($arFields as &$value)
				if (preg_match('/^\d+$/', $value))
					$value = (int) $value;
				elseif (preg_match('/^\d+\.\d+$/', $value))
					$value = (float) $value;
		}

		abstract public static function getByID(int $id);
		// abstract public static function getList(array $arOrder = ['SORT' => 'ASC'], array $arFilter)
	}
