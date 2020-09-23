<?php
	namespace SC\IBlock;

	interface ContainerEntity extends Entity {

		public function getChildren(array $arOrder, array $arFilter);
	}
