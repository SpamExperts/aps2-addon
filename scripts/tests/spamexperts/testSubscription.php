<?php

require_once 'phpunitbase.php';

class testSubscription extends \APSTEST\UnitBase
{
    var $this;
    var $contextTypeId;
    var $contextResource;
    var $instance;

    public function setUp()
    {
        parent::setUp();
        $this->contextTypeId = $this->getTypeId('context');
        $this->contextResource = $this->getResourceByType($this->contextTypeId, $this->getSubscriptionToken());
        if (!$this->contextResource) {
            throw new Exception("Resource $this->contextTypeId is not found in subscription {$this->getParam('subscription_id')}");
        }
        $this->instance = $this->getParam('application_instance');
    }

    public function createServiceUserAPSType($name)
    {
        $user_id = $this->createServiceUser($name);
        return $this->getResourceByTypeAndProperties($this->serviceUserType(), array("userId"=>$user_id), $this->getCustomerToken());
    }
}
