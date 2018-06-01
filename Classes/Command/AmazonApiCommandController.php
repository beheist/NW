<?php
namespace Beheist\NW\Command;

use Beheist\NW\Domain\Service\AmazonApiService;
use Beheist\NW\Domain\Service\ProductNodeService;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;

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
     * Update all products in this site.
     */
    public function updateProductsCommand()
    {
        $productNodes = $this->productNodeService->fetchAllProductNodes();
        $productsByASIN = $this->productNodeService->getProductNodesByASIN($productNodes);
        $productsInformation = $this->amazonApiService->lookupMultipleByASIN(array_keys($productsByASIN));

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