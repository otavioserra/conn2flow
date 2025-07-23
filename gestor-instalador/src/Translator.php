<?php

class Translator
{
    private static $instance = null;
    private $translations = [];
    private $lang = 'pt-br';

    private function __construct()
    {
        // Construtor privado para evitar instanciação direta.
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Translator();
        }
        return self::$instance;
    }

    public function load(string $lang = 'pt-br')
    {
        $this->lang = $lang;
        $filePath = __DIR__ . "/../lang/{$this->lang}.json";

        if (file_exists($filePath)) {
            $this->translations = json_decode(file_get_contents($filePath), true) ?? [];
        }
    }

    public function get(string $key, string $default = ''): string
    {
        return $this->translations[$key] ?? ($default ?: $key);
    }
}