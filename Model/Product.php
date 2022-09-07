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

namespace Ctech\Configurator\Model;

use Ctech\Configurator\Api\Data\ProductInterface;
use Ctech\Configurator\Api\Data\ProductInterfaceFactory;
use Ctech\Configurator\Model\ResourceModel\Product as ProductResource;
use Ctech\Configurator\Model\ResourceModel\Product\Collection;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;

class Product extends AbstractModel implements IdentityInterface
{
    const CACHE_TAG = 'ctech_configurator_product';
    protected $_eventPrefix = 'ctech_configurator_product';
    protected $_cacheTag = 'ctech_configurator_product';
    protected $productDataFactory;

    protected $dataObjectHelper;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ProductInterfaceFactory $productDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param ProductResource $resource
     * @param Collection $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ProductInterfaceFactory $productDataFactory,
        DataObjectHelper $dataObjectHelper,
        ProductResource $resource,
        Collection $resourceCollection,
        array $data = []
    ) {
        $this->productDataFactory = $productDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * Retrieve product model with product data
     * @return ProductInterface
     */
    public function getDataModel()
    {
        $productData = $this->getData();

        $productDataObject = $this->productDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $productDataObject,
            $productData,
            ProductInterface::class
        );

        return $productDataObject;
    }
}
