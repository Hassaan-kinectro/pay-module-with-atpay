<?php

namespace AtPay\CustomPayment\Model;
use Psr\Log\LoggerInterface;

class Order implements \AtPay\CustomPayment\Api\OrderInterface
{
    /** @var \Magento\Framework\View\Result\PageFactory */
    /** @var \Magento\Framework\HTTP\Client\Curl */
    protected $_curl;
    protected $orderRepository;
    protected $_resource;
    protected $productCollection;
    protected $_productloader;


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
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollection,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Framework\View\Result\PageFactory $PageFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Webapi\Rest\Response $response,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Catalog\Model\ProductFactory $_productloader,

        array $data = []
    ) {
        $this->PageFactory = $PageFactory;
        $this->orderRepository = $orderRepository;
        $this->productCollection = $productCollection;
        $this->productRepository = $productRepository;
        $this->_productloader = $_productloader;
        $this->stockRegistry = $stockRegistry;
        $this->_response = $response;
        $this->curl = $curl;
        $this->_resource = $resource;
    }
    /**
     * @inheritdoc
     */
    public function getLoadProduct($id)
    {
        return $this->_productloader->create()->load($id);
    }
    public function getOrder($orderid)
    {
        $response = ['success' => false];
        try {
            $username = $_SERVER['PHP_AUTH_USER'];
            $password = $_SERVER['PHP_AUTH_PW'];
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

                      if ($username !== $merchantId || $password !== $secretKey)
                    {
                      echo "wrong credentials";  
                      exit(); 
                    }
                    else {
           $order = $this->orderRepository->get($orderid);
                $object['order_info'] = $order->getData();
            $object['order_info'] = $order->getData();
                $object['order_info'][
                    'payment_info'
                ] = $order->getPayment()->getData();
                $object['order_info'][
                    'shipping_info'
                ] = $order->getShippingAddress()->getData();
                $object['order_info'][
                    'billing_info'
                ] = $order->getBillingAddress()->getData();
                $object['order_info'][
                    'info'
                ] = $order
                    ->getPayment()
                    ->getAdditionalInformation('method_title');
                $resul = [];
                    foreach ($order->getAllVisibleItems() as $item) {
                    $productId = $item->getProductId();
                    $product = $this->getLoadProduct($productId);
                    $send_product = $product->getData();
                    $image_product = $this->productRepository->getById(
                        $productId
                    );
                    $productImages = $image_product->getMediaGalleryImages();

                    $mainImage = $image_product->getData('image');
                    

                    $send_product['product_images'] = [
                        'main_image' => [],
                        'extra_images' => [],
                    ];
                    if ($productImages->getSize() > 0) {
                        foreach ($productImages as $image) {
                            if ($mainImage == $image->getFile()) {
                                $send_product['product_images'][
                                    'main_image'
                                ][] = $image->getUrl();
                            } else {
                                $send_product['product_images'][
                                    'extra_images'

                                ][] = $image->getUrl();
                            }
                        }
                    }
                    if ($product->getSku()) {
                        $send_product[
                            'product_url'
                        ] = $product->getProductUrl();
                        $send_product[
                            'selected_variant'
                        ] = $item->getProductOptions();
                        $send_product['product_image'] = $product->getData(
                            'image'
                        );

                        $resul[] = $send_product;

                    }
                }
        }
           $object['order_info']['items'] = $resul;
                $res['data']['code'] = 200;
                $res['data']['message'] = 'success';
                $res['data']['data'] = $object['order_info'];
                $response = $res;
        }
        else
            {
                $res['code'] = 404;
                $res['message'] = 'not found';
               
                $response = $res;
            }
        } catch (\Exception $e) {
             $res['code'] = 500;
            $res['message'] = 'internal server';
          
            $response = $res;
        }
        $returnArray = $response;
        return $returnArray;

    }
}
