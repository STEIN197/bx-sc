<?php
	namespace SC\Bitrix;

	final class EventHandler {

		public static $classifiers = [];

		public static function onEndImport(): void {
			self::importPipes();
			self::importOtvody();
		}

		// /truby/dxt/element/
		private static function importPipes(): void {
			$cl = new Classifier(1);
			$cl->setElementSource(Classifier::ELEMENT_SOURCE_SECTION, 1);
			$cl->add(2, [
				'properties' => ['STANDARD']
			]);
			$cl->add(3, [
				'properties' => [3]
			]);
			$cl->addMain(6, [
				'properties' => ['D', 'T'],
				'callbacks' => [
					'createName' => function($dValue, $tValue): string {
						return "{$dValue}х{$tValue}";
					},
					'createCode' => function($dValue, $tValue): string {
						return Util::translit($dValue).'x'.Util::translit($tValue);
					},
					'sort' => function($dValue, $tValue) {
						return $tValue * 1000;
					}
				]
			]);
			$cl->add(4, [
				'properties' => ['D']
			]);
			$cl->add(5, [
				'properties' => ['TYPE']
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
