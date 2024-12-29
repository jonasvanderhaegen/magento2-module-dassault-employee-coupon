<?php
namespace Jvdh\DassaultEmployeeCoupon\Cron;

// Import the helper classes that provide the functionality needed 
use Jvdh\DassaultEmployeeCoupon\Helper\Data;
use Jvdh\DassaultEmployeeCoupon\Helper\Config;

/**
 * Class PruneExpiredSalesRules
 *
 * This class is responsible for automatically pruning (removing) 
 * expired coupon codes on a scheduled (cron) basis.
 */
class PruneExpiredSalesRules
{
    /**
     * Constructor
     *
     * @param Data   $helper Data helper that deals with coupon code operations
     * @param Config $config Config helper that provides module configuration settings
     */
    public function __construct(
        protected Data $helper,
        protected Config $config
    ) {
        // The protected properties $helper and $config will be automatically assigned
        // to this class once the object is instantiated, thanks to PHP 8 constructor promotion.
    }

    /**
     * execute
     *
     * Method called by the Magento cron scheduler. This method checks if the module
     * is enabled, and if so, it triggers the coupon code pruning logic.
     *
     * @return void
     */
    public function execute(): void
    {
        // Check the module's enabled/disabled setting in system configuration
        if (!$this->config->isEnabled()) {
            // If the module isn't enabled, exit early to avoid unnecessary processing
            return;
        }

        // If the module is enabled, proceed to remove any expired coupon codes
        $this->helper->pruneExpiredCouponCodes();
    }
}
