<?php
	namespace SC;

	// TODO
	class Query {

		private static $handler;

		private const PREFIX_NAME = '@'; // Переданный параметр - колонка/таблица
		private const PREFIX_RAW = '&'; // Переданный параметр должен быть вставлен "как есть"
		private const PREFIX_STRING = '%'; // Форсировать параметр как sql-строку,
		private const PREFIX_ALL = '*'; // Все колонки

		// TODO: Экранировать удвоением. У каждого метода есть метод резолюции по умолчанию, если явно не указано
		private static $prefixes = [
			self::PREFIX_NAME,
			self::PREFIX_RAW,
			self::PREFIX_STRING,
			self::PREFIX_ALL,
		];

		/** @var array */
		private $query = [];

		public function __construct(...$arguments) {
			// $this->query = $arguments;
		}

		public function select(...$arguments): self {
			$this->query[] = 'SELECT';
			$argsCount = sizeof($arguments);
			if ($argsCount) {
				if ($argsCount === 1) {
					if (is_array($arguments[0])) {

					} else {

					}
				} else {

				}
				$arguments = array_map('strval', $arguments);
				$arguments = array_map('self::trim', $arguments);
				// ...
				select('*');
				select('s', 'd');
				select([
					'sd',
					'cd' => '34'
				]);
			}
			return $this;
		}

		public function distinct(): self {}
		public function from(): self {}
		public function partition(): self {}
		public function where(): self {}
		public function groupBy(): self {}
		public function having(): self {}
		public function orderBy(): self {}
		public function limit(): self {}

		public function query(?string $raw = null): ?array {}

		// В вызов mysql-функции
		public function __call($method, $arguments): ?self {
			$ucMethod = strtoupper($method);
			$arguments = array_map('strval', $arguments);
			$arguments = array_map('self::escape', $arguments);
			$arguments = array_map('self::enclose', $arguments);
			// TODO: Проверка на колонки
			$arguments = array_map('self::convertType', $arguments);
			$this->query[] = $ucMethod.'('.join(', ', $arguments).')';
			return $this;
		}

		public function __toString(): string {
			return join(' ', $this->query);
		}

		public static function new(...$arguments): self {
			return new self($arguments);
		}

		public static function setQueryHandler($callback): void {
			self::$hander = $callback;
		}

		public static function setQueryEscape($callback) {}

		private static function convertToSQLType($value): string {
			if (is_string($value))
				return "'{$value}'";
			if (is_null($value))
				return 'NULL';
			if (is_bool($value))
				return $value ? 'TRUE' : 'FALSE';
			return $value;
		}

		private static function filter($input, ?string $defaultEntity = null): string {
			if ($input instanceof self)
				return (string) $input;
			if (is_string($input) && in_array($input{0}, self::$prefixes)) {

			}
		}

		private static function escape($value): string {
			return addslashes($value);
		}

		private static function enclose($value): string {
			
		}
	}


		Query::new()
			->select('*')
			->from(
				Query::new()
					->select([
						Query::new()->substringIndex('@Steel', ': ', 1) => 'Steel_Type', // TODO: Ключи-объекты не разрешены
						Query::new()->substringIndex('@Steel', ': ', -1)->as('Steel'),
						Query::new()->min('@Steel_Size') => 'Min_Steel_Size',
						Query::new()->max('@Steel_Size') => 'Max_Steel_Size',
						Query::new()->groupConcat(Query::new()->distinct('@Steel_Size')->orderBy()->field('@Steel_Standart', 'ГОСТ')->separator(',')) => 'Max_Steel_Size',
					])
					->from('@Message236')
					->where([
						'@Subdivision_ID' => 99,
						'@Checked'
					])
					->groupBy('@Steel')
			, 'MainTable')
			->orderBy(
				Query::new()->position('@Steel')->in(
					Query::new()
					->select()->groupConcat(Query::new('@Subdivision_Name')->separator(','))
					->from(
						Query::new()
						->select([
							'@Child.Subdivision_ID',
							'@Child.Subdivision_Name',
							'@Parent.Subdivision_Name' => 'Parent_Name',
							'@Child.Parent_Sub_ID',
							'@Parent.Priority' => 'Parent_Priority',
							'@Child.Priority' => 'Child_Priority',
						])
						->from('@Subdivision', 'Child')
							->leftJoin('@Subdivision', 'Parent')
							->on('@Child.Parent_Sub_ID', '@Parent.Subdivision_ID')
						->where([
							'@Child.Catalogue_ID' => 6,
							'@Parent.Parent_Sub_ID' => 120
						])
						->orderBy([
							'@Parent.Priority',
							'@Child.Priority',
						]), 't1'
					)
				)
			);

	// TODO
	function select(): Query {}
