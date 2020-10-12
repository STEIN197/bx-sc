<?php
	namespace SC\Bitrix;

	use CIBlock;
	use CModule;
	use PHPUnit\Framework\TestCase;

	class BaseTest extends TestCase {

		public static function setUpBeforeClass(): void {
			self::clearDatabase();
		}

		public static function tearDownAfterClass(): void {
			self::clearDatabase();
		}

		private static function clearDatabase(): void {
			fwrite(STDOUT, __METHOD__ . "\n");
			global $DB, $DBName;
			CModule::includeModule('iblock');
			$rs = CIBlock::GetList([], [
				'CHECK_PERMISSIONS' => 'N'
			]);
			while ($ar = $rs->GetNext())
				CIBlock::Delete($ar['ID']);
			$q = "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = '{$DBName}' AND TABLE_NAME LIKE 'b_iblock%' AND TABLE_ROWS = 0 AND AUTO_INCREMENT > 1";
			$rs = $DB->Query($q);
			while ($row = $rs->Fetch())
				$DB->Query("ALTER TABLE {$row['TABLE_NAME']} AUTO_INCREMENT = 1");
		}
	}
