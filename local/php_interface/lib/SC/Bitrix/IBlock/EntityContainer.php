<?php
	namespace SC\Bitrix\IBlock;

	/**
	 * Интерфейс, содержащий методы для выборки дочерних элементов.
	 * Это может быть раздел или инфоблок.
	 */
	interface EntityContainer {

		/**
		 * Возвращает список дочерних элементов раздела или инфоблока.
		 * @param array $arFilter Массив для фильтрации.
		 * @param array $arOrder Массив для сортировки.
		 * @param array $arSelect Массив для выборки полей.
		 * @param array $arNav Массив постраничной навигации.
		 * @return array Массив сущностей. Каждый элемент массива - ассоциативный массив.
		 *               Внутренние массивы не должны содержать ~-ключи. Если сущностей нет,
		 *               возвращается пустой массив.
		 */
		public function getElements(array $arFilter = [], array $arOrder = [], ?array $arSelect = null, ?array $arNav = null): array;

		/**
		 * Возвращает список дочерних разделов раздела или инфоблока.
		 * @param array $arFilter Массив для фильтрации.
		 * @param array $arOrder Массив для сортировки.
		 * @param array $arSelect Массив для выборки полей.
		 * @param array $arNav Массив постраничной навигации.
		 * @return array Массив сущностей. Каждый элемент массива - ассоциативный массив.
		 *               Внутренние массивы не должны содержать ~-ключи. Если сущностей нет,
		 *               возвращается пустой массив.
		 */
		public function getSections(array $arFilter = [], array $arOrder = [], ?array $arSelect = null, ?array $arNav = null): array;

		/**
		 * Возвращает список уникальных значений выбранных свойств для
		 * конкретного раздела или инфоблока.
		 * @param array $properties Массив свойств, уникальные значения котоых требуется вывести.
		 * @param array $arFilter Массив для фильтрации свойств, ограничивающий выборку.
		 *                        Массив имеет вид '<символьное имя>' => '<значение>'.
		 * @param bool $includeInactive Учитывать ли неактивные элементы в выборке значений.
		 */
		public function getDistinctValues(array $properties, array $arFilter = null, bool $includeInactive = false): ?array;
	}
