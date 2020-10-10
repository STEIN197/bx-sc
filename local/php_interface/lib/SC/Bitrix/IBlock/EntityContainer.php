<?php
	namespace SC\Bitrix\IBlock;

	interface EntityContainer {

		public function getElements(array $arFilter = [], array $arOrder = ['SORT' => 'ASC'], ?array $arSelect = null, ?array $arNav = null): array;

		public function getSections(array $arFilter = [], array $arOrder = ['SORT' => 'ASC'], ?array $arSelect = null, ?array $arNav = null): array;

		public function getDistinctValues(array $properties, array $arFilter = null, bool $includeInactive = false): ?array;
	}
