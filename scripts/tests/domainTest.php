<?php

@session_start();

class domainTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function constructor()
    {
        $domain = new domain();

        $this->assertAttributeInstanceOf('Monolog\Logger', 'logger', $domain);
        $this->assertAttributeInstanceOf('Report', 'report', $domain);
    }

    // context admin login/pass
    /**
     *
     */
    public function API()
    {
        $domain = new domain();
        $domain->context = new stdClass();
        $domain->context->service = new stdClass();

        foreach (APIClientTest::$service as $attribute => $value) {
            $domain->context->service->{$attribute} = $value;
        }

        $API = $domain->API();

        $this->assertAttributeEquals($API, 'API', $domain);
    }

    /**
     * @test
     */
    public function setAPI()
    {
        $domain = new domain();

        $domain->setAPI();

        $this->assertAttributeEquals(NULL, 'API', $domain);

        $API = new APIClient((object) APIClientTest::$service);

        $domain->setAPI($API);

        $this->assertAttributeEquals($API, 'API', $domain);
    }

    /**
     * @test
     */
    public function setAPSC()
    {
        $domain = new domain();

        $domain->setAPSC();

        $this->assertAttributeEquals(NULL, 'APSC', $domain);

        $APSC = "APSC";

        $domain->setAPSC($APSC);

        $this->assertAttributeEquals($APSC, 'APSC', $domain);
    }

    /**
     * @test
     * @depends setAPSC
     */
    public function APSC()
    {
        $domain = new domain();

        $domain->setAPSC("APSC");

        $APSC = $domain->APSC();

        $this->assertAttributeEquals($APSC, 'APSC', $domain);
    }
}
