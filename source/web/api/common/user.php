<?php

function make_user_search($user)
{
	return $user["name"] . " " . $user["description"] . " " . $user["twitter"];
}
