<?php
	require 'AutoloadMapping.php';
	$mapping = new AutoloadMapping(__DIR__.DIRECTORY_SEPARATOR.'lib');
	$mapping->registerClasses();
	
	use SC;
	Options::init(2);

	trait delete_it {

		// trait Propertiable
		protected $arProperties;
		public final function getProperties(): array {}
		public final function setProperties(array $arProperties): void {}
		public final function getProperty(string $key) {}
		public final function setProperty(string $key, $value) {}
		abstract protected function fetchProperties(): void;
		
		// abstract class Entity
		protected $id;
		protected $arFields;
		public final function getFields(): array {}
		public final function setFields(array $arFields): void {}
		public final function getField(string $key) {}
		public final function setField(string $key, $value) {}
		public final function getID(): ?int {}
		public function __toString() {}
		abstract public function save(): void;
		abstract public function delete(): void;
		abstract protected function fetchFields(): void;
		abstract public static function getList(array $arFilter, array $arOrder = ['SORT' => 'ASC'], ?array $arSelect = null, ?array $arNav = null): array;
		abstract public static function getByID(int $id, bool $onlyStub = false);
		public static function wrap(array $arFields) {}
		public static final function make($entity) {}
		public static final function castTypes(array &$arFields): void {}
		
		// interface EntityContainer
		abstract public function getElements(array $arFilter = [], array $arOrder = ['SORT' => 'ASC'], ?array $arSelect = null, ?array $arNav = null): array;
		abstract public function getSections(array $arFilter = [], array $arOrder = ['SORT' => 'ASC'], ?array $arSelect = null, ?array $arNav = null): array;
		abstract public function getDistinctValues($property, array $arFilter = null, bool $includeInactive = false): array;
		
		// trait IBlockEntity
		protected $iblock;
		public final function getIBlock(): ?IBlock {}

		// trait Parentable
		protected $parent;
		public final function getParent(): ?Section {}
		public function setParent($parent): ?Section {}

		// class IBlock extends Entity implements EntityContainer user Propertiable
		// class Section extends Entity implements EntityContainer use IBlockEntity, Parentable, Propertiable

		// class Element extends Entity use IBlockEntity, Parentable, Propertiable
		abstract function getParents(): array;
		abstract function setParents(): array;
		
		// class Property extends Entity use IBlockEntity
	}
