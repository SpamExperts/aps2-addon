<?php

require_once __DIR__ . "/../src/scripts/vendor/autoload.php";
require_once __DIR__ . "/../src/scripts/email.php";

class emailTest extends \PHPUnit\Framework\TestCase
{
    public function testProvision()
    {
        $ctxMock = $this->getMockBuilder(\context::class)
            ->setMethods([ 'emailProtect' ])
            ->getMock();
        $ctxMock->expects($this->once())
            ->method( 'emailProtect');

        /** @var $email PHPUnit\Framework\MockObject\MockObject | email */
        $email = $this->getMockBuilder(\email::class)
            ->setMethods([ 'API' ])
            ->getMock();
        $email->expects($this->never())
            ->method( 'API' );
        $email->status = true;
        $email->context = $ctxMock;
        $email->email = new stdClass;
        $email->email->aps = new stdClass;
        $email->email->aps->id = uniqid('aps_id_');

        $email->provision();
    }

    public function testUnprovision()
    {
        $emailAddress = 'test@example.com';

        $apiMock = $this->getMockBuilder(\APIClient::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'removeEmailUser' ])
            ->getMock();
        $apiMock->expects($this->once())
            ->method( 'removeEmailUser')
            ->with($this->equalTo($emailAddress));

        /** @var $email PHPUnit\Framework\MockObject\MockObject | email */
        $email = $this->getMockBuilder(\email::class)
            ->setMethods([ 'API' ])
            ->getMock();
        $email->expects($this->once())
            ->method( 'API')
            ->willReturn($apiMock);
        $email->name = $emailAddress;

        $email->unprovision();
    }

    public function testGetControlPanelLoginLink()
    {
        $emailAddress = 'test@example.com';
        $authTicket = uniqid('auth_ticket_');

        $ctxMock = $this->getMockBuilder(\context::class)
            ->setMethods([ 'getAuthTicket' ])
            ->getMock();
        $ctxMock->expects($this->once())
            ->method( 'getAuthTicket')
            ->with($this->equalTo($emailAddress))
            ->willReturn($authTicket);

        /** @var $email PHPUnit\Framework\MockObject\MockObject | email */
        $email = $this->getMockBuilder(\email::class)
            ->setMethods([ 'API' ])
            ->getMock();
        $email->expects($this->never())
            ->method( 'API');
        $email->name = $emailAddress;
        $email->context = $ctxMock;

        $this->assertEquals($authTicket, $email->getControlPanelLoginLink());
    }
}
