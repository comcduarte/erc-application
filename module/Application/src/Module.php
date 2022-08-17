<?php

declare(strict_types=1);

namespace Application;

class Module
{
    const TITLE = "Electronic Records Clerk";
    const SHORT_TITLE = "ERC";
    const VERSION = "v0.0.1";
    
    public function getConfig(): array
    {
        /** @var array $config */
        $config = include __DIR__ . '/../config/module.config.php';
        return $config;
    }
}
