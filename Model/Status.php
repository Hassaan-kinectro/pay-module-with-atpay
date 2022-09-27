<?php

    namespace Pay\WithAtPay\Model;

    use Psr\Log\LoggerInterface;

        class Status implements \Pay\WithAtPay\Api\StatusInterface
        {
            /** @var \Magento\Framework\View\Result\PageFactory */
            protected $resultPageFactory;
            protected $orderRepository;

            public function __construct(
            \Magento\Framework\View\Result\PageFactory $resultPageFactory,
            \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        
            array $data = []
            ) {
                $this->resultPageFactory = $resultPageFactory;
                $this->orderRepository = $orderRepository;
            }         
/**
* @inheritdoc
*/
        public function setStatus($orderid, $status) {
        
            $response = ['success' => false];
            try {
                $order = $this->orderRepository->get($orderid);       
                $order->setData('status', $status);
                $order->save();
                $object['order_info'] = $order->getData(); 
                $resul=array();
            
            foreach ($order->getAllItems() as $item)
        {
            //fetch whole item information
            $resul= $item->getData();
    }
            $object['items'] = $resul;
            $object['status'] = $status;

// $response = json_decode(json_encode($object), true);
     //update
        $response = $object;

    } catch (\Exception $e) {
        $response = ['success' => false, 'message' => $e->getMessage()];
    }
        $returnArray = $response;
        return $returnArray; 
    }
}