<?php
	namespace SC\Bitrix;

	class EntityCreationException extends \Exception {

		/** @var Entity */
		private $entity;

		public function __construct($entity, string $message = '', int $code = 0) {
			$this->entity = $entity;
			$this->message = $message;
			$this->code = $code;
		}
	}
