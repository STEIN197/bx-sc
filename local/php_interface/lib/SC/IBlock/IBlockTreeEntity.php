<?php
	namespace SC\IBlock;

	trait IBlockTreeEntity {

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
			$this->parent = Section::make($parent);
			if ($this->parent)
				$this->arFields['IBLOCK_SECTION_ID'] = $this->parent->getID();
			return $old;
		}
	}
