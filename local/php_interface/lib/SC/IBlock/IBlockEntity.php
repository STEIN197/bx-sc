<?php
	namespace SC\IBlock;

	trait IBlockEntity {

		protected $iblock;

		public final function getIBlock(): ?IBlock {
			if (!$this->iblock && $this->arFields['IBLOCK_ID'])
				$this->iblock = IBlock::getByID((int) $this->arFields['IBLOCK_ID']);
			return $this->iblock;
		}
	}
