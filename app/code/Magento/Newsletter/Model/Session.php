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
 * @package     Magento_Newsletter
 * @copyright   Copyright (c) 2013 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Newsletter session model
 *
 * @category   Magento
 * @package    Magento_Newsletter
 * @author      Magento Core Team <core@magentocommerce.com>
 */

namespace Magento\Newsletter\Model;

class Session extends \Magento\Core\Model\Session\AbstractSession
{
    /**
     * @param \Magento\Core\Model\Session\Context $context
     * @param \Magento\Session\SidResolverInterface $sidResolver
     * @param \Magento\Session\Config\ConfigInterface $sessionConfig
     * @param null $sessionName
     * @param array $data
     */
    public function __construct(
        \Magento\Core\Model\Session\Context $context,
        \Magento\Session\SidResolverInterface $sidResolver,
        \Magento\Session\Config\ConfigInterface $sessionConfig,        
        $sessionName = null,
        array $data = array()
    ) {
        parent::__construct($context, $sidResolver, $sessionConfig, $data);
        $this->start('newsletter', $sessionName);
    }

    public function addError($message)
    {
        $this->setErrorMessage($message);
        return $this;
    }

    public function addSuccess($message)
    {
        $this->setSuccessMessage($message);
        return $this;
    }

    public function getError()
    {
        $message = $this->getErrorMessage();
        $this->unsErrorMessage();
        return $message;
    }

    public function getSuccess()
    {
        $message = $this->getSuccessMessage();
        $this->unsSuccessMessage();
        return $message;
    }
}
