<?php

namespace AtPay\CustomPayment\Model;
use Psr\Log\LoggerInterface;

class Status implements \AtPay\CustomPayment\Api\StatusInterface
{
    /** @var \Magento\Framework\View\Result\PageFactory */
    protected $resultPageFactory;
    protected $orderRepository;
    protected $_resource;
    protected $logger;

    public function __construct(
        LoggerInterface $logger,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,

        array $data = []
    ) {
        $this->logger = $logger;
        $this->_resource = $resource;
        $this->resultPageFactory = $resultPageFactory;
        $this->orderRepository = $orderRepository;
    }
    /**
     * @inheritdoc
     */
    public function setStatus($orderid, $status)
    {
        
        $response = ['success' => false];
        try {
            $username = $_SERVER['PHP_AUTH_USER'];
            $password = $_SERVER['PHP_AUTH_PW'];
            if (!isset($_SERVER['PHP_AUTH_USER'])) {
                echo 'you need proper credentials';
                exit();
            } elseif ($_SERVER['PHP_AUTH_USER'] && $_SERVER['PHP_AUTH_PW']) {
                
                $tableName = $this->_resource->getTableName('core_config_data');

                //Initiate Connection

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

                if ($username !== $merchantId || $password !== $secretKey) {
                    echo 'wrong credentials';
                    exit();
                } else {
                    $order = $this->orderRepository->get($orderid);
                    $order->setData('status', $status);
                    $order->save();
                    $object['order_info'] = $order->getData();
                    $resul = [];

                    foreach ($order->getAllItems() as $item) {
                    
                        $resul = $item->getData();
                    }
                    $object['order_info']['items'] = $resul;
                    $object['order_info']['status'] = $status;
                }

                $res['data']['code'] = 200;
                $res['data']['message'] = 'success';
                $res['data']['data'] = $object['order_info'];

                $response = $res;
                $data['code'] = 200;
                $data['message'] = 'success';
                $data['data'] = $productData;

                $response = $data;
            } else {
                echo 'you need proper credentials';
                exit();
            }
        } catch (\Exception $e) {

        }
        $returnArray = $response;
        return $returnArray;
    }
}
