<?php
	namespace SC\Bitrix;

	class EntityNotFoundException extends \Exception {

		/** @var Entity */
		private $entity;

		public function __construct($entity, string $message = '', int $code = 0) {
			$this->entity = $entity;
			$this->message = $message ?: 'Entity '.get_class($entity)." with ID '{$entity->getID()}' not found";
			$this->code = $code;
		}
	}
