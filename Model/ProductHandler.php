<?php

namespace Irs\Klevu\Model;

use GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer;
use Psr\Log\LoggerInterface;

class ProductHandler
{
    const HTTP_TIMEOUT = 5; // Request timeout in seconds.
    const CONFIG_SYSTEM_A_URI = 'klevu/system_a';
    const CONFIG_SYSTEM_B_URI = 'klevu/system_b';

    protected $json;
    protected $logger;
    protected $productInfoFactory;
    protected $httpClientFactory;
    protected $config;
    protected $productRepository;

    public function __construct(
        Serializer\Json $json,
        LoggerInterface $logger,
        ProductInfoFactory $productInfoFactory,
        ClientFactory $httpClientFactory,
        ScopeConfigInterface $config,
        ProductRepositoryInterface $productRepository
    ) {
        $this->json = $json;
        $this->logger = $logger;
        $this->productInfoFactory = $productInfoFactory;
        $this->httpClientFactory = $httpClientFactory;
        $this->config = $config;
        $this->productRepository = $productRepository;
    }

    public function process(string $message)
    {
        try {
            $products = $this->parseMessage($message);
            $this->saveProducts($products);
            $this->updateSystemA($products);
            $this->updateSystemB($products);
        } catch (\InvalidArgumentException $e) {
            $this->logger->debug('Unable to parse message: ' . $message);
        }
    }

    /**
     * Parses message and return array of product info
     *
     * @param string $message
     * @return ProductInfo
     * @throw \InvalidArgumentException
     */
    protected function parseMessage(string $message): array
    {
        $message = $this->json->unserialize($message);
        $result = [];

        if (!is_array($message)) {
            throw new \InvalidArgumentException('Message should be an array');
        }
        foreach ($message as $data) {
            $info = $this->productInfoFactory->create();

            if (isset($data['id'])) {
                $info->setId($data['id']);
            }
            if (isset($data['name'])) {
                $info->setName($data['name']);
            }
            if (isset($data['prices']['now'])) {
                if (isset($data['prices']['was'])) {
                    $info->setPrice($data['prices']['was']);
                    $info->setSpecialPrice($data['prices']['now']);
                } else {
                    $info->setPrice($data['prices']['now']);
                }
            }
            if (isset($data['images']) && is_array($data['images'])) {
                $info->setImages($data['images']);
            }
            $result[] = $info;
        }

        return $result;
    }

    /**
     * Sends update to system A
     *
     * @param ProductInfo[] $products
     */
    protected function updateSystemA(array $products)
    {

        $client = $this->httpClientFactory->create(['config' => [
            'base_uri' => $this->config->getValue(self::CONFIG_SYSTEM_A_URI),
            'timeout' => self::HTTP_TIMEOUT,
        ]]);

        foreach ($products as $product) {
            if (!$product instanceof ProductInfo) {
                continue;
            }
            try {
                $client->post('/', [
                    'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                    'body' => $this->json->serialize([
                        "id" => $product->getId(),
                        "price" => $product->getSpecialPrice() ?: $product->getPrice(),
                        "image" => $product->getImages()[0] ?? null,
                    ])]);
            } catch (GuzzleException $e) {
                $this->logger->error($e);
            }
        }
    }

    /**
     * Sends update to system B
     *
     * @param ProductInfo[] $products
     */
    protected function updateSystemB(array $products)
    {
        $client = $this->httpClientFactory->create(['config' => [
            'base_uri' => $this->config->getValue(self::CONFIG_SYSTEM_B_URI),
            'timeout' => self::HTTP_TIMEOUT,
        ]]);
        $ids = array_filter(array_map(function ($p) {
            return ($p instanceof ProductInfo) ? $p->getId() : null;
        }, $products));

        $client->get('/', ['query' => ['ids' => implode(',', $ids)]]);
    }

    /**
     * Saves products in product repository
     *
     * @param ProductInfo[] $products
     */
    protected function saveProducts(array $products)
    {
        foreach ($products as $info) {
            try {
                $product = $this->productRepository->get($info->getId());
                $product->setName($info->getName());
                $product->setPrice($info->getPrice());
                $this->productRepository->save($product);
            } catch (NoSuchEntityException $e) {
                $this->logger->info('Product #' . $info->getId() . ' is not found');
            }
        }
    }
}
