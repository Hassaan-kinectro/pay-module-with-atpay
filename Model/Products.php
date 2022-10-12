<?php

namespace AtPay\CustomPayment\Model;

use AtPay\CustomPayment\Api\ProductsInterface;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Api\Data\ProductExtension;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Gallery\MimeTypeExtensionMap;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Eav\Model\Entity\Attribute\Exception as AttributeException;
use Magento\Framework\Api\Data\ImageContentInterfaceFactory;
use Magento\Framework\Api\ImageContentValidatorInterface;
use Magento\Framework\Api\ImageProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\DB\Adapter\ConnectionException;
use Magento\Framework\DB\Adapter\DeadlockException;
use Magento\Framework\DB\Adapter\LockWaitException;
use Magento\Framework\EntityManager\Operation\Read\ReadExtensions;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\TemporaryState\CouldNotSaveException as TemporaryCouldNotSaveException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Catalog\Model\Product;

/**
 * Product Repository.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class Products implements ProductsInterface
{
    protected $_resource;
    protected $logger;
    /**
     * @var \Magento\Catalog\Api\ProductCustomOptionRepositoryInterface
     */
    protected $optionRepository;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var Product[]
     */
    protected $instances = [];

    /**
     * @var Product[]
     */
    protected $instancesById = [];

    /**
     * @var \Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper
     */
    protected $initializationHelper;

    /**
     * @var \Magento\Catalog\Api\Data\ProductSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product
     */
    protected $resourceModel;

    /**
     * @var Product\Initialization\Helper\ProductLinks
     */
    protected $linkInitializer;

    /**
     * @var Product\LinkTypeProvider
     */
    protected $linkTypeProvider;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Catalog\Api\ProductAttributeRepositoryInterface
     */
    protected $attributeRepository;

    /**
     * @var \Magento\Catalog\Api\ProductAttributeRepositoryInterface
     */
    protected $metadataService;

    /**
     * @var \Magento\Framework\Api\ExtensibleDataObjectConverter
     */
    protected $extensibleDataObjectConverter;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $fileSystem;

    /**
     * @deprecated
     * @see \Magento\Catalog\Model\MediaGalleryProcessor
     * @var ImageContentInterfaceFactory
     */
    protected $contentFactory;

    /**
     * @deprecated
     * @see \Magento\Catalog\Model\MediaGalleryProcessor
     * @var ImageProcessorInterface
     */
    protected $imageProcessor;

    /**
     * @var \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface
     */
    protected $extensionAttributesJoinProcessor;

    /**
     * @var ProductRepository\MediaGalleryProcessor
     */
    protected $mediaGalleryProcessor;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var int
     */
    private $cacheLimit = 0;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;

    /**
     * @var ReadExtensions
     */
    private $readExtensions;
    /**
     * @var \Magento\Framework\Locale\CurrencyInterface
     */

    /**
     * ProductRepository constructor.
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper $initializationHelper
     * @param \Magento\Catalog\Api\Data\ProductSearchResultsInterfaceFactory $searchResultsFactory
     * @param ResourceModel\Product\CollectionFactory $collectionFactory
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Catalog\Api\ProductAttributeRepositoryInterface $attributeRepository
     * @param ResourceModel\Product $resourceModel
     * @param Product\Initialization\Helper\ProductLinks $linkInitializer
     * @param Product\LinkTypeProvider $linkTypeProvider
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Catalog\Api\ProductAttributeRepositoryInterface $metadataServiceInterface
     * @param \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter
     * @param Product\Option\Converter $optionConverter
     * @param \Magento\Framework\Filesystem $fileSystem
     * @param ImageContentValidatorInterface $contentValidator
     * @param ImageContentInterfaceFactory $contentFactory
     * @param MimeTypeExtensionMap $mimeTypeExtensionMap
     * @param ImageProcessorInterface $imageProcessor
     * @param \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param CollectionProcessorInterface $collectionProcessor [optional]
     * @param \Magento\Framework\Serialize\Serializer\Json|null $serializer
     * @param int $cacheLimit [optional]
     * @param ReadExtensions|null $readExtensions
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        LoggerInterface $logger,

        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Framework\Webapi\Rest\Response $response,
        \Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper $initializationHelper,
        \Magento\Catalog\Api\Data\ProductSearchResultsInterfaceFactory $searchResultsFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Catalog\Api\ProductAttributeRepositoryInterface $attributeRepository,
        \Magento\Catalog\Model\ResourceModel\Product $resourceModel,
        \Magento\Catalog\Model\Product\Initialization\Helper\ProductLinks $linkInitializer,
        \Magento\Catalog\Model\Product\LinkTypeProvider $linkTypeProvider,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Catalog\Api\ProductAttributeRepositoryInterface $metadataServiceInterface,
        \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        \Magento\Catalog\Model\Product\Option\Converter $optionConverter,
        \Magento\Framework\Filesystem $fileSystem,
        ImageContentValidatorInterface $contentValidator,
        ImageContentInterfaceFactory $contentFactory,
        MimeTypeExtensionMap $mimeTypeExtensionMap,
        ImageProcessorInterface $imageProcessor,
        \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface $extensionAttributesJoinProcessor,
        CollectionProcessorInterface $collectionProcessor = null,
        \Magento\Framework\Serialize\Serializer\Json $serializer = null,
        $cacheLimit = 1000,
        ReadExtensions $readExtensions = null,

        array $data = []
    ) {
        $this->logger = $logger;
        $this->_resource = $resource;
        $this->_response = $response;
        $this->productFactory = $productFactory;
        $this->collectionFactory = $collectionFactory;
        $this->initializationHelper = $initializationHelper;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->resourceModel = $resourceModel;
        $this->linkInitializer = $linkInitializer;
        $this->linkTypeProvider = $linkTypeProvider;
        $this->_storeManager = $storeManager;
        $this->attributeRepository = $attributeRepository;
        $this->filterBuilder = $filterBuilder;
        $this->metadataService = $metadataServiceInterface;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
        $this->fileSystem = $fileSystem;
        $this->contentFactory = $contentFactory;
        $this->imageProcessor = $imageProcessor;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->collectionProcessor =
            $collectionProcessor ?: $this->getCollectionProcessor();
        $this->serializer =
            $serializer ?:
            \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Framework\Serialize\Serializer\Json::class
            );
        $this->cacheLimit = (int) $cacheLimit;
        $this->readExtensions =
            $readExtensions ?:
            \Magento\Framework\App\ObjectManager::getInstance()->get(
                ReadExtensions::class
            );
    }

    /**
     * @inheritdoc
     */
    public function get(
        $sku,
        $editMode = false,
        $storeId = null,
        $forceReload = false
    ) {
        $cacheKey = $this->getCacheKey([$editMode, $storeId]);
        $cachedProduct = $this->getProductFromLocalCache($sku, $cacheKey);
        if ($cachedProduct === null || $forceReload) {
            $productId = $this->resourceModel->getIdBySku($sku);
            if (!$productId) {
                throw new NoSuchEntityException(
                    __('Requested product doesn\'t exist')
                );
            }

            $product = $this->getById(
                $productId,
                $editMode,
                $storeId,
                $forceReload
            );

            $this->cacheProduct($cacheKey, $product);
            $cachedProduct = $product;
        }

        return $cachedProduct;
    }

    /**
     * @inheritdoc
     */
    public function getById(
        $productId,
        $editMode = false,
        $storeId = null,
        $forceReload = false
    ) {
        $cacheKey = $this->getCacheKey([$editMode, $storeId]);
        if (
            !isset($this->instancesById[$productId][$cacheKey]) ||
            $forceReload
        ) {
            $product = $this->productFactory->create();
            if ($editMode) {
                $product->setData('_edit_mode', true);
            }
            if ($storeId !== null) {
                $product->setData('store_id', $storeId);
            }
            $product->load($productId);
            if (!$product->getId()) {
                throw new NoSuchEntityException(
                    __('Requested product doesn\'t exist')
                );
            }
            $this->cacheProduct($cacheKey, $product);
        }

        return $this->instancesById[$productId][$cacheKey];
    }

    /**
     * Get key for cache
     *
     * @param array $data
     * @return string
     */
    protected function getCacheKey($data)
    {
        $serializeData = [];
        foreach ($data as $key => $value) {
            if (is_object($value)) {
                $serializeData[$key] = $value->getId();
            } else {
                $serializeData[$key] = $value;
            }
        }
        $serializeData = $this->serializer->serialize($serializeData);

        return sha1($serializeData);
    }

    /**
     * Add product to internal cache and truncate cache if it has more than cacheLimit elements.
     *
     * @param string $cacheKey
     * @param ProductInterface $product
     * @return void
     */
    private function cacheProduct($cacheKey, ProductInterface $product)
    {
        $_imageHelper = \Magento\Framework\App\ObjectManager::getInstance()->get(
            'Magento\Catalog\Helper\Image'
        );
        $image = $_imageHelper
            ->init($product, 'thumbnail', ['type' => 'thumbnail'])
            ->keepAspectRatio(true)
            ->resize('600', '600')
            ->getUrl();
        $product->setCustomAttribute('thumbnail', $image);

        $this->instancesById[$product->getId()][$cacheKey] = $product;

        if ($this->cacheLimit && count($this->instances) > $this->cacheLimit) {
            $offset = round($this->cacheLimit / -2);
            $this->instancesById = array_slice(
                $this->instancesById,
                $offset,
                null,
                true
            );
            $this->instances = array_slice(
                $this->instances,
                $offset,
                null,
                true
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    ) {
        try {
            /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
            $username = $_SERVER['PHP_AUTH_USER'];
            $password = $_SERVER['PHP_AUTH_PW'];
            if (!isset($_SERVER['PHP_AUTH_USER'])) {
                echo 'you need proper credentials';
                exit();
            } elseif ($_SERVER['PHP_AUTH_USER'] && $_SERVER['PHP_AUTH_PW']) {
                $tableName = $this->_resource->getTableName('core_config_data');
                $connection = $this->_resource->getConnection();
                $path = 'payment/atpay/secret_key';
                $select = $connection
                    ->select()
                    ->from(['c' => $tableName], ['value'])
                    ->where('c.path = :path');
                $bind = ['path' => $path];

                $secretKey = $connection->fetchOne($select, $bind);

                $path = 'payment/atpay/merchant_id';
                $select = $connection
                    ->select()
                    ->from(['c' => $tableName], ['value'])
                    ->where('c.path = :path');
                $bind = ['path' => $path];

                $merchantId = $connection->fetchOne($select, $bind);

                $resp = null;

                if ($username !== $merchantId || $password !== $secretKey) {
                    echo 'wrong credentials';
                    exit();
                } else {
                    $collection = $this->collectionFactory->create();
                    $this->extensionAttributesJoinProcessor->process(
                        $collection
                    );

                    $collection->addAttributeToSelect('*');
                    $collection->joinAttribute(
                        'status',
                        'catalog_product/status',
                        'entity_id',
                        null,
                        'inner'
                    );
                    $collection->joinAttribute(
                        'visibility',
                        'catalog_product/visibility',
                        'entity_id',
                        null,
                        'inner'
                    );
                    $collection->addMinimalPrice(); //adding minmalprice you can change to addFinalPrice amjad fluxstore
                    $this->collectionProcessor->process(
                        $searchCriteria,
                        $collection
                    );
                    $collection->load();
                    $collection->addCategoryIds();
                    $collection->addAttributeToSelect('*');
                    $this->addExtensionAttributes($collection);
                    $searchResult = $this->searchResultsFactory->create();
                    $searchResult->setSearchCriteria($searchCriteria);
                    $searchResult->setItems($collection->getItems());
                    $searchResult->setTotalCount($collection->getSize());
                    $resp = $collection->getItems();
                }
                $object = [];
                foreach ($collection->getItems() as $product) {
                    $this->cacheProduct(
                        $this->getCacheKey([false, $product->getStoreId()]),
                        $product
                    );
                    $object[] = $product->getData();
                }
                $obb['storeInfo']['currencySymbol'] = $this->_storeManager
                    ->getStore()
                    ->getCurrentCurrency()
                    ->getCurrencySymbol();
                $obb['storeInfo']['currencyCode'] = $this->_storeManager
                    ->getStore()
                    ->getCurrentCurrency()
                    ->getCode();

                $obb['code'] = 200;
                $obb['message'] = 'success';
                $obb['data'] = $object;

                $response['res'] = $obb;
                return $response;
            } else {
                echo 'you need proper credentials';
                exit();
            }
        } catch (\Throwable $th) {
            echo 'Internal Server Error';
            exit();
        }

        return $response;
    }

    /**
     * Add extension attributes to loaded items.
     *
     * @param Collection $collection
     * @return Collection
     */
    private function addExtensionAttributes(Collection $collection): Collection
    {
        foreach ($collection->getItems() as $item) {
            $this->readExtensions->execute($item);
        }
        return $collection;
    }

    /**
     * Helper function that adds a FilterGroup to the collection.
     *
     * @deprecated 101.1.0
     * @param \Magento\Framework\Api\Search\FilterGroup $filterGroup
     * @param Collection $collection
     * @return void
     */
    protected function addFilterGroupToCollection(
        \Magento\Framework\Api\Search\FilterGroup $filterGroup,
        Collection $collection
    ) {
        $fields = [];
        $categoryFilter = [];
        foreach ($filterGroup->getFilters() as $filter) {
            $conditionType = $filter->getConditionType() ?: 'eq';

            if ($filter->getField() == 'category_id') {
                $categoryFilter[$conditionType][] = $filter->getValue();
                continue;
            }
            $fields[] = [
                'attribute' => $filter->getField(),
                $conditionType => $filter->getValue(),
            ];
        }

        if ($categoryFilter) {
            $collection->addCategoriesFilter($categoryFilter);
        }

        if ($fields) {
            $collection->addFieldToFilter($fields);
        }
    }

    /**
     * Retrieve collection processor
     *
     * @deprecated 101.1.0
     * @return CollectionProcessorInterface
     */
    private function getCollectionProcessor()
    {
        if (!$this->collectionProcessor) {
            $this->collectionProcessor = \Magento\Framework\App\ObjectManager::getInstance()->get(
                'Magento\Catalog\Model\Api\SearchCriteria\ProductCollectionProcessor'
            );
        }

        return $this->collectionProcessor;
    }

    /**
     * Converts SKU to lower case and trims.
     *
     * @param string $sku
     * @return string
     */
    private function prepareSku(string $sku): string
    {
        return mb_strtolower(trim($sku));
    }
}
