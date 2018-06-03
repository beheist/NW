<?php
namespace Beheist\NW\Domain\Service;

use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\ContentContextFactory;
use Neos\Neos\Domain\Service\SiteService;

/**
 * Class ProductNodeService
 * @package Beheist\NW\Domain\Service
 * @Flow\Scope("singleton")
 */
class ProductNodeService
{
    /**
     * @var ContentContextFactory
     * @Flow\Inject
     */
    protected $contentContextFactory;

    /**
     * @var NodeInterface
     */
    protected $rootNode;

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
        $this->rootNode = $this->contentContextFactory->create()->getRootNode();
    }

    /**
     * @return array<NodeInterface>
     */
    public function fetchAllProductNodes()
    {
        $flowQuery = new FlowQuery([$this->rootNode]);
        return $flowQuery->find('[instanceof Beheist.NW:Product][_hidden = false]')->get();
    }

    /**
     * @param array $productNodes
     * @return array<NodeInterface>
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
     * @param NodeInterface $productNode
     * @return array
     */
    public function getPropertyExtractionPaths(NodeInterface $productNode)
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
     * @param NodeInterface $productNode
     * @param array $properties
     */
    public function setExtractedProperties(NodeInterface $productNode, array $properties)
    {
        foreach ($properties as $key => $value){
            $productNode->setProperty($key, $value);
        }
        // This is set to a value far in thr past so it is always visible, but updated each time the updater runs
        $productNode->setHiddenBeforeDateTime(new \DateTime('-4 months'));
    }


}
