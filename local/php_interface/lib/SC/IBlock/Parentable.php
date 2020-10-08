<?php
	namespace SC\IBlock;

	trait Parentable {

		protected $parent;

		public final function getParent(): ?Section {
			if (!$this->parent) {
				$sectionID = @$this->arFields['IBLOCK_SECTION_ID'];
				if ($sectionID)
					$this->parent = Section::getByID((int) $sectionID, true);
			}
			return $this->parent;
		}

		public function setParent($parent): ?Section {
			$old = $this->getParent();
			if ($parent === null) {
				$this->arFields['IBLOCK_SECTION_ID'] = false;
			} else {
				$this->parent = Section::make($parent);
				if ($this->parent)
					$this->arFields['IBLOCK_SECTION_ID'] = $this->parent->getID();
			}
			return $old;
		}
	}
