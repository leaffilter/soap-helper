<?php
namespace LeafFilter\Integration\WebService;
class SoapHandler extends \SoapClient {
  private $attempts               = 0;
  private $max_attempts           = 4;
  private $base_attempt_interval  = 100; // milliseconds
  private $service_secret         = "@vv20!3";

  function __construct($wsdl_uri = "http://operations.noclogs.com/orderentrywebservice/common/lfpubwebservice.asmx?wsdl") {
    try {
      parent::__construct($wsdl_uri);
    }
    catch (SoapFault $e) {
      return false; // Hard failure on construction error
    }
  }
  protected function __doCall($name, $args) {
    do {
      try {
        $response = $this->{$name}($args)->{$name."Result"};
      }
      catch (\SoapFault $e) {
        // Do not retry unless connection-related error
        if ( $e->faultcode != "HTTP" ) {
          return false;
        } else $response = false;
      }
      // Handle returning 0
      switch( $name ) {
        case 'UpsertMarketoLead':
        case 'UpsertMarketoLeadAddress':
        case 'UpsertMarketoLeadAddressHomeAdvisor':
        case 'SaveWebLeadMetadata':
        case 'UpsertMarketoLeadAddressSpendMetadata':
        case 'UpsertMarketoLeadAddressSpendMetadataExt':
          if ( $args['QualifiedLead'] == false && $response == 0 )
            return true;
          break;
      }
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
  public function saveLead($lead_object) {
    $lead = $this->getBaseLead($lead_object);
    return $this->__doCall("SaveWebLead", $lead);
  }
  public function saveLeadWithNotesNoSF($lead_object) {
    $lead = array_merge(
      $this->getBaseLead($lead_object),
      array( "Notes" => $lead_object->Notes )
    );
    return $this->__doCall("SaveWebLeadWithNotesNoSF", $lead);
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
  public function saveLeadMarketoAddress($lead_object) {
    $lead = array_merge(
      $this->getBaseLead($lead_object),
      array(
        "QualifiedLead" => $lead_object->QualifiedLead,
        "Address" => $lead_object->Address,
        "City" => $lead_object->City,
        "State" => $lead_object->State,
        "LeadSource" => $lead_object->LeadSource,
        "Notes" => $lead_object->Notes,
        "CampaignId" => $lead_object->CampaignId,
        "TrackingCookie" => $lead_object->TrackingCookie
      )
    );
    return $this->__doCall("UpsertMarketoLeadAddress", $lead);
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
  public function saveLeadMarketoAddressSpendMetadata($lead_object) {
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
        "network" => $lead_object->network,
        "feeditemid" => $lead_object->feeditemid,
        "device" => $lead_object->device,
        "creative" => $lead_object->creative,
        "keyword" => $lead_object->keyword,
        "adposition" => $lead_object->adposition,
        "loc_physical_ms" => $lead_object->loc_physical_ms,
        "PaidCampaign" => $lead_object->PaidCampaign,
        "PaidAdGroup" => $lead_object->PaidAdGroup,
        "UTMMedium" => $lead_object->UTMMedium,
        "UTMCampaign" => $lead_object->UTMCampaign
      )
    );
    return $this->__doCall("UpsertMarketoLeadAddressSpendMetadata", $lead);
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
  public function saveLeadMetadata($lead_object) {
    $lead = array_merge(
      $this->getBaseLead($lead_object),
      array(
        "QualifiedLead" => $lead_object->QualifiedLead,
        "LeadSource" => $lead_object->LeadSource,
        "Notes" => $lead_object->Notes,
        "CampaignId" => $lead_object->CampaignId,
        "TrackingCookie" => $lead_object->TrackingCookie,
        "network" => $lead_object->network,
        "feeditemid" => $lead_object->feeditemid,
        "device" => $lead_object->device,
        "creative" => $lead_object->creative,
        "keyword" => $lead_object->keyword,
        "adposition" => $lead_object->adposition,
        "loc_physical_ms" => $lead_object->loc_physical_ms,
        "PaidCampaign" => $lead_object->PaidCampaign,
        "PaidAdGroup" => $lead_object->PaidAdGroup,
        "UTMMedium" => $lead_object->UTMMedium,
        "UTMCampaign" => $lead_object->UTMCampaign
      )
    );
    return $this->__doCall("SaveWebLeadMetadata", $lead);
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
  public function ArrayToNameValueTupleList($key, $value) {
    return [
      "Name" => $key,
      "Value" => $value
    ];
  }
}
