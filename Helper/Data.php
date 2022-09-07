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

namespace Ctech\Configurator\Helper;

use Ctech\Configurator\Model\ProductFactory as ProductCronFactory;
use GuzzleHttp\Client;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\StateException;
use function GuzzleHttp\json_decode;
use GuzzleHttp\RequestOptions;
use Magento\Backend\Model\Session;
use Magento\Catalog\Api\Data\ProductCustomOptionInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\OptionFactory;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory;
use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\ObjectManager\ObjectManager;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class Data extends AbstractHelper
{
    /** @var attribute_set_name string  */
    public $attribute_set_name   = "Configuretech_Configurable";
    /** @var installer_api_key string  */
    public $installer_api_key = "KpYk24VUCVm6NqUrRS1EUgP3pmVr6DF2";
    /** @var installer_domain_name string  */
    public $installer_domain_name = "magento.ext";

    /** @var Curl  */
    protected $curlClient;

    /** @var DirectoryList  */
    protected $_directoryList;

    /** @var StoreManagerInterface $storemanager */
    protected $storeManager;

    /** @var  LoggerInterface  */
    protected $logger;

    /** @var OptionFactory */
    protected $optionFactory;

    /** @var  ProductRepositoryInterface */
    protected $productRepository;

    /** @var ProductInterfaceFactory */
    private $productFactory;

    /** @var  TypeListInterface */
    private $cacheTypeList;

    /** @var  Pool */
    private $cacheFrontendPool;

    /** @var  ProductCronFactory */
    private $ProductCronFactory;

    /* @var ObjectManager  $om */
    protected $om;

    /** @var Session  */
    protected $session;
    /** @var string  */
    protected $VALIDATE_KEY_URL = "https://ctiapi.com/api/v1/validate-key";
    /** @var string  */
    protected $BRAND_LIST_URL = "https://ctiapi.com/api/v1/brand/list";
    /** @var string  */
    protected $PRODUCT_LINE_LIST_URL = "https://ctiapi.com/api/v1/product-line/list";
    /** @var string  */
    protected $PRODUCT_LINE_DATA_LIST_URL = "https://ctiapi.com/api/v1/product-line";

    /**
     * @var ConfigInterface
     */

    private $config;

    public function __construct(
        Curl $curl,
        ObjectManager $om,
        DirectoryList $directoryList,
        ProductRepositoryInterface $productRepository,
        ProductInterfaceFactory $productFactory,
        OptionFactory $optionFactory,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        ProductCronFactory $productcronFactory,
        CollectionFactory $attributeSetCollection,
        ConfigInterface $config,
        TypeListInterface $cacheTypeList,
        Pool $cacheFrontendPool,
        Session $session,
        Context $context
    ) {
        $this->curlClient = $curl;
        $this->om = $om;
        $this->_directoryList = $directoryList;
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->storeManager = $storeManager;
        $this->optionFactory = $optionFactory;
        $this->logger = $logger;
        $this->ProductCronFactory = $productcronFactory;
        $this->attributeSetCollection = $attributeSetCollection;
        $this->config = $config;
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
        $this->session = $session;
        return parent::__construct($context);
    }

    /**
     * get attribute set by name
     *
     * @param string $attributeSetName
     * @return int attributeSetId
     */
    public function getAttributeSetIdNByName($attributeSetName): int
    {
        $attributeSetCollection = $this->attributeSetCollection->create()
            ->addFieldToSelect('attribute_set_id')
            ->addFieldToFilter('attribute_set_name', $attributeSetName)
            ->getFirstItem()
            ->toArray();

        $attributeSetId = (int) $attributeSetCollection['attribute_set_id'];
        return $attributeSetId;
    }

    /**
     * get attributeset Name by Id
     *
     * @param int $id
     * @return string
     */
    public function getAttributeSeNameById($id): string
    {
        $attributeSetCollection = $this->attributeSetCollection->create()
            ->addFieldToSelect('attribute_set_name')
            ->addFieldToFilter('attribute_set_id', $id)
            ->getFirstItem()
            ->toArray();
        $attributeSetName = (string) $attributeSetCollection['attribute_set_name'];
        return $attributeSetName;
    }

    /**
     * get store config from configuration tree
     * @param $key
     * @return mixed
     */
    public function getStoreConfig($key)
    {
        return $this->scopeConfig->getValue("ctech/general/{$key}", ScopeInterface::SCOPE_STORE);
    }

    /**
     * save config to db
     *
     * @param string  $key
     * @param string  $value
     * @return void
     */
    public function saveStoreConfig($key, $value)
    {
        return $this->config->saveConfig(
            $key,
            $value,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            Store::DEFAULT_STORE_ID
        );
    }

    /**
     *
     * helper method to check if current product object is a configuretech product object
     * its always faster to compare numbers instead of fetching the attributeSet name from db
     * @param \Magento\Catalog\Model\Product $product
     * @return bool
     *
     */
    public function isCtechProduct(\Magento\Catalog\Model\Product $product)
    {
        $attribute_set_name = $this->getAttributeSeNameById($product->getAttributeSetId());
        if ($this->attribute_set_name == $attribute_set_name) {
            return true;
        }
        return false;
    }

    /**
     *
     * get an array of hidden options.
     * @return array
     */
    public function getCheckoutHiddenOptions()
    {
        return ['Mfr Part Number', 'Total', 'Timestamp', 'Part Number'];
    }

    /**
     * get installer session data
     *
     * @return array
     */
    public function getInstallerSessionData(): array
    {
        $data = $this->session->getData("ctechInstaller");
        if (is_array($data)) {
            return $data;
        }
        return [];
    }

    /**
     * validate keys
     *
     * @param String $storeKey
     * @param String $storeDomain
     * @return boolean
     */
    public function validateKey(String  $storeKey, String $storeDomain)
    {
        if (empty($storeDomain) || empty($storeKey)) {
            return false;
        }
        try {
            $client = $this->getClient();
            $response = $client->request('POST', $this->VALIDATE_KEY_URL, [
                'form_params' => [
                    'apikey' => $storeKey,
                    'domainName' => $storeDomain
                ]
            ]);
            $body = json_decode($response->getBody()->__toString(), true);
            return $body;
        } catch (Throwable $th) {
            return ["message" => $th->getMessage()];
        }
    }

    /**
     * get brands from ctech api
     *
     * @return void
     */
    public function getBrands(): array
    {
        $sessionData = $this->getInstallerSessionData();
        $storeKey = $this->getStoreConfig("store_key");
        $storeDomain = $this->getStoreConfig("store_domain");
        $client = $this->getClient();
        $response = $client->request('POST', $this->BRAND_LIST_URL, [
            'form_params' => [
                "apikey" => $sessionData['apiKey'],
                "domainName" => $sessionData['domainName'],
                'storeDomain' => $storeDomain,
                'storeKey' => $storeKey
            ]
        ]);
        $data = json_decode($response->getBody()->__toString(), true);
        return $data["data"];
    }

    /**
     * get product line data
     *
     * @return array
     */
    public function getLines(): array
    {
        $sessionData = $this->getInstallerSessionData();
        $storeKey = $this->getStoreConfig("store_key");
        $storeDomain = $this->getStoreConfig("store_domain");
        $brandCodes = $sessionData["brands"];
        $client = $this->getClient();
        $data = [];
        foreach ($brandCodes as $brandCode) {
            $brandData = explode("||", $brandCode);
            $response = $client->request('POST', $this->PRODUCT_LINE_LIST_URL, [
                'form_params' => [
                    "apikey" => $sessionData['apiKey'],
                    "domainName" => $sessionData['domainName'],
                    'storeDomain' => $storeDomain,
                    'storeKey' => $storeKey,
                    'brandCode' => $brandData[0]
                ]
            ]);
            $data[$brandCode] = json_decode($response->getBody()->__toString(), true)['data'];
        }
        return $data;
    }

    /**
     * get product line data
     *
     * @param array $keys
     * @return array
     */
    public function getProductLinesData(array $keys)
    {
        $sessionData = $this->getInstallerSessionData();
        $storeKey = $this->getStoreConfig("store_key");
        $storeDomain = $this->getStoreConfig("store_domain");
        $client = $this->getClient();
        $brandCode = $keys['brandCode'];
        $brandLine = $keys['lineCode'];
        $response = $client->request('POST', $this->PRODUCT_LINE_DATA_LIST_URL, [
            'form_params' => [
                "apikey" => $sessionData['apiKey'],
                "domainName" => $sessionData['domainName'],
                'storeDomain' => $storeDomain,
                'storeKey' => $storeKey,
                'brandCode' => $brandCode,
                'productLineCode' => $brandLine
            ]
        ]);
        $data = json_decode($response->getBody()->__toString(), true)['data'];
        return $data;
    }

    /**
     * add product to queue
     *
     * @param array $config
     * @return bool
     */
    public function addProductToCron(array $config): bool
    {
        try {
            $line_information = $this->getProductLinesData($config)[0];
            $product_name = trim($line_information['product_line_name']);
            $product_price = number_format($line_information['minimum_price'], 2);
            $category_ids = implode(",", $config['categories']);
            $images = json_encode($line_information['images']);
            $brandCode = trim($config['brandCode']);
            $productLineCode = trim($config['lineCode']);
            $row = $this->ProductCronFactory->create();
            $row->setName($product_name);
            $row->setPrice($product_price);
            $row->setCategories($category_ids);
            $row->setImages($images);
            $row->setBrandCode($brandCode);
            $row->setProductLineCode($productLineCode);
            $row->setSku(strtoupper(str_replace(" ", "_", $product_name)));
            $row->setStatus('new');
            $row->save();
            unset($row);
            return true;
        } catch (Throwable $th) {
            $this->logger->critical($th->getMessage());
            return false;
        }
    }

    /**
     * create line products
     *
     * @param array $data
     * @return array
     * @throws FileSystemException
     */
    public function createProducts(array $data): array
    {
        $brandCode = trim($data['brand_code']);
        $productLineCode = trim($data['product_line_code']);

        $attribute_set_id = $this->getAttributeSetIdNByName($this->attribute_set_name);

        $product_name = trim($data['name']);
        $product_price = number_format($data['price'], 2);
        $category_ids = explode(',', $data['categories']);
        /// lets create the product on magento
        $images = $this->downloadImages(json_decode($data['images']));
        ///  create the hidden product
        $sku  = strtoupper(str_replace(" ", "_", $product_name));
        $sku_hidden_product  = strtolower($sku . '_configured');
        $configuretech_purchase_product = $sku_hidden_product;
        $website_id = [$this->storeManager->getDefaultStoreView()->getWebsiteId()];
        // visible product
        $this->createSingleProductAndSave($brandCode, $productLineCode, $sku, $product_name, $attribute_set_id, $category_ids, $website_id, $product_price, $images, false, $configuretech_purchase_product);
        // hidden product
        $this->createSingleProductAndSave($brandCode, $productLineCode, $sku_hidden_product, $product_name, $attribute_set_id, $category_ids, $website_id, $product_price, $images, true);
        return ['success' => 'true', 'message' => $product_name . ' has been installed successfully'];
    }

    /**
     * http client class
     *
     * @return Client
     */
    private function getClient()
    {
        return  new Client();
        //return $this->curlClient;
    }

    /**
     * download images and returns an array of its downloaded folder locations
     *
     * @param array $images
     * @return array
     * @throws FileSystemException
     */
    private function downloadImages(array $images): array
    {
        $varDirectory = $this->_directoryList->getPath('upload') . DIRECTORY_SEPARATOR . "ctech";
        if (!file_exists($varDirectory)) {
            mkdir($varDirectory, 0744, true);
        }
        $saved_images = [];
        $httpClient = $this->getClient();
        foreach ($images as $image) {
            try {
                $this->logger->info('started downloading this url: ' . $image);
                $image_path = parse_url($image, PHP_URL_PATH);
                $imageName = pathinfo($image_path, PATHINFO_BASENAME);
                $targetImageResource = fopen($varDirectory . DIRECTORY_SEPARATOR . $imageName, 'w+');
                $targetFilePath = $varDirectory . DIRECTORY_SEPARATOR . $imageName;
                if (file_exists($targetFilePath) && filesize($targetFilePath) > 0 ) {
                    $saved_images[] = $targetFilePath;
                    continue;
                }
                $response =  $httpClient->get($image, [RequestOptions::SINK => $targetImageResource]);
                if ($response->getStatusCode() === 200) {
                    $saved_images[] = $targetFilePath;
                }
                $this->logger->info('finished downloading this url: ' . $image);
                continue;
            } catch (Throwable $th) {
                $this->_logger->info("failed to download this image: " . $image);
                $this->_logger->error($th->getMessage());
                continue;
            }
        }
        return $saved_images;
    }

    /**
     * get options array
     *
     * @return array
     */
    private function getOptionsArray(): array
    {
        return [
            [
                'title'         => 'Description',
                'type'          => ProductCustomOptionInterface::OPTION_TYPE_FIELD,
                'is_require'    => 1,
                'sort_order'    => 1,
                "price_type"    => "fixed",
                "price"         => "0",

            ],
            [
                'title'         => 'Mfr Part Number',
                'type'          => ProductCustomOptionInterface::OPTION_TYPE_FIELD,
                'is_require'    => 1,
                'sort_order'    => 2,
                "price_type"    => "fixed",
                "price"         => "0",
            ],
            [
                'title'         => 'Part Number',
                'type'          => ProductCustomOptionInterface::OPTION_TYPE_FIELD,
                'is_require'    => 1,
                'sort_order'    => 3,
                "price_type"    => "fixed",
                "price"         => "0",
            ],
            [
                'title'         => 'Total',
                'type'          => ProductCustomOptionInterface::OPTION_TYPE_FIELD,
                'is_require'    => 1,
                'sort_order'    => 4,
                "price_type"    => "fixed",
                "price"         => "0",
            ]
        ];
    }

    /**
     * create and save product
     *
     * @param $brandCode
     * @param $productLineCode
     * @param $sku
     * @param String $product_name
     * @param Integer $attribute_set_id
     * @param Array $category_ids
     * @param Integer $website_id
     * @param Double $product_price
     * @param Array $images
     * @param Bool $hidden_product
     * @param string $configuretech_purchase_product
     * @return ProductInterface
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws StateException
     */
    public function createSingleProductAndSave(
        $brandCode,
        $productLineCode,
        $sku,
        $product_name,
        $attribute_set_id,
        $category_ids,
        $website_id,
        $product_price,
        $images,
        $hidden_product,
        $configuretech_purchase_product = ''
    ) {
        $_product = $this->productFactory->create();
        $_product->setName($product_name);
        $_product->setTypeId(Type::TYPE_SIMPLE);
        $_product->setAttributeSetId($attribute_set_id);
        $_product->setStatus(Status::STATUS_ENABLED);
        $_product->setSku($sku);
        $_product->setCategoryIds($category_ids);
        $_product->setWebsiteIds($website_id);
        if ($hidden_product) {
            $_product->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE);
        } else {
            $_product->setVisibility(Visibility::VISIBILITY_BOTH);
        }
        $_product->setPrice($product_price);
        foreach ($images as $image) {
            $_product->addImageToMediaGallery($image, ['image', 'small_image', 'thumbnail'], false, false);
        }
        $_product->setStockData(
            [
                'use_config_manage_stock' => 0, //'Use config settings' checkbox
                'manage_stock' => 0, //manage stock
                'is_in_stock' => 1, //Stock Availability
                //'qty' => 99999 //qty
            ]
        );
        $_product = $this->productRepository->save($_product);
        $_product->setBrandCode($brandCode);
        $_product->setProductLineCode($productLineCode);
        if ($configuretech_purchase_product !== '') {
            $_product->setConfiguretechPurchaseProduct($configuretech_purchase_product);
        }
        $_product = $this->productRepository->save($_product);
        if ($hidden_product) {
            $_product->setHasOptions(1);
            $_product->setCanSaveCustomOptions(true);
            foreach ($this->getOptionsArray() as $optionValue) {
                $option = $this->optionFactory->create();
                $option->setProductId($_product->getId())
                    ->setStoreId($_product->getStoreId())
                    ->addData($optionValue);
                $_product->addOption($option);
            }
            $_product = $this->productRepository->save($_product);
        }
        return $_product;
    }

    /**
     * clean cache
     *
     * @return void
     */
    public function cleanCache(): void
    {
        try {
            $types = ['config', 'layout', 'block_html', 'collections', 'reflection', 'db_ddl', 'eav', 'config_integration', 'config_integration_api', 'full_page', 'translate', 'config_webservice'];
            foreach ($types as $type) {
                $this->cacheTypeList->cleanType($type);
            }
            foreach ($this->cacheFrontendPool as $cacheFrontend) {
                $cacheFrontend->getBackend()->clean();
            }
        } catch (Throwable $th) {
            $this->logger->error($th);
        }
    }
}
