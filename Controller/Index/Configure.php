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

namespace Ctech\Configurator\Controller\Index;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Checkout\Model\Cart;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Phrase;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\Escaper;
use Magento\Catalog\Model\ProductRepository;

/**
 * Class Configure
 * @package Ctech\Configurator\Controller\Index
 */
class Configure extends Action implements CsrfAwareActionInterface
{
    protected $resultPageFactory;

    /** @var $cart Cart */
    protected $cart;
    /**
     * @var Session
     */
    private $session;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var Escaper
     */
    private $escaper;
    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * Configure constructor.
     * @param Context $context
     * @param ProductRepository $productRepository
     * @param PageFactory $resultPageFactory
     * @param Cart $cart
     * @param Session $session
     * @param LoggerInterface $logger
     * @param Escaper $escaper
     */
    public function __construct(
        Context $context,
        ProductRepository $productRepository,
        PageFactory $resultPageFactory,
        Cart $cart,
        Session $session,
        LoggerInterface $logger,
        Escaper $escaper

    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->cart = $cart;
        $this->session = $session;
        $this->logger = $logger;
        $this->escaper = $escaper;
        $this->productRepository = $productRepository;
        parent::__construct($context);
    }

    /**
     *
     * Create exception in case CSRF validation failed.
     * Return null if default exception will suffice.
     *
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * Perform custom request validation.
     * Return null if default validation is needed.
     *
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * @return ResponseInterface
     * @throws NotFoundException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        if (!$this->getRequest()->isPost()) {
            $this->logger->error("Page wasn't accessed with POST request");
            throw new NotFoundException(new Phrase("Method not allowed!"));
        }
        /// we need to validate the code before accepting it , we haven't agreed on a method yet
        // @todo: rewrite to a better validator 
        if(
            !isset($params["product_id"]) ||
            !isset($params["description"]) ||
            !isset($params["wholesale"]) ||
            !isset($params["retail"]) ||
            !isset($params["part_number"]) ||
            !isset($params["mfr_part_number"]) ||
            !isset($params["weight"])
        ){
            $this->logger->error("posted data: ". json_encode($params));
            throw new InputException(new Phrase("Missing Paramters!, check the log to see all posted data"));
        }
        $cart                     = $this->cart;
        $request                  = $this->getRequest();
        $params                   = $request->getParams();
        $products                 = $params["description"];
        $wholesale                = $params["wholesale"];
        $retail                   = $params["retail"];
        $part_number              = $params["part_number"];
        $mfr_part_number          = $params["mfr_part_number"];
        $weight                   = $params["weight"];
        $product_id               = $params["product_id"];
        
        // re-add timestamp for Lloyd Mats Store , its optional
        $timestamp                = isset($params["timestamp"]) ? $params["timestamp"] : "";

        $product                  = $this->productRepository->getById($product_id);
        $configuretech_purchase_sku = $product->getData("configuretech_purchase_product");
        if ($configuretech_purchase_sku) {
            $product = $product->reset()->load($product->getIdBySku($configuretech_purchase_sku));
        } else {
            $this->logger->error($product->getId() . " has a missing attributes [configuretech_purchase_product]");
            throw new NotFoundException(new Phrase("System Error - this product has missing attributes , please contact support"));
        }

        if (is_null($product) || ($product->getStatus() == Status::STATUS_DISABLED)) {
            $this->messageManager->addErrorMessage(
                $this->escaper->escapeHtml("We're unable to add this product to your cart, it might be disabled ,  Please try again later or contact support.")
            );
            return $this->_redirect($this->_redirect->getRefererUrl());
        }

        $formatted_products = [];
        $i = 0;
        foreach ($products as $key => $p) {
            $formatted_products[$i]["description"]               = $p;
            $formatted_products[$i]["wholesale"]                 = $wholesale[$key];
            $formatted_products[$i]["retail"]                    = $retail[$key];
            $formatted_products[$i]["part_number"]               = $part_number[$key];
            $formatted_products[$i]["mfr_part_number"]           = $mfr_part_number[$key];
            $formatted_products[$i]["weight"]                    = $weight[$key];
            $formatted_products[$i]["total"]                     = $retail[$key];
            // re-add timestamp for Lloyd Mats Store
            $formatted_products[$i]["timestamp"]                 = $timestamp;
            $i++;
        }

        // match the product's custom options with the data in the post-back
        $options = [];
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
        $this->session->setCartWasUpdated(true);
        $this->_eventManager->dispatch(
            'checkout_cart_add_product_complete',
            [
                'product' => $product,
                'request' => $this->getRequest(),
                'response' => $this->getResponse()
            ]
        );

        $this->messageManager->addSuccessMessage(
            $this->escaper->escapeHtml("the selected product has been added to your cart")
        );
        return $this->_redirect('checkout/cart');      
    }
}
