<?php
/**
*
* Developer: Hemant Singh Magento 2x Developer
* Category: Wishusucess_Customer
* Website: http://www.wishusucess.com/
*/
namespace AtPay\CustomPayment\Api;
/**
* @api
*/
interface ProductsInterface
{
/**
* Get product list
*
* @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
* @return \Magento\Catalog\Api\Data\ProductSearchResultsInterface
*/
public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);
}