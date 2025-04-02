<?php

namespace DIMA\CustomCommands\Console;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\StateException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanSupplierCommand extends Command
{
    /**
     * @var State
     */
    protected $appState;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;


    public function __construct(
        State $appState,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->appState = $appState;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        parent::__construct();
    }

    /**
     * Configures the command by setting its name, description, required arguments, and optional flags.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('clean:supplier')
            ->setDescription('Cleans up data for a specific supplier')
            ->addArgument(
                'supplierName', // Argument name
                InputArgument::REQUIRED, // Set as REQUIRED or OPTIONAL
                'The name of the supplier to clean.' // Description
            )->addArgument(
                'method', // Argument name
                InputArgument::REQUIRED, // Set as REQUIRED or OPTIONAL
                'disable, storeview, delete' // Description
            )->addArgument(
                'date', // Argument name
                InputArgument::OPTIONAL, // Set as REQUIRED or OPTIONAL
                'set date to remove older items' // Description
            )->addOption(
                'dry-run', // Option name
                null, // Shortcut (e.g., `-d`), null means no shortcut
                InputOption::VALUE_NONE, // VALUE_NONE means a flag (no value required)
                'Run the process without making changes'
            );

        parent::configure();
    }

    /**
     * Executes the command for cleaning supplier data based on specified arguments and options.
     *
     * @param InputInterface $input The command input interface containing arguments and options.
     * @param OutputInterface $output The command output interface used for writing messages.
     * @return int Returns Command::SUCCESS on success or Command::FAILURE on failure.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Get the argument value
        $supplierName = $input->getArgument('supplierName');
        $method = $input->getArgument('method');
        $date = $input->getArgument('date');

        if (!$supplierName) {
            $output->writeln('<error>Supplier name is required.</error>');
            return Command::FAILURE;
        }

        if (!$method) {
            $output->writeln('<error>Method name is required.</error>');
            return Command::FAILURE;
        }

        if (!$input->getArgument('date')) {
            $date = (new \DateTime())->format('Y-m-d');
        }

        try { //Run execution code for
            $this->appState->setAreaCode(Area::AREA_GLOBAL);

            $output->writeln('<info>Cleaning data for supplier: ' . $supplierName . ' with method: '.$method.'</info>');
            $output->writeln('<info>------------------------------------------------</info>');

            if ($input->getOption('dry-run')) {
                $output->writeln('<error>This is a dry run. No changes will be made.</error>');
                $output->writeln('<info>------------------------------------------------</info>');
            }

            // Retrieve products by attribute filter
            $products = $this->getProductsByAttributes($supplierName, $date);

            //if no products found stop command
            if (empty($products)) {
                $output->writeln('<info>No products found for the given filters.</info>');
                return Command::SUCCESS;
            }

            //Continue if products found
            $output->writeln('<info>Found ' . count($products) . ' products to process.</info>');
            $output->writeln('<info>------------------------------------------------</info>');

            // Logic to process products (e.g., delete or mark them)
            foreach ($products as $product) {
                match( $method ) {
                    'disable' => $this->disableProduct($product, $input,  $output, $input->getOption('dry-run')),
                    'storeview' => $this->removeFromStore($product, $input,  $output, $input->getOption('dry-run')),
                    'delete' => $this->deleteProduct($product, $input,  $output, $input->getOption('dry-run')),
                };
            }

            $output->writeln('<info>------------------------------------------------</info>');
            $output->writeln('<info>Successfully cleaned data for supplier: ' . $supplierName . '</info>');
        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * @throws StateException
     * @throws CouldNotSaveException
     * @throws InputException
     */
    private function removeFromStore($product, InputInterface $input, OutputInterface $output, $dont_execute): bool|int
    {
        try {
            if (!$dont_execute) {
                $product->setWebsiteIds([]); // Unassigns the product from all websites
                $this->productRepository->save($product);
            }

            $output->writeln('<info>Removed product: ' . $product->getSku() . ' from stores</info>');
        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        return true;
    }

    /**
     * @param $product
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    private function deleteProduct($product, InputInterface $input, OutputInterface $output, $dont_execute): bool|int
    {
        try {
            $output->writeln('<info>Deleting product: ' . $product->getSku() . '</info>');
            if (!$dont_execute) {
                $this->productRepository->delete($product);
            }
        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        return true;
    }

    /**
     * @throws StateException
     * @throws CouldNotSaveException
     * @throws InputException
     */
    private function disableProduct($product, InputInterface $input, OutputInterface $output, $dont_execute): bool|int
    {
        try {
            if (!$dont_execute) {
                $product->setStoreId(0); // 0 for global scope
                $product->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED);
                $this->productRepository->save($product);
            }

            $output->writeln('<info>Disabled product: ' . $product->getSku() . '</info>');


        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        return true;
    }

    /**
     * Get products by 'leverancier' and 'laatste_importdatum' attributes
     *
     * @param string $supplierName
     * @param $date
     * @return ProductInterface[]
     */
    private function getProductsByAttributes(string $supplierName, $date): array
    {
        // Calculate today's date
        $today = (new \DateTime())->format('Y-m-d');

        // Build search criteria with filters
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('leverancier', $supplierName, 'eq') // Filter by 'leverancier'
            ->addFilter('last_import_date_automation', $date, 'lt') // Filter by 'last_import_date_automation', older than today
            ->create();

        // Fetch products based on the search criteria
        $productList = $this->productRepository->getList($searchCriteria);

        return $productList->getItems(); // Return an array of product objects
    }
}
