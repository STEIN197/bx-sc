<?php
	namespace SC\Bitrix\IBlock;

	use \Exception;

	abstract class Entity extends \SC\Bitrix\Entity {

		public function getIBlock(): ?IBlock {
			static $iblock = null;
			if (!$iblock && $this->getField('IBLOCK_ID'))
				$iblock = IBlock::getByID((int) $this->getField('IBLOCK_ID'));
			return $iblock;
		}
	}
