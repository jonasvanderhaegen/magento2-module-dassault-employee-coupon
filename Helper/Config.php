<?php

namespace Jvdh\DassaultEmployeeCoupon\Helper;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class Config
 *
 * Provides access to the Dassault Employee Coupon configuration settings
 * at the website scope level. This includes:
 *  - Whether the functionality is enabled
 *  - Which customer groups are eligible
 *  - The discount amount
 */
class Config
{
    /**
     * Constructor
     *
     * @param ScopeConfigInterface $scopeConfig  Magento's configuration interface,
     *                                          used to fetch custom settings 
     *                                          from the store/website scope.
     */
    public function __construct(
        protected ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Checks if the Dassault Employee Coupon functionality is enabled.
     *
     * @param  int  $websiteId The Magento website ID; defaults to 0 (global).
     * @return bool            True if the functionality is enabled; false otherwise.
     */
    public function isEnabled(int $websiteId = 0): bool
    {
        return $this->scopeConfig->isSetFlag(
            'dassaultemployeecoupon/general/enabled',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * Retrieves the list of customer group IDs that are allowed
     * to use Dassault Employee Coupons.
     *
     * @param  int   $websiteId The Magento website ID; defaults to 0 (global).
     * @return array            An array of customer group IDs.
     */
    public function getSelectedCustomerGroupIds(int $websiteId = 0): array
    {
        // The configuration value is stored as a comma-separated string;
        // explode it into an array of IDs.
        $groups = $this->scopeConfig->getValue(
            'dassaultemployeecoupon/general/customer_groups',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
        return explode(',', $groups);
    }

    /**
     * Retrieves the discount amount configured for Dassault Employee Coupons.
     *
     * @param  int   $websiteId The Magento website ID; defaults to 0 (global).
     * @return float            The discount amount, converted to a float.
     */
    public function getDiscountAmount(int $websiteId = 0): float
    {
        return (float) $this->scopeConfig->getValue(
            'dassaultemployeecoupon/general/discount_amount',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }
}
