<?php
namespace LeafFilter\Integration\WebService\Response;

use LeafFilter\Integration\WebService\Entity\JobInfo;

class JobInfoResponse {
  private $LeadGetJobInfoResult;
  /* The SoapClient classmap does not call this constructor, but it can be used for thawing cached results */
  public function __construct($result) {
      $this->LeadGetValidJobInfoResult = $result;
  }
  public function getRawResult() {
      return $this->LeadGetValidJobInfoResult;
  }
  public function getJobInfo() {
      $jobInfo = new JobInfo($this->LeadGetValidJobInfoResult);
      return $jobInfo;
  }
  public function getJob() {
      $jobInfo = $this->getJobInfo();
      $job = new \stdClass();
      $job->first_name = $jobInfo->getFirstName();
      $job->last_name = $jobInfo->getLastName();
      $job->email = $jobInfo->getEmail();
      $job->phone = $jobInfo->getPhone();
      $job->job_number = $jobInfo->getJobNumber();
      $job->address = $jobInfo->getAddress();
      $job->city = $jobInfo->getCity();
      // Note name mismatch
      $job->region = $jobInfo->getState();
      // Note name mismatch
      $job->postal_code = $jobInfo->getZip();
      $job->date_installed = $jobInfo->getDateInstalled();
      if ($job->date_installed instanceof \DateTime) {
        $job->date_installed = $job->date_installed->format(\DateTime::ATOM);
      }
      $job->warranty_registered = $jobInfo->getWarrantyRegistered();
      return $job;
  }
  public function getEntity() {
    return $this->getJobInfo();
  }
  public function getObject() {
    return (!$this->LeadGetValidJobInfoResult) ? null : $this->getJob();
  }
}
