<?php

namespace Microit\StoreAhApi\Processors;

use Exception;
use Microit\StoreBase\Collections\ImageCollection;
use Microit\StoreBase\Enums\Currency;
use Microit\StoreBase\Models\Image;
use Microit\StoreBase\Models\Price;
use Microit\StoreBase\Models\Product;

class ProductProcessor
{
    private Product $product;

    public function __construct(public readonly object $rawObject)
    {
        $this->product = new Product(
            id: (int) $this->rawObject->webshopId,
            title: (string) $this->rawObject->title,
            brand: (string) $this->rawObject->brand,
            description: (string) $this->rawObject->descriptionHighlights,
        );

        $this->processPrices();
        $this->processImages();
    }

    private function processPrices(): void
    {
        try {
            $this->product->addPrice(new Price($this->getCurrentPrice(), (string) $this->rawObject->salesUnitSize, Currency::EUR));
        } catch (Exception $exception) {
            // TODO implement logging here
        }
    }

    /**
     * @throws Exception
     */
    private function getCurrentPrice(): float
    {
        switch (true) {
            case isset($this->rawObject->currentPrice):
                $price = (float) $this->rawObject->currentPrice;
                break;
            case isset($this->rawObject->priceBeforeBonus):
                $price = (float) $this->rawObject->priceBeforeBonus;
                break;
            default:
                throw new Exception('No price found');
        }

        return $price;
    }

    private function processImages(): void
    {
        if (! isset($this->rawObject->images) || ! is_array($this->rawObject->images)) {
            return;
        }

        $images = new ImageCollection();
        foreach ($this->rawObject->images as $image) {
            assert(is_object($image));
            $images->add(new Image(url: (string) $image->url, width: (int) $image->width, height: (int) $image->height));
        }

        $this->product->setImageCollection($images);
    }

    public function getProductObject(): Product
    {
        return $this->product;
    }
}
