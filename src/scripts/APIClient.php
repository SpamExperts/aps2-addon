<?php

class APIClient extends Guzzle\Http\Client
{
    /** @var $logger Logger */
    private $logger;
    /** @var $report Report */
    private $report;

    public function __construct($service /** @var $service service */)
    {
        parent::__construct("http" . ($service->ssl ? 's' : '') . "://{$service->hostname}");
        $this->report  = new Report($this->logger = new Logger("API_Client"));
        $this->setUserAgent("ProSpamFilter/" . App::VERSION);
        $this->setDefaultOption('auth', array($service->username, $service->password));
        $this->setDefaultOption('verify', false);
        $this->addSubscriber(new Guzzle\Plugin\Log\LogPlugin(new Guzzle\Log\MonologLogAdapter($this->logger), Guzzle\Log\MessageFormatter::DEBUG_FORMAT));
    }

    ## Domain

    public function addDomain($domain, $destinations = null, $aliases = null)
    {
        $this->logger->debug(__METHOD__ . ": " . "Domain addition request");

        $result = false;
        $rawResponses = '';

        try {
            $domainAddResponse = $this->get(
                "/api/domain/add/domain/$domain/format/json"  .
                (is_array($destinations) ? "/destinations/" . json_encode($destinations) : "") .
                (is_array($aliases)      ? "/aliases/"      . json_encode($aliases)      : "")
            );
            $domainAddResponseRaw = $domainAddResponse->send()->getBody(true); // skip for 5.4 (GuzzleHttp)
            $rawResponses .= $domainAddResponseRaw;
            $domainAddResponseData = json_decode($domainAddResponseRaw, true);

            if (!empty($domainAddResponseData['messages']['success'])
                && in_array(
                    sprintf(
                        "Domain '%s' added",
                        function_exists('idn_to_utf8') ? idn_to_utf8($domain) : $domain
                    ),
                    $domainAddResponseData['messages']['success']
                )) {
                // The domain has been added successfully
                $result = true;
            } elseif (!empty($domainAddResponseData['messages']['error'])
                && in_array("Domain already exists.", $domainAddResponseData['messages']['error'])) {
                // The domain laready exists in the spamfilter
                // Check it's owner. We cannot just use the "domain/getowner" call here as otherwise
                // if a domain belongs to one of sub-admins of current admin the check would fail
                $domainGetproductsResponse = $this->get("/api/domain/getproducts/domain/$domain/format/json");
                $domainGetproductsResponseRaw = $domainGetproductsResponse->send()->getBody(true);
                $rawResponses .= $domainGetproductsResponseRaw;
                $domainGetproductsResponseData = json_decode($domainGetproductsResponseRaw, true);
                if (empty($domainGetproductsResponseData['messages']['error'])
                    || ! is_array($domainGetproductsResponseData['messages']['error'])
                    || ! in_array(
                        sprintf(
                            "You are not allowed to manage the domain '%s'",
                            function_exists('idn_to_utf8') ? idn_to_utf8($domain) : $domain
                        ),
                        $domainGetproductsResponseData['messages']['error']
                    )) {
                    $result = true;
                }
            }
        } catch (Exception $e) {
            $this->report->add("Error: " . $e->getMessage() . " | Code: " . $e->getCode(), Report::ERROR);
        }

        $this->logger->debug(__METHOD__ . ": Result: " . var_export($result, true)
            . " Responses: " . var_export($rawResponses, true));
        
        return $result;
    }

    public function removeDomain($domain)
    {
        $this->logger->debug(__FUNCTION__ . ": " . "Domain removal request");

        try {
            $response = $this->get('/api/domain/remove/domain/' . $domain);
            $response = $response->send()->getBody(true);
            $result = stripos($response, 'removed') !== false;
        } catch (Exception $e) {
            $response = "Error: " . $e->getMessage() . " | Code: " . $e->getCode();
            $this->report->add($response, Report::ERROR);
            $result = false;
        }

        $this->logger->debug(__FUNCTION__ . ": Result: " . var_export($result, true) . " Response: " . var_export($response, true));

        return $result;
    }

    public function checkDomain($domain)
    {
        $this->logger->debug(__FUNCTION__ . ": " . "Domain protection check request");

        try {
            $response = $this->get("/api/domain/exists/domain/$domain");
            $response = $response->send()->getBody(true); // skip for 5.4 (GuzzleHttp)
            $result = in_array(1, (array)json_decode($response, true), true);
        } catch (Exception $e) {
            $response = "Error: " . $e->getMessage() . " | Code: " . $e->getCode();
            $this->report->add($response, Report::ERROR);
            $result = false;
        }

        $this->logger->debug(__FUNCTION__ . ": Result: " . var_export($result, true) . " Response: " . var_export($response, true));

        return $result;
    }

    public function setDomainProducts($domain, $products)
    {
        $this->logger->debug(__FUNCTION__ . ": " . "Domain addition request");
        try {
            $response = $this->get("/api/domain/setproducts/domain/$domain" .
                (isset($products["incoming"])  ? "/incoming/"  . $products["incoming"]  : "") .
                (isset($products["outgoing"])  ? "/outgoing/"  . $products["outgoing"]  : "") .
                (isset($products["archiving"]) ? "/archiving/" . $products["archiving"] : "")
            );
            $response = $response->send()->getBody(true); // skip for 5.4 (GuzzleHttp)
            $result = stripos($response, 'error') === false;
        } catch (Exception $e) {
            $response = "Error: " . $e->getMessage() . " | Code: " . $e->getCode();
            $this->report->add($response, Report::ERROR);
            $result = false;
        }

        $this->logger->debug(__FUNCTION__ . ": Result: " . var_export($result, true) . " Response: " . var_export($response, true));

        return $result;
    }


    ## Domain user

    public function addDomainUser($domain)
    {
        $this->logger->debug(__FUNCTION__ . ": " . "Domain user addition request");

        $password = substr(str_shuffle(md5(microtime())), 0, 10);

        try {
            $response = $this->get("/api/domainuser/add/domain/$domain/password/$password/email/contact@$domain");
            $response = $response->send()->getBody(true);
            $result = stripos($response, 'saved') !== false || stripos($response, 'already') !== false;
        } catch (Exception $e) {
            $response = "Error: " . $e->getMessage() . " | Code: " . $e->getCode();
            $this->report->add($response, Report::ERROR);
            $result = false;
        }

        $this->logger->debug(__FUNCTION__ . ": Result: " . var_export($result, true) . " Response: " . var_export($response, true));

        return $result;
    }

    public function removeDomainUser($domain)
    {
        $this->logger->debug(__FUNCTION__ . ": " . "Domain user removal request");

        try {
            $response = $this->get("/api/domainuser/remove/username/$domain");
            $response = $response->send()->getBody(true);
            $result = stripos($response, 'deleted') !== false || stripos($response, 'unable') !== false;
        } catch (Exception $e) {
            $response = "Error: " . $e->getMessage() . " | Code: " . $e->getCode();
            $this->report->add($response, Report::ERROR);
            $result = false;
        }

        $this->logger->debug(__FUNCTION__ . ": Result: " . var_export($result, true) . " Response: " . var_export($response, true));

        return $result;
    }

    public function checkDomainUser($domain)
    {
        $this->logger->debug(__FUNCTION__ . ": " . "Domain user protection check request");

        try {
            $response = $this->get("/api/user/get/username/" . $domain);
            $response = $response->send()->getBody(true);
            if (!empty($response)) {
                $userData = json_decode($response, true);
                $result = !empty($userData['username'])
                    && strtolower($userData['username']) == strtolower($domain);
            } else {
                $result = false;
            }
        } catch (Exception $e) {
            $response = "Error: " . $e->getMessage() . " | Code: " . $e->getCode();
            $this->report->add($response, Report::ERROR);
            $result = false;
        }

        $this->logger->debug(__FUNCTION__ . ": Result: " . var_export($result, true) . " Response: " . var_export($response, true));

        return $result;
    }


    ## Email user

    public function addEmailUser($email)
    {
        $this->logger->debug(__FUNCTION__ . ": " . "Email user addition request");

        if (strpos($email, '@') === false) {
            return false;
        }

        list($username, $domain) = explode('@', $email);
        $password = substr(str_shuffle(md5(microtime())), 0, 10);

        try {
            $response = $this->get("/api/emailusers/add/username/$username/password/$password/domain/$domain");
            $response = $response->send()->getBody(true);
            $result = stripos($response, 'saved') !== false || stripos($response, 'already') !== false;
        } catch (Exception $e) {
            $response = "Error: " . $e->getMessage() . " | Code: " . $e->getCode();
            $this->report->add($response, Report::ERROR);
            $result = false;
        }

        $this->logger->debug(__FUNCTION__ . ": Result: " . var_export($result, true) . " Response: " . var_export($response, true));

        return $result;
    }

    public function removeEmailUser($email)
    {
        $this->logger->debug(__FUNCTION__ . ": " . "Email user removal request");

        try {
            $response = $this->get("/api/emailusers/remove/username/$email");
            $response = $response->send()->getBody(true);
            $result = stripos($response, 'deleted') !== false || stripos($response, 'unable') !== false;
        } catch (Exception $e) {
            $response = "Error: " . $e->getMessage() . " | Code: " . $e->getCode();
            $this->report->add($response, Report::ERROR);
            $result = false;
        }

        $this->logger->debug(__FUNCTION__ . ": Result: " . var_export($result, true) . " Response: " . var_export($response, true));

        return $result;
    }

    public function checkEmailUser($email)
    {
        $this->logger->debug(__FUNCTION__ . ": " . "Email user protection check request");

        try {
            $response = $this->get("/api/user/get/username/" . $email);
            $response = $response->send()->getBody(true);
            if (!empty($response)) {
                $userData = json_decode($response, true);
                $result = !empty($userData['username']) 
                    && strtolower($userData['username']) == strtolower($email);
            } else {
                $result = false;
            }
        } catch (Exception $e) {
            $response = "Error: " . $e->getMessage() . " | Code: " . $e->getCode();
            $this->report->add($response, Report::ERROR);
            $result = false;
        }

        $this->logger->debug(__FUNCTION__ . ": Result: " . var_export($result, true) . " Response: " . var_export($response, true));

        return $result;
    }



    ## Incoming

    public function getIncoming()
    {
        $this->logger->debug(__FUNCTION__ . ": " . "Get incoming product availability request");

        try {
            $response = $this->get("/api/productslist/get");
            $response = $response->send()->getBody(true);
            $result = json_decode($response);
            $result = is_array($result) ? in_array('incoming', $result) : false;
        } catch (Exception $e) {
            $response = "Error: " . $e->getMessage() . " | Code: " . $e->getCode();
            $this->report->add($response, Report::ERROR);
            $result = false;
        }

        $this->logger->debug(__FUNCTION__ . ": Result: " . var_export($result, true) . " Response: " . var_export($response, true));

        return $result;
    }

    public function getIncomingDomains($reseller)
    {
        $this->logger->debug(__FUNCTION__ . ": " . "Get incoming domains request");

        try {
            $response = $this->get("/api/reseller/domainswithproduct/username/$reseller/product/incoming");
            $response = $response->send()->getBody(true);
            $result = is_numeric($response) ? (int)$response : 0;
        } catch (Exception $e) {
            $response = "Error: " . $e->getMessage() . " | Code: " . $e->getCode();
            $this->report->add($response, Report::ERROR);
            $result = 0;
        }

        $this->logger->debug(__FUNCTION__ . ": Result: " . var_export($result, true) . " Response: " . var_export($response, true));

        return $result;
    }

    public function getIncomingUsers($reseller)
    {
        $this->logger->debug(__FUNCTION__ . ": " . "Get incoming valid recipients request");

        try {
            $response = $this->get("/api/reseller/getvalidrecipientcount/username/$reseller");
            $response = $response->send()->getBody(true);
            $result = is_numeric($response) ? (int)$response : 0;
        } catch (Exception $e) {
            $response = "Error: " . $e->getMessage() . " | Code: " . $e->getCode();
            $this->report->add($response, Report::ERROR);
            $result = 0;
        }

        $this->logger->debug(__FUNCTION__ . ": Result: " . var_export($result, true) . " Response: " . var_export($response, true));

        return $result;
    }

    /**
     * @return bool|int
     *
     * @codeCoverageIgnore
     */
    public function getIncomingBandwidth()
    {
        $this->logger->debug(__FUNCTION__ . ": " . "Get incoming bandwidth request");

        try {
            /*$response = $this->get("/api"); // Need support
            $response = $response->send()->getBody(true);*/
            $response = "Need support";
            $result = 100; // fake value, clear API call is supported
        } catch (Exception $e) {
            $response = "Error: " . $e->getMessage() . " | Code: " . $e->getCode();
            $this->report->add($response, Report::ERROR);
            $result = false;
        }

        $this->logger->debug(__FUNCTION__ . ": Result: " . var_export($result, true) . " Response: " . var_export($response, true));

        return $result;
    }



    ## Outgoing

    public function getOutgoing()
    {
        $this->logger->debug(__FUNCTION__ . ": " . "Get outgoing product availability request");

        try {
            $response = $this->get("/api/productslist/get");
            $response = $response->send()->getBody(true);
            $result = json_decode($response);
            $result = is_array($result) ? in_array('outgoing', $result) : false;
        } catch (Exception $e) {
            $response = "Error: " . $e->getMessage() . " | Code: " . $e->getCode();
            $this->report->add($response, Report::ERROR);
            $result = false;
        }

        $this->logger->debug(__FUNCTION__ . ": Result: " . var_export($result, true) . " Response: " . var_export($response, true));

        return $result;
    }

    public function getOutgoingDomains($reseller)
    {
        $this->logger->debug(__FUNCTION__ . ": " . "Get outgoing domains request");

        try {
            $response = $this->get("/api/reseller/domainswithproduct/username/$reseller/product/outgoing");
            $response = $response->send()->getBody(true);
            $result = is_numeric($response) ? (int)$response : 0;
        } catch (Exception $e) {
            $response = "Error: " . $e->getMessage() . " | Code: " . $e->getCode();
            $this->report->add($response, Report::ERROR);
            $result = 0;
        }

        $this->logger->debug(__FUNCTION__ . ": Result: " . var_export($result, true) . " Response: " . var_export($response, true));

        return $result;
    }

    public function getOutgoingUsers($reseller)
    {
        $this->logger->debug(__FUNCTION__ . ": " . "Get outgoing valid recipients request");

        try {
            $response = $this->get("/api/reseller/getoutgoingusercount/username/$reseller");
            $response = $response->send()->getBody(true);
            $result = is_numeric($response) ? (int)$response : 0;
        } catch (Exception $e) {
            $response = "Error: " . $e->getMessage() . " | Code: " . $e->getCode();
            $this->report->add($response, Report::ERROR);
            $result = 0;
        }

        $this->logger->debug(__FUNCTION__ . ": Result: " . var_export($result, true) . " Response: " . var_export($response, true));

        return $result;
    }

    /**
     * @return bool|int
     *
     * @codeCoverageIgnore
     */
    public function getOutgoingBandwidth()
    {
        $this->logger->debug(__FUNCTION__ . ": " . "Get outgoing bandwidth request");

        try {
            /*$response = $this->get("/api"); // Need support
            $response = $response->send()->getBody(true);*/
            $response = "Need support";
            $result = 100; // fake value, clear when API call is supported
        } catch (Exception $e) {
            $response = "Error: " . $e->getMessage() . " | Code: " . $e->getCode();
            $this->report->add($response, Report::ERROR);
            $result = false;
        }

        $this->logger->debug(__FUNCTION__ . ": Result: " . var_export($result, true) . " Response: " . var_export($response, true));

        return $result;
    }


    ## Archiving

    public function getArchiving()
    {
        $this->logger->debug(__FUNCTION__ . ": " . "Get archiving product availability request");

        try {
            $response = $this->get("/api/productslist/get");
            $response = $response->send()->getBody(true);
            $result = json_decode($response);
            $result = is_array($result) ? in_array('archiving', $result) : false;
        } catch (Exception $e) {
            $response = "Error: " . $e->getMessage() . " | Code: " . $e->getCode();
            $this->report->add($response, Report::ERROR);
            $result = false;
        }

        $this->logger->debug(__FUNCTION__ . ": Result: " . var_export($result, true) . " Response: " . var_export($response, true));

        return $result;
    }

    public function getArchivingDomains($reseller)
    {
        $this->logger->debug(__FUNCTION__ . ": " . "Get archiving domains request");

        try {
            $response = $this->get("/api/reseller/domainswithproduct/username/$reseller/product/archiving");
            $response = $response->send()->getBody(true);
            $result = is_numeric($response) ? (int)$response : 0;
        } catch (Exception $e) {
            $response = "Error: " . $e->getMessage() . " | Code: " . $e->getCode();
            $this->report->add($response, Report::ERROR);
            $result = 0;
        }

        $this->logger->debug(__FUNCTION__ . ": Result: " . var_export($result, true) . " Response: " . var_export($response, true));

        return $result;
    }

    /**
     * @return bool|int
     *
     * @codeCoverageIgnore
     */
    public function getArchivingAccounts()
    {
        $this->logger->debug(__FUNCTION__ . ": " . "Get archiving accounts request");

        try {
            /*$response = $this->get("/api"); // Need support
            $response = $response->send()->getBody(true);*/
            $response = "Need support";
            $result = 100; // fake value, clear when API call is supported
        } catch (Exception $e) {
            $response = "Error: " . $e->getMessage() . " | Code: " . $e->getCode();
            $this->report->add($response, Report::ERROR);
            $result = false;
        }

        $this->logger->debug(__FUNCTION__ . ": Result: " . var_export($result, true) . " Response: " . var_export($response, true));

        return $result;
    }

    /**
     * @return bool|int
     *
     * @codeCoverageIgnore
     */
    public function getArchivingSpace()
    {
        $this->logger->debug(__FUNCTION__ . ": " . "Get archiving space request");

        try {
            /*$response = $this->get("/api"); // Need support
            $response = $response->send()->getBody(true);*/
            $response = "Need support";
            $result = 100; // fake value, clear when API call is supported
        } catch (Exception $e) {
            $response = "Error: " . $e->getMessage() . " | Code: " . $e->getCode();
            $this->report->add($response, Report::ERROR);
            $result = false;
        }

        $this->logger->debug(__FUNCTION__ . ": Result: " . var_export($result, true) . " Response: " . var_export($response, true));

        return $result;
    }

    /**
     * @return bool|int
     *
     * @codeCoverageIgnore
     */
    public function getArchivingPeriod()
    {
        $this->logger->debug(__FUNCTION__ . ": " . "Get archiving period request");

        try {
            /*$response = $this->get("/api"); // Need support
            $response = $response->send()->getBody(true);*/
            $response = "Need support";
            $result = 100; // fake value, clear when API call is supported
        } catch (Exception $e) {
            $response = "Error: " . $e->getMessage() . " | Code: " . $e->getCode();
            $this->report->add($response, Report::ERROR);
            $result = false;
        }

        $this->logger->debug(__FUNCTION__ . ": Result: " . var_export($result, true) . " Response: " . var_export($response, true));

        return $result;
    }

    /**
     * @param $domain
     * @param $quota
     *
     * @codeCoverageIgnore
     */
    public function setArchivingQuotaSoft($domain, $quota)
    {
        $this->logger->debug(__FUNCTION__ . ": " . "Set soft archiving quota request");

        try {
            $response = $this->get("/api/domain/setsoftquota/domain/$domain/quota/$quota");
            $response = $response->send()->getBody(true);
        } catch (Exception $e) {
            $response = "Error: " . $e->getMessage() . " | Code: " . $e->getCode();
            $this->report->add($response, Report::ERROR);
        }

        $this->logger->debug(__FUNCTION__ . ": Response: " . var_export($response, true));
    }

    /**
     * @param $domain
     * @param $quota
     *
     * @codeCoverageIgnore
     */
    public function setArchivingQuotaHard($domain, $quota)
    {
        $this->logger->debug(__FUNCTION__ . ": " . "Set soft archiving quota request");

        try {
            $response = $this->get("/api/domain/sethardquota/domain/$domain/quota/$quota");
            $response = $response->send()->getBody(true);
        } catch (Exception $e) {
            $response = "Error: " . $e->getMessage() . " | Code: " . $e->getCode();
            $this->report->add($response, Report::ERROR);
        }

        $this->logger->debug(__FUNCTION__ . ": Response: " . var_export($response, true));
    }


    ## Reseller

    public function addReseller($username, $password, $email, $domainLimit = 0)
    {
        $this->logger->debug(__FUNCTION__ . ": " . "Add reseller request");

        try {
            $response = $this->get("/api/reseller/add/username/$username/password/$password/email/$email/domainslimit/$domainLimit/api_usage/1");
            $response = $response->send()->getBody(true);
            $result = stripos($response, 'already') !== false
                ? $this->updateReseller($username, $password, $email, $domainLimit)
                : stripos($response, 'success') !== false;
        } catch (Exception $e) {
            $response = "Error: " . $e->getMessage() . " | Code: " . $e->getCode();
            $this->report->add($response, Report::ERROR);
            $result = false;
        }

        $this->logger->debug(__FUNCTION__ . ": Result: " . var_export($result, true) . " Response: " . var_export($response, true));

        return $result;
    }

    public function removeReseller($username)
    {
        $this->logger->debug(__FUNCTION__ . ": " . "Remove reseller request");

        try {
            $response = $this->get("/api/reseller/remove/username/$username");
            $response = $response->send()->getBody(true);
            $result = empty($response);
        } catch (Exception $e) {
            $response = "Error: " . $e->getMessage() . " | Code: " . $e->getCode();
            $this->report->add($response, Report::ERROR);
            $result = false;
        }

        $this->logger->debug(__FUNCTION__ . ": Result: " . var_export($result, true) . " Response: " . var_export($response, true));

        return $result;
    }

    public function updateReseller($username, $password, $email, $domainLimit = 0)
    {
        $this->logger->debug(__FUNCTION__ . ": " . "Update reseller request");

        try {
            $reseller = $this->getReseller($username);
            if (empty($reseller)) {
                $this->addReseller($username, $password, $email, $domainLimit);
                $reseller = $this->getReseller($username);
            }
            $response = $this->get("/api/reseller/update/id/{$reseller->id}/username/$username/password/$password/email/$email/domainslimit/$domainLimit");
            $response = $response->send()->getBody(true);
            $result = stripos($response, 'success') !== false;
        } catch (Exception $e) {
            $response = "Error: " . $e->getMessage() . " | Code: " . $e->getCode();
            $this->report->add($response, Report::ERROR);
            $result = false;
        }

        $this->logger->debug(__FUNCTION__ . ": Result: " . var_export($result, true) . " Response: " . var_export($response, true));

        return $result;
    }

    public function getReseller($username)
    {
        $this->logger->debug(__FUNCTION__ . ": " . "Get reseller request");

        try {
            $response = $this->get("/api/reseller/list/username/$username/no_domains/1/");
            $response = $response->send()->getBody(true);
            if (stripos($response, 'domainslimit') !== false) {
                $decodedResponse = json_decode($response);
                $result = (array) array_pop($decodedResponse);
            } else {
                $result = null;
            }
            $result = is_array($result) ? array_pop($result) : null;
        } catch (Exception $e) {
            $response = "Error: " . $e->getMessage() . " | Code: " . $e->getCode();
            $this->report->add($response, Report::ERROR);
            $result = null;
        }

        $this->logger->debug(__FUNCTION__ . ": Result: " . var_export($result, true) . " Response: " . var_export($response, true));

        return $result;
    }

    public function setResellerProducts($username, $settings)
    {
        $this->logger->debug(__FUNCTION__ . ": " . "Set reseller products request");

        try {
            $response = $this->get("/api/reseller/setproducts/username/$username" .
                (isset($settings['incoming'])      ? "/incoming/{$settings['incoming']}"          : '') .
                (isset($settings['outgoing'])      ? "/outgoing/{$settings['outgoing']}"          : '') .
                (isset($settings['archiving'])     ? "/archiving/{$settings['archiving']}"        : '') .
                (isset($settings['private_label']) ? "/privatelabel/{$settings['private_label']}" : '')
            );
            $response = $response->send()->getBody(true);
            $result = stripos($response, 'success') !== false;
        } catch (Exception $e) {
            $response = "Error: " . $e->getMessage() . " | Code: " . $e->getCode();
            $this->report->add($response, Report::ERROR);
            $result = false;
        }

        $this->logger->debug(__FUNCTION__ . ": Result: " . var_export($result, true) . " Response: " . var_export($response, true));

        return $result;
    }

    public function getOwner($domain)
    {
        $this->logger->debug(__FUNCTION__ . ": " . "Get domain owner");

        try {
            $response = $this->get("/api/domain/getowner/domain/$domain");
            $response = $response->send()->getBody(true);
            $owner = json_decode($response);
            $result = isset($owner->username) ? $owner->username : null;
        } catch (Exception $e) {
            $response = "Error: " . $e->getMessage() . " | Code: " . $e->getCode();
            $this->report->add($response, Report::ERROR);
            $result = null;
        }

        $this->logger->debug(__FUNCTION__ . ": Result: " . var_export($result, true) . " Response: " . var_export($response, true));

        return $result;
    }

    public function assertOwner($domain, $owner)
    {
        $this->logger->debug(__FUNCTION__ . ": " . "Verify and set correct owner");

        $domain = $this->toLowercase($domain);

        try {
            $currentOwner = $this->getOwner($domain);
            if ($currentOwner != $owner) {
                if (!empty($currentOwner)) {
                    $response = $this->get("/api/reseller/unbinddomains/username/$currentOwner/domains/$domain")->send()->getBody(true);
                    $this->logger->debug(__FUNCTION__ . ": Response: " . var_export($response, true));
                }
                $response = $this->get("/api/reseller/binddomains/username/$owner/domains/$domain")->send()->getBody(true);
                $result = stripos($response, 'success') !== false;
            } else {
                $response = "Current owner is correct. ($domain => $owner)";
                $result = true;
            }
        } catch (Exception $e) {
            $response = "Error: " . $e->getMessage() . " | Code: " . $e->getCode();
            $this->report->add($response, Report::ERROR);
            $result = false;
        }

        $this->logger->debug(__FUNCTION__ . ": Result: " . var_export($result, true) . " Response: " . var_export($response, true));

        return $result;
    }

    public function wipeReseller($username)
    {
        $this->logger->debug(__FUNCTION__ . ": " . "Get reseller request");

        try {
            $response = $this->get("/api/reseller/wipe/username/[\"$username\"]");
            $response = $response->send()->getBody(true);
            $result = empty($response);
        } catch (Exception $e) {
            $response = "Error: " . $e->getMessage() . " | Code: " . $e->getCode();
            $this->report->add($response, Report::ERROR);
            $result = false;
        }

        $this->logger->debug(__FUNCTION__ . ": Result: " . var_export($result, true) . " Response: " . var_export($response, true));

        return $result;
    }

    ## Misc

    public function getPrivateLabel()
    {
        $this->logger->debug(__FUNCTION__ . ": " . "Get private label availability request");

        try {
            $response = $this->get("/api/productslist/get");
            $response = $response->send()->getBody(true);
            $result = json_decode($response);
            $result = is_array($result) ? in_array('whitelabel', $result) : false;
        } catch (Exception $e) {
            $response = "Error: " . $e->getMessage() . " | Code: " . $e->getCode();
            $this->report->add($response, Report::ERROR);
            $result = false;
        }

        $this->logger->debug(__FUNCTION__ . ": Result: " . var_export($result, true) . " Response: " . var_export($response, true));

        return $result;
    }

    public function getAuthTicket($username)
    {
        $this->logger->debug(__FUNCTION__ . ": " . "Authentication ticket request");

        try {
            $response = $this->get("/api/authticket/create/username/$username");
            $response = $response->send()->getBody(true);
            $result = $response;
        } catch (Exception $e) {
            $response = "Error: " . $e->getMessage() . " | Code: " . $e->getCode();
            $this->report->add($response, Report::ERROR);
            $result = null;
        }

        $this->logger->debug(__FUNCTION__ . ": Result: " . var_export($result, true) . " Response: " . var_export($response, true));

        return $result;
    }

    public function getProductsList()
    {
        $this->logger->debug(__FUNCTION__ . ": " . "Get product availability request");

        $response = $this->get("/api/productslist/get");
        $response = $response->send()->getBody(true);
        $result = $response;

        $this->logger->debug(__FUNCTION__ . ": Result: " . var_export($result, true) . " Response: " . var_export($response, true));

        return $result;
    }

    public function toLowercase($string)
    {
        return function_exists('mb_strtolower') ? mb_strtolower($string, 'UTF-8') : strtolower($string);
    }
}
