<?php
require_once "vendor/autoload.php";
require_once "aps/2/runtime.php";

/**
 * Class context
 * @type("http://aps.spamexperts.com/app/context/2.0")
 * @implements("http://aps-standard.org/types/core/resource/1.0")
 */
class context extends \APS\ResourceBase
{
    ## Strong link with the service (global settings)
    /**
     * @link("http://aps.spamexperts.com/app/service/1.3")
     * @required
     */
    public $service;

    ## Strong link with the subscription (subscription specifics/limits)
    /**
     * @link("http://aps-standard.org/types/core/subscription/1.0")
     * @required
     */
    public $subscription;

    ## Strong link with the account (attributes and access)
    /**
     * @link("http://aps-standard.org/types/core/account/1.0")
     * @required
     */
    public $account;

    ## Link with the admin user (information for setting up the SE account)
    /**
     * @link("http://parallels.com/aps/types/pa/admin-user/1.0")
     */
    public $admin;

    ## Link to a collection of SE domain resources
    /**
     * @link("http://aps.spamexperts.com/app/domain/1.0[]")
     */
    public $domains;

    ## Link to a collection of SE email resources
    /**
     * @link("http://aps.spamexperts.com/app/email/1.0[]")
     */
    public $emails;


    ## COUNTERS ##


    ## Incoming

    /**
     * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
     * @title("Incoming")
     * @description("(unset/unlimited/1+ => enabled, 0 => disabled)")
     */
    public $incoming;

    /**
     * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
     * @title("Incoming domains")
     * @description("Domains with incoming product")
     */
    public $incoming_domains;

    /**
     * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
     * @title("Incoming users (valid recipients)")
     */
    public $incoming_users;

    /**
     * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
     * @title("Incoming bandwidth")
     * @unit("mb")
     */
    public $incoming_bandwidth;


    ## Outgoing

    /**
     * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
     * @title("Outgoing")
     * @description("(unset/unlimited/1+ => enabled, 0 => disabled)")
     */
    public $outgoing;

    /**
     * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
     * @title("Outgoing domains")
     * @description("Domains with outgoing product")
     */
    public $outgoing_domains;

    /**
     * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
     * @title("Outgoing users")
     */
    public $outgoing_users;

    /**
     * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
     * @title("Outgoing bandwidth")
     * @unit("mb")
     */
    public $outgoing_bandwidth;


    ## Archiving

    /**
     * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
     * @title("Archiving")
     * @description("(unset/unlimited/1+ => enabled, 0 => disabled)")
     */
    public $archiving;

    /**
     * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
     * @title("Archiving domains")
     * @description("Domains with archiving product")
     */
    public $archiving_domains;

    /**
     * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
     * @title("Archiving accounts")
     */
    public $archiving_accounts;

    /**
     * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
     * @title("Archiving disk space")
     * @unit("mb")
     */
    public $archiving_space;

    /**
     * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
     * @title("Archiving storage period (days)")
     */
    public $archiving_period;

    /**
     * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
     * @title("Archiving quota (soft)")
     * @description("Value to set soft archiving quota per domain (mb)")
     * @unit("mb")
     */
    public $archiving_quota_soft;

    /**
     * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
     * @title("Archiving quota (hard)")
     * @description("Value to set hard archiving quota per domain (mb)")
     * @unit("mb")
     */
    public $archiving_quota_hard;

    ## Other

    /**
     * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
     * @title("Private label")
     * @description("(unset/unlimited/0 => none, 1 => standard, 2 => premium)")
     */
    public $private_label;

    /**
     * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
     * @title("Automatically protect domains")
     * @description("(unset/unlimited/0 => disabled, 1+ => enabled)")
     */
    public $auto_protect_domain;

    /**
     * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
     * @title("Automatically protect emails")
     * @description("(unset/unlimited/0 => disabled, 1+ => enabled) Not in use")
     */
    public $auto_protect_email;

    /**
     * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
     * @title("Domain user control panel access")
     * @description("(unset/unlimited/1+ => enabled, 0 => disabled)")
     */
    public $cp_domain;

    /**
     * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
     * @title("Email user control panel access")
     * @description("(unset/unlimited/1+ => enabled, 0 => disabled)")
     */
    public $cp_email;

    /**
     * @type(string)
     * @title("Admin Username")
     * @description("The SE admin username")
     */
    public $username;

    /**
     * @type(string)
     * @title("Admin Password")
     * @description("The SE admin password")
     * @encrypted
     */
    public $password;

    /**
     * @type(string)
     * @title("Admin Email")
     * @description("The SE admin email address")
     */
    public $adminEmail;

    /**
     * @type(string[])
     * @title("MX records")
     * @description("The current MX record array")
     */
    public $mx;

    /** @var $logger Logger */
    private $logger;
    /** @var $report Report */
    private $report;
    private $API;
    private $APSC;
    private $APSN;

    public function __construct($API = NULL, $APSC = NULL)
    {
        parent::__construct();

        $this->API = $API;
        $this->APSC = $APSC;
        $this->report = new Report($this->logger = new Logger("Context"));
    }

    public function provision()
    {
        $this->logger->info(__FUNCTION__ . ": start");

        $this->logger->info(__FUNCTION__ . ": Provisioning context");

        if (empty($this->username)) {
            $admins = $this->APSC()->getResources('implementing(http://parallels.com/aps/types/pa/admin-user/1.0)');
            $admin = reset($admins);
            $this->username = $admin->login . '_' . $this->subscription->subscriptionId;
            $this->password = md5($this->aps->id);
            $this->adminEmail = $admin->email;
        }

        $this->mx = $this->getServiceMXRecords();

        ## Create a new reseller container for the account
        $this->logger->info(__FUNCTION__ . ": Creating new SE account");
        if ($this->createReseller()) {

            ## Subscribe to relevant events
            $this->logger->info(__FUNCTION__ . ": Subscribing to events");

            $onDomainAvailable          = new \APS\EventSubscription(\APS\EventSubscription::Available,                "onDomainAvailable");
            $onServiceChanged           = new \APS\EventSubscription(\APS\EventSubscription::Changed,                  "onServiceChanged");
            $onSubscriptionLimitChanged = new \APS\EventSubscription(\APS\EventSubscription::SubscriptionLimitChanged, "onSubscriptionLimitChanged");

            ## Event type string or Resource to subscribe to
            $onDomainAvailable->source          = (object) array('type' => "http://parallels.com/aps/types/pa/dns/zone/1.0");
            $onServiceChanged->source           = (object) array('id'   => $this->service->aps->id);
            $onSubscriptionLimitChanged->source = (object) array('id'   => $this->subscription->aps->id);

            ## Subscribe to events on this account
            $this->APSC()->subscribe($this, $onDomainAvailable);
            $this->APSC()->subscribe($this, $onServiceChanged);
            $this->APSC()->subscribe($this, $onSubscriptionLimitChanged);
        } else {
            $this->logger->error(__FUNCTION__ . ": Couldn't create SE account!");
            throw new Exception("ERROR: Couldn't create a SpamExperts account for the new subscription. More details can be found in the logs (if advanced logging has been enabled).");
        }

        $this->logger->info(__FUNCTION__ . ": stop");
    }

    public function retrieve()
    {
        $this->logger->debug(__METHOD__ . ": start");

        $currentDow = date('D');
        $currentHour = date('G');
        if (!empty($this->service->usageUpdateDayOfWeek)
            && ($this->service->usageUpdateDayOfWeek != $currentDow)) {
            $this->logger->debug(
                __METHOD__ . ": Skip updating counters on $currentDow as it's set to only update on {$this->service->usageUpdateDayOfWeek}"
            );
        } elseif (!empty($this->service->usageUpdateHour)
            && ($this->service->usageUpdateHour != $currentHour)) {
            $this->logger->debug(
                __METHOD__ . ": Skip updating counters at $currentHour as it's set to only update at {$this->service->usageUpdateHour}"
            );
        } else {
            $this->logger->debug(__METHOD__ . ": Updating counters");

            $API = $this->API();

            ## Let's make an array to check for used counters and update them
            $counters = array(
                /* Product availability counters */
                "incoming" => "getIncoming",
                "outgoing" => "getOutgoing",
                "archiving" => "getArchiving",
                "private_label" => "getPrivateLabel",

                /* Domain product usage counters */
                "incoming_domains" => "getIncomingDomains",
                "outgoing_domains" => "getOutgoingDomains",
                "archiving_domains" => "getArchivingDomains",

                /* User counters */
                "incoming_users" => "getIncomingUsers",
                "outgoing_users" => "getOutgoingUsers",
            );

            foreach ($counters as $counter => $APICall) {
                if (isset($this->{$counter}->limit)) {

                    /**
                     * Product availability actual usage should always be zero
                     * as there is a bunch of another, product-specific countrs available
                     * (like incoming_domains, outgoing_users, archiving_accounts, etc).
                     * Limiting on actual product availability can cause issues
                     *
                     * @see https://trac.spamexperts.com/ticket/30271
                     */
                    if (in_array($counter, array('getIncoming', 'getOutgoing', 'getArchiving'))) {
                        $this->{$counter}->usage = 0;
                    } else {
                        $this->{$counter}->usage = (int)$API->{$APICall}($this->username);
                    }
                }
            }

            /* TBD */
            //$this->incoming_bandwidth->usage = $API->getIncomingBandwidth();
            //$this->outgoing_bandwidth->usage = $API->getOutgoingBandwidth();
            //$this->archiving_accounts->usage = $API->getArchivingAccounts();
            //$this->archiving_space->usage    = $API->getArchivingSpace();
            //$this->archiving_period->usage   = $API->getArchivingPeriod();
        }

        $this->logger->debug(__METHOD__ . ": stop");
    }

    public function configure($context = null /** @var $context context */)
    {
        $this->logger->info(__FUNCTION__ . ": start");

        $this->logger->info(__FUNCTION__ . ": Configuring context ($this->username)");

        $this->logger->info(__FUNCTION__ . ": stop");
    }

    public function unprovision()
    {
        $this->logger->info(__FUNCTION__ . ": start");
        $this->logger->info(__FUNCTION__ . ": Unprovisioning context");

        try {
            $this->API()->wipeReseller($this->username);
        } catch (Exception $e) {
            $this->logger->info(__FUNCTION__ . ": ERROR: $e");
        }

        $this->logger->info(__FUNCTION__ . ": stop");
    }

    public function domainsUnlink($domain)
    {
        $this->logger->info(__FUNCTION__ . ": start");

        try {
            $this->logger->info(__FUNCTION__ . ": Reverting MX records for domain: {$domain->name}");
            $this->revertMXRecords($domain->domain);
        } catch (Exception $e) {
            $this->report->add("Could not revert MX records. [$e]", Report::WARNING);
        }

        $this->logger->info(__FUNCTION__ . ": stop");
    }

    /**
     * @verb(POST)
     * @path("/onDomainAvailable")
     * @param("http://aps-standard.org/types/core/resource/1.0#Notification",body)
     */
    public function onDomainAvailable($event)
    {
        $this->logger->info(__METHOD__ . ": start");

        $domain = $this->APSC()->getResource($event->source->id);

        /**
         * A domain should be auto-provisioned in 2 cases:
         * 1 - If a subscription what adds the domain sends the event (we compare subscription IDs to check that)
         * 2 - If the most recent subscription sends the event - it's the case for the most fist domain
         *
         * Here the 1st scenario is being checked
         */
        $subscriptionIdsMatch = false;
        try {
            $domainHosting = $domain->hosting;
            if (isset($domainHosting) && isset($domainHosting->aps->id)) {
                $domainSubscriptionApsId = $domainHosting->aps->id;
                $currentSubscriptionApsId = $this->subscription->aps->id;
                $subscriptionIdsMatch = $domainSubscriptionApsId == $currentSubscriptionApsId;
            }
        } catch (Exception $e) {
            $this->logger->info(__METHOD__ . ": " . $e->getMessage());
        }

        $autoProtectionEnabled = ! $this->domainAutoprotectionDisabled();

        $this->logger->info(__METHOD__ . ": auto_protect_domain is "
            . ($autoProtectionEnabled ? 'enabled' : 'disabled') . "; "
            . "subscription does " . ($subscriptionIdsMatch ? '' : 'NOT ') . "match.");

        if ($autoProtectionEnabled && $subscriptionIdsMatch) {

            $this->logger->info(__METHOD__ . ": New domain: " . $domain->name);

            $this->APSN = array('type' => 'domain', 'name' => 'name');
            $this->updateResources(array($domain), true);
        }

        $this->logger->info(__METHOD__ . ": stop");
    }

    public function domainAutoprotectionDisabled()
    {
        return isset($this->auto_protect_domain->limit) && '0' === $this->auto_protect_domain->limit;
    }

    public function emailAutoprotectionDisabled()
    {
        return isset($this->auto_protect_email->limit) && '0' === $this->auto_protect_email->limit;
    }

    /**
     * @verb(POST)
     * @path("/onServiceChanged")
     * @param("http://aps-standard.org/types/core/resource/1.0#Notification",body)
     */
    public function onServiceChanged($event)
    {
        $this->logger->info(__FUNCTION__ . ": start");

        $this->logger->info(__FUNCTION__ . ": Service has been updated");

        $this->logger->info(__FUNCTION__ . ": Updating domain MX records...");

        if ($this->mx != $this->mx) {
            // Removing old records
            foreach ($this->domains as $domain) {
                $this->revertMXRecords($domain->domain);
            }

            // Updating current records
            $this->mx = $this->mx;
            $this->APSC()->updateResource($this);

            // Updating domain records
            foreach ($this->domains as $domain) {
                $this->replaceMXRecords($domain->domain);
            }
        }

        $this->logger->info(__FUNCTION__ . ": stop");
    }

    /**
     * @verb(POST)
     * @path("/onSubscriptionLimitChanged")
     * @param("http://aps-standard.org/types/core/resource/1.0#Notification",body)
     */
    public function onSubscriptionLimitChanged($event)
    {
        $this->logger->info(__FUNCTION__ . ": start");

        $this->logger->info(__FUNCTION__ . ": A subscription limit has changed");

        $domainLimit = $this->getLimit("http://aps.spamexperts.com/app/domain/1.0");
        $domainLimit = isset($domainLimit) ? $domainLimit : 0;

        $this->logger->info(__FUNCTION__ . ": Updating reseller ($this->username : domainLimit => $domainLimit)");

        $this->API()->updateReseller($this->username, $this->password, $this->adminEmail, $domainLimit);

        $this->logger->info(__FUNCTION__ . ": stop");
    }


    ### Domain actions

    ## Check if selected domain resources are protected; status update
    /**
     * @verb(PUT)
     * @path("/domainCheck")
     * @param(string,body)
     */
    public function domainCheck($IDs)
    {
        $this->APSN = array('type' => 'domain', 'name' => 'name');
        $identifiers = json_decode(rawurldecode($IDs));
        $idType = (!$identifiers || (strlen($identifiers[0]) == 36 && strpos($identifiers[0], '.') === false)) ? 'IDs' : 'Names';
        return $this->updateResources($this->{"getResourcesFrom$idType"}($identifiers));
    }

    ## Protect selected domains
    /**
     * @verb(PUT)
     * @path("/domainProtect")
     * @param(string,body)
     */
    public function domainProtect($IDs)
    {
        set_time_limit(0);

        $this->APSN = array('type' => 'domain', 'name' => 'name');
        $this->updateResources($this->getResourcesFromIDs(json_decode(rawurldecode($IDs))), true);
    }

    ## Unprotect selected domains
    /**
     * @verb(PUT)
     * @path("/domainUnprotect")
     * @param(string,body)
     */
    public function domainUnprotect($IDs)
    {
        set_time_limit(0);

        $this->APSN = array('type' => 'domain', 'name' => 'name');
        $this->unprotectResources($this->getResourcesFromIDs(json_decode(rawurldecode($IDs))));
    }


    ### Email actions

    ## Check if selected email resources are protected; status update
    /**
     * @verb(PUT)
     * @path("/emailCheck")
     * @param(string,body)
     */
    public function emailCheck($IDs)
    {
        $this->APSN = array('type' => 'email', 'name' => 'login');
        $this->updateResources($this->getResourcesFromIDs(json_decode(rawurldecode($IDs))));
    }

    ## Protect selected email resources
    /**
     * @verb(PUT)
     * @path("/emailProtect")
     * @param(string,body)
     */
    public function emailProtect($IDs)
    {
        set_time_limit(0);

        $this->APSN = array('type' => 'email', 'name' => 'login');
        $this->updateResources($this->getResourcesFromIDs(json_decode(rawurldecode($IDs))), true);
    }

    ## Unprotect  selected email resources
    /**
     * @verb(PUT)
     * @path("/emailUnprotect")
     * @param(string,body)
     */
    public function emailUnprotect($IDs)
    {
        $this->APSN = array('type' => 'email', 'name' => 'login');
        $this->unprotectResources($this->getResourcesFromIDs(json_decode(rawurldecode($IDs))));
    }


    ### Other actions


## Protect all domain and email resources
    /**
     * @verb(GET)
     * @path("/protectAll")
     */
    public function protectAll()
    {
        $this->domainProtect('');
        $this->emailProtect('');
    }

    /**
     * @verb(GET)
     * @path("/autoprotectAll")
     */
    public function autoprotectAll()
    {
        if ($this->domainAutoprotectionDisabled()) {
            $this->logger->info(__METHOD__ . ": skip domains autoprotection as 'auto_protect_domain' prevents that");
        } else {
            $this->domainProtect('');
        }

        if ($this->emailAutoprotectionDisabled()) {
            $this->logger->info(__METHOD__ . ": skip emails autoprotection as 'auto_protect_email' prevents that");
        } else {
            $this->emailProtect('');
        }
    }

## Unprotect all domain and email resources
    /**
     * @verb(GET)
     * @path("/unprotectAll")
     */
    public function unprotectAll()
    {
        $this->domainUnprotect('');
    }

    ## Update reseller container
    /**
     * @verb(GET)
     * @path("/refreshContainer")
     */
    public function refreshContainer()
    {
        $this->logger->info(__FUNCTION__ . ": start");

        $this->API()->updateReseller($this->username, $this->password, $this->adminEmail);

        $this->logger->info(__FUNCTION__ . ": stop");
    }

    ## Get a CP authentication ticket
    /**
     * @verb(GET)
     * @path("/getAuthTicket")
     * @param(string,query)
     * @return(string)
     */
    public function getAuthTicket($username)
    {
        $this->logger->info(__FUNCTION__ . ": start");

        $username = rawurldecode($username);
        $type = (count(explode('@', $username)) == 1) ? "domain" : "email";
        $this->logger->info(__FUNCTION__ . ": Getting $type user authentication ticket for '$username'");

        $ticket = ($this->service->ssl ? "https://" : "http://") . $this->service->hostname;

        if ($this->getSEResource($username) && !isset($this->{"cp_$type"}->limit) ?: $this->{"cp_$type"}->limit) {
            $ticket .= "?authticket=" . $this->API()->getAuthTicket($username);
        }

        $this->logger->info(__FUNCTION__ . ": stop");

        return  $ticket;
    }

    /**
     * Get the report
     * @verb(GET)
     * @path("/report")
     * @param()
     */
    public function report()
    {
        return Report::getMessages();
    }


    ### Helper functions

    ## Resources

    /**
     * Update SE resources and optionally protect them
     *
     * @access private
     * @param array $resources Resources to update
     * @param bool $protect Whether to protect the resource or not
     * @return array
     */
    private function updateResources($resources, $protect = false)
    {
        $this->logger->info(__FUNCTION__ . ": start");

        $SEResources = array();

        try {
            if (is_array($resources) && !empty($resources)) {
                foreach ($resources as $resource) {
                    $this->logger->info(__FUNCTION__ . ": Starting update process for resource ({$resource->{$this->APSN['name']}})");

                    Report::clear(Report::ERROR);

                    if ($protect) {
                        $this->logger->info(__FUNCTION__ . ": Protecting resource");
                        $this->protectResource($resource);
                    }

                    if (!Report::hasErrors()) {
                        if ($SEResource = $this->getSEResource($resource->{$this->APSN['name']})) {
                            $this->logger->info(__FUNCTION__ . ": Check if SE resource is protected and update status");
                            $SEResource->status = $this->checkProtectionStatus($SEResource->name);
                            $this->APSC()->updateResource($SEResource);
                            $this->logger->info(__FUNCTION__ . ": Done! ({$resource->{$this->APSN['name']}})");
                            $SEResources[] = $SEResource;
                        } elseif (!$protect) {
                            $this->report->add("SE {$this->APSN['type']} resource '{$resource->{$this->APSN['name']}}' not found. Use the 'Protect' action to have it created and protected.", Report::WARNING);
                        }
                    } else {
                        break;
                    }
                }
            } else {
                $this->logger->info(__FUNCTION__ . ": [EMPTY RESOURCES]");
            }
        } catch (Exception $e) {
            $this->report->add("Error updating resources: [$e]", Report::ERROR);
        }

        $this->logger->info(__FUNCTION__ . ": stop");

        return $SEResources;
    }

    private function protectResource($resource)
    {
        $this->logger->info(__FUNCTION__ . ": start");

        $email_a = explode('@', $name = $resource->{$this->APSN['name']});

        $this->logger->info(__FUNCTION__ . ": Protection process starting");
        if ($w =

            ## If protecting an email resource, check if the respective domain is protected first
            (isset($email_a[1]) && !$this->checkProtectionStatus($email_a[1])) ?
                "Domain '{$email_a[1]}' not protected, please make sure it is in order to add this email. ($name)." :

                ## If the SE resource doesn't exists, check if the limit is exceeded
                ((!($SEResource = $this->getSEResource($resource->{$this->APSN['name']})) && $this->exceedingLimit("http://aps.spamexperts.com/app/{$this->APSN['type']}/1.0")) ?
                    "Exceeding SE {$this->APSN['type']} resource limit. Your service provider will be able to extend the limit of your plan. ($name)" :

                    ## If the SE resource doesn't exist, create it
                    ((!$SEResource && !($SEResource = $this->createSEResource($resource))) ?
                        "Failed to create SE {$this->APSN['type']} resource. This is an issue with POA/APS, please contact support for further information. ($name)" :

                        ## If protecting a domain resource, add the domain first (we skip checking if the domain already exists in SP, the check is done anyway during addition)
                        ((!isset($email_a[1]) && !$this->addDomain($SEResource)) ?
                            "Failed to add domain to SpamExperts, an issue occurred with the request. This issue that can be further investigated by your service provider via advanced logging. ($name)" :

                            ## If protecting a domain resource, verify the owner is correct
                            ((!isset($email_a[1]) && !$this->API()->assertOwner($name, $this->username)) ?
                                "Failed to transfer domain to owner. This issue that can be further investigated by your service provider via advanced logging.  ($name)" :

                                ## Add respective SP user
                                (!$this->API(true)->{"add" . ucfirst($this->APSN['type']) . "User"}($name) ?
                                    "Failed to add {$this->APSN['type']} user to SpamExperts. This issue that can be further investigated by your service provider via advanced logging ($name)" :

                                    ## If protecting a domain resource, replace the existing MX records
                                    ((!isset($email_a[1]) && !$this->replaceMXRecords($resource)) ?
                                        "Failed to replace domain MX records. This is an issue with POA/APS, please contact support for further information. ($name)" :
                                        NULL))))))) {

            ## If something failed in the process above, issue the respective warning concerning this resource
            $this->report->add($w, Report::WARNING);
        }

        $this->logger->info(__FUNCTION__ . ": stop");
    }

    private function unprotectResources($resources)
    {
        $this->logger->info(__FUNCTION__ . ": start");

        foreach ($resources as $resource) {
            if ($SER = $this->getSEResource($resource->{$this->APSN['name']})) {
                $this->APSC()->getIo()->sendRequest(\APS\Proto::DELETE, "/aps/2/resources/{$SER->aps->id}");
            }
        }

        $this->logger->info(__FUNCTION__ . ": stop");
    }

    private function createSEResource($resource)
    {
        $this->logger->info(__METHOD__ . ": start");

        // If a SE resource already exists then it should not be duplicated
        if ($existingResource = $this->getSEResource($resource->{$this->APSN['name']}, '/aps/2/resources')) {
            $this->logger->info(
                __METHOD__ . ": SE {$this->APSN['type']} resource already exists, skip re-creating it"
            );

            $return = $existingResource;
        } else {

            $this->logger->info(__METHOD__ . ": New SE {$this->APSN['type']} resource");
            $SEResource = \APS\TypeLibrary::newResourceByTypeId("http://aps.spamexperts.com/app/{$this->APSN['type']}/1.0");

            ## Set resource properties
            $SEResource->name = $resource->{$this->APSN['name']};
            $SEResource->status = false;

            ## Set resource links
            $SEResource->aps->links[0] = new \APS\Link($resource, $this->APSN['type'], $SEResource);

            ## If email, link domain
            $email_a = explode('@', $resource->{$this->APSN['name']});
            if (isset($email_a[1])) {
                $SEResource->aps->links[1] = new \APS\Link($this->getSEResource($email_a[1]), 'domain', $SEResource);
            }

            ## Link SE resource to the context
            $this->logger->info(__METHOD__ . ": Linking SE {$this->APSN['type']} resource to context");
            $result = $this->APSC()->linkResource($this, "{$this->APSN['type']}s", $SEResource);

            $return = $result ? $this->getSEResource($SEResource->name) : false;
        }

        $this->logger->info(__METHOD__ . ": stop");

        return $return;
    }

    private function getSEResource($name, $path = null)
    {
        $type = count(explode('@', $name)) == 1 ? "domain" : "email";

        $resources = $this->APSC()->getResources(
            "and(implementing(http://aps.spamexperts.com/app/$type/1.0),like(name,$name))",
            null === $path ? "/aps/2/resources/{$this->aps->id}/{$type}s" : $path
        );

        return array_pop($resources);
    }

    private function getResource($name)
    {
        $by_name = count(explode('@', $name)) == 1 ? 'name' : 'login';
        $type = $by_name == 'name' ? "http://parallels.com/aps/types/pa/dns/zone/1.0" : "http://aps-standard.org/types/core/service-user/1.0";

        $resources = $this->APSC()->getResources("and(implementing($type),like($by_name,$name))");

        return array_pop($resources);
    }

    private function checkProtectionStatus($name)
    {
        $type = count($email_a = explode('@', $name)) == 1 ? "Domain" : "Email";

        if ($w =
            (isset($email_a[1]) && !$this->checkProtectionStatus($email_a[1])) ? "Domain '{$email_a[1]}' not protected. ($name)" :
                (!$this->getSEResource($name) ? "$type not protected. ($name)" :
                    (!(isset($email_a[1]) ?: $this->API()->getOwner($name) == $this->username) ? "Domain is in another SE container. Use the 'Protect' action to transfer it to the current container. ($name)" :
                        (!$this->API()->{"check{$type}User"}($name) ? "$type exists, but the $type user was not found in SpamExperts. The 'Protect' action may fix this for you. ($name)" :
                            (!(isset($email_a[1]) ?: $this->getSEResource($name) && $this->checkMXRecords($this->getResource($name))) ? "Domain MX records aren't set correctly. The 'Protect' action may fix this for you, otherwise please contact your service provider. ($name)" :
                                NULL))))) {
            $this->report->add($w, Report::WARNING);
        }

        return !$w;
    }

    private function exceedingLimit($apsType)
    {
        $subscriptionResources = $this->APSC()->getResource($this->subscription->aps->id)->resources();

        foreach ($subscriptionResources as $subscriptionResource) {
            if ($subscriptionResource->apsType == $apsType) {
                return isset($subscriptionResource->limit) ? $subscriptionResource->limit <= $subscriptionResource->usage : false;
            }
        }

        return false;
    }

    private function getLimit($apsType)
    {
        $subscriptionResources = $this->APSC()->getResource($this->subscription->aps->id)->resources();

        foreach ($subscriptionResources as $subscriptionResource) {
            if ($subscriptionResource->apsType == $apsType) {
                return isset($subscriptionResource->limit) ? $subscriptionResource->limit : null;
            }
        }

        return null;
    }

    private function getResourcesFromIDs($IDs)
    {
        $type = $this->APSN['type'] == 'domain' ? "http://parallels.com/aps/types/pa/dns/zone/1.0" : "http://aps-standard.org/types/core/service-user/1.0";
        return !empty($IDs) ? array_map(array($this->APSC(), 'getResource'), $IDs) : $this->APSC()->getResources("implementing($type)");
    }

    private function getResourcesFromNames($names)
    {
        $names = implode(',', $names);
        return $this->APSC()->getResources("in(name,($names))", "/aps/2/resources/{$this->account->aps->id}/domains");
    }

    private function getAssocArray($items, $property)
    {
        return array_reduce($items, function ($items, $item) use ($property) { $items[$item->{$property}] = $item; return $items; }, array());
    }


    ## Reseller


    private function createReseller()
    {
        $this->logger->info(__FUNCTION__ . ": start");

        $products = array();
        foreach (array('incoming', 'outgoing', 'archiving') as $product) {
            $products[$product] = (int)(!isset($this->{$product}->limit) ?: !!$this->{$product}->limit);
        }

        $PL = array( null => 'none', -1 => 'none', 0 => 'none', 1 => 'standard', 2 => 'premium' );
        $products['private_label'] = $PL[isset($this->private_label->limit) ? $this->private_label->limit : null];

        $this->logger->info(__FUNCTION__ . ": Creating SE account");
        $domainLimit = $this->getLimit("http://aps.spamexperts.com/app/domain/1.0");
        $domainLimit = isset($domainLimit) ? $domainLimit : 0;
        $result = $this->API()->addReseller($this->username, $this->password, $this->adminEmail, $domainLimit) &&
                  $this->API()->setResellerProducts($this->username, $products);

        $this->logger->info(__FUNCTION__ . ": stop");

        return $result;
    }


    ## Domain


    private function addDomain($domain)
    {
        $this->logger->info(__FUNCTION__ . ": start");

        if ($result = $this->API(true)->addDomain($domain->name, $this->getPAMXRecords($domain->domain, 'out', true) ?: NULL)) {
            $products = array();
            foreach (array('incoming', 'outgoing', 'archiving') as $product) {
                if ($this->{$product}) {
                    $products[$product] = isset($this->{$product}->limit) ? ($this->{$product}->limit ? 1 : 0) : 1;
                }
            }
            $result = $this->API(true)->setDomainProducts($domain->name, $products);
        }

        $this->logger->info(__FUNCTION__ . ": stop");

        return $result;
    }


    ## MX Records


    private function getServiceMXRecords()
    {
        $mx = array();

        for ($i = 1; $i <= 4; $i++) {
            if ($this->service->{"mx$i"}) {
                $mx[] = $this->service->{"mx$i"} . ".";
            }
        }

        return $mx;
    }

    private function getPAMXRecords($domain, $io = '', $asExchangeArray = false)
    {
        $rql = "and(implementing(http://parallels.com/aps/types/pa/dns/record/mx/1.0),";
        if ($io) {
            // Get only SE records or except SE records
            $SEMXs = implode(',', $this->mx);
            $rql .= "$io(exchange,($SEMXs)),";
        }

        $rql .= "sort(+priority))";

        $records = $this->APSC()->getResources($rql, "/aps/2/resources/{$domain->aps->id}/records");
        if ($asExchangeArray) {
            foreach ($records as $index => $record) {
                $records[$index] = rtrim($record->exchange, ".");
            }
        }
        return $records;
    }

    private function checkMXRecords($domain)
    {
        return count($this->mx) == count($this->getPAMXRecords($domain, 'in'));
    }

    private function revertMXRecords($domain) {
        $records = $this->getPAMXRecords($domain, 'in');
        foreach ($records as $record) {
            try {
                $this->logger->info(__FUNCTION__ . ": Removing {$record->exchange}");
                $this->APSC()->getIo()->sendRequest(\APS\Proto::DELETE, "/aps/2/resources/{$domain->aps->id}/records/{$record->aps->id}");
            } catch (Exception $e) {
                $this->report->add("Could not remove MX record '{$record->exchange}' ({$domain->name}) [$e]", Report::WARNING);
            }
        }
    }

    private function replaceMXRecords($domain)
    {
        $this->logger->info(__FUNCTION__ . ": start");

        $this->logger->info(__FUNCTION__ . ": Verifying and replacing MX records");

        $result = false;

        try {
            $pa_records = $this->getPAMXRecords($domain);

            ## Avoid potential conflicts from existing SE records
            $SEMXs = $this->mx;
            foreach ($pa_records as $pa_index => $pa_record) {
                if (($se_index = array_search($pa_record->exchange, $SEMXs)) !== false) {
                    unset($pa_records[$pa_index]);
                    unset($SEMXs[$se_index]);
                }
            }

            ## Add SE MX records to domain collection
            if (count($SEMXs)) {
                $this->logger->info(__FUNCTION__ . ": Creating new PA MX record resources");
                $record = \APS\TypeLibrary::newResourceByTypeId("http://parallels.com/aps/types/pa/dns/record/mx/1.0");
                foreach ($SEMXs as $index => $SEMX) {
                    $this->logger->info(__FUNCTION__ . ": Setting up record: $SEMX -> {$domain->name}.");
                    $record->source      = $domain->name . ".";
                    $record->exchange    = $SEMX;
                    $record->RRState     = 'active';
                    $record->priority    = 10*$index + 10;
                    $record->TTL         = 3600;
                    $record->recordId    = $index;

                    $this->logger->info(__FUNCTION__ . ": Linking new record to domain records collection");
                    $this->APSC()->linkResource($domain, 'records', $record);
                }

                if (count($pa_records)) {
                    $this->logger->info(__FUNCTION__ . ": Replacing existing PA MX RRs with the first created SE MX RR");
                    $SEMX = array_pop($SEMXs);

                    $resources = $this->APSC()->getResources(
                        "and(implementing(http://parallels.com/aps/types/pa/dns/record/mx/1.0),like(exchange,{$SEMX}))", "/aps/2/resources/{$domain->aps->id}/records"
                    );

                    $record = array_pop($resources);
                    foreach ($pa_records as $pa_record) {
                        $this->APSC()->linkResource($record, 'replaces', $pa_record);
                    }
                }
            }

            $result = true;
        } catch (Exception $e) {
            $this->report->add("Could not replace MX records for domain '{$domain->name}'. [$e]", Report::WARNING);
        }

        $this->logger->info(__FUNCTION__ . ": stop");

        return $result;
    }

    private function API($reseller = false)
    {
        if (!$this->API || $reseller) {
            $API = new APIClient($this->service);
            if ($reseller) {
                return $API->setDefaultOption('auth', array($this->username, $this->password));
            }
            $this->API = $API;
        }
        return $this->API;
    }

    private function APSC($resource = null)
    {
        return $this->APSC ?: $this->APSC = \APS\Request::getController()->impersonate($resource ?: $this);
    }
}
