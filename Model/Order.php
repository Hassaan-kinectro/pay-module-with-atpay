<?php

    namespace Pay\WithAtPay\Model;
    use Psr\Log\LoggerInterface;

        class Order implements \Pay\WithAtPay\Api\OrderInterface
        {
            
            /** @var \Magento\Framework\View\Result\PageFactory */
            /** @var \Magento\Framework\HTTP\Client\Curl */
            protected $_curl;
            protected $PageFactory;
            protected $orderRepository;
            protected $_resource;
            
            public function __construct(
            \Magento\Framework\App\ResourceConnection $resource,
            \Magento\Framework\View\Result\PageFactory $PageFactory,
            \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
            \Magento\Framework\Webapi\Rest\Response $response,
            \Magento\Framework\HTTP\Client\Curl $curl,

            array $data = []
            ) {
           
                $this->PageFactory = $PageFactory;
                $this->orderRepository = $orderRepository;
                $this->_response = $response;
                $this->curl = $curl;
                $this->_resource= $resource;
            }         
/**
* @inheritdoc
*/              
        public function getOrder($orderid) {
            
            $response = ['success' => false];
            try {

                if( !isset($_SERVER ['PHP_AUTH_USER'])) {
                    $this->_response->setHeader('WWW-Authenticate', "private Area");
                    echo "you need proper credentials";
                    exit;
                }
                else if (($_SERVER['PHP_AUTH_USER'] == 'bill' && ($_SERVER['PHP_AUTH_PW'] == '1234'))) {
                    
                    $order = $this->orderRepository->get($orderid);
                    $object['order_info'] = $order->getData();
                    $object['payment_info'] =$order->getPayment()->getData();
                    $object['shipping_info'] =$order->getShippingAddress()->getData();
                    $object['billing_info'] =$order->getBillingAddress()->getData();
                    $object['info'] = $order->getPayment()->getAdditionalInformation('method_title',);       
            $resul=array();
            
            foreach ($order->getAllItems() as $item)
        {
            //fetch whole item information
            $resul= $item->getData();
    }
            $object['items'] = $resul;
            
            //  $response = json_decode(json_encode($object), true);
    
        $response = $object;
        }
        else {
                 $this->_response->setHeader('WWW-Authenticate', "private Area");
                    echo "you need proper credentials";   
                }
        }
                
     catch (\Exception $e) {
        $response = ['success' => false, 'message' => $e->getMessage()];
    }
        $returnArray = $response;
        return $returnArray; 
    }
}