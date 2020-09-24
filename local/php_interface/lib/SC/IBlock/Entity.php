<?php
	namespace SC\IBlock;

	abstract class Entity {

		protected $id;
		protected $arFields;

		public final function getFields(): array {
			if (!is_array($this->arFields))
				$this->fetchFields();
			if (!is_array($this->arFields))
				$this->arFields = [];
			return $this->arFields;
		}

		public final function setFields(array $arFields): void {
			$this->arFields = array_merge($this->getFields(), $arFields);
			if ($this->id && isset($arFields['ID']) && $this->id != $arFields['ID'])
				$this->id = (int) $arFields['ID'];
		}

		public final function getField(string $key) {
			return @$this->getFields()[$key];
		}

		public final function setField(string $key, $value) {
			$old = $this->getField($key);
			$this->arFields[$key] = $value;
			if ($this->id && $key === 'ID' && $this->id != $value)
				$this->id = (int) $value;
			return $old;
		}

		public final function getID(): ?int {
			return $this->id;
		}

		public function __toString() {
			return (string) $this->id;
		}

		abstract public function save(): void;

		abstract public function delete(): void;

		abstract protected function fetchFields(): void;

		abstract public static function getList(array $arFilter, array $arOrder = ['SORT' => 'ASC'], ?array $arSelect = null, ?array $arNav = null): array;

		abstract public static function getByID(int $id, bool $onlyStub = false);

		public static function wrap(array $arFields) {
			$o = new static($arFields);
			$o->id = (int) $arFields['ID'];
			return $o;
		}

		public static final function make($entity) {
			if (is_int($entity) || is_string($entity) && preg_match('/^\d+$/', $entity))
				return static::getByID((int) $entity, true);
			if (is_array($entity) && $entity['ID'])
				return static::getByID((int) $entity['ID'], true);
			if ($entity instanceof self)
				return $entity;
			return null;
		}

		public static final function castTypes(array &$arFields): void {
			foreach ($arFields as &$value)
				if (preg_match('/^\d+$/', $value))
					$value = (int) $value;
				elseif (preg_match('/^\d+\.\d+$/', $value))
					$value = (float) $value;
		}
	}
