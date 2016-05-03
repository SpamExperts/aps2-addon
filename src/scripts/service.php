<?php
require_once "vendor/autoload.php";
require_once "aps/2/runtime.php";

/**
 * Class service
 * @type("http://aps.spamexperts.com/app/service/1.1")
 * @implements("http://aps-standard.org/types/core/application/1.0")
 */
class service extends \APS\ResourceBase
{
    ## Link with a collection of contexts

    /**
     * @link("http://aps.spamexperts.com/app/context/1.1[]")
     */
    public $contexts;

    /**
     * @type(string)
     * @title("SpamExperts CP")
     * @description("The URL of your SpamExperts application's control panel.")
     * @format("uri")
     * @required
     */
    public $SECP;

    /**
     * @type(string)
     * @title("API Hostname")
     * @description("The hostname of your master server (without http or https, likely the same as in your SpamExperts CP URL)")
     * @format("host-name")
     * @required
     */
    public $hostname;

    /**
     * @type(string)
     * @title("API Username")
     * @description("The username of your SpamPanel user (reseller or admin account, do not use a Software API user)")
     * @required
     */
    public $username;

    /**
     * @type(string)
     * @title("API Password")
     * @description("The password of your SpamPanel user (reseller or admin account)")
     * @required
     * @encrypted
     */
    public $password;

    /**
     * @type(string)
     * @title("Primary MX")
     * @format("host-name")
     * @required
     */
    public $mx1;

    /**
     * @type(string)
     * @title("Secondary MX")
     */
    public $mx2;

    /**
     * @type(string)
     * @title("Tertiary MX")
     */
    public $mx3;

    /**
     * @type(string)
     * @title("Quaternary MX")
     */
    public $mx4;

    /**
     * @type(boolean)
     * @default(false)
     * @title("Enable SSL")
     * @description("Enable SSL to communicate with the API.")
     * @required
     */
    public $ssl;

    const VERSION = "2.0-8";
    const MIN_APS_VERSION = "2.1";

    /** @var $logger Logger */
    private $logger;
    private $APSC;

    public function __construct()
    {
        parent::__construct();

        $this->logger = new Logger("Service");
    }

    public function provision()
    {
        $this->logger->info(__FUNCTION__ . ": start");

        // Check APS Runtime Library version
        $APS_VERSION = array_shift(explode('-', \Rest\RestService::VERSION));
        $MIN_APS_VERSION = self::MIN_APS_VERSION;
        if ($APS_VERSION < $MIN_APS_VERSION) {
            throw new Exception("The minimum supported version of the APS Runtime Library is v$MIN_APS_VERSION for POA 6.0 and up; you have version v$APS_VERSION. Please update your environment accordingly and try to reinstall the instance.");
        }

        $this->logger->info(__FUNCTION__ . ": stop");
    }

    public function configure($service = null /** @var $service service */)
    {
        $this->logger->info(__FUNCTION__ . ": start");
        $this->logger->info(__FUNCTION__ . ": stop");
    }

    public function unprovision()
    {
        $this->logger->info(__FUNCTION__ . ": start");
        $this->logger->info(__FUNCTION__ . ": stop");
    }

    public function upgrade($version)
    {
        $this->logger->info(__FUNCTION__ . ": start");

        $this->logger->info(__FUNCTION__ . ": Upgrading from $version to " . self::VERSION);

        switch($version) {
            case "2.0-3":
                $this->contextUpgrade("1.1");
                break;
            case "2.0-4":
                $this->contextUpgrade("1.1");
                break;
        }

        $this->logger->info(__FUNCTION__ . ": stop");
    }

    private function contextUpgrade($version)
    {
        switch ($version) {
            case "1.1":
                $this->logger->info(__FUNCTION__ . ": Upgrading contexts from 1.0 to 1.1");

                $contexts = $this->APSC()->getResources('implementing(http://aps.spamexperts.com/app/context/1.0)');
                foreach ($contexts as $context) {
                    $context->aps->type = "http://aps.spamexperts.com/app/context/1.1";
                    $this->APSC()->updateResource($context);
                }

                $this->logger->info(__FUNCTION__ . ": Updating context resources (new mx property and subscription limit event subscription)");

                $contexts = $this->APSC()->getResources('implementing(http://aps.spamexperts.com/app/context/1.1)');


                $mx = array();
                for ($i = 1; $i <= 4; $i++) {
                    if ($this->{"mx$i"}) {
                        $mx[] = $this->{"mx$i"} . ".";
                    }
                }

                foreach ($contexts as $context) {
                    $this->logger->info(__FUNCTION__ . ": Container: $context->username");

                    // New subscription - on subscription limit changed
                    $onSubscriptionLimitChanged = new \APS\EventSubscription(\APS\EventSubscription::SubscriptionLimitChanged, "onSubscriptionLimitChanged");
                    $onSubscriptionLimitChanged->source = (object)array('id'   => $context->subscription->aps->id);
                    $this->APSC()->subscribe($context, $onSubscriptionLimitChanged);

                    // New property - mx
                    $context->mx = $mx;
                    $this->APSC()->updateResource($context);
                }
                break;
        }
    }

    private function APSC($resource = null)
    {
        return $this->APSC ?: $this->APSC = \APS\Request::getController();
    }
}
