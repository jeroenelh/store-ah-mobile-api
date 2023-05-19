<?php

namespace Microit\StoreAhApi;

use Microit\StoreAhApi\Models\Category;
use Microit\StoreAhApi\Models\Image;

class AH
{
    protected ClientConnection $clientConnection;
    public function __construct()
    {
        $connection = ClientConnection::getInstance();
        assert($connection instanceof ClientConnection);
        $this->clientConnection = $connection;
    }

    public function getCategories(): array
    {
        $request = $this->clientConnection->createRequest('get', 'mobile-services/v1/product-shelves/categories');
        $response = $this->clientConnection->getJsonResponse($request);

        $categories = [];
        /** @var object $categoryResponse */
        foreach ($response as $categoryResponse) {
            assert(is_int($categoryResponse->id));
            assert(is_string($categoryResponse->name));
            assert(is_string($categoryResponse->slugifiedName));
            assert(is_array($categoryResponse->images) && count($categoryResponse->images) && is_object($categoryResponse->images[0]));
            assert(is_string($categoryResponse->images[0]->url));
            assert(is_int($categoryResponse->images[0]->width));
            assert(is_int($categoryResponse->images[0]->height));

            $categories[] = (new Category(
                id: $categoryResponse->id,
                name: $categoryResponse->name,
                slug: $categoryResponse->slugifiedName,
                image: (new Image(
                    url: $categoryResponse->images[0]->url,
                    width: $categoryResponse->images[0]->width,
                    height: $categoryResponse->images[0]->height
                )
                )
            ));
        }

        return $categories;
    }
}
