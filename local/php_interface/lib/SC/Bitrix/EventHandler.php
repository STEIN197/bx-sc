<?php
	namespace SC\Bitrix;

	use SC\Bitrix\KDAImportExcel\Classifier;

	final class EventHandler {

		public static $classifiers = [];

		public static function onEndImport(): void {
			self::importPipes();
			// self::importOtvody();
		}

		// /truby/dxt/element/
		private static function importPipes(): void {
			$cl = new Classifier(1);
			$cl->setElementSource(Classifier::ELEMENT_SOURCE_SECTION, 2);
			$cl->add(4, [
				'properties' => ['STEEL']
			]);
			$cl->add(8, [
				'properties' => ['STANDARD']
			]);
			$cl->addMain(3, [
				'properties' => ['D'],
			]);
			$cl->execute();
			self::$classifiers[] = $cl;
		}

		// /otvody/std/angle/element/
		private static function importOtvody(): void {
			$cl = new Classifier(1);
			$cl->setElementSource(Classifier::ELEMENT_SOURCE_SECTION, 8);
			$cl->add(11, [
				'properties' => ['D']
			]);
			$cl->add(12, [
				'properties' => ['ANGLE']
			]);
			$cl->addMain(10, [
				'properties' => ['STANDARD'],
				'child' => [
					'properties' => ['ANGLE']
				]
			]);
			$cl->execute();
			self::$classifiers[] = $cl;
		}
	}
