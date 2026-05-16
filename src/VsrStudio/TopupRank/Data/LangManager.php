<?php

namespace VsrStudio\TopupRank\Data;

use pocketmine\utils\Config;

class LangManager {

    private string $lang;
    private array $messages;

    public function __construct(string $langDir, string $defaultLang = "en") {
        $this->lang = $defaultLang;
        $langPath = "$langDir/$defaultLang.yml";

        if (!file_exists($langPath)) {
            throw new \Exception("Default language file $langPath not found.");
        }

        $this->messages = (new Config($langPath, Config::YAML))->getAll();
    }

    public function setLanguage(string $langDir, string $language): void {
        $langPath = "$langDir/$language.yml";
        if (file_exists($langPath)) {
            $this->messages = (new Config($langPath, Config::YAML))->getAll();
            $this->lang = $language;
        }
    }

    public function translate(string $key, array $params = []): string {
        $message = $this->messages[$key] ?? $key;

        foreach ($params as $param => $value) {
            $message = str_replace("{" . $param . "}", $value, $message);
        }

        return $message;
    }

    public function getLanguage(): string {
        return $this->lang;
    }
}
