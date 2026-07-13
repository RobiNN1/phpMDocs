<?php
/**
 * This file is part of the phpMDocs.
 * Copyright (c) Róbert Kelčák (https://kelcak.com/)
 */

declare(strict_types=1);

namespace RobiNN\Pmd;

use Exception;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class Template {
    private static ?Environment $twig = null;

    /**
     * Render template.
     *
     * @param array<string, mixed> $data
     */
    public static function render(string $tpl, array $data = []): string {
        try {
            return self::twig()->render($tpl.'.twig', $data);
        } catch (Exception $e) {
            return $e->getMessage().' in '.$e->getFile().' at line: '.$e->getLine();
        }
    }

    private static function twig(): Environment {
        if (self::$twig instanceof Environment) {
            return self::$twig;
        }

        $twig = new Environment(new FilesystemLoader(__DIR__.'/../templates'), [
            'cache' => __DIR__.'/../cache/twig',
            'debug' => Config::get('twig_debug'),
        ]);

        if (Config::get('twig_debug')) {
            $twig->addExtension(new DebugExtension());
        }

        $twig->addFunction(new TwigFunction('config', Config::get(...)));
        $twig->addFunction(new TwigFunction('path', Helpers::path(...)));
        $twig->addFunction(new TwigFunction('asset', Helpers::asset(...)));
        $twig->addFunction(new TwigFunction('svg', Helpers::svg(...), ['is_safe' => ['html']]));
        $twig->addFunction(new TwigFunction('is_active', Helpers::isActive(...)));

        return self::$twig = $twig;
    }
}
