<?php

/*
 * Helper methods for integration tasks such as validations
 */
class Library
{
	public static function validateEstonianPersonalCode($in)
	{
		if(!preg_match("'^\d{11}$'", $in))
		{
			//improper length and/or not numbers input
			return false;
		}

		$in = str_split($in);
		$checksum = $in[10];
		$testDigits = array_slice($in, 0, 10);

		$weights1 = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 1);
		$weights2 = array(3, 4, 5, 6, 7, 8, 9, 1, 2, 3);

		$test = 0;
		for($i=0; $i<10; $i++)
		{
			$test += $weights1[$i] * $testDigits[$i];
		}

		if($test % 11 == 10)
		{
			$test = 0;
			for($i=0; $i<10; $i++)
			{
				$test += $weights2[$i] * $testDigits[$i];
			}
		}

		if($test % 11 == 10)
		{
			$test = 0;
		}

		return $checksum == $test % 11;
	}
}