<?php
declare(strict_types=1);

namespace Mike\Order\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Status extends Command
{

    const NAME_ARGUMENT = "name";
    const NAME_OPTION = "option";
    const ORDER_IDS = "ids";

    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    private $objectManager;

    /**
     * @var \Magento\Framework\App\State
     */
    private $state;

    public function init()
    {
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->state = $this->objectManager->get(\Magento\Framework\App\State::class);

//        $this->storeManager = $this->objectManager->get(\Magento\Store\Model\StoreManagerInterface::class);
//        $this->store = $this->objectManager->get(\Magento\Store\Api\Data\StoreInterface::class);
        $this->logger = $this->objectManager->get(\Psr\Log\LoggerInterface::class);

        # Should use emulator instead below

//        # Put this code in execute function
//        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_FRONTEND); // Required

//        # Set locale
//        $localeInterface = $this->objectManager->create(\Magento\Framework\Locale\ResolverInterface::class);
//        $localeInterface->setDefaultLocale('sv_SE');
//        $localeInterface->setLocale('sv_SE');

//        # Use when have to load media
//        $designLoader = $this->objectManager->get(\Magento\Framework\View\DesignLoader::class);
//        $designLoader->load();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface  $input,
        OutputInterface $output
    )
    {
        $this->init();

        $name = $input->getArgument(self::NAME_ARGUMENT);
        $option = $input->getOption(self::ORDER_IDS);

        try {
            switch ($name) {
                case 'complete':
                    #php bin/magento mike_order:status complete -i "1464, 1468"
                    $output->writeln("Change order status to complete:");
                    $orderIds = $input->getOption(self::ORDER_IDS);

                    $appEmulation = $this->objectManager->get(\Magento\Store\Model\App\Emulation::class);
                    $appEmulation->startEnvironmentEmulation(0, \Magento\Framework\App\Area::AREA_ADMINHTML, true);

                    if (!empty($orderIds)) {
//                        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
                        $orderIds = array_map('trim', explode(',', $orderIds));

                        foreach ($orderIds as $orderId) {
                            /** @var \Magento\Sales\Model\Order $order */
                            $order = $this->objectManager->get(\Magento\Sales\Model\OrderFactory::class)->create()->load($orderId);
                            if (!$order->getId()) {
                                $output->writeln("Order ID: {$order->getId()} doesn't exist.");
                            }
                            $orderState = \Magento\Sales\Model\Order::STATE_COMPLETE;
                            $order->setState($orderState)->setStatus(\Magento\Sales\Model\Order::STATE_COMPLETE);
                            $order->save();
                            $output->writeln($order->getIncrementId());
                        }
                    }
                    $output->writeln("Done!");

                    $appEmulation->stopEnvironmentEmulation();
                    break;
                case 'other':
                    #php bin/magento mike_order:status other -i "3"
                    $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
                    $eventManager = $this->objectManager->get(\Magento\Framework\Event\ManagerInterface::class);
                    break;
            }
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName("mike_order:status");
        $this->setDescription("To manage order status");
        $this->setDefinition([
            new InputArgument(self::NAME_ARGUMENT, InputArgument::OPTIONAL, "Name"),
            new InputOption(self::ORDER_IDS, "-i", InputOption::VALUE_OPTIONAL, "Order ids to change status"),
        ]);
        parent::configure();
    }
}
