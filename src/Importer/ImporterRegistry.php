<?php

declare(strict_types=1);

namespace LWC\ImportExportBundle\Importer;

use Sylius\Component\Registry\ServiceRegistry;

class ImporterRegistry extends ServiceRegistry
{
    public static function buildServiceName(string $type, string $format): string
    {
        return sprintf('%s.%s', $type, $format);
    }
}
