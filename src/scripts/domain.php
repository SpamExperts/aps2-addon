<?php
require_once "vendor/autoload.php";
require_once "aps/2/runtime.php";

/**
 * Class domain
 * @type("http://aps.spamexperts.com/app/domain/1.0")
 * @implements("http://aps-standard.org/types/core/resource/1.0")
 */
class domain extends \APS\ResourceBase
{
    ## Strong link with the context
    /**
     * @link("http://aps.spamexperts.com/app/context/2.2")
     * @required
     */
    public $context;

    ## Strong link with the hosted domain
    /**
     * @link("http://parallels.com/aps/types/pa/dns/zone/1.0")
     * @required
     */
    public $domain;

    ## Domain name
    /**
     * @type(string)
     * @title("Domain name")
     * @description("Domain name")
     * @required
     */
    public $name;

    ## Domain protection status
    /**
     * @type(boolean)
     * @title("Domain sync status")
     * @description("Whether the domain is synced with SpamExperts filtering")
     * @required
     */
    public $status;

    /** @var $logger Logger */
    private $logger;
    /** @var $report Report */
    private $report;
    private $API;

    public function __construct($API = NULL, $APSC = NULL)
    {
        parent::__construct();

        $this->API = $API;
        $this->APSC = $APSC;
        $this->report = new Report($this->logger = new Logger("Domain"));
    }

    public function unprovision()
    {
        $this->logger->info(__FUNCTION__ . ": start");

        $this->logger->info(__FUNCTION__ . ": Removing SE domain resource");
        $this->API()->removeDomain($this->name);

        $this->logger->info(__FUNCTION__ . ": stop");
    }


    ###


    public function API()
    {
        if (!$this->API) {
            $this->API = new APIClient($this->context->service);
            $this->API->setDefaultOption('auth', array($this->context->username, $this->context->password));
        }
        return $this->API;
    }
}
