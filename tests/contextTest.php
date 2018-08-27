<?php

define('APS_RUNTIME_NO_PROCESS', 1);

require_once __DIR__ . "/../src/scripts/vendor/autoload.php";
require_once __DIR__ . "/../src/scripts/context.php";

class contextTest extends \PHPUnit\Framework\TestCase
{
    public function testProvision()
    {
        $apscMock = $this->getMockBuilder(\APS\ControllerProxy::class)
            ->setMethods([ 'getResources', 'subscribe' ])
            ->disableOriginalConstructor()
            ->getMock();
        $admin = new stdClass;
        $admin->login = uniqid('admin_user_');
        $admin->email = 'test@example.com';
        $apscMock->expects($this->once())
            ->method( 'getResources' )
            ->with($this->equalTo('implementing(http://parallels.com/aps/types/pa/admin-user/1.0)'))
            ->will($this->returnValue([ $admin ]));
        $apscMock->expects($this->exactly(3))
            ->method( 'subscribe' );

        /** @var $ctx PHPUnit\Framework\MockObject\MockObject | context */
        $ctx = $this->getMockBuilder(\context::class)
            ->setMethods([ 'APSC', 'createReseller' ])
            ->setConstructorArgs([ null, $apscMock ])
            ->getMock();
        $ctx->expects($this->exactly(4))
            ->method( 'APSC' )
            ->will($this->returnValue($apscMock));
        $ctx->expects($this->once())
            ->method( 'createReseller' )
            ->will($this->returnValue(true));
        $ctx->subscription = new stdClass;
        $ctx->subscription->subscriptionId = uniqid('subscription_id_');
        $ctx->subscription->aps = new stdClass;
        $ctx->subscription->aps->id = uniqid('subscription_id_');
        $ctx->aps = new stdClass;
        $ctx->aps->id = uniqid('aps_id_');
        $ctx->service = new stdClass;
        $ctx->service->mx1 = uniqid('mx_record_');
        $ctx->service->mx2 = uniqid('mx_record_');
        $ctx->service->mx3 = uniqid('mx_record_');
        $ctx->service->aps = new stdClass;
        $ctx->service->aps->id = uniqid('aps_id_');

        $ctx->provision();
    }

    public function testRetrieve()
    {
        $adminUsername = uniqid('admin_user_');

        $apiMock = $this->getMockBuilder(\APIClient::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'getIncoming', 'getOutgoing', 'getArchiving', 'getPrivateLabel' ])
            ->getMock();
        $apiMock->expects($this->never())->method( 'getIncoming');
        $apiMock->expects($this->never())->method( 'getOutgoing');
        $apiMock->expects($this->never())->method( 'getArchiving');
        $apiMock->expects($this->once())
            ->method( 'getPrivateLabel')
            ->with($this->equalTo($adminUsername));

        /** @var $ctx PHPUnit\Framework\MockObject\MockObject | context */
        $ctx = $this->getMockBuilder(\context::class)
            ->setMethods([ 'API' ])
            ->setConstructorArgs([ $apiMock, null ])
            ->getMock();
        $ctx->expects($this->any())
            ->method( 'API')
            ->will($this->returnValue($apiMock));
        $ctx->private_label = new stdClass;
        $ctx->private_label->limit = 1;
        $ctx->username = $adminUsername;

        $ctx->retrieve();
    }

    public function testUnprovision()
    {
        $adminUsername = uniqid('admin_user_');

        $apiMock = $this->getMockBuilder(\APIClient::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'wipeReseller' ])
            ->getMock();
        $apiMock->expects($this->once())
            ->method( 'wipeReseller')
            ->with($this->equalTo($adminUsername));

        /** @var $ctx PHPUnit\Framework\MockObject\MockObject | context */
        $ctx = $this->getMockBuilder(\context::class)
            ->setMethods([ 'API' ])
            ->setConstructorArgs([ $apiMock, null ])
            ->getMock();
        $ctx->expects($this->any())
            ->method( 'API')
            ->will($this->returnValue($apiMock));
        $ctx->username = $adminUsername;

        $ctx->unprovision();
    }

    public function testDomainUnlink()
    {
        $domain = new stdClass;
        $domain->domain = $domain->name = uniqid('domain') . '.test';

        /** @var $ctx PHPUnit\Framework\MockObject\MockObject | context */
        $ctx = $this->getMockBuilder(\context::class)
            ->setMethods([ 'revertMXRecords' ])
            ->setConstructorArgs([ null, null ])
            ->getMock();
        $ctx->expects($this->once())
            ->method( 'revertMXRecords')
            ->with($this->equalTo($domain->domain));

        $ctx->domainsUnlink($domain);
    }

    public function testOnDomainAvailableSubscriptionsMatch()
    {
        $domainId = uniqid('iDdomain');

        $domain = new stdClass;
        $domain->name = 'example.com';
        $domain->hosting = new stdClass;
        $domain->hosting->aps = new stdClass;
        $domain->hosting->aps->id = uniqid('aps_id_');
        $domain->hosting->subscriptionId = uniqid('subscription_id_');

        $apscMock = $this->getMockBuilder(\APS\ControllerProxy::class)
            ->setMethods([ 'getResource' ])
            ->disableOriginalConstructor()
            ->getMock();
        $apscMock->expects($this->once())
            ->method( 'getResource' )
            ->with($this->equalTo($domainId))
            ->will($this->returnValue($domain));

        /** @var $ctx PHPUnit\Framework\MockObject\MockObject | context */
        $ctx = $this->getMockBuilder(\context::class)
            ->setMethods([ 'APSC', 'domainAutoprotectionDisabled', 'subscriptionHasSEReources', 'updateResources' ])
            ->setConstructorArgs([ null, $apscMock ])
            ->getMock();
        $ctx->expects($this->once())
            ->method( 'APSC' )
            ->will($this->returnValue($apscMock));
        $ctx->expects($this->once())
            ->method( 'domainAutoprotectionDisabled')
            ->will($this->returnValue(false));
        $ctx->expects($this->once())
            ->method( 'subscriptionHasSEReources')
            ->will($this->returnValue(true));
        $ctx->expects($this->once())
            ->method( 'updateResources')
            ->with(
                $this->equalTo([ $domain ]),
                $this->equalTo(true)
            );
        $ctx->subscription = new stdClass;
        $ctx->subscription->aps = new stdClass;
        $ctx->subscription->aps->id = $domain->hosting->aps->id;
        $ctx->service = new stdClass;
        $ctx->service->ignoreRemoteDomains = true;

        $event = new stdClass;
        $event->source = new stdClass;
        $event->source->id = $domainId;
        $ctx->onDomainAvailable($event);
    }

    public function testOnDomainAvailableSubscriptionsDontMatch()
    {
        $domainId = uniqid('iDdomain');

        $domain = new stdClass;
        $domain->name = 'example.com';
        $domain->hosting = new stdClass;
        $domain->hosting->aps = new stdClass;
        $domain->hosting->aps->id = uniqid('aps_id_');

        $apscMock = $this->getMockBuilder(\APS\ControllerProxy::class)
            ->setMethods([ 'getResource' ])
            ->disableOriginalConstructor()
            ->getMock();
        $apscMock->expects($this->once())
            ->method( 'getResource' )
            ->with($this->equalTo($domainId))
            ->will($this->returnValue($domain));

        /** @var $ctx PHPUnit\Framework\MockObject\MockObject | context */
        $ctx = $this->getMockBuilder(\context::class)
            ->setMethods([ 'APSC', 'domainAutoprotectionDisabled', 'subscriptionHasSEReources', 'updateResources' ])
            ->setConstructorArgs([ null, $apscMock ])
            ->getMock();
        $ctx->expects($this->once())
            ->method( 'APSC' )
            ->will($this->returnValue($apscMock));
        $ctx->expects($this->once())
            ->method( 'domainAutoprotectionDisabled')
            ->will($this->returnValue(false));
        $ctx->expects($this->never())
            ->method( 'subscriptionHasSEReources');
        $ctx->expects($this->never())
            ->method( 'updateResources' );
        $ctx->subscription = new stdClass;
        $ctx->subscription->aps = new stdClass;
        $ctx->subscription->aps->id = uniqid('aps_id_');
        $ctx->service = new stdClass;
        $ctx->service->ignoreRemoteDomains = true;

        $event = new stdClass;
        $event->source = new stdClass;
        $event->source->id = $domainId;
        $ctx->onDomainAvailable($event);
    }

    public function testOnDomainAvailableSubscriptionsMatchRemoteDomainIgnored()
    {
        $domainId = uniqid('iDdomain');

        $domain = new stdClass;
        $domain->name = 'example.com';
        $domain->hosting = new stdClass;
        $domain->hosting->aps = new stdClass;
        $domain->hosting->aps->id = uniqid('aps_id_');
        $domain->hosting->subscriptionId = uniqid('subscription_id_');

        $apscMock = $this->getMockBuilder(\APS\ControllerProxy::class)
            ->setMethods([ 'getResource' ])
            ->disableOriginalConstructor()
            ->getMock();
        $apscMock->expects($this->once())
            ->method( 'getResource' )
            ->with($this->equalTo($domainId))
            ->will($this->returnValue($domain));

        /** @var $ctx PHPUnit\Framework\MockObject\MockObject | context */
        $ctx = $this->getMockBuilder(\context::class)
            ->setMethods([ 'APSC', 'domainAutoprotectionDisabled', 'subscriptionHasSEReources', 'updateResources' ])
            ->setConstructorArgs([ null, $apscMock ])
            ->getMock();
        $ctx->expects($this->once())
            ->method( 'APSC' )
            ->will($this->returnValue($apscMock));
        $ctx->expects($this->once())
            ->method( 'domainAutoprotectionDisabled')
            ->will($this->returnValue(false));
        $ctx->expects($this->once())
            ->method( 'subscriptionHasSEReources')
            ->will($this->returnValue(false));
        $ctx->expects($this->never())
            ->method( 'updateResources');
        $ctx->subscription = new stdClass;
        $ctx->subscription->aps = new stdClass;
        $ctx->subscription->aps->id = $domain->hosting->aps->id;
        $ctx->service = new stdClass;
        $ctx->service->ignoreRemoteDomains = true;

        $event = new stdClass;
        $event->source = new stdClass;
        $event->source->id = $domainId;
        $ctx->onDomainAvailable($event);
    }
}
