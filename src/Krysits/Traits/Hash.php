<?php
namespace Krysits\Traits;

trait Hash
{
	public function genHash($length = 32)
	{
		try {
			$hash = md5(random_bytes($length));
		}
		catch (\Exception $e) {
			$hash = '';
		}
		return $hash;
	}

	public function genCode($number)
	{
		$out   = "";
		$codes = "abcdefghjkmnpqrstuvwxyz0123456789ABCDEFGHJKMNPQRSTUVWXYZ";

		while ($number > 55) {
			$key = $number % 56;
			$number = floor($number / 56) - 1;
			$out = $codes[$key].$out;
		}

		return $codes[$number].$out;
	}
};
