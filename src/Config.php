<?php
/**
 * This file is part of the phpMDocs.
 * Copyright (c) Róbert Kelčák (https://kelcak.com/)
 */

declare(strict_types=1);

namespace RobiNN\Pmd;

class Config {
    /**
     * @var ?array<string, mixed>
     */
    private static ?array $config = null;

    /**
     * Override the runtime configuration (or reset it with null), useful in tests.
     *
     * @param ?array<string, mixed> $config
     */
    public static function set(?array $config): void {
        self::$config = $config;
    }

    /**
     * @template Default
     *
     * @param Default $default
     *
     * @return mixed|Default
     */
    public static function get(string $key, $default = null) {
        if (self::$config === null) {
            if (!is_file(__DIR__.'/../config.php')) {
                exit('The configuration file is missing.');
            }

            $config = (array) require __DIR__.'/../config.php';
            $config['site_url'] .= $config['site_path'];

            self::$config = $config;
        }

        return self::$config[$key] ?? $default;
    }
}
