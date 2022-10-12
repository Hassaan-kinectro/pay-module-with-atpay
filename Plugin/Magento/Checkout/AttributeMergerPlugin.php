<?php

namespace AtPay\CustomPayment\Plugin\Magento\Checkout;

class AttributeMergerPlugin
{
    /**
     * @var \Psr\Log\LoggerInterface
     */

    private $logger;

    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function afterMerge(
        \Magento\Checkout\Block\Checkout\AttributeMerger $subject,
        $fields
    ) {
        $this->logger->info(__METHOD__);

        $fieldNames = $this->shippingAddressFieldAnAutoFillValues();

        foreach ($fields as $attributeCode => $field) {
      
            if (in_array($attributeCode, $fieldNames)) {
                //  Different Code for set Value for Street
                if (
                    $attributeCode == 'street' &&
                    isset($field['children'][0]['config']['customScope']) &&
                    $field['children'][0]['config']['customScope'] ==
                        'shippingAddress'
                ) {
                    $fields[$attributeCode]['children'][0]['value'] =
                        'Oregon State University';
                }
                // Checking Address Type Shipping and Attribute is not Street
                if (
                    $attributeCode != 'street' &&
                    (isset($field['config']['customScope']) &&
                        $field['config']['customScope'] == 'shippingAddress')
                ) {
                    $this->logger->info($attributeCode);
                    switch ($attributeCode) {
                        case 'country_id':
                            $fields[$attributeCode]['value'] = 'AU';
                            break;
                        default:
                        // echo "ELSE";
                    }
                }
            }
        }
        return $fields;
    }

    /**
     * Makeing an array of
     * @return array
     */
    private function shippingAddressFieldAnAutoFillValues()
    {
        return [
            'country_id',
        ];
    }
}
