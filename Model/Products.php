<?php

namespace Pay\WithAtPay\Model;

use Psr\Log\LoggerInterface;

class Products implements \Pay\WithAtPay\Api\ProductsInterface
{
    protected $_curl;
    protected $logger;
    protected $_resource;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollection;
    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry;
    protected $selectedStore = 1;
    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;

    public function __construct(
        LoggerInterface $logger,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollection,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Framework\HTTP\Client\Curl $curl,
    ) {
        $this->logger = $logger;
        $this->productCollection = $productCollection;
        $this->productRepository = $productRepository;
        $this->stockRegistry = $stockRegistry;
        $this->curl = $curl;
        $this->_resource= $resource;
    }

    /**
     * @inheritdoc
     */

    public function getProducts()
    {
        try {
            // Your Code here
            $username = $_SERVER['PHP_AUTH_USER'];
            $password = $_SERVER['PHP_AUTH_PW'];
            // $auth = $username.':'.$password;
            // echo $auth;
            if (!isset($_SERVER['PHP_AUTH_USER']))
            {
                echo "you need proper credentials";
                exit;
            }
            else if (($_SERVER['PHP_AUTH_USER'] && ($_SERVER['PHP_AUTH_PW'])))
            {
                $tableName = $this->_resource->getTableName('core_config_data');

                //Initiate Connection

                    $connection = $this->_resource->getConnection();
                    $path = 'payment/atpay/secret_key';
                    $select = $connection->select()
                    ->from(
                            ['c' => $tableName],
                            ['value']
                        )->where(
                            "c.path = :path"
                        );
                    $bind = ['path'=>$path ];

                    $secretKey = $connection->fetchOne($select, $bind);
                 
                    $path = 'payment/atpay/merchant_id';
                    $select = $connection->select()
                    ->from(
                            ['c' => $tableName],
                            ['value']
                        )->where(
                            "c.path = :path"
                        );
                    $bind = ['path'=>$path ];

                    $merchantId = $connection->fetchOne($select, $bind);
                   
                    // $db = $merchantId."".$secretKey;
                    // echo $db;

                    if ($username !== $merchantId || $password !== $secretKey)
                    {
                      echo "wrong credentials";   
                    }
                    else {
            $productData = [];
            $productCollection = $this->productCollection->create()->getAllIds();
            foreach ($productCollection as $id) {
                $product = $this->productRepository->getById($id);
                $productImages = $product->getMediaGalleryImages();
                $mainImage = $product->getData('image');
                $images = ['main_image' => [], 'extra_images' => []];
                if ($productImages->getSize() > 0) {
                    foreach ($productImages as $image) {
                        if ($mainImage == $image->getFile()) {
                            $images['main_image'][] = $image->getUrl();
                        } else {
                            $images['extra_images'][] = $image->getUrl();
                        }
                    }
                }
                // Product data
                $productData[$id] = [
                    'Name' => $product->getName(),
                    'Url' => $product->getProductUrl(),
                    'Price' => $product->getPrice(),
                    'Sku' => $product->getSku(),
                    'Qty' => $this->getInventory($product),
                    'Stock_Status' => $this->getStockStatus($product),
                   
                    'Images' => $images,
                ];
                    }
            }
            $response = ['success' => '200', 'message' => 'Success', 'data' => $productData];
        }
        else
            {
                echo "you need proper credentials";
            }
        } catch (\Exception $e) {
            $response = ['success' => '404', 'message' => $e->getMessage(), 'data' => []];
            $this->logger->info($e->getMessage());
        }
        return $response;
    }

    /**
     * @param $productObject
     * @return float
     */
    public function getInventory($productObject)
    {
        $stockItem = $this->stockRegistry->getStockItem(
            $productObject->getId(),
            $this->selectedStore
        );
        return $stockItem->getQty();
    }

    /**
     * @param $productObject
     * @return float
     */
    public function getStockStatus($productObject)
    {
        $stockItem = $this->stockRegistry->getStockItem(
            $productObject->getId(),
            $this->selectedStore
        );
        return $stockItem->getManageStock();
    }
}