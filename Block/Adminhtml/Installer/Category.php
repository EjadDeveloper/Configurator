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

namespace Ctech\Configurator\Block\Adminhtml\Installer;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\Session;
use Magento\Catalog\Helper\Category as CategoryHelper;
use Magento\Catalog\Model\CategoryRepository;

class Category extends Template
{

    /* @var CategoryHelper $categoryHelper */
    protected $categoryHelper;
    protected $categoryRepository;
    protected $categoryList;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Session $session
     * @param CategoryHelper $categoryHelper
     * @param CategoryRepository $categoryRepository
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $session,
        CategoryHelper $categoryHelper,
        CategoryRepository $categoryRepository,
        array $data = []
    ) {
        $this->session = $session;
        $this->categoryHelper  = $categoryHelper;
        $this->categoryRepository = $categoryRepository;
        parent::__construct($context, $data);
    }

    /**
     *  get installer config
     *
     * @return array
     */
    public function getInstallerConfig(): array
    {
        return (array) $this->session->getData('ctechInstaller');
    }

    /**
     * get active categories array
     *
     * @return array
     */
    public function getCategories(): array
    {
        $c = [];
        $categoris =  $this->categoryHelper->getStoreCategories(false, true, true)->toArray();
        foreach ($categoris as  $category) {
            $level = (int) $category['level'];
            $name = str_repeat("--", $level) . " " . $category['name'];
            $c[$category['entity_id']] = ['id' => (int) $category['entity_id'],  'name' =>  $name];
        }
        return $c;
    }

    /**
     * slugify a string
     *
     * @param string $text
     * @return string
     */
    public function slugify($text): string
    {
        // Strip html tags
        $text = strip_tags($text);
        // Replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        // Transliterate
        setlocale(LC_ALL, 'en_US.utf8');
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        // Remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);
        // Trim
        $text = trim($text, '-');
        // Remove duplicate -
        $text = preg_replace('~-+~', '-', $text);
        // Lowercase
        $text = strtolower($text);
        // Check if it is empty
        if (empty($text)) {
            return 'n-a';
        }
        // Return result
        return $text;
    }
}
