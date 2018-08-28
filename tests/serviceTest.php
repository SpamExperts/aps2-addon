<?php

require_once __DIR__ . "/../src/scripts/vendor/autoload.php";
require_once __DIR__ . "/../src/scripts/service.php";

class serviceTest extends \PHPUnit\Framework\TestCase
{
    public function testProvision()
    {
        $apscMock = $this->getMockBuilder(\APS\ControllerProxy::class)
            ->setMethods([ 'subscribe' ])
            ->disableOriginalConstructor()
            ->getMock();
        $apscMock->expects($this->once())
            ->method( 'subscribe' );

        /** @var $srv PHPUnit\Framework\MockObject\MockObject | service */
        $srv = $this->getMockBuilder(\service::class)
            ->setMethods([ 'APSC' ])
            ->getMock();
        $srv->expects($this->once())
            ->method( 'APSC' )
            ->will($this->returnValue($apscMock));

        $srv->provision();
    }
}
