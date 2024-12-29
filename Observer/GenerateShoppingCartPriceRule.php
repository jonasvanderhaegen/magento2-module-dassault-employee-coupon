<?php

namespace Jvdh\DassaultEmployeeCoupon\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Jvdh\DassaultEmployeeCoupon\Helper\Data;
use Jvdh\DassaultEmployeeCoupon\Helper\Config;

/**
 * Class GenerateShoppingCartPriceRule
 *
 * This observer triggers when a specific Magento event is fired (e.g., customer registration 
 * or some other event tied to the `observer.xml` configuration). 
 *
 * It checks:
 *  1) If the Dassault Employee Coupon module is enabled
 *  2) If the customer belongs to an allowed customer group
 * 
 * If both conditions are met, it generates/assigns a unique coupon code to the customer.
 */
class GenerateShoppingCartPriceRule implements ObserverInterface
{
    /**
     * Constructor
     *
     * @param Data   $helper  Contains logic to assign (generate) coupon codes to a given customer
     * @param Config $config  Provides configuration info (e.g., if the module is enabled, etc.)
     */
    public function __construct(
        protected Data $helper,
        protected Config $config
    ) {
    }

    /**
     * execute
     *
     * This method is automatically called when the associated event is dispatched.
     * 
     * @param  Observer $observer The Magento observer object that contains event data
     * @return void
     */
    public function execute(Observer $observer): void
    {
        // Check if the Dassault Employee Coupon module is globally enabled
        if (!$this->config->isEnabled()) {
            return; // If disabled, do nothing
        }

        // Retrieve the customer object from the event
        $customer = $observer->getEvent()->getCustomer();

        // Check if the customer's group ID matches any of the allowed group IDs
        if (!in_array($customer->getGroupId(), $this->config->getSelectedCustomerGroupIds())) {
            return; // If not in the allowed groups, do nothing
        }

        // If the module is enabled and the customer is in an allowed group,
        // generate/assign a unique coupon code for this customer
        $this->helper->assignCouponCode($customer);
    }
}
