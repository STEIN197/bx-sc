<?php
	namespace SC;

	// TODO: Смотреть назад по запросу?
	// TODO: Список кейвордов с массивом действий?
	class Query {

		public static $handler;

		private const PREFIX_NAME = '@'; // Переданный параметр - колонка/таблица
		private const PREFIX_RAW = '&'; // Переданный параметр должен быть вставлен "как есть"
		private const PREFIX_STRING = '%'; // Форсировать параметр как sql-строку,
		// private const PREFIX_ALL = '*'; // Все колонки

		// TODO: Экранировать удвоением. У каждого метода есть метод резолюции по умолчанию, если явно не указано
		private static $prefixes = [
			self::PREFIX_NAME,
			self::PREFIX_RAW,
			self::PREFIX_STRING,
			// self::PREFIX_ALL,
		];

		private static $operators = [
			'bitand' => '&',
			'gt' => '>',
			'rshift' => '>>',
			'gte' => '>=',
			'lt' => '<',
			'eq' => '=',
			'neq' => '!=',
			'lshift' => '<<',
			'lte' => '<=',
			'mod' => '%',
			'mul' => '*',
			'plus' => '+',
			'minus' => '-',
			'jsonExtract' => '->',
			'div' => '-',
			'bitxor' => '^',
			'and' => 'AND',
			'is' => 'IS',
			'isnt' => 'IS NOT',
			'like' => 'LIKE',
			'not' => 'NOT',
			'or' => 'OR',
			'regexp' => 'REGEXP',
			'rlike' => 'RLIKE',
			'xor' => 'XOR',
			'bitor' => '|',
			'bitinv' => '~',
		];

		private static $types = [
			'int',
			'text'
		];

		/** @var array */
		private $query;

		public function __construct(...$arguments) {
			$this->query = $arguments;
		}

		public function select(...$arguments): self {
			$this->query[] = 'SELECT';
			$argsCount = sizeof($arguments);
			if ($argsCount) {
				$arguments = array_map('self::trim', $arguments);
				if ($argsCount === 1) {
					if (is_array($arguments[0])) {
						$select = [];
						foreach ($arguments[0] as $key => $value) {
							if (is_string($key)) {
								$select[] = self::filter($key, self::PREFIX_NAME).' AS '.self::filter($value, self::PREFIX_NAME);
							} else {
								$select[] = self::filter($value, self::PREFIX_NAME);
							}
						}
						$this->query[] = join(', ', $select);
					} else {
						$this->query[] = self::filter($arguments[0], self::PREFIX_NAME);
					}
				} else {
					$this->query[] = self::filter($arguments[0], self::PREFIX_NAME).' AS '.self::filter($arguments[1], self::PREFIX_NAME);
				}
			}
			return $this;
		}

		public function distinct(): self {}

		public function from(...$arguments): self {
			$this->query[] = 'FROM';
			$argsCount = sizeof($arguments);
			// FIXME
			if ($argsCount) {
				$result = [];
				if (is_array($arguments[0])) {
					foreach ($arguments[0] as $key => $value) {
						if (is_string($key)) {
							$result[] = self::filter($key, self::PREFIX_NAME).' AS '.self::filter($value, self::PREFIX_NAME);
						} elseif ($value instanceof self) {
							$result[] = '('.self::filter($value, self::PREFIX_NAME).')';
						} else {
							$result[] = self::filter($value, self::PREFIX_NAME);
						}
					}
				} else {
					foreach ($arguments as $arg) {
						if ($arg instanceof self)
							$result[] = '('.self::filter($arg, self::PREFIX_NAME).')';
						else
							$result[] = self::filter($arg, self::PREFIX_NAME);
					}
				}
				$this->query[] = join(', ', $result);
			}
			return $this;
		}

		public function partition(): self {}
		public function join(): self {}

		// TODO
		public function where($cond): self {
			$this->query[] = 'WHERE';
			if (is_string($cond)) {
				$this->query[] = self::filter($cond, self::PREFIX_NAME);
			} elseif (is_array($cond)) {
				$result = [];
				foreach ($cond as $key => $value) {
					if (is_string($key)) {
						$result[] = self::filter($key, self::PREFIX_NAME).' = '.self::filter($value);
					} elseif ($value instanceof self) {
						$result[] = '('.self::filter($value).')';
					} else {
						$result[] = self::filter($value);
					}
				}
				$this->query[] = join(' AND ', $result);
			}
			return $this;
		}
		public function groupBy(): self {}
		public function having(): self {}

		public function orderBy($a, $direction = null): self {
			$this->query[] = 'ORDER BY';
			if (is_array($a)) {
				$result = [];
				foreach ($a as $key => $value) {
					if (is_string($key)) {
						$direction = $value;
						if ($direction === null)
							$direction = 'ASC';
						if (is_string($direction))
							$direction = strtoupper($direction);
						elseif (is_bool($direction))
							$direction = $direction ? 'ASC' : 'DESC';
						$result[] = self::filter($key, self::PREFIX_NAME)." {$direction}";
					} elseif ($value instanceof self) {
						$result[] = "{$value} ASC";
					} else {
						$result[] = self::filter($value, self::PREFIX_NAME).' ASC';
					}
				}
				$this->query[] = join(', ', $result);
			} else {
				$a = self::filter($a, self::PREFIX_NAME);
				if ($direction === null)
					$direction = 'ASC';
				if (is_string($direction))
					$direction = strtoupper($direction);
				elseif (is_bool($direction))
					$direction = $direction ? 'ASC' : 'DESC';
				$this->query[] = "{$a} {$direction}";
			}
			return $this;
		}

		public function limit(int $a, ?int $b = null): self {
			$this->query[] = 'LIMIT';
			if ($b)
				$this->query[] = "{$a}, {$b}";
			else
				$this->query[] = $a;
			return $this;
		}

		public function offset(int $offset): self {
			$this->query[] = "OFFSET {$offset}";
			return $this;
		}

		public function id(string $id): self {
			$this->query[] = self::filter($id, self::PREFIX_NAME);
			return $this;
		}

		public function as(string $as): self {
			$this->query[] = 'AS';
			$this->query[] = self::filter($as, self::PREFIX_NAME);
			return $this;
		}

		public function table(...$arguments): self {
			$this->query[] = 'TABLE';
			$argsCount = sizeof($arguments);
			if ($argsCount >= 2 && is_array($arguments[1])) {
				$this->query[] = self::filter($arguments[0], self::PREFIX_NAME);
				$this->query[] = '('.join(', ', $arguments[1]).')';
			} else {
				$tblList = [];
				foreach ($arguments as $tbl) {
					$tblList[] = self::filter($tbl, self::PREFIX_NAME);
				}
				$this->query[] = join(', ', $tblList);
			}
			return $this;
		}

		// TODO
		public function query(?string $raw = null): ?array {}

		public function __call($method, $arguments): ?self {
			$lcMethod = strtolower($method);
			if (isset(self::$operators[$lcMethod])) {
				$this->query[] = self::$operators[$lcMethod];
				$arguments = array_map('self::trim', $arguments);
				foreach ($arguments as $arg)
					$this->query[] = self::filter($arg);
			} else {
				$matches = null;
				preg_match_all('/((?:^|[A-Z])[a-z]+)/', $method, $matches);
				$method = join('_', $matches[0]);
				$arguments = array_map('self::filter', $arguments);
				$this->query[] = strtoupper($method).'('.join(', ', $arguments).')';
			}
			return $this;
		}

		public function __get($name) {
			$matches = null;
			preg_match_all('/((?:^|[A-Z])[a-z]+)/', $name, $matches);
			$name = join(' ', $matches[0]);
			$this->query[] = strtoupper($name);
			return $this;
		}

		public function __toString(): string {
			return trim(join(' ', $this->query));
		}

		public static function new(...$arguments): self {
			return new self(...$arguments);
		}

		public static function setQueryEscape($callback) {}

		// private static function processList(array $list, string $delimiter, ?string $considerKeyAs = null, ?string $considerValueAs = null): array {
		// 	$result = [];
		// 	foreach ($list as $k => $v)
		// 		$result[] = self::filter($k, $considerKeyAs).$delimiter.self::filter($v, $considerValueAs);
		// 	return $result;
		// }

		private static function convertToSQLType($value): string {
			if (is_string($value))
				return '\''.self::escape($value).'\'';
			if (is_null($value))
				return 'NULL';
			if (is_bool($value))
				return $value ? 'TRUE' : 'FALSE';
			return $value;
		}

		private static function filter($input, ?string $defaultPrefix = null): string {
			if ($input instanceof self)
				return (string) $input;
			if (is_string($input) && strlen($input)) {
				if (!in_array($input{0}, self::$prefixes) && $defaultPrefix)
					$input = $defaultPrefix.$input;
				if (in_array($input{0}, self::$prefixes)) {
					$prefix = $input{0};
					$input = substr($input, 1);
					if (in_array($prefix, self::$prefixes)) {
						switch ($prefix) {
							case self::PREFIX_NAME:
								return join('.', array_map(function($value) {
									if ($value === '*')
										return $value;
									return "`{$value}`";
								}, explode('.', $input)));
							case self::PREFIX_RAW:
								return $input;
							case self::PREFIX_STRING:
								return self::convertToSQLType($input);
						}
					}
				}
			}
			return self::convertToSQLType($input);
		}

		private static function escape($value): string {
			return addslashes($value);
		}

		private static function trim($value) {
			if (is_array($value)) {
				$result = [];
				foreach ($value as $k => $v) {
					$result[$k] = self::trim($v);
				}
				return $result;
			} elseif (is_string($value)) {
				return trim($value);
			} else {
				return $value;
			}
		}
	}

	return;

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
		Query::new()
		->position('@Steel')
		->in(
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
