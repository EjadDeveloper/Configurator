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

namespace Ctech\Configurator\Setup;

use Ctech\Configurator\Helper\Data as Helper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Class UpgradeData
 * @package Ctech\Configurator\Setup
 */
class UpgradeData implements UpgradeDataInterface
{

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var CategorySetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var AttributeSetFactory
     */
    private $attributeSetFactory;

    /**
     * @var CategorySetupFactory
     */
    private $categorySetupFactory;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var Helper
     */
    private $helper;

    /**
     *
     * @param LoggerInterface $logger
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        AttributeSetFactory $attributeSetFactory,
        CategorySetupFactory $categorySetupFactory,
        LoggerInterface $logger,
        ConfigInterface $config,
        Helper $helper
    ) {
        $this->logger = $logger;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->categorySetupFactory = $categorySetupFactory;
        $this->config = $config;
        $this->helper = $helper;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();
        if (version_compare($context->getVersion(), "3.0.0", "<")) {
            $this->createAttributeSet($setup);
            $this->createAttributes($setup);
        }
        $setup->endSetup();
    }

    /**
     * create attribute_set and save its id in config table
     *
     * @param ModuleDataSetupInterface $setup
     * @return bool
     */
    private function createAttributeSet(ModuleDataSetupInterface $setup): bool
    {
        try {
            $categorySetup = $this->categorySetupFactory->create(['setup' => $setup]);
            $attributeSet = $this->attributeSetFactory->create();
            $entityTypeId = $categorySetup->getEntityTypeId(Product::ENTITY);
            $attributeSetId = $categorySetup->getDefaultAttributeSetId($entityTypeId);
            $data = [
                'attribute_set_name' => $this->helper->attribute_set_name,
                'entity_type_id' => $entityTypeId,
                'sort_order' => 200,
            ];
            $attributeSet->setData($data);
            $attributeSet->validate();
            $attributeSet->save();
            $attributeSet->initFromSkeleton($attributeSetId);
            $attributeSet->save();
            return true;
        } catch (Throwable $th) {
            $this->logger->error($th->getMessage());
            return false;
        }
    }

    /**
     * create attributes
     *
     * @param ModuleDataSetupInterface $setup
     * @return bool
     */
    private function createAttributes(ModuleDataSetupInterface $setup): bool
    {
        try {
            $attribute_set_id = $this->helper->getAttributeSetIdNByName($this->helper->attribute_set_name);
            // create attributes
            $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
            if (!$eavSetup->getAttributeId(Product::ENTITY, 'brand_code')) {
                $eavSetup->addAttribute(
                    Product::ENTITY,
                    'brand_code',
                    [
                        'attribute_set_id' => $attribute_set_id,
                        'type' => 'text',
                        'backend' => '',
                        'frontend' => '',
                        'label' => 'Brand Code',
                        'input' => 'text',
                        'class' => '',
                        'source' => '',
                        'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                        'visible' => true,
                        'required' => false,
                        'user_defined' => false,
                        'default' => '',
                        'searchable' => false,
                        'filterable' => false,
                        'comparable' => false,
                        'visible_on_front' => false,
                        'used_in_product_listing' => true,
                        'unique' => false,
                        'apply_to' => ''
                    ]
                );
            }
            if (!$eavSetup->getAttributeId(Product::ENTITY, 'product_line_code')) {
                $eavSetup->addAttribute(
                    Product::ENTITY,
                    'product_line_code',
                    [
                        'attribute_set_id' => $attribute_set_id,
                        'type' => 'text',
                        'backend' => '',
                        'frontend' => '',
                        'label' => 'Product Line Code',
                        'input' => 'text',
                        'class' => '',
                        'source' => '',
                        'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                        'visible' => true,
                        'required' => false,
                        'user_defined' => false,
                        'default' => '',
                        'searchable' => false,
                        'filterable' => false,
                        'comparable' => false,
                        'visible_on_front' => false,
                        'used_in_product_listing' => true,
                        'unique' => false,
                        'apply_to' => ''
                    ]
                );
            }
            if (!$eavSetup->getAttributeId(Product::ENTITY, 'product_type_code')) {
                $eavSetup->addAttribute(
                    Product::ENTITY,
                    'product_type_code',
                    [
                        'attribute_set_id' => $attribute_set_id,
                        'type' => 'text',
                        'backend' => '',
                        'frontend' => '',
                        'label' => 'Product Type Code',
                        'input' => 'text',
                        'class' => '',
                        'source' => '',
                        'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                        'visible' => true,
                        'required' => false,
                        'user_defined' => false,
                        'default' => '',
                        'searchable' => false,
                        'filterable' => false,
                        'comparable' => false,
                        'visible_on_front' => false,
                        'used_in_product_listing' => true,
                        'unique' => false,
                        'apply_to' => ''
                    ]
                );
            }
            if (!$eavSetup->getAttributeId(Product::ENTITY, 'configuretech_purchase_product')) {
                $eavSetup->addAttribute(
                    Product::ENTITY,
                    'configuretech_purchase_product',
                    [
                        'attribute_set_id' => $attribute_set_id,
                        'type' => 'text',
                        'backend' => '',
                        'frontend' => '',
                        'label' => 'Configuretech Purchaseable Product SKU',
                        'input' => 'text',
                        'class' => '',
                        'source' => '',
                        'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                        'visible' => true,
                        'required' => false,
                        'user_defined' => false,
                        'default' => '',
                        'searchable' => false,
                        'filterable' => false,
                        'comparable' => false,
                        'visible_on_front' => false,
                        'used_in_product_listing' => true,
                        'unique' => false,
                        'apply_to' => ''
                    ]
                );
            }
            return true;
        } catch (Throwable $th) {
            $this->logger->error($th->getMessage());
            return false;
        }
    }
}
