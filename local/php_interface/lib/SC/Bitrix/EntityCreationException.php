<?php
	namespace SC\Bitrix;

	class EntityCreationException extends \Exception {

		public function __construct(string $message = '', int $code = 0) {
			$this->entity = $entity;
			$this->message = $message;
			$this->code = $code;
		}
	}
