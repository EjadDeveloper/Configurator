<?php

/**
 *
 * ConfigureTech
 *
 * @category    Magento2-module
 * @license MIT
 * @version 0.0.1
 * @author Tawfek Daghistani <tawfekov@gmail.com>
 * @copyright Copyright (c) 2016  ConfigureTech, Inc <http://www.configuretech.com/>
 *
 */

namespace Ctech\Configurator\Controller\Index;

use \Magento\Checkout\Model\Cart;
use \Magento\Framework\View\Result\PageFactory;
use \Magento\Framework\App\Action\Context;
use \Magento\Framework\Phrase;
use \Magento\Framework\App\Action\Action;

/**
 * Class Configure
 * @package Ctech\Configurator\Controller\Index
 */
class Configure extends Action
{

    protected $resultPageFactory;

    /** @var $cart Cart */
    protected $cart;

    /**
     * Configure constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Cart $cart
     */
    public function __construct(Context $context, PageFactory $resultPageFactory, Cart $cart)
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->cart = $cart;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $logger = $this->_objectManager->get('Psr\Log\LoggerInterface');
        if ($this->getRequest()->isPost()) {
            /// we need to validate the code before accepting it , we haven't agreed on a method yet
            $session        = $this->_objectManager->get('Magento\Customer\Model\Session');
            $cart           = $this->cart;

            $request                  = $this->getRequest();
            $params                   = $request->getParams();
            $products                 = $params["description"];
            $wholesale                = $params["wholesale"];
            $retail                   = $params["retail"];
            $part_number              = $params["part_number"];
            $manufacturer_part_number = $params["manufacturer_part_number"];
            $weight                   = $params["weight"];

            $product        = $this->_objectManager->get('Magento\Catalog\Model\Product')->load($params["product_id"]);
            $configuretech_purchase_sku = $product->getData("configuretech_purchase_product");
            if ($configuretech_purchase_sku) {
                $product = $product->reset()->load($product->getIdBySku($configuretech_purchase_sku));
            } else {
                $logger->addError($product->getId() . " has a missing attributes");
                throw new \Magento\Framework\Exception\NotFoundException(new Phrase("System Error - this product has missing attributes , please contact support"));
            }

            if (is_null($product)) {
                $this->messageManager->addErrorMessage(
                    $this->_objectManager->get('Magento\Framework\Escaper')->escapeHtml("We're unable to add this product to your cart. Please try again later.")
                );
                return $this->_redirect($this->_redirect->getRefererUrl());
            }

            $formatted_products = [];
            $i = 0;
            foreach ($products as $key => $p)
            {
                $formatted_products[$i]["description"]      = $p;
                $formatted_products[$i]["wholesale"]        = $wholesale[$key];
                $formatted_products[$i]["retail"]           = $retail[$key];
                $formatted_products[$i]["part_number"]      = $part_number[$key];
                $formatted_products[$i]["manufacturer_part_number"]  = $manufacturer_part_number[$key];
                $formatted_products[$i]["weight"]           = $weight[$key];
                $formatted_products[$i]["total"]            = $retail[$key];
                $i++;
            }

            // match the product's custom options with the data in the post-back
            $options = array();
            foreach ($product->getOptions() as $o) {
                if ($o->getType() != "field") {
                    continue;
                }
                $options[$o->getOptionId()] = str_replace(" ", "_", strtolower($o->getTitle()));
            }

            $product_params = [];
            foreach ($formatted_products as $selected_product) {
                foreach ($options as $option_id => $option_title) {
                    if (isset($selected_product[$option_title])) {
                        $product_params['options'][$option_id] = preg_replace('#<br\s*/?>#i', "\n", urldecode($selected_product[$option_title]));
                    }
                }
                $cart->addProduct($product, $product_params);
                $product_params = [];
            }

            $cart->save();
            $session->setCartWasUpdated(true);
            $this->_eventManager->dispatch(
                'checkout_cart_add_product_complete',
                [
                    'product' => $product,
                    'request' => $this->getRequest(),
                    'response' => $this->getResponse()
                ]
            );

            $this->messageManager->addSuccessMessage(
                $this->_objectManager->get('Magento\Framework\Escaper')->escapeHtml("the selected product has been added to your cart")
            );
            return $this->_redirect('checkout/cart');

        } else {
            $logger->addError("Page wasn't accessed with POST request");
            throw new \Magento\Framework\Exception\NotFoundException(new Phrase("Method not allowed!"));
        }
    }
}