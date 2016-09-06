<?php

class serviceTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function constructor()
    {
        $service = new service();

        $this->assertAttributeInstanceOf('Monolog\Logger', 'logger', $service);
        $this->assertAttributeInstanceOf('Report',         'report', $service);

        // private label assertions
        $this->assertEquals('none',     service::$PL[NULL]);
        $this->assertEquals('none',     service::$PL[-1]);
        $this->assertEquals('none',     service::$PL[0]);
        $this->assertEquals('standard', service::$PL[1]);
        $this->assertEquals('premium',  service::$PL[2]);
    }

    /**
     * @test
     */
    public function API()
    {
        $service = new service();

        foreach (APIClientTest::$service as $attribute => $value) {
            $service->{$attribute} = $value;
        }

        $API = $service->API();

        $this->assertAttributeEquals($API, 'API', $service);
    }

    /**
     * @test
     */
    public function setAPI()
    {
        $service = new service();

        foreach (APIClientTest::$service as $attribute => $value) {
            $service->{$attribute} = $value;
        }

        $service->setAPI();

        $this->assertAttributeEquals(NULL, 'API', $service);

        $API = new APIClient($service);

        $service->setAPI($API);

        $this->assertAttributeEquals($API, 'API', $service);
    }

    /**
     * @test
     */
    public function setAPSC()
    {
        $service = new service();

        $service->setAPSC();

        $this->assertAttributeEquals(NULL, 'APSC', $service);

        $APSC = "APSC";

        $service->setAPSC($APSC);

        $this->assertAttributeEquals($APSC, 'APSC', $service);
    }

    /**
     * @test
     * @depends setAPSC
     */
    public function APSC()
    {
        $service = new service();

        $service->setAPSC("APSC");

        $APSC = $service->APSC();

        $this->assertAttributeEquals($APSC, 'APSC', $service);
    }

    /**
     * @test
     * @depends setAPI
     * @depends setAPSC
     * @dataProvider generalContextCasesProvider
     */
    public function onContextAvailable($resellerResult, $resellerExpectation, $productResult, $productExpectation, $domainAutoAdd, $domainExpectation, $emailAutoAdd, $emailExpectation)
    {
        $service = new service();

        $controller = $this->getMockBuilder('\APS\ControllerProxy')->disableOriginalConstructor()->getMock();
        $context    = $this->getMockBuilder('context')->disableOriginalConstructor()->getMock();
        $API        = $this->getMockBuilder('APIClient')->disableOriginalConstructor()->getMock();

        $context->admins = [new stdClass()];
        $context->aps = new stdClass();

        $context->admins[0]->login = "reseller";
        $context->admin[0]->email = "email@domain.test";
        $context->aps->id      = "id";

        $products = array();
        foreach (array('incoming', 'outgoing', 'archiving', 'private_label') as $productName) {
            $context->{$productName} = new stdClass();
            $context->{$productName}->limit = 2;
            $products[$productName] = 2;
        }

        $products['private_label'] = service::$PL[$products['private_label']];

        $API->method('addReseller')        ->willReturn($resellerResult);
        $API->method('setResellerProducts')->willReturn($productResult);

        $API->expects($this->exactly($resellerExpectation))
            ->method('addReseller')
            ->with(
                $this->equalTo($context->admins[0]->login),
                $this->equalTo(md5($context->aps->id)),
                $this->equalTo($context->admins[0]->email)
            );
        $API->expects($this->exactly($productExpectation))
            ->method('setResellerProducts')
            ->with($this->equalTo($context->admins[0]->login), $this->equalTo($products));

        $service->auto_add_domains = $domainAutoAdd;
        $service->auto_add_emails  = $emailAutoAdd;

        $context->expects($this->exactly($domainExpectation))->method('domainProtectAll');
        $context->expects($this->exactly($emailExpectation))->method('emailProtectAll');

        $controller->method('getResource')->willReturn($context);

        $service->setAPI($API);
        $service->setAPSC($controller);

        $event = new stdClass();
        $event->source = new stdClass();
        $event->source->id = "id";

        $service->onContextAvailable($event);
    }

    public function generalContextCasesProvider()
    {
        return array(
            array( true,   1, true,   1,  true,   1,  true,   1 ),
            array( true,   1, true,   1,  true,   1,  false,  0 ),
            array( true,   1, true,   1,  false,  0,  true,   1 ),
            array( true,   1, true,   1,  false,  0,  false,  0 ),
            array( true,   1, false,  1,  NULL,   0,  NULL,   0 ),
            array( false,  1, NULL,   0,  NULL,   0,  NULL,   0 ),
        );
    }
}
