<?php

function __(string $key, string $default = '')
{
    return Translator::getInstance()->get($key, $default);
}