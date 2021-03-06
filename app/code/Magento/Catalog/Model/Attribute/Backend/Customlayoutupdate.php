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
 * @package     Magento_Catalog
 * @copyright   Copyright (c) 2013 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Magento\Catalog\Model\Attribute\Backend;

/**
 * Product url key attribute backend
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class Customlayoutupdate extends \Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend
{

    /**
     * Layout update validator factory
     *
     * @var \Magento\Core\Model\Layout\Update\ValidatorFactory
     */
    protected $_layoutUpdateValidatorFactory;

    /**
     * @param \Magento\Logger $logger
     * @param \Magento\Core\Model\Layout\Update\ValidatorFactory $layoutUpdateValidatorFactory
     */
    public function __construct(
        \Magento\Logger $logger,
        \Magento\Core\Model\Layout\Update\ValidatorFactory $layoutUpdateValidatorFactory
    ) {
        $this->_layoutUpdateValidatorFactory = $layoutUpdateValidatorFactory;
        parent::__construct($logger);
    }

    public function validate($object)
    {
        $attributeName = $this->getAttribute()->getName();
        $xml = trim($object->getData($attributeName));

        if (!$this->getAttribute()->getIsRequired() && empty($xml)) {
            return true;
        }

        /** @var $validator \Magento\Core\Model\Layout\Update\Validator */
        $validator = $this->_layoutUpdateValidatorFactory->create();
        if (!$validator->isValid($xml)) {
            $messages = $validator->getMessages();
            //Add first message to exception
            $massage = array_shift($messages);
            $eavExc = new \Magento\Eav\Model\Entity\Attribute\Exception($massage);
            $eavExc->setAttributeCode($attributeName);
            throw $eavExc;
        }
        return true;
    }
}
