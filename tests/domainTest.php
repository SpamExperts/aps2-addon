<?php

require_once __DIR__ . "/../src/scripts/vendor/autoload.php";
require_once __DIR__ . "/../src/scripts/domain.php";

class domainTest extends \PHPUnit\Framework\TestCase
{
    public function testUnprovision()
    {
        $domainName = 'example.com';

        $apiMock = $this->getMockBuilder(\APIClient::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'removeDomain' ])
            ->getMock();
        $apiMock->expects($this->once())
            ->method( 'removeDomain')
            ->with($this->equalTo($domainName));

        /** @var $domain PHPUnit\Framework\MockObject\MockObject | domain */
        $domain = $this->getMockBuilder(\domain::class)
            ->setMethods([ 'API' ])
            ->getMock();
        $domain->expects($this->once())
            ->method( 'API' )
            ->will($this->returnValue($apiMock));
        $domain->name = $domainName;

        $domain->unprovision();
    }
}
