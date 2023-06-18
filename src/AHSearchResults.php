<?php

namespace Microit\StoreAhWebApi;

use Microit\StoreBase\Exceptions\InvalidResponseException;

class AHSearchResults
{
    /**
     * @param array<array-key, mixed> $elements
     * @param int $currentPage
     * @param int $totalPages
     * @param int $totalElements
     */
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
     * @return array<array-key, mixed>
     */
    public function getElements(): array
    {
        return $this->elements;
    }

    /**
     * @param object $rawOutput
     * @return AHSearchResults
     * @throws InvalidResponseException
     */
    public static function process(object $rawOutput): self
    {
        $pageInfo = self::getPageInformationOfRawOutput($rawOutput);
        $elements = self::getElementsOfRawOutput($rawOutput);

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
    public static function getPageInformationOfRawOutput(object $rawOutput): array
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

    public static function getElementsOfRawOutput(object $rawOutput): array
    {
        if (isset($rawOutput->products) && is_array($rawOutput->products)) {
            return $rawOutput->products;
        }

        throw new InvalidResponseException('Missing elements');
    }
}
