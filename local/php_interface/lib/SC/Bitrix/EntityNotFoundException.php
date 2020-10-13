<?php
	namespace SC\Bitrix;

	class EntityNotFoundException extends \Exception {

		public function __construct(string $message = '', int $code = 0) {
			$this->message = $message;
			$this->code = $code;
		}
	}
