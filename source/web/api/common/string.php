<?php

function typesafe_iconv_substr($str, $offset, $length)
{
	return iconv_substr($str, $offset, $length) ?: "";
}
