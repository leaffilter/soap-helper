<?php
namespace LeafFilter\Integration\WebService\Entity;

class JobInfo {
    private $leadId;
    private $jobNumber;
    private $firstName;
    private $lastName;
    private $address;
    private $city;
    private $state;
    private $zip;
    private $email;
    private $phone;
    private $dateInstalled;
    private $warrantyRegistered;
    public function __construct($fieldArray) {
        $fieldNames = array(
            'leadId',
            'jobNumber',
            'firstName',
            'lastName',
            'address',
            'city',
            'state',
            'zip',
            'email',
            'phone',
            'dateInstalled',
            'warrantyRegistered'
        );
        foreach ($fieldNames as $i => $fieldName) {
            $this->$fieldName = $fieldArray[$i];
        }
    }
    public function getLeadId() {
        return $this->leadId;
    }
    public function setLeadId($leadId) {
        $this->leadId = $leadId;
    }
    public function getJobNumber() {
        return $this->jobNumber;
    }
    public function setJobNumber($jobNumber) {
        $this->jobNumber = $jobNumber;
    }
    public function getFirstName() {
        return $this->firstName;
    }
    public function setFirstName($firstName) {
        $this->firstName = $firstName;
    }
    public function getLastName() {
        return $this->lastName;
    }
    public function setLastName($lastName) {
        $this->lastName = $lastName;
    }
    public function getAddress() {
        return $this->address;
    }
    public function setAddress($address) {
        $this->address = $address;
    }
    public function getCity() {
        return $this->city;
    }
    public function setCity($city) {
        $this->city = $city;
    }
    public function getState() {
        return $this->state;
    }
    public function setState($state) {
        $this->state = $state;
    }
    public function getZip() {
        return $this->zip;
    }
    public function setZip($zip) {
        $this->zip = $zip;
    }
    public function getEmail() {
        return $this->email;
    }
    public function setEmail($email) {
        $this->email = $email;
    }
    public function getPhone() {
        return $this->phone;
    }
    public function setPhone($phone) {
        $this->phone = $phone;
    }
    public function getDateInstalled() {
        return $this->dateInstalled;
    }
    public function setDateInstalled($dateInstalled) {
        $this->dateInstalled = $dateInstalled;
    }
    public function getWarrantyRegistered() {
        return $this->warrantyRegistered;
    }
    public function setWarrantyRegistered($warrantyRegistered) {
        $this->warrantyRegistered = $warrantyRegistered;
    }
}
