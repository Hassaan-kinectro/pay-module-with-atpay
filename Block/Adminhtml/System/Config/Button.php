<?php
namespace Pay\WithAtPay\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Block\Template\Context;


class Button extends Field
{
protected $_template = 'Pay_WithAtPay::System/Config/Button.phtml';
public function __construct(Context $context, array $data = [])
{
    parent::__construct($context, $data);
}

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->_toHtml();
    }
}

?>