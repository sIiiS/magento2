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
 * @package     Magento_Directory
 * @copyright   Copyright (c) 2013 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * \Directory country format model
 *
 * @method \Magento\Directory\Model\Resource\Country\Format _getResource()
 * @method \Magento\Directory\Model\Resource\Country\Format getResource()
 * @method string getCountryId()
 * @method \Magento\Directory\Model\Country\Format setCountryId(string $value)
 * @method string getType()
 * @method \Magento\Directory\Model\Country\Format setType(string $value)
 * @method string getFormat()
 * @method \Magento\Directory\Model\Country\Format setFormat(string $value)
 *
 * @category    Magento
 * @package     Magento_Directory
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\Directory\Model\Country;

class Format extends \Magento\Core\Model\AbstractModel
{

    protected function _construct()
    {
        $this->_init('Magento\Directory\Model\Resource\Country\Format');
    }

}
