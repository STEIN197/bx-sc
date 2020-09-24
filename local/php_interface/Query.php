<?php
	namespace SC;

	// TODO
	class Query {

		/** @var array */
		private $query = [];
		private static $handler;
		private static $keywords = [
			'BETWEEN' => [
				'paramCount' => 2,
				'pattern' => 'BETWEEN %0 AND %1',
				'excludeKwd' => true
			],
			'FROM' => [
				'patterns' => [
					'%0',
					'%0 AS %1'
				]
			]
		];

		public function select(...$arguments): self {
			$this->query[] = 'SELECT';
			$arguments = array_map('strval', $arguments);
			$arguments = array_map('self::trim', $arguments);
			// ...
			select('*');
			select('s', 'd');
			select([
				'sd',
				'cd' => '34'
			]);
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
		
		public function join(): self {}
		public function innerJoin(): self {}
		public function leftJoin(): self {}
		public function rightJoin(): self {}
		public function on(): self {}
		public function using(): self {}
		
		public function create(): self {}
		public function table(): self {}
		public function alter(): self {}
		
		public function between(): self {}
		public function in(): self {}

		public function query(?string $raw = null): void {}
		public function fetchAll(): array {}
		public function fetchLazy() {}

		// В вызов mysql-функции
		public function __call($method, $arguments): ?self {
			$ucMethod = strtoupper($method);
			$arguments = array_map('strval', $arguments);
			$arguments = array_map('en', $arguments);
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

		public static function new() {
			return new self;
		}

		public static function setQueryHandler($callback) {}
		public static function setQueryEscape($callback) {}

		private static function trim($input) {
			return is_array($input) ? array_map('trim', $input) : trim($input);
		}

		private static function convertType($value): array {
			if (is_string($value))
				return "'{$value}'";
			if (is_null($value))
				return 'NULL';
			if (is_bool($value))
				return $value ? 'TRUE' : 'FALSE';
			return $value;
		}

		private static function escape($value): string {
			return addslashes($value);
		}

		private static function enclose($value): string {
			
		}

		private static function arrayMap(array $ar, array $functions): array {}
	}

	// TODO
	function select(): Query {}
	function delete(): Query {}
	function update(): Query {}
	function insert(): Query {}
	function create(): Query {}
	function alter(): Query {}
