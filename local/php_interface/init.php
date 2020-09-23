<?php
	require 'AutoloadMapping.php';
	$mapping = new AutoloadMapping(__DIR__.DIRECTORY_SEPARATOR.'lib');
	$mapping->registerClasses();
	SC\Options::init(2);
	trait delete_it {
		abstract function getFields();
		abstract function getProperties();
		abstract function setFields();
		abstract function setProperties();
		abstract function getField();
		abstract function getProperty();
		abstract function setField();
		abstract function setProperty();
		protected $arFields;
		protected $arProperties;
		
		// abstract class Entity
		protected $id;
		function getID(): ?int {}
		abstract function save(): void;
		abstract function delete(): void;
		abstract static function getList(array $arFilter, array $arOrder = ['SORT' => 'ASC'], ?array $arSelect = null, ?array $arNav = null): array;
		abstract static function getByID(int $id): ?self; // TODO: Только в трейтах
		static function castTypes(array &$arFields): void {}
		
		// interface EntityContainer
		abstract function getElements(array $arFilter = [], array $arOrder = ['SORT' => 'ASC'], ?array $arSelect = null, ?array $arNav = null): array;
		abstract function getSections(array $arFilter = [], array $arOrder = ['SORT' => 'ASC'], ?array $arSelect = null, ?array $arNav = null): array;
		abstract function getDistinctValues($property, bool $includeInactive = true, array $arFilter = null);
		
		// trait IBlockEntity
		abstract function getIBlock(): ?IBlock;

		// trait IBlockTreeEntity
		abstract function getParent();
		abstract function setParent(Section $parent);

		// class IBlock extends Entity implements EntityContainer
		// class Section extends Entity implements EntityContainer use IBlockEntity, IBlockTreeEntity
		// class Element extends Entity use IBlockEntity, IBlockTreeEntity
		abstract function getParents(): array;
		abstract function setParents(): array;
		
		// class Property extends Entity use IBlockEntity
		public static function make($property): Property {}
	}
