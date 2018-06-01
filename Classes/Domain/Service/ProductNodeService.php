<?php
namespace Beheist\NW\Domain\Service;

use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\SiteService;

/**
 * Class ProductNodeService
 * @package Beheist\NW\Domain\Service
 * @Flow\Scope("singleton")
 */
class ProductNodeService
{
    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @var NodeInterface
     */
    protected $siteNode;

    /**
     * @var Workspace
     */
    protected $workspace;

    /**
     * Caches extraction config per node type.
     *
     * @var array
     */
    protected $_nodeTypeExtractionCache = [];

    public function initializeObject()
    {
        // Get the site node once.
        /** @var NodeInterface $rootNode */
        $rootNode = $this->contextFactory->create()->getRootNode();
        $this->workspace = $rootNode->getContext()->getWorkspace();
        $sitesNode = $rootNode->getNode(SiteService::SITES_ROOT_PATH);
        $this->siteNode = $sitesNode->getNode('root');
    }

    /**
     * @return array
     */
    public function fetchAllProductNodes()
    {
        return $this->nodeDataRepository->findByParentAndNodeTypeRecursively('/', 'Beheist.NW:Product', $this->workspace);
    }

    /**
     * @param array $productNodes
     * @return array<NodeData>
     */
    public function getProductNodesByASIN(array $productNodes){
        $productsByASIN = [];
        /** @var NodeData $productNode */
        foreach ($productNodes as $productNode) {
            $asin = $productNode->getProperty('asin');
            if ($asin === '') {
                continue;
            }
            $productsByASIN[$asin] = $productNode;
        }
        return $productsByASIN;
    }

    /**
     * @param NodeData $productNode
     * @return array
     */
    public function getPropertyExtractionPaths(NodeData $productNode)
    {
        $nodeType = $productNode->getNodeType();

        if(isset($this->_nodeTypeExtractionCache[$nodeType->getName()])){
            return $this->_nodeTypeExtractionCache[$nodeType->getName()];
        }

        $extractionPaths = [];
        foreach ($nodeType->getConfiguration('properties') as $propertyName => $property) {
            if (array_key_exists('_xpath', $property)) {
                $extractionPaths[$propertyName] = $property['_xpath'];
            }
        }
        return $extractionPaths;
    }

    /**
     * @param NodeData $productNode
     * @param array $properties
     */
    public function setExtractedProperties(NodeData $productNode, array $properties)
    {
        foreach ($properties as $key => $value){
            $productNode->setProperty($key, $value);
        }
        // This is set to a value far in thr past so it is always visible, but updated each time the updater runs
        $productNode->setHiddenBeforeDateTime(new \DateTime('-4 months'));
    }


}