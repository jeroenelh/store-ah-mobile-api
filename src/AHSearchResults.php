<?php

namespace Microit\StoreAhApi;

use Microit\StoreBase\Exceptions\InvalidResponseException;

class AHSearchResults
{
    public function __construct(
        public readonly array $elements,
        public readonly int $currentPage,
        public readonly int $totalPages,
        public readonly int $totalElements
    ) {
    }

    public function count(): int
    {
        return count($this->elements);
    }

    /**
     * @param object $rawOutput
     * @return AHSearchResults
     * @throws InvalidResponseException
     */
    public static function process(object $rawOutput): self
    {
        $pageInfo = self::getPageInformation($rawOutput);
        $elements = self::getElements($rawOutput);

        return new self(
            elements: $elements,
            currentPage: 0,
            totalPages: $pageInfo['totalPages'],
            totalElements: $pageInfo['totalElements']
        );
    }

    /**
     * @param object $rawOutput
     * @return array{totalElements: int, totalPages: int}
     * @throws InvalidResponseException
     */
    public static function getPageInformation(object $rawOutput): array
    {
        if (
            ! assert(is_object($rawOutput->page)) ||
            ! assert(is_int($rawOutput->page->totalPages)) ||
            ! assert(is_int($rawOutput->page->totalElements))
        ) {
            throw new InvalidResponseException('Invalid page information response');
        }

        return [
            'totalPages' => (int) $rawOutput->page->totalPages,
            'totalElements' => (int) $rawOutput->page->totalElements,
        ];
    }

    public static function getElements(object $rawOutput): array
    {
        if (isset($rawOutput->products) && is_array($rawOutput->products)) {
            return $rawOutput->products;
        }

        throw new InvalidResponseException('Missing elements');
    }
}
