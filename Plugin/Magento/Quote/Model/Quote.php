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

namespace Ctech\Configurator\Plugin\Magento\Quote\Model;

use Closure;
use Ctech\Configurator\Helper\Data;
use Magento\Catalog\Model\Product;
use Magento\Quote\Model\Quote as BaseQuote;

/**
 * Class Quote
 * @package Ctech\Configurator\Plugin\Magento\Quote\Model
 */
class Quote
{
    /**
     * @var Data
     */
    protected $ctechHelper;

    /**
     * Quote constructor.
     * @param Data $ctechHelper
     */
    public function __construct(Data $ctechHelper)
    {
        $this->ctechHelper = $ctechHelper;
    }

    /**
     * This allows you to add same product with different custom options to the cart
     * whereas otherwise Magento would normally just +1 the quantity of the product.
     *
     * @param BaseQuote $quote
     * @param Closure $proceed
     * @param  Product $product
     * @return bool
     */
    public function aroundGetItemByProduct(BaseQuote $quote, Closure $proceed, $product)
    {
        if ($this->ctechHelper->isCtechProduct($product)) {
            return false;
        }

        return $proceed($product);
    }
}
