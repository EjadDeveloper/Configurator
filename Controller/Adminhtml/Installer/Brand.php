<?php

/**
 * Copyright (c) 2022 Tawfek Daghistani - ConfigureTech
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Ctech\Configurator\Controller\Adminhtml\Installer;

use Ctech\Configurator\Helper\Data;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Session;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Result\PageFactory;

class Brand extends \Magento\Backend\App\Action
{
    protected $resultPageFactory;

    /** @var  Data $helper */
    protected $helper;
    /** @var  ManagerInterface $messageManager  */
    protected $messageManager;
    /** @var  Session $session  */
    protected $session;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Data $helper
     * @param ManagerInterface $messageManager
     * @param Session $session
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Data $helper,
        ManagerInterface $messageManager,
        Session $session
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->helper = $helper;
        $this->messageManager = $messageManager;
        $this->session = $session;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $keys = $this->session->getData("ctechInstaller");
        if (empty($keys)) {
            $this->messageManager->addNoticeMessage("Installer session has been expired , please start over");
            return $this->_redirect("ctechinstaller/installer/index");
        }
        $data = $this->getRequest()->getPostValue();
        if (!empty($data['brands'])) {
            $installer_config = $this->session->getData('ctechInstaller');
            $installer_config['brands'] = $data['brands'];
            $this->session->setData("ctechInstaller", $installer_config);
            if (count($data['brands']) == 1) {
                $this->messageManager->addSuccessMessage("Brand has been selected");
            } else {
                $this->messageManager->addSuccessMessage("Brands have been selected");
            }
            return $this->_redirect("ctechinstaller/installer/line");
        }
        return $this->resultPageFactory->create();
    }
}
