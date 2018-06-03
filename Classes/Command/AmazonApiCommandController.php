<?php
namespace Beheist\NW\Command;

use Beheist\NW\Domain\Service\AmazonApiService;
use Beheist\NW\Domain\Service\ProductNodeService;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Neos\Service\PublishingService;

/**
 * @Flow\Scope("singleton")
 */
class AmazonApiCommandController extends CommandController
{
    /**
     * @var AmazonApiService
     * @Flow\Inject
     */
    protected $amazonApiService;

    /**
     * @var ProductNodeService
     * @Flow\Inject
     */
    protected $productNodeService;

    /**
     * @var PublishingService
     * @Flow\Inject
     */
    protected $publishingService;

    /**
     * Update all products in this site.
     * @param string $asin
     */
    public function updateProductsCommand(string $asin = '')
    {
        $productNodes = $this->productNodeService->fetchAllProductNodes();
        $productsByASIN = $this->productNodeService->getProductNodesByASIN($productNodes);
        $asins = $asin !== '' ? [$asin] : array_keys($productsByASIN);
        $productsInformation = $this->amazonApiService->lookupMultipleByASIN($asins);

        // Check for errors
        if ($this->amazonApiService->containsErrors($productsInformation)) {
            $this->outputLine('ERRORS OCCURED.');
            foreach ($this->amazonApiService->extractErrors($productsInformation) as $error) {
                $this->outputLine($error['code'] . ': ' . $error['message']);
            }
            return;
        }

        /** @var \DOMElement $item */
        foreach ($productsInformation->getElementsByTagName('Item') as $item) {
            $asin = $item->getElementsByTagName('ASIN')->item(0)->nodeValue;
            /** @var NodeData $productNode */
            $productNode = $productsByASIN[$asin];
            $this->outputLine('Updating Product "' . $productNode->getProperty('title') . '" with ASIN "' . $asin . '"');

            $extractionPaths = $this->productNodeService->getPropertyExtractionPaths($productNode);
            $extractedProperties = $this->amazonApiService->extractProperties($productsInformation, $asin, $extractionPaths);
            $this->productNodeService->setExtractedProperties($productNode, $extractedProperties);
            $this->publishingService->publishNode($productNode);
        }
    }

    /**
     * Fetches info for one ASIN.
     *
     * @param string $asin
     */
    public function fetchCommand($asin)
    {
        $itemInformation = $this->amazonApiService->lookupByASIN($asin);
        echo $itemInformation->saveXML();
    }
}
