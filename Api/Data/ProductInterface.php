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

namespace Ctech\Configurator\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface ProductInterface extends ExtensibleDataInterface
{
    const PRICE = 'price';
    const IMAGES = 'images';
    const NAME = 'name';
    const PRODUCT_LINE_CODE = 'product_line_code';
    const SKU = 'sku';
    const BRAND_CODE = 'brand_code';
    const PRODUCT_ID = 'product_id';
    const CATEGORIES = 'categories';

    /**
     * Get product_id
     * @return string|null
     */
    public function getProductId();

    /**
     * Set product_id
     * @param string $productId
     * @return self
     */
    public function setProductId($productId);

    /**
     * Get categories
     * @return string|null
     */
    public function getCategories();

    /**
     * Set categories
     * @param string $categories
     * @return self
     */
    public function setCategories($categories);

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Ctech\Configurator\Api\Data\ProductExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     * @param \Ctech\Configurator\Api\Data\ProductExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Ctech\Configurator\Api\Data\ProductExtensionInterface $extensionAttributes
    );

    /**
     * Get brand_code
     * @return string|null
     */
    public function getBrandCode();

    /**
     * Set brand_code
     * @param string $brandCode
     * @return self
     */
    public function setBrandCode($brandCode);

    /**
     * Get product_line_code
     * @return string|null
     */
    public function getProductLineCode();

    /**
     * Set product_line_code
     * @param string $productLineCode
     * @return self
     */
    public function setProductLineCode($productLineCode);

    /**
     * Get price
     * @return string|null
     */
    public function getPrice();

    /**
     * Set price
     * @param string $price
     * @return self
     */
    public function setPrice($price);

    /**
     * Get images
     * @return string|null
     */
    public function getImages();

    /**
     * Set images
     * @param string $images
     * @return self
     */
    public function setImages($images);

    /**
     * Get name
     * @return string|null
     */
    public function getName();

    /**
     * Set name
     * @param string $name
     * @return self
     */
    public function setName($name);

    /**
     * Get sku
     * @return string|null
     */
    public function getSku();

    /**
     * Set sku
     * @param string $sku
     * @return self
     */
    public function setSku($sku);

    /**
     * Get status
     * @return string|null
     */
    public function getStatus();

    /**
     * Set status
     * @param string $status
     * @return self
     */
    public function setStatus($status);

    /**
     * Get created_at
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created_at
     * @param string $createdAt
     * @return self
     */
    public function setCreatedAt($createdAt);

    /**
     * Get updated_at
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * Set updated_at
     * @param string $updatedAt
     * @return self
     */
    public function setUpdatedAt($updatedAt);
}
