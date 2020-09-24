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

		abstract protected function fetchProperties(): void;
	}
