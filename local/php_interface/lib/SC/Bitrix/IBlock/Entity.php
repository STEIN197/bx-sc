<?php
	namespace SC\Bitrix\IBlock;

	use \Exception;

	abstract class Entity extends \SC\Bitrix\Entity {

		/** @var IBlock Инфоблок, к которому принадлежит сущность. */
		protected $iblock;

		/**
		 * Возвращает инфоблок, к которому принадлежит сущность.
		 * @return IBlock
		 */
		public function getIBlock(): ?IBlock {
			if (!$this->iblock && $this->getField('IBLOCK_ID'))
				$this->iblock = IBlock::stubFromID($this->getField('IBLOCK_ID'));
			return $this->iblock;
		}
	}
