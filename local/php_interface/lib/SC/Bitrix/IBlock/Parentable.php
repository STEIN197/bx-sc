<?php
	namespace SC\Bitrix\IBlock;

	use \Exception;

	/**
	 * Используется классами, сущности которых
	 * могут иметь родителя.
	 */
	trait Parentable {

		/** @var Section Кеш-переменная родителя */
		protected $parent;

		/**
		 * Возвращает родителя раздела или элемента.
		 * Если это элемент, то возвращает основной раздел.
		 * @return Section|null Родителя. Null, если у сущности нет родителя
		 */
		public final function getParent(): ?Section {
			$parentID = $this->getField('IBLOCK_SECTION_ID');
			if (!$this->parent && $parentID)
				$this->parent = Section::stubFromID($parentID);
			return $this->parent;
		}

		/**
		 * Устанавливает родителя раздела или элемента.
		 * Если это элемент, то устанавливается основной родитель.
		 * @param Section|int|string|array|null $parent Установить родителя. Если null, то удаляется родитель.
		 * @return void
		 * @throws Exception
		 * @see Entity::make()
		 */
		public function setParent($parent): void {
			if ($parent === null) {
				$this->arFields['IBLOCK_SECTION_ID'] = 0;
				$this->parent = null;
			} else {
				$oParent = Section::make($parent);
				$this->arFields['IBLOCK_SECTION_ID'] = $oParent->getID();
				$this->parent = $oParent;
			}
		}
	}
