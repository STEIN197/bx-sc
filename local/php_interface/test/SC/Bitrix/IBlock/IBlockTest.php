<?php
	namespace SC\Bitrix\IBlock;
	
	use SC\Bitrix\EntityCreationException;

	class IBlockTest extends \SC\Bitrix\BaseTest {

		protected static function populateData(): void {
			$ib = new \CIBlock;
			$ib->Add([
				'NAME' => 'Каталог',
				'CODE' => 'catalogue',
				'IBLOCK_TYPE_ID' => 'catalogue',
				'ACTIVE' => 'Y'
			]);
			$ib = new \CIBlock;
			$ib->Add([
				'NAME' => 'Каталог 2',
				'CODE' => 'catalogue2',
				'IBLOCK_TYPE_ID' => 'catalogue',
				'ACTIVE' => 'Y'
			]);
		}

		public function test_constructor_WhenIDInArray_ThrowsException() {
			$this->expectException(EntityCreationException::class);
			new IBlock([
				'ID' => 23
			]);
		}

		public function test_getFields_ReturnsConstructorArray() {
			$ib = new IBlock;
			$this->assertEquals([], $ib->getFields());
			$ib = new IBlock([
				'CODE' => 'catalogue'
			]);
			$this->assertEquals([
				'CODE' => 'catalogue'
			], $ib->getFields());
		}

		public function test_getFields_ReturnsDBRow() {} // TODO
		public function test_getFields_ReturnsInteger() {} // TODO
		public function test_getFields_AfterSetFields_ReturnsInteger() {} // TODO
		public function test_getFields_DoesNotContainTilda() {} // TODO

		public function test_setFields_ActuallySetsData() {} // TODO
		
		public function test_getField_ReturnsScalarType() {} // TODO

		public function test_refresh_OnNew_DoesNothing() {} // TODO
		public function test_refresh_OnExisting_RefreshesFieldsAndProperties() {} // TODO
		
		public function test_save_createsRowInDB() {} // TODO
		public function test_save_changesDataInExistingRow() {} // TODO
		public function test_save_savesProperties() {} // TODO
		public function test_save_createsProperties() {} // TODO

		public function test_delete_OnNew_DoesNothing() {} // TODO
		public function test_delete_DeletesAllData() {} // TODO
		
		public function test_getList_ReturnsCorrectData() {} // TODO
		public function test_getList_WhenDBIsClean_ReturnsEmpty() {
			$this->assertEmpty(IBlock::getList());
		}

		public function test_getByID_WhenExists_ReturnsRowFromDB() {} // TODO
		public function test_getByID_WhenDoesNotExist_ThrowsException() {} // TODO

		public function test_fromArray_WhenExists_ReturnsObject() {} // TODO
		public function test_fromArray_WhenDoesNotExist_ThrowsException() {} // TODO

		public function test_make_IsCorrect() {} // TODO
		// TODO Propertiable, EntityContainer
	}
