<?php
	namespace SC;

	final class Util {

		public static function translit(string $str): string {
			static $dictionary = [
				'а' => 'a',
				'б' => 'b',
				'в' => 'v',
				'г' => 'g',
				'д' => 'd',
				'е' => 'e',
				'ё' => 'ye',
				'ж' => 'zh',
				'з' => 'z',
				'и' => 'i',
				'й' => 'y',
				'к' => 'k',
				'л' => 'l',
				'м' => 'm',
				'н' => 'n',
				'о' => 'o',
				'п' => 'p',
				'р' => 'r',
				'с' => 's',
				'т' => 't',
				'у' => 'u',
				'ф' => 'f',
				'х' => 'kh',
				'ц' => 'ts',
				'ч' => 'ch',
				'ш' => 'sh',
				'щ' => 'shch',
				'ъ' => '',
				'ы' => 'y',
				'ь' => '',
				'э' => 'e',
				'ю' => 'yu',
				'я' => 'ya',
			];
			$str = mb_strtolower($str);
			$str = str_replace(array_keys($dictionary), $dictionary, $str);
			$str = preg_replace('/[[:punct:][:space:][:cntrl:][:blank:]]+/', '-', $str);
			return $str;
		}
	}
