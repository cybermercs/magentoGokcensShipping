<?php

namespace Gokcens\Skyshipping\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;

/**
 * Custom shipping model
 */
class Skyshipping extends AbstractCarrier implements CarrierInterface
{
    /**
     * @var string
     */
    protected $_code = 'skyshipping';

    /**
     * @var bool
     */
    protected $_isFixed = true;

    /**
     * @var \Magento\Shipping\Model\Rate\ResultFactory
     */
    private $rateResultFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory
     */
    private $rateMethodFactory;
    

    private  $maxweight;
    private $minweight;
    private $basicshippingcost;
    private $additionalcost;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
                   
        array $data = []
    ) {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data, );
         
        
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;

        
        
    }

    /**
     * Custom Shipping Rates Collector
     *
     * @param RateRequest $request
     * @return \Magento\Shipping\Model\Rate\Result|bool
     */
    public function collectRates(RateRequest $request)
    {
        $items = $request->getAllItems();
        if (empty($items)) {
            return false;
        }

        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $this->maxweight = $this->getConfigData('maxweight');
        $this->minweight = $this->getConfigData('minweight');
        $this->basicshippingcost = $this->getConfigData('basicshippingcost');
        $this->additionalcost = $this->getConfigData('additionalcost');

         
        if($request->getPackageWeight() > $this->maxweight)  return false;



        /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->rateResultFactory->create();

        /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
        $method = $this->rateMethodFactory->create();

        $method->setCarrier($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));

      
        $calcRes = $this->GetTotalWeightCalc($request->getPackageWeight());

        $title =  "Total Weight ".$request->getPackageWeight()."KG // (Max ".$this->maxweight."KG) (".$this->minweight."KG = ".$this->basicshippingcost."$ // Per 1KG = ".$this->additionalcost."$)";

        $method->setCarrierTitle($title); 
        


        $method->setMethod($this->_code);
        $method->setMethodTitle("Gokcens ExpressShipping");
        
        
        
        $method->setPrice($calcRes);
        $method->setCost($calcRes);

        $result->append($method);
       
    

      return $result;


        
    }

    /**
     * @return array
     */
    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }


    public function GetTotalWeightCalc($totalWeight) {


        if($totalWeight == 0) return 0;
        
        $totalShippingCost = 0;        


        if($totalWeight >  $this->minweight) {

            $totalShippingCost +=  $this->basicshippingcost;

            $div =  $totalWeight - $this->minweight;
            $divCost = $div * $this->additionalcost;
            
            $totalShippingCost += $divCost;

         } else {

            $totalShippingCost +=  $this->basicshippingcost;

         }

       return $totalShippingCost;


    }

}
