<?php
	namespace SC\Bitrix;

	class EntityCreationException extends \Exception {

		public const ID_NOT_PRESENT = 1;
		public const ID_IS_PRESENT = 2;

		/** @var Entity */
		private $entity;

		public function __construct($entity, string $message = '', int $code = 0) {
			$this->entity = $entity;
			$this->message = $message;
			$this->code = $code;
			$this->makeMessage();
		}

		private function makeMessage(): void {
			if ($this->message)
				return;
			switch ($this->code) {
				case self::ID_NOT_PRESENT:
					$this->message = 'Cannot create entity '.get_class($this->entity).'. ID field must be presented';
					break;
				case self::ID_IS_PRESENT:
					$this->message = 'Cannot create entity '.get_class($this->entity)." with ID '{$entity->getID()}'. ID field must not be presented";
					break;
				default:
					$this->message = 'Cannot create entity '.get_class($this->entity);
			}
		}
	}
