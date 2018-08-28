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
     * @param $thisMethodMocks
     */
    public function APICall($call, $arguments, $message, $assertion, $exception, $thisMethodMocks = array())
    {
        if (! is_array($thisMethodMocks)) {
            $thisMethodMocks = array();
        }

        $this->assertEquals($assertion, call_user_func_array(array($this->clientMock($message, $exception, $thisMethodMocks), $call), $arguments));
    }

    public function APICallCasesProvider()
    {
        $resellerStub = new stdClass;
        $resellerStub->id = uniqid('admin_id_');

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

            array( "removeEmailUser",     array("email@example.com"),       "_Deleted_",        true,  false ),
            array( "removeEmailUser",     array("email@example.com"),       "_Unable_",         true,  false ),
            array( "removeEmailUser",     array("email@example.com"),       "",                 false, false ),
            array( "removeEmailUser",     array("email@example.com"),       "other",            false, false ),
            array( "removeEmailUser",     array("email@example.com"),       "",                 false, true  ),

            array( "checkEmailUser",      array("email@example.com"),       "{\"username\": \"email@example.com\"}", true, false ),
            array( "checkEmailUser",      array("email@example.com"),       "",                 false, false ),
            array( "checkEmailUser",      array("email@example.com"),       "other",            false, false ),
            array( "checkEmailUser",      array("email@example.com"),       "",                 false, true  ),

            array( "getIncoming",         array(),                          "[\"incoming\"]",   true, false ),
            array( "getIncoming",         array(),                          "",                 false, false ),
            array( "getIncoming",         array(),                          "",                 false, true ),

            array( "getIncomingDomains",  array("username"),                "100500",           true, false ),
            array( "getIncomingDomains",  array("username"),                "",                 false, false ),
            array( "getIncomingDomains",  array("username"),                "",                 false, true ),

            array( "getIncomingUsers",    array("username"),                "100500",           true, false ),
            array( "getIncomingUsers",    array("username"),                "",                 false, false ),
            array( "getIncomingUsers",    array("username"),                "",                 false, true ),

            array( "getOutgoing",         array(),                          "[\"outgoing\"]",   true, false ),
            array( "getOutgoing",         array(),                          "",                 false, false ),
            array( "getOutgoing",         array(),                          "",                 false, true ),

            array( "getOutgoingDomains",  array("username"),                "100500",           true, false ),
            array( "getOutgoingDomains",  array("username"),                "",                 false, false ),
            array( "getOutgoingDomains",  array("username"),                "",                 false, true ),

            array( "getOutgoingUsers",    array("username"),                "100500",           true, false ),
            array( "getOutgoingUsers",    array("username"),                "",                 false, false ),
            array( "getOutgoingUsers",    array("username"),                "",                 false, true ),

            array( "getArchiving",         array(),                          "[\"archiving\"]", true, false ),
            array( "getArchiving",         array(),                          "",                 false, false ),
            array( "getArchiving",         array(),                          "",                 false, true ),

            array( "getArchivingDomains",  array("username"),                "100500",           true, false ),
            array( "getArchivingDomains",  array("username"),                "",                 false, false ),
            array( "getArchivingDomains",  array("username"),                "",                 false, true ),

            array( "addReseller",         array("u", "p", "e"),      "_Success_",        true,  false ),
            array( "addReseller",         array("u", "p", "e"),      "",                 false, false ),
            array( "addReseller",         array("u", "p", "e"),      "other",            false, false ),
            array( "addReseller",         array("u", "p", "e"),      "",                 false, true  ),
            array( "addReseller",         array("u", "p", "e"),      "_alreadY_",        true,  false, array( 'updateReseller' => $this->returnValue(true) )  ),

            array( "removeReseller",      array("u", "p", "e"),      "",                 true,  false ),
            array( "removeReseller",      array("u", "p", "e"),      "success",          false, false ),
            array( "removeReseller",      array("u", "p", "e"),      "other",            false, false ),
            array( "removeReseller",      array("u", "p", "e"),      "",                 false, true  ),

            array( "updateReseller",      array("u", "p", "e"),      "_Success_",        true,  false, array( 'getReseller' => $this->returnValue($resellerStub) ) ),
            array( "updateReseller",      array("u", "p", "e"),      "",                 false, false, array( 'getReseller' => $this->returnValue($resellerStub) ) ),
            array( "updateReseller",      array("u", "p", "e"),      "other",            false, false, array( 'getReseller' => $this->returnValue($resellerStub) ) ),
            array( "updateReseller",      array("u", "p", "e"),      "",                 false, true, array( 'getReseller' => $this->returnValue($resellerStub) )  ),
            array( "updateReseller",      array("u", "p", "e"),      "_SuccEss_",        true,  false, array( 'getReseller' => $this->returnValue($resellerStub) )  ),
            array( "updateReseller",      array("u", "p", "e"),      "_SuccEss_",        true,  false, array( 'getReseller' => $this->onConsecutiveCalls(false, $resellerStub), 'addReseller' => $this->returnValue(true) )  ),

            array( "getReseller",         array("u", "p", "e"),      "[{\"id\":1, \"domainslimit\":100}]",        true,  false ),
            array( "getReseller",         array("u", "p", "e"),      "",                 false, false ),
            array( "getReseller",         array("u", "p", "e"),      "other",            false, false ),
            array( "getReseller",         array("u", "p", "e"),      "",                 false, true  ),

            array( "setResellerProducts", array("u", array()),       "_Success_",        true,  false ),
            array( "setResellerProducts", array("u", array()),       "",                 false, false ),
            array( "setResellerProducts", array("u", array()),       "other",            false, false ),
            array( "setResellerProducts", array("u", array()),       "",                 false, true  ),

            array( "getAuthTicket",       array("username"),         "ticket",        "ticket", false ),
            array( "getAuthTicket",       array("username"),         "",                 "",    false ),
            array( "getAuthTicket",       array("username"),         "",                 false, true  ),

            array( "getOwner",         array("example.com"),      "{\"username\":\"user\"}",        "user",  false ),
            array( "getOwner",         array("example.com"),      "",                 false, false ),
            array( "getOwner",         array("example.com"),      "other",            false, false ),
            array( "getOwner",         array("example.com"),      "",                 false, true  ),

            array( "assertOwner",         array("example.com", "owner"),      "{\"username\":\"user\"}",        true,  false, array( 'getOwner' => $this->returnValue("owner") ) ),
            array( "assertOwner",         array("example.com", "owner"),      "__SUccess@!__",                 true, false, array( 'getOwner' => $this->returnValue("another_owner") ) ),
            array( "assertOwner",         array("example.com", "owner"),      "other",            false, false, array( 'getOwner' => $this->returnValue("another_owner") ) ),
            array( "assertOwner",         array("example.com", "owner"),      "",                 false, true, array( 'getOwner' => $this->returnValue("another_owner") )  ),

            array( "wipeReseller",       array("username"),         "",                 true, false ),
            array( "wipeReseller",       array("username"),         "err msg goes here",false,    false ),
            array( "wipeReseller",       array("username"),         "",                 false, true  ),

            array( "getPrivateLabel",    array(),         "[\"whitelabel\"]",           true, false ),
            array( "getPrivateLabel",    array(),         "err msg goes here",          false,    false ),
            array( "getPrivateLabel",    array(),         "",                           false, true  ),

            array( "setDomainProducts",   array(
                                              "example.com",
                                              array(
                                                  'incoming' => 1,
                                                  'outgoing' => 1,
                                                  'archiving' => 1
                                              )
                                          ),                         "",                 true,  false ),
            array( "setDomainProducts",   array(
                                              "example.com",
                                              array(
                                                  'incoming' => 1,
                                                  'outgoing' => 1,
                                                  'archiving' => 1
                                              )
                                          ),                         "_Error_",          false, true  ),
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
    private function clientMock($message, $exception, $thisMethodMocks = array())
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
            ->setMethods(array_merge([ 'get' ], array_keys($thisMethodMocks)))
            ->setConstructorArgs([ $this->service() ])
            ->getMock();
        $client->method('get')->willReturn($request);

        foreach ($thisMethodMocks as $methodName => $action) {
            $client->method($methodName)->will($action);
        }

        return $client;
    }
}
