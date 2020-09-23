<?php
	require 'AutoloadMapping.php';
	$mapping = new AutoloadMapping(__DIR__.DIRECTORY_SEPARATOR.'lib');
	$mapping->registerClasses();
	SC\Options::init(2);
	trait delete_it {

		// trait Propertiable
		abstract function getProperties(): array;
		abstract function setProperties(array $properties);
		abstract function getProperty(string $key): ?array;
		abstract function setProperty(string $key, $value);
		protected $arProperties;
		
		// abstract class Entity
		protected $id;
		protected $arFields;
		abstract function getFields(): array;
		abstract function setFields(array $arFields);
		abstract function getField(string $key): ?array;
		abstract function setField(string $key, $value);
		function getID(): ?int {}
		abstract function save(): void;
		abstract function delete(): void;
		abstract function make($entity); // Из $intID, ['ID'], Entity::$id
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

		// class IBlock extends Entity implements EntityContainer user Propertiable
		// class Section extends Entity implements EntityContainer use IBlockEntity, IBlockTreeEntity, Propertiable

		// class Element extends Entity use IBlockEntity, IBlockTreeEntity, Propertiable
		abstract function getParents(): array;
		abstract function setParents(): array;
		
		// class Property extends Entity use IBlockEntity
	}
