<?php
	namespace SC\IBlock;

	use \Exception;

	abstract class Entity {

		protected $id;
		protected $arFields;

		public function getIBlock(): ?IBlock {
			static $iblock = null;
			if (!$iblock && $this->getField('IBLOCK_ID'))
				$iblock = IBlock::getByID((int) $this->getField('IBLOCK_ID'));
			return $iblock;
		}

		public final function getFields(): array {
			if (is_array($this->arFields))
				return $this->arFields;
			$this->fetchFields();
			if (is_array($this->arFields)) {
				Entity::castTypes($this->arFields);
			} else {
				$this->arFields = [];
			}
			return $this->arFields;
		}

		public final function setFields(array $arFields): void {
			foreach ($arFields as $key => $value)
				$this->setField($key, $value);
		}

		public final function getField(string $key) {
			return @$this->getFields()[$key];
		}

		public final function setField(string $key, $value) {
			$old = $this->getField($key);
			$this->arFields[$key] = $value;
			if ($this->id && $key === 'ID' && $this->id != $value)
				$this->id = (int) $value;
			if ($this->parent && $key === 'IBLOCK_SECTION_ID' && $this->parent->getID() != $value)
				$this->parent = null;
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
			if (!$o->id)
				throw new Exception('ID is not present');
			return $o;
		}

		/**
		 * @param mixed $entity
		 * @return static
		 */
		public static final function make($entity) {
			if (is_int($entity) || is_string($entity) && preg_match('/^\d+$/', $entity))
				return static::getByID((int) $entity, true);
			if (is_array($entity) && $entity['ID'])
				return static::wrap($entity);
			if ($entity instanceof static)
				return $entity;
			throw new Exception("Cannot make entity from input: {$entity}");
		}

		/**
		 * Преобразует все числовые строки массива в числа.
		 * @param array $arFields Массив, типы значений которых нужно преобразовать.
		 * @return void
		 */
		public static final function castTypes(array &$arFields): void {
			foreach ($arFields as &$value)
				if (is_numeric($value))
					$value = intval($value) == $value ? (int) $value : (float) $value;
		}
	}
