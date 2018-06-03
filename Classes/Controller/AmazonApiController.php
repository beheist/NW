<?php
namespace Beheist\NW\Controller;

use Beheist\NW\Domain\Service\AmazonApiService;
use Beheist\NW\Domain\Service\ProductNodeService;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Neos\Service\PublishingService;

/**
 * @Flow\Scope("singleton")
 */
class AmazonApiController extends ActionController
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
     * @var ProductNodeService
     * @Flow\InjectConfiguration(path="amazonAPIRefreshSecurityToken")
     */
    protected $securityToken;

    /**
     * @param string $securityToken
     * @return bool
     */
    public function updateProductsAction($securityToken)
    {
        $output = '';

        if ($securityToken !== $this->securityToken) {
            return false;
        }
        $productNodes = $this->productNodeService->fetchAllProductNodes();
        $productsByASIN = $this->productNodeService->getProductNodesByASIN($productNodes);
        $productsInformation = $this->amazonApiService->lookupMultipleByASIN(array_keys($productsByASIN));

        $output .= 'Updating ASINS: '.implode(', ', array_keys($productsByASIN)).'<br><br>';

        // Check for errors
        if ($this->amazonApiService->containsErrors($productsInformation)) {
            $output .= 'ERRORS OCCURED:<br>';
            foreach ($this->amazonApiService->extractErrors($productsInformation) as $error) {
                $output .= $error['code'] . ': ' . $error['message'] . '<br>';
            }
            $this->view->assign('output', $output);
            return true;
        }

        /** @var \DOMElement $item */
        foreach ($productsInformation->getElementsByTagName('Item') as $item) {
            $asin = $item->getElementsByTagName('ASIN')->item(0)->nodeValue;
            /** @var NodeData $productNode */
            $productNode = $productsByASIN[$asin];
            $output .= 'Updating Product "' . $productNode->getProperty('title') . '" with ASIN "' . $asin . '"<br>';

            $extractionPaths = $this->productNodeService->getPropertyExtractionPaths($productNode);
            $extractedProperties = $this->amazonApiService->extractProperties($productsInformation, $asin, $extractionPaths);
            $this->productNodeService->setExtractedProperties($productNode, $extractedProperties);
            $this->publishingService->publishNode($productNode);
            $this->persistenceManager->whitelistObject($productNode);
        }
        $this->persistenceManager->persistAll();
        $this->view->assign('output', $output);
    }
}
