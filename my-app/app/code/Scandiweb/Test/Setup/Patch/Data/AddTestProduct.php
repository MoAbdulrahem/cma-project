<?php
declare(strict_types=1);

namespace Scandiweb\Test\Setup\Patch\Data;

use Exception;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\App\State;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Validation\ValidationException;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\Store\Model\StoreManagerInterface;

class AddTestProduct implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    protected ModuleDataSetupInterface $setup;

    /**
     * @var ProductInterfaceFactory
     */
    protected ProductInterfaceFactory $productInterfaceFactory;

    /**
     * @var ProductRepositoryInterface
     */
    protected ProductRepositoryInterface $productRepository;

    /**
     * @var State
     */
    protected State $appState;

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var SourceItemInterfaceFactory
     */
    protected SourceItemInterfaceFactory $sourceItemFactory;

    /**
     * @var SourceItemsSaveInterface
     */
    protected SourceItemsSaveInterface $sourceItemsSaveInterface;

    /**
     * @var EavSetup
     */
    protected EavSetup $eavSetup;

    /**
     * @var array
     */
    protected array $sourceItems = [];

    /**
     * @var CategoryLinkManagementInterface
     */
    protected CategoryLinkManagementInterface $categoryLink;

    /**
     * Migration patch constructor.
     *
     * @param ModuleDataSetupInterface $setup
     * @param ProductInterfaceFactory $productInterfaceFactory
     * @param ProductRepositoryInterface $productRepository
     * @param SourceItemInterfaceFactory $sourceItemFactory
     * @param SourceItemsSaveInterface $sourceItemsSaveInterface
     * @param State $appState
     * @param StoreManagerInterface $storeManager
     * @param EavSetup $eavSetup
     * @param CategoryLinkManagementInterface $categoryLink
     */
    public function __construct(ModuleDataSetupInterface $setup, ProductInterfaceFactory $productInterfaceFactory, ProductRepositoryInterface $productRepository, State $appState, StoreManagerInterface $storeManager, EavSetup $eavSetup, SourceItemInterfaceFactory $sourceItemFactory, SourceItemsSaveInterface $sourceItemsSaveInterface, CategoryLinkManagementInterface $categoryLink)
    {
        $this->appState = $appState;
        $this->productInterfaceFactory = $productInterfaceFactory;
        $this->productRepository = $productRepository;
        $this->setup = $setup;
        $this->eavSetup = $eavSetup;
        $this->storeManager = $storeManager;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->sourceItemsSaveInterface = $sourceItemsSaveInterface;
        $this->categoryLink = $categoryLink;
    }

    /**
     * {@inheritDoc}
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Add new product
     * @return void
     * @throws Exception
     */
    public function apply(): void
    {
        $this->appState->emulateAreaCode('adminhtml', [$this, 'execute']);
    }

    /**
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws ValidationException
     */
    public function execute(): void
    {
        $product = $this->productInterfaceFactory->create();

        if ($product->getIdBySku('test-product2')) {
            return;
        }

        $attributeSetId = $this->eavSetup->getAttributeSetId(Product::ENTITY, 'Default');
        $websiteIds = [$this->storeManager->getStore()->getWebsiteId()];
        $product->setTypeId(Type::TYPE_SIMPLE)
            ->setWebsiteIds($websiteIds)
            ->setAttributeSetId($attributeSetId)
            ->setName('Test Product2')
            ->setUrlKey('testproduct2')
            ->setSku('test-product2')
            ->setPrice(9.99)
            ->setVisibility(Visibility::VISIBILITY_BOTH)
            ->setStatus(Status::STATUS_ENABLED)
            ->setStockData(['use_config_manage_stock' => 1, 'is_qty_decimal' => 0, 'is_in_stock' => 1]);
        $product = $this->productRepository->save($product);
        $sourceItemFactory = $this->sourceItemFactory->create();
        $sourceItemFactory->setSourceCode('default');
        $sourceItemFactory->setQuantity(10);
        $sourceItemFactory->setSku($product->getSku());
        $sourceItemFactory->setStatus(SourceItemInterface::STATUS_IN_STOCK);
        $this->sourceItems[] = $sourceItemFactory;
        $this->sourceItemsSaveInterface->execute($this->sourceItems);
        $this->categoryLink->assignProductToCategories($product->getSku(), [2]);
    }

    /**
     * {@inheritDoc}
     */
    public function getAliases()
    {
        return [];
    }
}
