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

namespace Ctech\Configurator\Observer;

use Ctech\Configurator\Helper\Data;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class CustomPrice
 * @package Ctech\Configurator\Observer
 */
class CustomPrice implements ObserverInterface
{
    /** @var $ctechHelper Data */
    protected $ctechHelper;

    /**
     * CustomPrice constructor.
     * @param Data $ctechHelper
     */
    public function __construct(Data $ctechHelper)
    {
        $this->ctechHelper = $ctechHelper;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var  $item */
        $item = $observer->getEvent()->getData('quote_item');
        /** @var $product Product $product */
        $product = $observer->getEvent()->getData('product');

        if ($this->ctechHelper->isCtechProduct($product)) {
            $item = ($item->getParentItem() ? $item->getParentItem() : $item);
            // Load the custom price
            $finalResult = [];
            $result = [];
            // Load the configured product options
            $options = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());
            // Check for options
            if (!empty($options)) {
                if (isset($options['options'])) {
                    $result = array_merge($result, $options['options']);
                }
                if (isset($options['additional_options'])) {
                    $result = array_merge($result, $options['additional_options']);
                }
                if (!empty($options['attributes_info'])) {
                    $result = array_merge($options['attributes_info'], $result);
                }
            }
            $finalResult = array_merge($finalResult, $result);

            $price = "0";
            foreach ($finalResult as $option) {
                if ($option['label'] == "Total") {
                    $price = $option['value'];
                }
            }

            // Set the custom price
            $item->setCustomPrice($price);
            $item->setOriginalCustomPrice($price);
            // Enable super mode on the product.
            $item->getProduct()->setIsSuperMode(true);
        }
    }
}
