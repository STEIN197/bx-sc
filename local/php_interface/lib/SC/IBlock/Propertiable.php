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
			$this->arProperties = array_merge($this->getProperties(), $arProperties);
		}

		public final function getProperty(string $key) {
			return @$this->getProperties()[$key];
		}

		public final function setProperty(string $key, $value) {
			$old = $this->getProperty($key);
			$this->arProperties[$key] = $value;
			return $old;
		}

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
