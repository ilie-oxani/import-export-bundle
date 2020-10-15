<?php

declare(strict_types=1);

namespace LWC\ImportExportBundle\DependencyInjection\Compiler;

use LWC\ImportExportBundle\Importer\ImporterRegistry;
use Sylius\Bundle\UiBundle\Block\BlockEventListener;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class RegisterImporterPass implements CompilerPassInterface
{
    private $eventHookNames = [
        'taxonomy' => 'app.block_event_listener.admin.taxon.create.after_content',
    ];

    private $eventNames = [
        'taxonomy' => 'sonata.block.event.sylius.admin.taxon.create.after_content',
    ];

    private $templateNames = [
        'taxonomy' => '@FOSImportExportBundle/Taxonomy/import.html.twig',
    ];

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $serviceId = 'sylius.importers_registry';
        if ($container->has($serviceId) == false) {
            return;
        }

        $importersRegistry = $container->findDefinition($serviceId);

        foreach ($container->findTaggedServiceIds('sylius.importer') as $id => $attributes) {
            if (!isset($attributes[0]['type'])) {
                throw new \InvalidArgumentException('Tagged importer ' . $id . ' needs to have a type');
            }
            if (!isset($attributes[0]['format'])) {
                throw new \InvalidArgumentException('Tagged importer ' . $id . ' needs to have a format');
            }

            $type = $attributes[0]['type'];
            $format = $attributes[0]['format'];
            $name = ImporterRegistry::buildServiceName($type, $format);

            $importersRegistry->addMethodCall('register', [$name, new Reference($id)]);

            if ($container->getParameter('sylius.importer.web_ui')) {
                $this->registerImportFormBlockEvent($container, $type);
            }
        }
    }

    private function registerImportFormBlockEvent(ContainerBuilder $container, string $type): void
    {
        $eventHookName = $this->buildEventHookName($type);

        if ($container->has($eventHookName)) {
            return;
        }

        $eventName = $this->buildEventName($type);
        $templateName = $this->buildTemplateName($type);
        $container
            ->register(
                $eventHookName,
                BlockEventListener::class
            )
            ->setAutowired(false)
            ->addArgument($templateName)
            ->addTag(
                'kernel.event_listener',
                [
                    'event' => $eventName,
                    'method' => 'onBlockEvent',
                ]
            )
        ;
    }

    private function buildEventHookName(string $type): string
    {
        if (isset($this->eventHookNames[$type])) {
            return $this->eventHookNames[$type];
        }

        return sprintf('app.block_event_listener.admin.crud.after_content_%s.import', $type);
    }

    private function buildEventName(string $type): string
    {
        if (isset($this->eventNames[$type])) {
            return $this->eventNames[$type];
        }

        $domain = 'sylius';
        // backward compatibility with the old configuration
        if (count(\explode('.', $type)) === 2) {
            [$domain, $type] = \explode('.', $type);
        }

        return \sprintf(
            'sonata.block.event.%s.admin.%s.index.after_content',
            $domain,
            $type
        );
    }

    private function buildTemplateName(string $type): string
    {
        if (isset($this->templateNames[$type])) {
            return $this->templateNames[$type];
        }

        return '@FOSImportExportBundle/Crud/import.html.twig';
    }
}
