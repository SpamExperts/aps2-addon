<?php

require_once __DIR__ . "/../src/scripts/vendor/autoload.php";

@session_start();

class APIClientTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function constructor()
    {
        $client = new APIClient($service = $this->service());
        $this->assertAttributeInstanceOf('Logger', 'logger', $client);
        $this->assertAttributeInstanceOf('Report', 'report', $client);
        $this->assertEquals(array($service->username, $service->password), $client->getConfig()[Guzzle\Http\Client::REQUEST_OPTIONS]['auth']);
        $this->assertAttributeEquals("https://$service->hostname", 'baseUrl', $client);

        $client = new APIClient($service = $this->service(array('ssl' => false)));
        $this->assertAttributeEquals("http://$service->hostname", 'baseUrl', $client);
    }

    /**
     * @test
     * @depends      constructor
     * @dataProvider APICallCasesProvider
     * @param $call
     * @param $arguments
     * @param $message
     * @param $assertion
     * @param $exception
     */
    public function APICall($call, $arguments, $message, $assertion, $exception)
    {
        $this->assertEquals($assertion, call_user_func_array(array($this->clientMock($message, $exception), $call), $arguments));
    }

    public function APICallCasesProvider()
    {
        return array(
            array( "addDomain",           array("domain.com"),       "{\"messages\": {\"success\": [\"Domain 'domain.com' added\"]}}", true, false ),
            array( "addDomain",           array("domain.com"),       "{\"messages\": {\"error\": [\"Domain already exists.\"]}}", true, false ),
            array( "addDomain",           array("domain.com"),       "",                 false, false ),
            array( "addDomain",           array("domain.com"),       "other",            false, false ),
            array( "addDomain",           array("domain.com"),       "",                 false, true  ),

            array( "removeDomain",        array("domain.com"),       "_Removed_",        true,  false ),
            array( "removeDomain",        array("domain.com"),       "",                 false, false ),
            array( "removeDomain",        array("domain.com"),       "other",            false, false ),
            array( "removeDomain",        array("domain.com"),       "",                 false, true  ),

            array( "checkDomain",         array("domain.com"),       '{"domain.com":1}', true,  false ),
            array( "checkDomain",         array("domain.com"),       '{"domain.com":0}', false, false ),
            array( "checkDomain",         array("domain.com"),       "",                 false, false ),
            array( "checkDomain",         array("domain.com"),       "other",            false, false ),
            array( "checkDomain",         array("domain.com"),       "",                 false, true  ),

            array( "addDomainUser",       array("domain.com"),       "_Saved_",          true,  false ),
            array( "addDomainUser",       array("domain.com"),       "_Already_",        true,  false ),
            array( "addDomainUser",       array("domain.com"),       "",                 false, false ),
            array( "addDomainUser",       array("domain.com"),       "other",            false, false ),
            array( "addDomainUser",       array("domain.com"),       "",                 false, true  ),

            array( "removeDomainUser",    array("domain.com"),       "_Deleted_",        true,  false ),
            array( "removeDomainUser",    array("domain.com"),       "_Unable_",         true,  false ),
            array( "removeDomainUser",    array("domain.com"),       "",                 false, false ),
            array( "removeDomainUser",    array("domain.com"),       "other",            false, false ),
            array( "removeDomainUser",    array("domain.com"),       "",                 false, true  ),

            array( "checkDomainUser",     array("Domain.com"),       "{\"username\": \"domain.com\"}", true, false ),
            array( "checkDomainUser",     array("domain.com"),       "",                 false, false ),
            array( "checkDomainUser",     array("domain.com"),       "other",            false, false ),
            array( "checkDomainUser",     array("domain.com"),       "",                 false, true  ),

            array( "addEmailUser",        array("email@domain.com"), "_Saved_",          true,  false ),
            array( "addEmailUser",        array("email@domain.com"), "_Already_",        true,  false ),
            array( "addEmailUser",        array("email@domain.com"), "",                 false, false ),
            array( "addEmailUser",        array("email@domain.com"), "other",            false, false ),
            array( "addEmailUser",        array("email@domain.com"), "",                 false, true  ),
            array( "addEmailUser",        array("other"),            "",                 false, false ),

            array( "addReseller",         array("u", "p", "e"),      "_Success_",        true,  false ),
            array( "addReseller",         array("u", "p", "e"),      "",                 false, false ),
            array( "addReseller",         array("u", "p", "e"),      "other",            false, false ),
            array( "addReseller",         array("u", "p", "e"),      "",                 false, true  ),

            array( "removeReseller",      array("u", "p", "e"),      "",                 true,  false ),
            array( "removeReseller",      array("u", "p", "e"),      "success",          false, false ),
            array( "removeReseller",      array("u", "p", "e"),      "other",            false, false ),
            array( "removeReseller",      array("u", "p", "e"),      "",                 false, true  ),

            array( "setResellerProducts", array("u", array()),       "_Success_",        true,  false ),
            array( "setResellerProducts", array("u", array()),       "",                 false, false ),
            array( "setResellerProducts", array("u", array()),       "other",            false, false ),
            array( "setResellerProducts", array("u", array()),       "",                 false, true  ),

            array( "getAuthTicket",       array("username"),         "ticket",        "ticket", false ),
            array( "getAuthTicket",       array("username"),         "",                 "",    false ),
            array( "getAuthTicket",       array("username"),         "",                 false, true  ),
        );
    }



    private function service($data = array())
    {
        return (object) array(
            'hostname' => isset($data['hostname']) ? $data['hostname'] : 'hostname.com',
            'username' => isset($data['username']) ? $data['username'] : 'username',
            'password' => isset($data['password']) ? $data['password'] : 'password',
            'ssl'      => isset($data['ssl'])      ? $data['ssl']      :  true,
        );
    }

    // Mock Guzzle's 'get' method with a custom response or raise an exception; get the rest of the client intact for testing
    private function clientMock($message, $exception)
    {
        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->setMethods([ 'getBody' ])
            ->disableOriginalConstructor()
            ->getMock();
        $response->method('getBody')
            ->willReturn($message);

        $request = $this->getMockBuilder('Guzzle\Http\Message\Request')
            ->setMethods([ 'send' ])
            ->disableOriginalConstructor()
            ->getMock();
        $request->method('send')->will($this->returnCallback(function () use ($response, $exception) {
            if (!$exception) {
                return $response;
            } else {
                throw new Exception("Controlled Test Failure");
            }
        }));

        $client = $this->getMockBuilder('APIClient')
            ->setMethods([ 'get' ])
            ->setConstructorArgs([ $this->service() ])
            ->getMock();
        $client->method('get')->willReturn($request);

        return $client;
    }
}
