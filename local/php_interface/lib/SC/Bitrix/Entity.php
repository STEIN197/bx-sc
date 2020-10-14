<?php
	namespace SC\Bitrix;

	use Exception;

	/**
	 * Класс сущности Bitrix.
	 * Каждая сущность соответствует записи в базе данных.
	 */
	abstract class Entity {

		/** @var int Идентификатор сущности. Поле имеет ненулевое значение только у существующих сущностей */
		protected $id;
		/** @var array Поля сущности. */
		protected $arFields;
		/** @var bool True, если был запрос в БД на выборку полей. */
		private $fieldsFetched = false;

		/**
		 * Создаёт совершенно новую сущность,
		 * которая ещё не существует в базе.
		 * @param array $arFields Массив полей сущности.
		 * @throws EntityCreationException Если есть ключ 'ID'. В этом случае стоит вызывать self::fromArray().
		 */
		public function __construct(array $arFields = []) {
			if (isset($arFields['ID']))
				throw new EntityCreationException('Cannot create entity '.static::class." with ID '{$arFields['ID']}'. ID field must not be presented");
			$this->fieldsFetched = true;
			$this->arFields = [];
			$this->setFields($arFields);
		}

		/**
		 * Возвращает поля сущности массивом.
		 * @return array
		 */
		public final function getFields(): array {
			if ($this->id && !$this->fieldsFetched) {
				$this->fieldsFetched = true;
				$this->arFields = array_merge($this->fetchFields(), $this->arFields ?? []);
				Entity::castArrayValuesType($this->arFields);
			}
			return $this->arFields;
		}

		/**
		 * Устанавливает поля сущности массивом.
		 * @param array $arFields Какие поля установить сущности.
		 * @return void
		 * @see self::setField();
		 */
		public final function setFields(array $arFields): void {
			foreach ($arFields as $key => $value)
				$this->setField($key, $value);
		}

		/**
		 * Возвращает значение поля по его ключу.
		 * @param string $key Ключ поля, значение которого нужно вернуть.
		 * @return mixed Значение поля.
		 */
		public final function getField(string $key) {
			return @$this->getFields()[$key];
		}

		/**
		 * Устанавливает значение одного поля по ключу.
		 * Числовые значения конвертируются в число.
		 * @param string $key Ключ поля, значение которого нужно изменить.
		 * @param mixed $value Новое значение поля.
		 * @return void
		 */
		public final function setField(string $key, $value): void {
			$value = self::castValueType($value);
			@$this->arFields[$key] = $value;
			if ($key === 'ID')
				$this->id = $value;
			if ($key === 'IBLOCK_SECTION_ID' && method_exists($this, 'setParent'))
				$this->setParent($value);
		}

		public final function getID(): ?int {
			return $this->id;
		}

		/**
		 * Обновляет поля сущности из базы только в том случае,
		 * если у сущности есть поле $id.
		 * @return void
		 */
		public final function refresh(): void {
			if (!$this->id)
				return;
			$this->fetchFields();
			if (is_array($this->arFields))
				self::castArrayValuesType($this->arFields);
			else
				$this->arFields = [];
			if (method_exists($this, 'fetchProperties')) {
				$this->fetchProperties();
				if (is_array($this->arProperties))
					self::castArrayValuesType($this->arProperties);
				else
					$this->arProperties = [];
			}
		}

		public function toArray(): array {
			return $this->getFields();
		}

		/**
		 * Сохраняет сущность в базу.
		 * @return void
		 * @throws EntityDatabaseException Если возникла ошибка при сохранении сущности.
		 */
		abstract public function save(): void;

		/**
		 * Удаляет сущность из базы,
		 * сохраняя при этом все поля и свойства объекта.
		 * @return void
		 * @throws EntityDatabaseException Если возникла ошибка при удалении сущности.
		 */
		abstract public function delete(): void;

		/**
		 * Получает данные сущности из базы.
		 * @return void
		 * @see self::getFields()
		 * @see self::refresh()
		 */
		abstract protected function fetchFields(): array;

		/**
		 * Получает список сущностей.
		 * @param array $arFilter Массив для фильтрации.
		 * @param array $arOrder Массив для сортировки.
		 * @param array $arSelect Массив для выборки полей.
		 * @param array $arNav Массив постраничной навигации.
		 * @return array Массив сущностей. Каждый элемент массива - ассоциативный массив.
		 *               Внутренние массивы не должны содержать ~-ключи. Если сущностей нет,
		 *               возвращается пустой массив.
		 */
		abstract public static function getList(array $arFilter = [], array $arOrder = [], ?array $arSelect = null, ?array $arNav = null): array;

		/**
		 * Возвращает сущность по её идентификатору.
		 * @param int $id Идентификатор сущности.
		 * @return static
		 * @throws EntityNotFoundException Если сущности с таким ID не найдено.
		 */
		abstract public static function getByID(int $id);

		/**
		 * Создаёт объект-заглушку, которая имеет только поле $id.
		 * Далее с ней можно обращаться как с обычным объектом.
		 * Можно использовать для ленивой выборки полей из базы.
		 * @param int $id Идентификатор сущности.
		 * @return static
		 */
		protected static function stubFromID(int $id) {
			$o = new static;
			$o->id = $id;
			$o->fieldsFetched = false;
			return $o;
		}

		/**
		 * Создаёт объект сущности из массива полей.
		 * Предполагается, что сущность уже существует в базе
		 * и делается обёртка вокруг полей.
		 * @param array $arFields Массив полей сущности.
		 * @return static
		 * @throws EntityCreationException Если массив не содержит числового ключа 'ID'.
		 */
		public static function fromArray(array $arFields) {
			if (!isset($arFields['ID']))
				throw new EntityCreationException('Cannot create entity '.static::class.". ID field must be presented");
			$o = new static;
			$o->setFields($arFields);
			return $o;
		}

		/**
		 * Возвращает объект сущности по типу переданного параметра.
		 * Если это число или строка, содержащая число, то возвращается
		 * сущность по идентификатору. Если это массив, то он оборачивается
		 * в объект, при этом если в массиве есть ключ 'ID', то возвращается объект из базы,
		 * а если это сама сущность, то возвращается сущность.
		 * @param string|int|array|static $entity Параметр, из которого нужно сделать сущность
		 * @return static Объект сущности
		 * @throws EntityCreationException Если нельзя создать сущность.
		 * @throws EntityNotFoundException Если передан ID сущности, но записи с таким идентификатором не существует.
		 */
		public static final function make($entity) {
			if (is_int($entity) || is_string($entity) && strval(intval($entity)) == $entity)
				return static::stubFromID((int) $entity);
			if (is_array($entity) && isset($entity['ID']))
				return static::fromArray($entity);
			if (is_array($entity))
				return new static($entity);
			if ($entity instanceof static)
				return $entity;
			throw new EntityCreationException("Cannot create entity from input: {$entity}");
		}

		/**
		 * Преобразует все числовые строки массива в числа.
		 * @param array $arFields Массив, типы значений которых нужно преобразовать.
		 * @return void
		 */
		public static final function castArrayValuesType(array &$arFields): void {
			foreach ($arFields as &$value)
				$value = self::castValueType($value);
		}

		// TODO: Recursive on array?
		public static final function castValueType($value) {
			return is_numeric($value) ? (intval($value) == $value ? (int) $value : (float) $value) : $value;
		}
	}
