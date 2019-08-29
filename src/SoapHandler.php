<?php
namespace LeafFilter\Integration\WebService;
use Monolog\Logger;
use Monolog\Handler\NullHandler;

class SoapHandler extends \SoapClient {
  const WS_NS = 'http://tempuri.org/';

  private $attempts               = 0;
  private $max_attempts           = 4;
  private $base_attempt_interval  = 100; // milliseconds
  private $service_secret         = "@vv20!3";
  private $logger;

  function __construct(array $options) {
    $defaults = [
      'wsdl_uri' => 'http://operations.noclogs.com/orderentrywebservice/common/lfpubwebservice.asmx?wsdl',
      'logger' => null
    ];
    $options = array_merge($defaults, array_intersect_key($options, $defaults));
    // Use channel with null handler if no logger supplied
    if ($options['logger'] === null) {
      $this->logger = new Logger('null');
      $this->logger->pushHandler(new NullHandler());
    } else {
      $this->logger = $options['logger'];
    }
    try {
      $connection_options = array(
        'classmap' => array(
            'LeadGetValidJobInfoResponse' => 'LeafFilter\Integration\WebService\Response\JobInfoResponse'
        ),
        'typemap' => array(
            array(
                'type_ns' => self::WS_NS,
                'type_name' => 'ArrayOfAnyType',
                'from_xml'  => array($this, 'array_from_xml')
            )
        )
      );
      parent::__construct($options['wsdl_uri'], $connection_options);
    }
    catch (SoapFault $e) {
      $this->logger->addWarning('Encountered SoapFault during construction', array('exception' => $e));
      return false; // Hard failure on construction error
    }
  }
  protected function __doCall($name, $args) {
    do {
      try {
        $response = $this->{$name}($args);
        if (!is_object($response))
          $response = $response->{$name."Result"};
      }
      catch (\SoapFault $e) {
        $this->logger->addWarning('Encountered SoapFault during method call', array('exception' => $e, 'method' => $name));
        // Do not retry unless connection-related error
        if ( $e->faultcode != "HTTP" ) {
          return false;
        } else $response = false;
      }
      // Handle returning 0
      switch( $name ) {
        case 'UpsertMarketoLead':
        case 'UpsertMarketoLeadAddressHomeAdvisor':
        case 'UpsertMarketoLeadAddressSpendMetadataExt':
          if ( $args['QualifiedLead'] == false && $response == 0 )
            return true;
          break;
        // Handle boolean
        case 'RegisterWarrantyByJobNumber':
          if ($name == 'RegisterWarrantyByJobNumber') {
            $response = $response->{$name."Result"};
          }
        // Handle other
        case 'LeadGetValidJobInfo':
          return $response;
          break;
      }
      // NOTE: below block should be unreachable based on above switch statement
      // Exponential backoff
      if ( !$response && $this->attempts < $this->max_attempts ) {
        usleep($this->base_attempt_interval*pow(2, $this->attempts-1)*1000);
        $this->attempts++;
      } else {
        return $response;
      }
    } while ( $this->attempts <= $this->max_attempts );
    $this->attempts = 0; // Reset attempt counter
    return false;
  }
  protected function getBaseLead($lead) {
    $lead = array(
                  "ServicePwd" => $this->service_secret,
                  "FirstName" => $lead->FirstName,
                  "LastName" => $lead->LastName,
                  "Email" => $lead->Email,
                  "Phone" => $lead->Phone,
                  "Zip" => $lead->Zip,
                  "LeadSourceId" => $lead->LeadSourceId,
                  "WebEventDetail" => $lead->WebEventDetail
              );
    return $lead;
  }
  public function saveLeadMarketo($lead_object) {
    $lead = array_merge(
      $this->getBaseLead($lead_object),
      array(
        "QualifiedLead" => $lead_object->QualifiedLead,
        "LeadSource" => $lead_object->LeadSource,
        "Notes" => $lead_object->Notes,
        "CampaignId" => $lead_object->CampaignId,
        "TrackingCookie" => $lead_object->TrackingCookie
      )
    );
    return $this->__doCall("UpsertMarketoLead", $lead);
  }
  public function saveLeadMarketoAddressHomeAdvisor($lead_object) {
    $lead = array_merge(
      $this->getBaseLead($lead_object),
      array(
        "QualifiedLead" => $lead_object->QualifiedLead,
        "Address" => $lead_object->Address,
        "City" => $lead_object->City,
        "State" => $lead_object->State,
        "LeadSource" => $lead_object->LeadSource,
        "Notes" => $lead_object->Notes,
        "HomeAdvisorAccount" => $lead_object->HomeAdvisorAccount,
        "HomeAdvisorJobId" => $lead_object->HomeAdvisorJobId,
        "HomeAdvisorLeadFee" => $lead_object->HomeAdvisorLeadFee,
        "HomeAdvisorLeadFeeDesc" => $lead_object->HomeAdvisorLeadFeeDesc,
        "CampaignId" => $lead_object->CampaignId,
        "TrackingCookie" => $lead_object->TrackingCookie
      )
    );
    return $this->__doCall("UpsertMarketoLeadAddressHomeAdvisor", $lead);
  }
  public function saveLeadMarketoAddressSpend($lead_object) {
    $lead = array_merge(
      $this->getBaseLead($lead_object),
      array(
        "QualifiedLead" => $lead_object->QualifiedLead,
        "Address" => $lead_object->Address,
        "City" => $lead_object->City,
        "State" => $lead_object->State,
        "LeadSource" => $lead_object->LeadSource,
        "Notes" => $lead_object->Notes,
        "DigitalPaidPlatformId" => $lead_object->DigitalPaidPlatformId,
        "Office" => $lead_object->Office,
        "PlatformLeadId" => $lead_object->PlatformLeadId,
        "LeadFee" => $lead_object->LeadFee,
        "LeadFeeDesc" => $lead_object->LeadFeeDesc,
        "CampaignId" => $lead_object->CampaignId,
        "TrackingCookie" => $lead_object->TrackingCookie
      )
    );
    return $this->__doCall("UpsertMarketoLeadAddressSpend", $lead);
  }
  public function saveLeadMarketoAddressSpendMetadataExt($lead_object) {
    $lead = array_merge(
      $this->getBaseLead($lead_object),
      array(
        "QualifiedLead" => $lead_object->QualifiedLead,
        "Address" => $lead_object->Address,
        "City" => $lead_object->City,
        "State" => $lead_object->State,
        "LeadSource" => $lead_object->LeadSource,
        "CampaignId" => $lead_object->CampaignId,
        "TrackingCookie" => $lead_object->TrackingCookie,
        "Notes" => $lead_object->Notes,
        "DigitalPaidPlatformId" => $lead_object->DigitalPaidPlatformId,
        "Office" => $lead_object->Office,
        "PlatformLeadId" => $lead_object->PlatformLeadId,
        "LeadFee" => $lead_object->LeadFee,
        "LeadFeeDesc" => $lead_object->LeadFeeDesc,
        "LeadMetaData" => [
          "NameValueTuple" => array_map(
            array(
              $this,
              'ArrayToNameValueTupleList'
            ),
            array_keys($lead_object->LeadMetaData),
            array_values($lead_object->LeadMetaData)
          )
        ]
      )
    );
    return $this->__doCall("UpsertMarketoLeadAddressSpendMetadataExt", $lead);
  }
  public function saveLeadReferralMarketoAssociation($lead_object) {
    $lead = array_merge(
      $this->getBaseLead($lead_object),
      array(
        "LeadSource" => $lead_object->LeadSource,
        "Notes" => $lead_object->Notes,
        "ReferrerJobNumber" => $lead_object->ReferrerJobNumber,
        "CampaignId" => $lead_object->CampaignId,
        "TrackingCookie" => $lead_object->TrackingCookie,
        "CampaignTokenList" => [
          "NameValueTuple" => array_map(
            array(
              $this,
              'ArrayToNameValueTupleList'
            ),
            array_keys($lead_object->CampaignTokenList),
            array_values($lead_object->CampaignTokenList)
          )
        ]
      )
    );
    return $this->__doCall("SaveWebLeadReferralMarketoAssociation", $lead);
  }
  public function saveLeadCustomerReferral($lead_object) {
    $lead = array_merge( $this->getBaseLead($lead_object),
      array(
        "LeadSource" => $lead_object->LeadSource,
        "Notes" => $lead_object->Notes,
        "ReferrerFirstName" => $lead_object->ReferrerFirstName,
        "ReferrerLastName" => $lead_object->ReferrerLastName,
        "ReferrerEmail" => $lead_object->ReferrerEmail,
        "ReferrerJobNumber" => $lead_object->ReferrerJobNumber,
        "CampaignId" => $lead_object->CampaignId,
        "TrackingCookie" => $lead_object->TrackingCookie,
        "CampaignTokenList" => [
          "NameValueTuple" => array_map(
            array(
              $this,
              'ArrayToNameValueTupleList'
            ),
            array_keys($lead_object->CampaignTokenList),
            array_values($lead_object->CampaignTokenList)
          )
        ],
        "LeadCampaignId" => $lead_object->LeadCampaignId,
        "LeadCampaignTokenList" => [
          "NameValueTuple" => array_map(
            array(
              $this,
              'ArrayToNameValueTupleList'
            ),
            array_keys($lead_object->LeadCampaignTokenList),
            array_values($lead_object->LeadCampaignTokenList)
          )
        ],
        "LeadMetaData" => [
          "NameValueTuple" => array_map(
            array(
              $this,
              'ArrayToNameValueTupleList'
            ),
            array_keys($lead_object->LeadMetaData),
            array_values($lead_object->LeadMetaData)
          )
        ]
      )
    );
    return $this->__doCall("SaveCustomerReferral", $lead);
  }
  public function saveReferral($lead_object) {
    $lead = array_merge(
      $this->getBaseLead($lead_object),
      array(
        "LeadSource" => $lead_object->LeadSource,
        "Notes" => $lead_object->Notes,
        "ReferrerFirstName" => $lead_object->ReferrerFirstName,
        "ReferrerLastName" => $lead_object->ReferrerLastName,
        "ReferrerPhone" => $lead_object->ReferrerPhone,
        "ReferrerEmail" => $lead_object->ReferrerEmail,
        "ReferrerAddress" => $lead_object->ReferrerAddress,
        "ReferrerJobNumber" => $lead_object->ReferrerJobNumber,
        "CampaignId" => $lead_object->CampaignId,
        "TrackingCookie" => $lead_object->TrackingCookie,
        "CampaignTokenList" => [
          "NameValueTuple" => array_map(
            array(
              $this,
              'ArrayToNameValueTupleList'
            ),
            array_keys($lead_object->CampaignTokenList),
            array_values($lead_object->CampaignTokenList)
          )
        ],
        "LeadMetaData" => [
          "NameValueTuple" => array_map(
            array(
              $this,
              'ArrayToNameValueTupleList'
            ),
            array_keys($lead_object->LeadMetaData),
            array_values($lead_object->LeadMetaData)
          )
        ]
      )
    );
    return $this->__doCall("SaveLeadReferral", $lead);
  }
  public function saveMarketo($lead_object) {
    return $this->__doCall("SaveMarketoOnly", array(
      "ServicePwd" => $this->service_secret,
      "FirstName" => $lead_object->FirstName,
      "LastName" => $lead_object->LastName,
      "Email" => $lead_object->Email,
      "Phone" => $lead_object->Phone,
      "Zip" => $lead_object->Zip,
      "WebEventDetail" => $lead_object->WebEventDetail,
      "LeadSource" => $lead_object->LeadSource,
      "CampaignId" => $lead_object->CampaignId,
      "TrackingCookie" => $lead_object->TrackingCookie,
      "CampaignTokenList" => [
        "NameValueTuple" => array_map(
          array(
            $this,
            'ArrayToNameValueTupleList'
          ),
          array_keys($lead_object->CampaignTokenList),
          array_values($lead_object->CampaignTokenList)
        )
      ]
    ));
  }
  public function registerWarranty($lead_object) {
    return $this->__doCall("RegisterWarrantyByJobNumber", array(
      "ServicePwd" => $this->service_secret,
      "JobNumber" => $lead_object->JobNumber,
      "LastName" => $lead_object->LastName,
      "Email" => $lead_object->Email,
      "Phone" => $lead_object->Phone,
      "CampaignId" => $lead_object->CampaignId,
      "TrackingCookie" => $lead_object->TrackingCookie
    ));
  }
  public function getValidJobInfo($lead_object) {
    return $this->__doCall("LeadGetValidJobInfo", array(
      "ServicePwd" => $this->service_secret,
      "JobNumber" => $lead_object->JobNumber,
      "LastName" => $lead_object->LastName
    ));
  }
  public function ArrayToNameValueTupleList($key, $value) {
    return [
      "Name" => $key,
      "Value" => $value
    ];
  }
  public function php_value_from_xml_node($param) {
    if ($param->hasAttributeNS(XSD_NAMESPACE . '-instance', 'nil')) {
      return null;
    } else {
      switch($param->getAttributeNS(XSD_NAMESPACE . '-instance', 'type')) {
        case 'xsd:int':
          return (int)$param->nodeValue;
          break;
        case 'xsd:decimal':
          return (float)$param->nodeValue;
          break;
        case 'xsd:dateTime':
          return \DateTime::createFromFormat('Y-m-d\TH:i:s+', $param->nodeValue);
          break;
        case 'xsd:boolean':
          $val = strtolower($param->nodeValue);
          return ($val === 'false' || $val === '0') ? false : true;
          break;
        case 'ArrayOfAnyType':
          $array = array();
          foreach ($param->childNodes as $child) {
              $array[] = $this->php_value_from_xml_node($child);
          }
          return $array;
          break;
        default:
          return strlen($param->nodeValue) ? $param->nodeValue : '';
          break;
      }
    }
  }
  public function array_from_xml($xml) {
    $array = array();
    $xmlDoc = new \DOMDocument();
    $xmlDoc->loadXML($xml);
    $params = $xmlDoc->documentElement->childNodes;
    foreach($params as $param) {
      $array[] = $this->php_value_from_xml_node($param);
    }
    return $array;
  }
}
