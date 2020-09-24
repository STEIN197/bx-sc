<?php
	namespace SC\IBlock;

	class Element extends Entity {

		use Parentable;
		use Propertiable;

		// TODO
		public function save(): void {}

		// TODO
		public function delete(): void {}

		// TODO
		protected function fetchFields(): void {}

		// TODO
		public static function getList(array $arFilter, array $arOrder = ['SORT' => 'ASC'], ?array $arSelect = null, ?array $arNav = null): array {}

		// TODO
		public static function getByID(int $id, bool $onlyStub = false) {}
		
		// TODO
		protected function fetchProperties(): void {}

		// TODO
		public function getParents(): array {}

		// TODO
		public function setParents(): array {}
	}
