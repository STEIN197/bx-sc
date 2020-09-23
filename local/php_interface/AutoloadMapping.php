<?php
	use Bitrix\Main\Loader;

	final class AutoloadMapping {

		private $classDir;
		private $mapping = [];

		public function __construct(string $classDir) {
			$this->classDir = $classDir;
			$this->make();
		}

		public function getMapping(): array {
			return $this->mapping;
		}

		public function registerClasses(): void {
			Loader::registerAutoLoadClasses(null, $this->mapping);
		}

		private function make(string $nsDir = ''): void {
			$list = $this->scandir($nsDir);
			foreach ($list as $filename) {
				$fullPath = $this->classDir.$nsDir.DIRECTORY_SEPARATOR.$filename;
				if (is_dir($fullPath)) {
					$this->make($nsDir.DIRECTORY_SEPARATOR.$filename);
				} else {
					$classname = $nsDir.'\\'.$filename;
					$classname = str_replace('/', '\\', $classname);
					$classname = preg_replace('/\.[[:alnum:]]+$/', '', $classname);
					$this->mapping[$classname] = substr($fullPath, strlen($_SERVER['DOCUMENT_ROOT']));
				}
			}
		}

		private function scandir(string $nsDir = ''): array {
			return array_filter(scandir($this->classDir.$nsDir), function($value) {
				return !in_array($value, ['.', '..']);
			});
		}
	}
