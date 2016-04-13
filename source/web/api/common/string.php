<?php

function typesafe_iconv_substr($str, $offset, $length)
{
	$result = iconv_substr($str, $offset, $length);
	if (false === $result)
	{
		$result = '';
	}
	return $result;
}
