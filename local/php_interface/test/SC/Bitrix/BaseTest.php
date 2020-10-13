<?php
	namespace SC\Bitrix;

	use CIBlock;
	use CModule;
	use PHPUnit\Framework\TestCase;

	class BaseTest extends TestCase {

		public static function setUpBeforeClass(): void {
			self::clearDatabase();
		}

		public function tearDown(): void {
			self::clearDatabase();
		}

		protected static function clearDatabase(): void {
			global $DB, $DBName;
			CModule::includeModule('iblock');
			$rs = CIBlock::GetList([], [
				'CHECK_PERMISSIONS' => 'N'
			]);
			while ($ar = $rs->GetNext())
				CIBlock::Delete($ar['ID']);
			foreach (['', '_section', '_element', '_property'] as $p)
				$DB->Query("ALTER TABLE b_iblock{$p} AUTO_INCREMENT = 1");
			// $q = "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = '{$DBName}' AND TABLE_NAME LIKE 'b_iblock%' AND TABLE_ROWS = 0 AND AUTO_INCREMENT > 1";
			// $rs = $DB->Query($q);
			// while ($row = $rs->Fetch())
			// 	$DB->Query("ALTER TABLE {$row['TABLE_NAME']} AUTO_INCREMENT = 1");
		}
	}
