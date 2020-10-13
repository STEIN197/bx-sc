<?php
	namespace SC\Bitrix\IBlock;

	/**
	 * Трейт используется классами, объекты которых
	 * могут иметь свойства - инфоблок, раздел или элемент.
	 */
	trait Propertiable {

		/** @var array Массив свойств. Ключи массива - это коды свойства. */
		protected $arProperties;
		/** @var bool True, если был запрос в БД на выборку свойств. */
		protected $propertiesFetched = false;

		public final function getProperties(): array {
			if ($this->id && !$this->propertiesFetched) {
				$this->propertiesFetched = true;
				$this->arProperties = array_merge($this->fetchProperties(), $this->arProperties ?? []);
				Entity::castArrayValuesType($this->arProperties);
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
			$this->arProperties[$key] = Entity::castValueType($value);
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

		abstract protected function fetchProperties(): array;
	}
