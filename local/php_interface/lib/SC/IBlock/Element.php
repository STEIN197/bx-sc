<?php
	namespace SC\IBlock;

	class Element extends Entity {
		
		use IBlockEntity;
		use IBlockTreeEntity;
		use Propertiable;

		// TODO
		public function getParents(): array {}
		// TODO
		public function setParents(): array {}
	}
