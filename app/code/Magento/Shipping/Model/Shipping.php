<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Magento
 * @package     Magento_Shipping
 * @copyright   Copyright (c) 2013 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


namespace Magento\Shipping\Model;

class Shipping
{
    /**
     * Store address
     */
    const XML_PATH_STORE_ADDRESS1     = 'shipping/origin/street_line1';
    const XML_PATH_STORE_ADDRESS2     = 'shipping/origin/street_line2';
    const XML_PATH_STORE_CITY         = 'shipping/origin/city';
    const XML_PATH_STORE_REGION_ID    = 'shipping/origin/region_id';
    const XML_PATH_STORE_ZIP          = 'shipping/origin/postcode';
    const XML_PATH_STORE_COUNTRY_ID   = 'shipping/origin/country_id';

    /**
     * Default shipping orig for requests
     *
     * @var array
     */
    protected $_orig = null;

    /**
     * Cached result
     *
     * @var \Magento\Shipping\Model\Rate\Result
     */
    protected $_result = null;

    /**
     * Part of carrier xml config path
     *
     * @var string
     */
    protected $_availabilityConfigField = 'active';

    /**
     * Core store config
     *
     * @var \Magento\Core\Model\Store\Config
     */
    protected $_coreStoreConfig;

    /**
     * @var \Magento\Core\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Shipping\Model\Config
     */
    protected $_shippingConfig;

    /**
     * @var \Magento\Shipping\Model\Carrier\Factory
     */
    protected $_carrierFactory;

    /**
     * @var \Magento\Shipping\Model\Rate\ResultFactory
     */
    protected $_rateResultFactory;

    /**
     * @var \Magento\Shipping\Model\Rate\RequestFactory
     */
    protected $_rateRequestFactory;

    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $_regionFactory;

    /**
     * @var \Magento\Math\Division
     */
    protected $mathDivision;

    /**
     * @param \Magento\Core\Model\Store\Config $coreStoreConfig
     * @param \Magento\Shipping\Model\Config $shippingConfig
     * @param \Magento\Core\Model\StoreManagerInterface $storeManager
     * @param \Magento\Shipping\Model\Carrier\Factory $carrierFactory
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Shipping\Model\Rate\RequestFactory $rateRequestFactory
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Magento\Math\Division $mathDivision
     */
    public function __construct(
        \Magento\Core\Model\Store\Config $coreStoreConfig,
        \Magento\Shipping\Model\Config $shippingConfig,
        \Magento\Core\Model\StoreManagerInterface $storeManager,
        \Magento\Shipping\Model\Carrier\Factory $carrierFactory,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Shipping\Model\Rate\RequestFactory $rateRequestFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Math\Division $mathDivision
    ) {
        $this->_coreStoreConfig = $coreStoreConfig;
        $this->_shippingConfig = $shippingConfig;
        $this->_storeManager = $storeManager;
        $this->_carrierFactory = $carrierFactory;
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateRequestFactory = $rateRequestFactory;
        $this->_regionFactory = $regionFactory;
        $this->mathDivision = $mathDivision;
    }

    /**
     * Get shipping rate result model
     *
     * @return \Magento\Shipping\Model\Rate\Result
     */
    public function getResult()
    {
        if (empty($this->_result)) {
            $this->_result = $this->_rateResultFactory->create();
        }
        return $this->_result;
    }

    /**
     * Set shipping orig data
     *
     * @param array $data
     * @return null
     */
    public function setOrigData($data)
    {
        $this->_orig = $data;
    }

    /**
     * Reset cached result
     *
     * @return \Magento\Shipping\Model\Shipping
     */
    public function resetResult()
    {
        $this->getResult()->reset();
        return $this;
    }

    /**
     * Retrieve configuration model
     *
     * @return \Magento\Shipping\Model\Config
     */
    public function getConfig()
    {
        return $this->_shippingConfig;
    }

    /**
     * Retrieve all methods for supplied shipping data
     *
     * @todo make it ordered
     * @param \Magento\Shipping\Model\Rate\Request $request
     * @return \Magento\Shipping\Model\Shipping
     */
    public function collectRates(\Magento\Shipping\Model\Rate\Request $request)
    {
        $storeId = $request->getStoreId();
        if (!$request->getOrig()) {
            $request
                ->setCountryId($this->_coreStoreConfig->getConfig(self::XML_PATH_STORE_COUNTRY_ID, $request->getStore()))
                ->setRegionId($this->_coreStoreConfig->getConfig(self::XML_PATH_STORE_REGION_ID, $request->getStore()))
                ->setCity($this->_coreStoreConfig->getConfig(self::XML_PATH_STORE_CITY, $request->getStore()))
                ->setPostcode($this->_coreStoreConfig->getConfig(self::XML_PATH_STORE_ZIP, $request->getStore()));
        }

        $limitCarrier = $request->getLimitCarrier();
        if (!$limitCarrier) {
            $carriers = $this->_coreStoreConfig->getConfig('carriers', $storeId);

            foreach ($carriers as $carrierCode => $carrierConfig) {
                $this->collectCarrierRates($carrierCode, $request);
            }
        } else {
            if (!is_array($limitCarrier)) {
                $limitCarrier = array($limitCarrier);
            }
            foreach ($limitCarrier as $carrierCode) {
                $carrierConfig = $this->_coreStoreConfig->getConfig('carriers/' . $carrierCode, $storeId);
                if (!$carrierConfig) {
                    continue;
                }
                $this->collectCarrierRates($carrierCode, $request);
            }
        }

        return $this;
    }

    /**
     * Collect rates of given carrier
     *
     * @param string                           $carrierCode
     * @param \Magento\Shipping\Model\Rate\Request $request
     * @return \Magento\Shipping\Model\Shipping
     */
    public function collectCarrierRates($carrierCode, $request)
    {
        /* @var $carrier \Magento\Shipping\Model\Carrier\AbstractCarrier */
        $carrier = $this->getCarrierByCode($carrierCode, $request->getStoreId());
        if (!$carrier) {
            return $this;
        }
        $carrier->setActiveFlag($this->_availabilityConfigField);
        $result = $carrier->checkAvailableShipCountries($request);
        if (false !== $result && !($result instanceof \Magento\Shipping\Model\Rate\Result\Error)) {
            $result = $carrier->proccessAdditionalValidation($request);
        }
        /*
        * Result will be false if the admin set not to show the shipping module
        * if the delivery country is not within specific countries
        */
        if (false !== $result){
            if (!$result instanceof \Magento\Shipping\Model\Rate\Result\Error) {
                if ($carrier->getConfigData('shipment_requesttype')) {
                    $packages = $this->composePackagesForCarrier($carrier, $request);
                    if (!empty($packages)) {
                        $sumResults = array();
                        foreach ($packages as $weight => $packageCount) {
                            $request->setPackageWeight($weight);
                            $result = $carrier->collectRates($request);
                            if (!$result) {
                                return $this;
                            } else {
                                $result->updateRatePrice($packageCount);
                            }
                            $sumResults[] = $result;
                        }
                        if (!empty($sumResults) && count($sumResults) > 1) {
                            $result = array();
                            foreach ($sumResults as $res) {
                                if (empty($result)) {
                                    $result = $res;
                                    continue;
                                }
                                foreach ($res->getAllRates() as $method) {
                                    foreach ($result->getAllRates() as $resultMethod) {
                                        if ($method->getMethod() == $resultMethod->getMethod()) {
                                            $resultMethod->setPrice($method->getPrice() + $resultMethod->getPrice());
                                            continue;
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        $result = $carrier->collectRates($request);
                    }
                } else {
                    $result = $carrier->collectRates($request);
                }
                if (!$result) {
                    return $this;
                }
            }
            if ($carrier->getConfigData('showmethod') == 0 && $result->getError()) {
                return $this;
            }
            // sort rates by price
            if (method_exists($result, 'sortRatesByPrice')) {
                $result->sortRatesByPrice();
            }
            $this->getResult()->append($result);
        }
        return $this;
    }

    /**
     * Compose Packages For Carrier.
     * Devides order into items and items into parts if it's necessary
     *
     * @param \Magento\Shipping\Model\Carrier\AbstractCarrier $carrier
     * @param \Magento\Shipping\Model\Rate\Request $request
     * @return array [int, float]
     */
    public function composePackagesForCarrier($carrier, $request)
    {
        $allItems   = $request->getAllItems();
        $fullItems  = array();

        $maxWeight  = (float) $carrier->getConfigData('max_package_weight');

        foreach ($allItems as $item) {
            if ($item->getProductType() == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE
                && $item->getProduct()->getShipmentType()
            ) {
                continue;
            }

            $qty            = $item->getQty();
            $changeQty      = true;
            $checkWeight    = true;
            $decimalItems   = array();

            if ($item->getParentItem()) {
                if (!$item->getParentItem()->getProduct()->getShipmentType()) {
                    continue;
                }
                $qty = $item->getIsQtyDecimal()
                    ? $item->getParentItem()->getQty()
                    : $item->getParentItem()->getQty() * $item->getQty();
            }

            $itemWeight = $item->getWeight();
            if ($item->getIsQtyDecimal() && $item->getProductType() != \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
                $stockItem = $item->getProduct()->getStockItem();
                if ($stockItem->getIsDecimalDivided()) {
                   if ($stockItem->getEnableQtyIncrements() && $stockItem->getQtyIncrements()) {
                        $itemWeight = $itemWeight * $stockItem->getQtyIncrements();
                        $qty        = round(($item->getWeight() / $itemWeight) * $qty);
                        $changeQty  = false;
                   } else {
                       $itemWeight = $itemWeight * $item->getQty();
                       if ($itemWeight > $maxWeight) {
                           $qtyItem = floor($itemWeight / $maxWeight);
                           $decimalItems[] = array('weight' => $maxWeight, 'qty' => $qtyItem);
                           $weightItem = $this->mathDivision->getExactDivision($itemWeight, $maxWeight);
                           if ($weightItem) {
                               $decimalItems[] = array('weight' => $weightItem, 'qty' => 1);
                           }
                           $checkWeight = false;
                       } else {
                           $itemWeight = $itemWeight * $item->getQty();
                       }
                   }
                } else {
                    $itemWeight = $itemWeight * $item->getQty();
                }
            }

            if ($checkWeight && $maxWeight && $itemWeight > $maxWeight) {
                return array();
            }

            if ($changeQty && !$item->getParentItem() && $item->getIsQtyDecimal()
                && $item->getProductType() != \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE
            ) {
                $qty = 1;
            }

            if (!empty($decimalItems)) {
                foreach ($decimalItems as $decimalItem) {
                    $fullItems = array_merge($fullItems,
                        array_fill(0, $decimalItem['qty'] * $qty, $decimalItem['weight'])
                    );
                }
            } else {
                $fullItems = array_merge($fullItems, array_fill(0, $qty, $itemWeight));
            }
        }
        sort($fullItems);

        return $this->_makePieces($fullItems, $maxWeight);
    }

    /**
     * Make pieces
     * Compose packeges list based on given items, so that each package is as heavy as possible
     *
     * @param array $items
     * @param float $maxWeight
     * @return array
     */
    protected function _makePieces($items, $maxWeight)
    {
        $pieces = array();
        if (!empty($items)) {
            $sumWeight = 0;

            $reverseOrderItems = $items;
            arsort($reverseOrderItems);

            foreach ($reverseOrderItems as $key => $weight) {
                if (!isset($items[$key])) {
                    continue;
                }
                unset($items[$key]);
                $sumWeight = $weight;
                foreach ($items as $key => $weight) {
                    if (($sumWeight + $weight) < $maxWeight) {
                        unset($items[$key]);
                        $sumWeight += $weight;
                    } elseif (($sumWeight + $weight) > $maxWeight) {
                        $pieces[] = (string)(float)$sumWeight;
                        break;
                    } else {
                        unset($items[$key]);
                        $pieces[] = (string)(float)($sumWeight + $weight);
                        $sumWeight = 0;
                        break;
                    }
                }
            }
            if ($sumWeight > 0) {
                $pieces[] = (string)(float)$sumWeight;
            }
            $pieces = array_count_values($pieces);
        }

        return $pieces;
    }

    /**
     * Collect rates by address
     *
     * @param \Magento\Object $address
     * @param null|bool|array $limitCarrier
     * @return \Magento\Shipping\Model\Shipping
     */
    public function collectRatesByAddress(\Magento\Object $address, $limitCarrier = null)
    {
        /** @var $request \Magento\Shipping\Model\Rate\Request */
        $request = $this->_rateRequestFactory->create();
        $request->setAllItems($address->getAllItems());
        $request->setDestCountryId($address->getCountryId());
        $request->setDestRegionId($address->getRegionId());
        $request->setDestPostcode($address->getPostcode());
        $request->setPackageValue($address->getBaseSubtotal());
        $request->setPackageValueWithDiscount($address->getBaseSubtotalWithDiscount());
        $request->setPackageWeight($address->getWeight());
        $request->setFreeMethodWeight($address->getFreeMethodWeight());
        $request->setPackageQty($address->getItemQty());
        $request->setStoreId($this->_storeManager->getStore()->getId());
        $request->setWebsiteId($this->_storeManager->getStore()->getWebsiteId());
        $request->setBaseCurrency($this->_storeManager->getStore()->getBaseCurrency());
        $request->setPackageCurrency($this->_storeManager->getStore()->getCurrentCurrency());
        $request->setLimitCarrier($limitCarrier);

        $request->setBaseSubtotalInclTax($address->getBaseSubtotalInclTax());

        return $this->collectRates($request);
    }

    /**
     * Set part of carrier xml config path
     *
     * @param string $code
     * @return \Magento\Shipping\Model\Shipping
     */
    public function setCarrierAvailabilityConfigField($code = 'active')
    {
        $this->_availabilityConfigField = $code;
        return $this;
    }

    /**
     * Get carrier by its code
     *
     * @param string $carrierCode
     * @param null|int $storeId
     * @return bool|\Magento\Core\Model\AbstractModel
     */
    public function getCarrierByCode($carrierCode, $storeId = null)
    {
        $isActive = $this->_coreStoreConfig
            ->getConfigFlag('carriers/' . $carrierCode . '/' . $this->_availabilityConfigField, $storeId);
        if (!$isActive) {
            return false;
        }

        return $this->_carrierFactory->create($carrierCode, $storeId);
    }
}
