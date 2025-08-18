<?php

function __(string $key, string $default = '')
{
    // Garantir que o singleton Translator está carregado corretamente
    return Translator::getInstance()->get($key, $default);
}