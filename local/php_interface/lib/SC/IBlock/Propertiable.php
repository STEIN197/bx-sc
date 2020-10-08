<?php
	namespace SC\IBlock;

	trait Propertiable {

		protected $arProperties;

		public final function getProperties(): array {
			if (is_array($this->arProperties))
				return $this->arProperties;
			$this->fetchProperties();
			if (is_array($this->arProperties)) {
				Entity::castTypes($this->arProperties);
			} else {
				$this->arProperties = [];
			}
			return $this->arProperties;
		}

		public final function setProperties(array $arProperties): void {
			foreach ($arProperties as $k => $v)
				$this->setProperty($k, $v);
		}

		public final function getProperty(string $key) {
			return @$this->getProperties()[$key];
		}

		public final function setProperty(string $key, $value) {
			$old = $this->getProperty($key);
			$this->arProperties[$key] = $value;
			return $old;
		}

		/**
		 * @param string $code
		 * @return string|array
		 * @throws \Exception
		 */
		public function getSEO(?string $code) {
			if ($this instanceof IBlock)
				$ipropValues = new \Bitrix\Iblock\InheritedProperty\IBlockValues($this->id, $this->id);
			elseif ($this instanceof Section)
				$ipropValues = new \Bitrix\Iblock\InheritedProperty\SectionValues($this->getField('IBLOCK_ID'), $this->id);
			elseif ($this instanceof Element)
				$ipropValues = new \Bitrix\Iblock\InheritedProperty\ElementValues($this->getField('IBLOCK_ID'), $this->id);
			else
				throw new \Exception('Unknown entity type');
			if ($code)
				return $ipropValues->getValue($code);
			else
				return $ipropValues->getValues();
		}

		abstract protected function fetchProperties(): void;
	}
