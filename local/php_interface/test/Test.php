  
<?php
	// namespace SC\;

	// use STEIN197\MySQLQueryBuilder\Query;
	use PHPUnit\Framework\TestCase;

	class Test extends TestCase {

		public function testGG() {
			global $DB;
			$this->assertTrue($DB !== null);
		}
	}