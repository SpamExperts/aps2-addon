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
        $domain->name = uniqid('domain') . '.test';

        $domain->domain = new stdClass;
        $domain->domain->aps = new stdClass;
        $domain->domain->aps->id = uniqid('aps_id_');
        $domain->domain->name = $domain->name;

        $mxRecord = new stdClass;
        $mxRecord->exchange = 'mx.example.com';
        $mxRecord->aps = new stdClass;
        $mxRecord->aps->id = uniqid('aps_id_');

        $ioMock = $this->getMockBuilder(\APS\Proto::class)
            ->setMethods([ 'sendRequest' ])
            ->disableOriginalConstructor()
            ->getMock();
        $ioMock->expects($this->once())
            ->method( 'sendRequest');

        $apscMock = $this->getMockBuilder(\APS\ControllerProxy::class)
            ->setMethods([ 'getIo' ])
            ->disableOriginalConstructor()
            ->getMock();
        $apscMock->expects($this->once())
            ->method( 'getIo' )
            ->willReturn($ioMock);

        /** @var $ctx PHPUnit\Framework\MockObject\MockObject | context */
        $ctx = $this->getMockBuilder(\context::class)
            ->setMethods([ 'getPAMXRecords' ])
            ->setConstructorArgs([ null, $apscMock ])
            ->getMock();
        $ctx->expects($this->once())
            ->method( 'getPAMXRecords')
            ->with($this->equalTo($domain->domain))
            ->willReturn([ $mxRecord ]);

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

    public function testOnSubscriptionLimitChanged()
    {
        $adminUsername = uniqid('admin_user_');
        $adminPassword = uniqid('admin_pass_');
        $adminEmail = "{$adminUsername}@example.com";
        $adminDomainsLimit = 100500;

        $apiMock = $this->getMockBuilder(\APIClient::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'updateReseller', 'setResellerProducts' ])
            ->getMock();
        $apiMock->expects($this->once())
            ->method( 'updateReseller')
            ->with(
                $this->equalTo($adminUsername),
                $this->equalTo($adminPassword),
                $this->equalTo($adminEmail),
                $this->equalTo($adminDomainsLimit)
            );
        $apiMock->expects($this->once())
            ->method( 'setResellerProducts')
            ->with(
                $this->equalTo($adminUsername),
                $this->equalTo(array( 'incoming' => 1, 'outgoing' => 1, 'archiving' => 1, 'private_label' => 'none' ))
            );

        /** @var $ctx PHPUnit\Framework\MockObject\MockObject | context */
        $ctx = $this->getMockBuilder(\context::class)
            ->setMethods([ 'getLimit' ])
            ->setConstructorArgs([ $apiMock, null ])
            ->getMock();
        $ctx->expects($this->once())
            ->method( 'getLimit')
            ->will($this->returnValue($adminDomainsLimit));
        $ctx->username = $adminUsername;
        $ctx->password = $adminPassword;
        $ctx->adminEmail = $adminEmail;

        $ctx->onSubscriptionLimitChanged(1);
    }

    public function testDomainCheckByIds()
    {
        $ids = array(
            '6c153a84-aa9e-11e8-a137-529269fb1459',
            '6c15411e-aa9e-11e8-a137-529269fb1459',
            '6c1543e4-aa9e-11e8-a137-529269fb1459'
        );

        $apscMock = $this->getMockBuilder(\APS\ControllerProxy::class)
            ->setMethods([ 'getResource', 'getResources' ])
            ->disableOriginalConstructor()
            ->getMock();
        $apscMock->expects($this->exactly(3))
            ->method( 'getResource' )
            ->will($this->returnValue(true));
        $apscMock->expects($this->never())
            ->method( 'getResources' )
            ->will($this->returnValue([]));

        /** @var $ctx PHPUnit\Framework\MockObject\MockObject | context */
        $ctx = $this->getMockBuilder(\context::class)
            ->setMethods([ 'updateResources',  ])
            ->setConstructorArgs([ null, $apscMock ])
            ->getMock();
        $ctx->expects($this->once())
            ->method( 'updateResources');

        $ctx->domainCheck(rawurlencode(json_encode($ids)));
    }

    public function testDomainCheckByNames()
    {
        $names = array(
            'example.com',
            'example.net',
            'example.org'
        );

        $apscMock = $this->getMockBuilder(APS\ControllerProxy::class)
            ->setMethods([ 'getResource', 'getResources' ])
            ->disableOriginalConstructor()
            ->getMock();
        $apscMock->expects($this->never())
            ->method( 'getResource' )
            ->will($this->returnValue(true));
        $apscMock->expects($this->once())
            ->method( 'getResources' )
            ->will($this->returnValue([]));

        /** @var $ctx PHPUnit\Framework\MockObject\MockObject | context */
        $ctx = $this->getMockBuilder(\context::class)
            ->setMethods([ 'updateResources',  ])
            ->setConstructorArgs([ null, $apscMock ])
            ->getMock();
        $ctx->expects($this->once())
            ->method( 'updateResources');
        $ctx->account = new stdClass;
        $ctx->account->aps = new stdClass;
        $ctx->account->aps->id = uniqid('aps_id_');

        $ctx->domainCheck(rawurlencode(json_encode($names)));
    }

    public function testDomainProtectByIds()
    {
        $ids = array(
            '6c153a84-aa9e-11e8-a137-529269fb1459',
            '6c15411e-aa9e-11e8-a137-529269fb1459',
            '6c1543e4-aa9e-11e8-a137-529269fb1459'
        );

        $apscMock = $this->getMockBuilder(\APS\ControllerProxy::class)
            ->setMethods([ 'getResource', 'getResources' ])
            ->disableOriginalConstructor()
            ->getMock();
        $apscMock->expects($this->exactly(3))
            ->method( 'getResource' )
            ->will($this->returnValue(true));
        $apscMock->expects($this->never())
            ->method( 'getResources' )
            ->will($this->returnValue([]));

        /** @var $ctx PHPUnit\Framework\MockObject\MockObject | context */
        $ctx = $this->getMockBuilder(\context::class)
            ->setMethods([ 'updateResources',  ])
            ->setConstructorArgs([ null, $apscMock ])
            ->getMock();
        $ctx->expects($this->once())
            ->method( 'updateResources');

        $ctx->domainProtect(rawurlencode(json_encode($ids)));
    }

    public function testDomainUnprotectByIds()
    {
        $ids = array(
            '6c153a84-aa9e-11e8-a137-529269fb1459',
            '6c15411e-aa9e-11e8-a137-529269fb1459',
            '6c1543e4-aa9e-11e8-a137-529269fb1459'
        );

        $apscMock = $this->getMockBuilder(\APS\ControllerProxy::class)
            ->setMethods([ 'getResource', 'getResources' ])
            ->disableOriginalConstructor()
            ->getMock();
        $apscMock->expects($this->exactly(3))
            ->method( 'getResource' )
            ->will($this->returnArgument(0));
        $apscMock->expects($this->never())
            ->method( 'getResources' )
            ->will($this->returnValue([]));

        /** @var $ctx PHPUnit\Framework\MockObject\MockObject | context */
        $ctx = $this->getMockBuilder(\context::class)
            ->setMethods([ 'unprotectResources' ])
            ->setConstructorArgs([ null, $apscMock ])
            ->getMock();
        $ctx->expects($this->once())
            ->method( 'unprotectResources')
            ->with($this->equalTo($ids));

        $ctx->domainUnprotect(rawurlencode(json_encode($ids)));
    }

    public function testEmailCheckByIds()
    {
        $ids = array(
            '6c153a84-aa9e-11e8-a137-529269fb1459',
            '6c15411e-aa9e-11e8-a137-529269fb1459',
            '6c1543e4-aa9e-11e8-a137-529269fb1459'
        );

        $apscMock = $this->getMockBuilder(\APS\ControllerProxy::class)
            ->setMethods([ 'getResource', 'getResources' ])
            ->disableOriginalConstructor()
            ->getMock();
        $apscMock->expects($this->exactly(3))
            ->method( 'getResource' )
            ->will($this->returnValue(true));
        $apscMock->expects($this->never())
            ->method( 'getResources' )
            ->will($this->returnValue([]));

        /** @var $ctx PHPUnit\Framework\MockObject\MockObject | context */
        $ctx = $this->getMockBuilder(\context::class)
            ->setMethods([ 'updateResources',  ])
            ->setConstructorArgs([ null, $apscMock ])
            ->getMock();
        $ctx->expects($this->once())
            ->method( 'updateResources');

        $ctx->emailCheck(rawurlencode(json_encode($ids)));
    }

    public function testEmailProtectByIds()
    {
        $ids = array(
            '6c153a84-aa9e-11e8-a137-529269fb1459',
            '6c15411e-aa9e-11e8-a137-529269fb1459',
            '6c1543e4-aa9e-11e8-a137-529269fb1459'
        );

        $apscMock = $this->getMockBuilder(\APS\ControllerProxy::class)
            ->setMethods([ 'getResource', 'getResources' ])
            ->disableOriginalConstructor()
            ->getMock();
        $apscMock->expects($this->exactly(3))
            ->method( 'getResource' )
            ->will($this->returnValue(true));
        $apscMock->expects($this->never())
            ->method( 'getResources' )
            ->will($this->returnValue([]));

        /** @var $ctx PHPUnit\Framework\MockObject\MockObject | context */
        $ctx = $this->getMockBuilder(\context::class)
            ->setMethods([ 'updateResources',  ])
            ->setConstructorArgs([ null, $apscMock ])
            ->getMock();
        $ctx->expects($this->once())
            ->method( 'updateResources');

        $ctx->emailProtect(rawurlencode(json_encode($ids)));
    }

    public function testEmailUnprotectByIds()
    {
        $ids = array(
            '6c153a84-aa9e-11e8-a137-529269fb1459',
            '6c15411e-aa9e-11e8-a137-529269fb1459',
            '6c1543e4-aa9e-11e8-a137-529269fb1459'
        );

        $apscMock = $this->getMockBuilder(\APS\ControllerProxy::class)
            ->setMethods([ 'getResource', 'getResources' ])
            ->disableOriginalConstructor()
            ->getMock();
        $apscMock->expects($this->exactly(3))
            ->method( 'getResource' )
            ->will($this->returnArgument(0));
        $apscMock->expects($this->never())
            ->method( 'getResources' )
            ->will($this->returnValue([]));

        /** @var $ctx PHPUnit\Framework\MockObject\MockObject | context */
        $ctx = $this->getMockBuilder(\context::class)
            ->setMethods([ 'unprotectResources' ])
            ->setConstructorArgs([ null, $apscMock ])
            ->getMock();
        $ctx->expects($this->once())
            ->method( 'unprotectResources')
            ->with($this->equalTo($ids));

        $ctx->emailUnprotect(rawurlencode(json_encode($ids)));
    }

    public function testGetAuthTicketForDomainUser()
    {
        $authTicket = uniqid('auth_ticket_');

        $apscMock = $this->getMockBuilder(\APS\ControllerProxy::class)
            ->setMethods([ 'getResources' ])
            ->disableOriginalConstructor()
            ->getMock();
        $apscMock->expects($this->any())
            ->method( 'getResources' )
            ->will($this->returnValue([ 'res' ]));

        $apiMock = $this->getMockBuilder(\APIClient::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'getAuthTicket' ])
            ->getMock();
        $apiMock->expects($this->once())
            ->method( 'getAuthTicket')
            ->will($this->returnValue($authTicket));

        /** @var $ctx PHPUnit\Framework\MockObject\MockObject | context */
        $ctx = $this->getMockBuilder(\context::class)
            ->setMethods([ 'getResource' ])
            ->setConstructorArgs([ $apiMock, $apscMock ])
            ->getMock();
        $ctx->expects($this->never())
            ->method( 'getResource');
        $ctx->service = new stdClass;
        $ctx->service->ssl = true;
        $ctx->service->hostname = 'host.name.test';
        $ctx->cp_domain = new stdClass;
        $ctx->cp_domain->limit = 1;
        $ctx->aps = new stdClass;
        $ctx->aps->id = uniqid('aps_id_');

        $this->assertContains(
            "?authticket={$authTicket}",
            $ctx->getAuthTicket('example.com')
        );
    }

    public function testGetAuthTicketForEmailUser()
    {
        $authTicket = uniqid('auth_ticket_');

        $apscMock = $this->getMockBuilder(\APS\ControllerProxy::class)
            ->setMethods([ 'getResources' ])
            ->disableOriginalConstructor()
            ->getMock();
        $apscMock->expects($this->any())
            ->method( 'getResources' )
            ->will($this->returnValue([ 'res' ]));

        $apiMock = $this->getMockBuilder(\APIClient::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'getAuthTicket' ])
            ->getMock();
        $apiMock->expects($this->once())
            ->method( 'getAuthTicket')
            ->will($this->returnValue($authTicket));

        /** @var $ctx PHPUnit\Framework\MockObject\MockObject | context */
        $ctx = $this->getMockBuilder(\context::class)
            ->setMethods([ 'getResource' ])
            ->setConstructorArgs([ $apiMock, $apscMock ])
            ->getMock();
        $ctx->expects($this->never())
            ->method( 'getResource');
        $ctx->service = new stdClass;
        $ctx->service->ssl = true;
        $ctx->service->hostname = 'host.name.test';
        $ctx->cp_domain = new stdClass;
        $ctx->cp_domain->limit = 1;
        $ctx->aps = new stdClass;
        $ctx->aps->id = uniqid('aps_id_');

        $this->assertContains(
            "?authticket={$authTicket}",
            $ctx->getAuthTicket('test@example.com')
        );
    }

    public function testUpdateResources()
    {
        $domainResource = new stdClass;
        $domainResource->name = 'example.com';
        $domainResource->domain = new stdClass;
        $domainResource->domain->domain = 'example.com';
        $domainResource->domain->aps = new stdClass;
        $domainResource->domain->aps->id = uniqid('aps_id_');
        $domainResource->aps = new stdClass;
        $domainResource->aps->id = uniqid('aps_id_');

        $mxRecordResource = new stdClass;
        $mxRecordResource->exchange = 'mx.example.com';

        $subscriptionResource = new stdClass;
        $subscriptionResource->apsType = 'http://aps.spamexperts.com/app/domain/1.0';
        $subscriptionResource->limit = 100;
        $subscriptionResource->usage = 10;
        $resourcesCollection = new APS2TestResourcesCollection([ $subscriptionResource ]);

        $seResourceByTypeId = new stdClass;
        $seResourceByTypeId->aps = new stdClass;
        $seResourceByTypeId->aps->links = [];

        $apscMock = $this->getMockBuilder(\APS\ControllerProxy::class)
            ->setMethods([ 'getResources', 'getResource', 'updateResource', 'linkResource' ])
            ->disableOriginalConstructor()
            ->getMock();
        $apscMock->expects($this->any())
            ->method( 'getResources' )
            ->will(
                $this->onConsecutiveCalls(
                    [ $domainResource ],
                    [ $mxRecordResource ],
                    [ $mxRecordResource ],
                    [ null ],
                    [ null ]
                )
            );
        $apscMock->expects($this->any())
            ->method( 'getResource' )
            ->will($this->returnValue($resourcesCollection));
        $apscMock->expects($this->any())
            ->method( 'linkResource' )
            ->will($this->returnValue(true));

        $apiMock = $this->getMockBuilder(\APIClient::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'addDomain', 'setDomainProducts', 'assertOwner', 'addDomainUser' ])
            ->getMock();
        $apiMock->expects($this->once())
            ->method( 'addDomain')
            ->will($this->returnValue(true));
        $apiMock->expects($this->once())
            ->method( 'setDomainProducts')
            ->will($this->returnValue(true));
        $apiMock->expects($this->once())
            ->method( 'assertOwner')
            ->will($this->returnValue(true));
        $apiMock->expects($this->once())
            ->method( 'addDomainUser')
            ->will($this->returnValue(true));

        /** @var $ctx PHPUnit\Framework\MockObject\MockObject | context */
        $ctx = $this->getMockBuilder(\context::class)
            ->setMethods([ 'getResourcesFromIDs', 'createResourceByTypeId', 'makeAPSLinkInstance', 'API' ])
            ->setConstructorArgs([ $apiMock, $apscMock ])
            ->getMock();
        $ctx->expects($this->once())
            ->method( 'getResourcesFromIDs')
            ->willReturn([ $domainResource ]);
        $ctx->expects($this->once())
            ->method( 'createResourceByTypeId')
            ->willReturn($seResourceByTypeId);
        $ctx->expects($this->any())
            ->method( 'makeAPSLinkInstance')
            ->willReturn(null);
        $ctx->expects($this->any())
            ->method( 'API')
            ->willReturn($apiMock);
        $ctx->aps = new stdClass;
        $ctx->aps->id = uniqid('aps_id_');
        $ctx->subscription = new stdClass;
        $ctx->subscription->aps = new stdClass;
        $ctx->subscription->aps->id = uniqid('aps_id_');
        $ctx->mx = [
            uniqid('mx_record_') . '.',
            uniqid('mx_record_') . '.',
            uniqid('mx_record_') . '.'
        ];

        $ctx->domainProtect('"example.com"');
    }


    public function testEamilStatusCheck()
    {
        $subscriptionResource = new stdClass;
        $subscriptionResource->apsType = 'http://aps.spamexperts.com/app/domain/1.0';
        $subscriptionResource->limit = 100;
        $subscriptionResource->usage = 10;
        $resourcesCollection = new APS2TestResourcesCollection([ $subscriptionResource ]);

        $apscMock = $this->getMockBuilder(\APS\ControllerProxy::class)
            ->setMethods([ 'getResource', 'getResources' ])
            ->disableOriginalConstructor()
            ->getMock();
        $apscMock->expects($this->any())
            ->method( 'getResources' )
            ->will(
                $this->onConsecutiveCalls(
                    [ null ]
                )
            );
        $apscMock->expects($this->any())
            ->method( 'getResource' )
            ->will($this->returnValue($resourcesCollection));

        $apiMock = $this->getMockBuilder(\APIClient::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'checkEmailUser', 'addDomain' ])
            ->getMock();
        $apiMock->expects($this->never())
            ->method( 'addDomain');

        /** @var $ctx PHPUnit\Framework\MockObject\MockObject | context */
        $ctx = $this->getMockBuilder(\context::class)
            ->setMethods([ 'API', 'addDomain' ])
            ->setConstructorArgs([ $apiMock, $apscMock ])
            ->getMock();
        $ctx->aps = new stdClass;
        $ctx->aps->id = uniqid('aps_id_');

        $ctx->emailCheck('["test@example.com"]');
    }

}

class APS2TestResourcesCollection extends stdClass
{
    public $login;

    protected $resources = [];

    public function __construct(array $resources = [])
    {
        $this->resources = $resources;
    }

    public function resources()
    {
        return $this->resources;
    }
}
