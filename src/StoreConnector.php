<?php

namespace Microit\StoreAhWebApi;

use Exception;
use Microit\StoreAhWebApi\Processors\ProductProcessor;
use Microit\StoreBase\Collections\CategoryCollection;
use Microit\StoreBase\Collections\ProductCollection;
use Microit\StoreBase\Exceptions\InvalidResponseException;
use Microit\StoreBase\Models\Category;
use Microit\StoreBase\Models\Image;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class StoreConnector extends \Microit\StoreBase\StoreConnector implements LoggerAwareInterface
{
    protected HttpClient $httpClient;

    protected ?LoggerInterface $logger = null;

    public function __construct()
    {
        $connection = HttpClient::getInstance();
        assert($connection instanceof HttpClient);
        $this->httpClient = $connection;
    }

    /**
     * @return CategoryCollection
     * @throws ClientExceptionInterface|InvalidResponseException
     */
    public function getCategories(): CategoryCollection
    {
        $request = $this->httpClient->createRequest('get', 'mobile-services/v1/product-shelves/categories');
        $response = $this->httpClient->getJsonResponse($request);

        $categories = new CategoryCollection();
        /** @var object $categoryResponse */
        foreach ($response as $categoryResponse) {
            assert(is_int($categoryResponse->id));
            assert(is_string($categoryResponse->name));
            assert(is_string($categoryResponse->slugifiedName));
            assert(is_array($categoryResponse->images) && count($categoryResponse->images) && is_object($categoryResponse->images[0]));
            assert(is_string($categoryResponse->images[0]->url));
            assert(is_int($categoryResponse->images[0]->width));
            assert(is_int($categoryResponse->images[0]->height));

            $categories->add(new Category(
                id: $categoryResponse->id,
                name: $categoryResponse->name,
                slug: $categoryResponse->slugifiedName,
                image: (new Image(
                    url: $categoryResponse->images[0]->url,
                    width: $categoryResponse->images[0]->width,
                    height: $categoryResponse->images[0]->height
                ))
            ));
        }

        return $categories;
    }

    /**
     * @param Category $category
     * @return ProductCollection
     * @throws ClientExceptionInterface
     */
    public function getProductsOfCategory(Category $category): ProductCollection
    {
        $products = new ProductCollection();
        $page = 0;

        while (true) {
            $searchResults = $this->requestProductSearch(query: '', category: $category, page: $page, size: 500);

            /** @var array|object $product */
            foreach ($searchResults->getElements() as $product) {
                assert(is_object($product));
                $products->add((new ProductProcessor($product))->getProductObject());
            }

            $page++;
            if ($page > $searchResults->totalPages) {
                break;
            }
        }

        return $products;
    }

    public function getProductsByName(string $name): ProductCollection
    {
        $products = new ProductCollection();
        $page = 0;

        while (true) {
            $searchResults = $this->requestProductSearch(query: $name, page: $page, size: 500);

            /** @var array|object $product */
            foreach ($searchResults->getElements() as $product) {
                assert(is_object($product));
                $products->add((new ProductProcessor($product))->getProductObject());
            }

            $page++;
            if ($page > $searchResults->totalPages) {
                break;
            }
        }

        return $products;
    }

    /**
     * @return AHSearchResults
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    private function requestProductSearch(string $query = '', string $sortOn = '', Category $category = null, int $page = 0, int $size = 100): AHSearchResults
    {
        $fields = [
            'query' => $query,
            'sortOn' => $sortOn,
            'taxonomyId' => $category?->getId(),
            'page' => $page,
            'size' => $size,
        ];
        $request = $this->httpClient->createRequest('get', 'mobile-services/product/search/v2?'.http_build_query($fields));

        $response = $this->httpClient->getJsonResponse($request);
        assert(is_object($response));

        return AHSearchResults::process($response);
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
