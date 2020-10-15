<?php

declare(strict_types=1);

namespace spec\FriendsOfSylius\SyliusImportExportPlugin\Processor;

use Doctrine\ORM\EntityManagerInterface;
use FriendsOfSylius\SyliusImportExportPlugin\Processor\MetadataValidatorInterface;
use FriendsOfSylius\SyliusImportExportPlugin\Processor\PaymentMethodProcessor;
use FriendsOfSylius\SyliusImportExportPlugin\Processor\ResourceProcessorInterface;
use Payum\Core\Model\GatewayConfigInterface;
use PhpSpec\ObjectBehavior;
use Sylius\Component\Core\Factory\PaymentMethodFactoryInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

class PaymentMethodProcessorSpec extends ObjectBehavior
{
    function let(
        PaymentMethodFactoryInterface $factory,
        RepositoryInterface $repository,
        MetadataValidatorInterface $metadataValidator,
        EntityManagerInterface $entityManager
    ) {
        $this->beConstructedWith($factory, $repository, $metadataValidator, $entityManager, []);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(PaymentMethodProcessor::class);
    }

    function it_implements_the_resource_processor_interface()
    {
        $this->shouldImplement(ResourceProcessorInterface::class);
    }

    function it_can_process_an_array_of_payment_method_data(
        PaymentMethodFactoryInterface $factory,
        PaymentMethodInterface $paymentMethod,
        GatewayConfigInterface $gatewayConfig,
        MetadataValidatorInterface $metadataValidator,
        RepositoryInterface $repository,
        EntityManagerInterface $entityManager
    ) {
        $headerKeys = ['Code', 'Name', 'Instructions', 'Gateway'];
        $dataset = ['Code' => 'OFFLINE', 'Name' => 'Offline', 'Instructions' => 'Offline payment method instructions.', 'Gateway' => 'offline'];

        $this->beConstructedWith($factory, $repository, $metadataValidator, $entityManager, $headerKeys);

        $metadataValidator->validateHeaders($headerKeys, $dataset)->shouldBeCalled();

        $repository->findOneBy(['code' => 'OFFLINE'])->willReturn(null);
        $factory->createWithGateway('offline')->willReturn($paymentMethod);
        $entityManager->persist($paymentMethod)->shouldBeCalledTimes(1);

        $gatewayConfig->setGatewayName('Offline')->shouldBeCalled();

        $paymentMethod->setCode('OFFLINE')->shouldBeCalled();
        $paymentMethod->setName('Offline')->shouldBeCalled();
        $paymentMethod->setInstructions('Offline payment method instructions.')->shouldBeCalled();
        $paymentMethod->getGatewayConfig()->willReturn($gatewayConfig);
        $paymentMethod->setGatewayConfig($gatewayConfig)->shouldBeCalled();

        $this->process($dataset);
    }
}
