<?php
namespace Beheist\NW\Domain\Service;

use ApaiIO\ApaiIO;
use ApaiIO\Configuration\GenericConfiguration;
use ApaiIO\Operations\Lookup;
use ApaiIO\Request\GuzzleRequest;
use ApaiIO\ResponseTransformer\XmlToDomDocument;
use Neos\Flow\Annotations as Flow;

/**
 * Class AmazonApiService
 * @package Beheist\NW\Domain\Service
 * @Flow\Scope("singleton")
 */
class AmazonApiService
{
    /**
     * @var array
     * @Flow\InjectConfiguration(path="amazonAPI")
     */
    protected $amazonApiConfig;

    /**
     * @var ApaiIO
     */
    protected $apaiIO;

    public function initializeObject()
    {
        $conf = new GenericConfiguration();
        $client = new \GuzzleHttp\Client();
        $request = new GuzzleRequest($client);
        try {
            $conf
                ->setCountry($this->amazonApiConfig['COUNTRY'])
                ->setAccessKey($this->amazonApiConfig['AWS_API_KEY'])
                ->setSecretKey($this->amazonApiConfig['AWS_API_SECRET_KEY'])
                ->setAssociateTag($this->amazonApiConfig['AWS_ASSOCIATE_TAG'])
                ->setRequest($request)
                ->setResponseTransformer(new XmlToDomDocument());
        } catch (\Exception $e) {
            echo $e->getMessage();
            return;
        }
        $this->apaiIO = new ApaiIO($conf);
    }

    /**
     * Looks up an item by its ASIN.
     *
     * @param $asin
     * @param array $responseGroups
     * @return \DOMDocument
     */
    public function lookupByASIN($asin, $responseGroups = ['Small', 'Images', 'ItemAttributes', 'Offers'])
    {
        $lookup = new Lookup();
        $lookup->setItemId($asin);
        $lookup->setResponseGroup($responseGroups);

        /** @var \DOMDocument $formattedResponse */
        return $this->apaiIO->runOperation($lookup);
    }

    /**
     * Looks up multiple items by their ASINs.
     *
     * @param array $asins
     * @param array $responseGroups
     * @return \DOMDocument
     */
    public function lookupMultipleByASIN($asins, $responseGroups = ['Small', 'Images', 'ItemAttributes', 'Offers'])
    {
        $lookup = new Lookup();
        $lookup->setItemIds($asins);
        $lookup->setResponseGroup($responseGroups);

        /** @var \DOMDocument $formattedResponse */
        return $this->apaiIO->runOperation($lookup);
    }

    /**
     * @param \DOMDocument $DOMDocument
     * @return boolean
     */
    public function containsErrors(\DOMDocument $DOMDocument)
    {
        return $DOMDocument->getElementsByTagName('Error')->length > 0;
    }

    /**
     * @param \DOMDocument $DOMDocument
     * @return boolean
     */
    public function isValid(\DOMDocument $DOMDocument)
    {
        return $DOMDocument->getElementsByTagName('IsValid')->length > 0 && $DOMDocument->getElementsByTagName('IsValid')->item(0)->nodeValue === 'True';
    }

    /**
     * @param \DOMDocument $DOMDocument
     * @return array
     */
    public function extractErrors(\DOMDocument $DOMDocument)
    {
        $errors = [];
        /** @var \DOMElement $errorTag */
        foreach ($DOMDocument->getElementsByTagName('Error') as $errorTag) {
            $errors[] = ['code' => $errorTag->getElementsByTagName('Code')->item(0)->nodeValue, 'message' => $errorTag->getElementsByTagName('Message')->item(0)->nodeValue];
        }
        return $errors;
    }

    /**
     * @param \DOMDocument $DOMDocument
     * @param string $asin
     * @param array $extractionPaths
     * @return array
     */
    public function extractProperties(\DOMDocument $DOMDocument, string $asin, array $extractionPaths)
    {
        $xpath = new \DOMXPath($DOMDocument);
        $xpath->registerNamespace('g', 'http://webservices.amazon.com/AWSECommerceService/2013-08-01');
        $extractedProperties = [];
        foreach ($extractionPaths as $propertyName => $path) {
            $path = '//g:Item/g:ASIN[text()="' . $asin . '"]/..' . $path;
            $queryResult = $xpath->query($path);
            if ($queryResult->length === 0) {
                $extractedProperties[$propertyName] = ''; //we need to unset if things get removed
            } else if ($queryResult->length === 1) {
                $extractedProperties[$propertyName] = $queryResult->item(0)->nodeValue;
//                echo 'FOUND: ' . $propertyName . ' -> ' . $path . ' -> ' . $extractedProperties[$propertyName] . PHP_EOL;
            } else {
//                echo 'FOUND ARRAY: ' . $propertyName . ' -> ' . $path . ' -> ' . PHP_EOL;
                // Multiple results, we expect it to be an array property
                $values = [];
                for ($i = 0; $i < $queryResult->length; $i++) {
                    $values[] = $queryResult->item($i)->nodeValue;
                }
                $extractedProperties[$propertyName] = $values;
            }
        }
        return $extractedProperties;
    }
}
