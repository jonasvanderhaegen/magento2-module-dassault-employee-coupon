<?php
namespace Jvdh\DassaultEmployeeCoupon\Helper;

use DateTime;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\SalesRule\Api\Data\RuleInterface;
use Magento\SalesRule\Api\Data\RuleInterfaceFactory;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\SalesRule\Api\Data\CouponInterfaceFactory;
use Magento\SalesRule\Api\CouponRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Jvdh\DassaultEmployeeCoupon\Helper\Config;

/**
 * Class CartPriceRule
 *
 * This class is responsible for creating and pruning cart price rules and
 * their associated coupons. 
 * 
 * 1) It checks if a rule exists for a particular date range (e.g., current month)
 * 2) If it doesn't, it creates a new rule for that date range
 * 3) It then creates a specific coupon code and associates it with the newly created or existing rule
 * 4) It can also prune (delete) expired rules based on the to_date
 */
class CartPriceRule
{
    /**
     * Constructor
     *
     * @param StoreManagerInterface   $storeManager       Used to retrieve the default store's website ID
     * @param RuleInterfaceFactory    $cartPriceRuleFactory Factory for creating RuleInterface objects
     * @param RuleRepositoryInterface $ruleRepository     Repository interface for managing sales rules
     * @param CouponInterfaceFactory  $couponFactory      Factory for creating coupon objects
     * @param CouponRepositoryInterface $couponRepository Repository interface for managing coupons
     * @param SearchCriteriaBuilder   $searchCriteriaBuilder Used to build search criteria for repository lookups
     * @param Config                  $config             Custom config helper to fetch discount info, etc.
     */
    public function __construct(
        protected StoreManagerInterface $storeManager,
        protected RuleInterfaceFactory $cartPriceRuleFactory,
        protected RuleRepositoryInterface $ruleRepository,
        protected CouponInterfaceFactory $couponFactory,
        protected CouponRepositoryInterface $couponRepository,
        protected SearchCriteriaBuilder $searchCriteriaBuilder,
        protected Config $config
    ) {
    }

    /**
     * Checks whether a cart price rule already exists for a given start date.
     * 
     * @param  string      $fromDate The "start of month" date in 'Y-m-d' format
     * @return int|bool    Returns the rule ID if one is found, otherwise false
     */
    private function ruleExists(string $fromDate): int|bool
    {
        // Build search criteria to find rules matching the exact from_date
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('from_date', $fromDate, 'eq');

        // Fetch matching rules
        $results = $this->ruleRepository->getList($searchCriteria->create());

        // If any rule is found, return the first rule's ID; else return false
        return ($results->getTotalCount())
            ? $results->getItems()[0]->getRuleId()
            : false;
    }

    /**
     * Checks whether a coupon code already exists in the database.
     *
     * @param  string $couponCode The coupon code to check
     * @return bool               True if the code exists, false otherwise
     */
    private function couponCodeExist(string $couponCode): bool
    {
        // Build search criteria to find a coupon with matching code
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('code', $couponCode, 'eq');

        // Fetch matching coupons
        $results = $this->couponRepository->getList($searchCriteria->create());

        // If at least one coupon is found, the code exists
        return $results->getTotalCount() > 0;
    }

    /**
     * Removes any expired coupon rules from the system. 
     *
     * A rule is considered expired if:
     *  - to_date < the current date
     *  - the name begins with "DASSAULT employee coupon"
     *
     * This method retrieves matching rules and deletes them one by one.
     *
     * @return void
     */
    public function prune(): void
    {
        // Current date/time
        $now = new DateTime();
        
        // Build search criteria for rules whose 'to_date' is before today
        // and whose name matches a Dassault employee coupon pattern
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('to_date', $now->format('Y-m-d'), 'lt')
            ->addFilter('name', 'DASSAULT employee coupon%', 'like');

        // Fetch the rules that match our criteria
        $results = $this->ruleRepository->getList($searchCriteria->create());

        // If there's at least one matching rule, delete it
        if ($results->getTotalCount()) {
            foreach ($results->getItems() as $item) {
                $this->ruleRepository->deleteById($item->getRuleId());
            }
        }
    }

    /**
     * Creates (or associates) a cart price rule for the current month 
     * and generates a new coupon code if it does not already exist.
     *
     * @param  string $couponCode The new coupon code to be created/linked
     * @return void
     */
    public function create(string $couponCode): void
    {
        // If the coupon code already exists, don't create it again
        if ($this->couponCodeExist($couponCode)) {
            return;
        }

        // Get the first day of the current month (e.g., 2024-12-01)
        $now = new DateTime();
        $now->modify('first day of this month');
        $startOfMonth = $now->format('Y-m-d');

        // Check if there's an existing rule for the start of this month
        $ruleId = $this->ruleExists($startOfMonth);

        // If no rule exists yet, create a new one
        if (!$ruleId) {
            $ruleId = $this->createRule($now, $startOfMonth);
        }

        // Finally, create and link the specific coupon code to that rule ID
        $this->createCoupon($ruleId, $couponCode);
    }

    /**
     * Actually creates a new cart price rule for the specified month 
     * if one did not already exist. The rule will be valid up to 5 months 
     * after the starting month.
     *
     * @param  DateTime $now          The DateTime object already set to the first day of the month
     * @param  string   $startOfMonth The 'Y-m-d' formatted date string for the month start
     * @return int                    The newly created rule ID
     */
    private function createRule(DateTime $now, string $startOfMonth): int
    {
        // Retrieve the default store's website ID
        $websiteId = $this->storeManager->getDefaultStoreView()->getWebsiteId();
        
        // Get the customer group IDs that should apply to this discount
        $groupIds = $this->config->getSelectedCustomerGroupIds($websiteId);
        
        // Get the discount amount from configuration (e.g., 10% discount)
        $discount = $this->config->getDiscountAmount($websiteId);

        // Create a user-friendly month name, e.g., "December 2024"
        $monthName = $now->format('F Y');

        // Advance the date by 5 months and move to the last day of that month
        // to set the coupon's valid ending date
        $now->modify('+5 months');
        $now->modify('last day of this month');
        $endOfMonth = $now->format('Y-m-d');

        // Create a new cart price rule entity
        /** @var RuleInterface $cartPriceRule */
        $cartPriceRule = $this->cartPriceRuleFactory->create();
        $cartPriceRule->setName("DASSAULT employee coupons for {$monthName}");
        $cartPriceRule->setIsActive(true);
        $cartPriceRule->setCouponType(RuleInterface::COUPON_TYPE_SPECIFIC_COUPON);
        $cartPriceRule->setCustomerGroupIds($groupIds);
        $cartPriceRule->setWebsiteIds([$websiteId]);
        $cartPriceRule->setSimpleAction(\Magento\SalesRule\Model\Rule::BY_PERCENT_ACTION);
        $cartPriceRule->setDiscountAmount($discount);
        $cartPriceRule->setFromDate($startOfMonth);
        $cartPriceRule->setToDate($endOfMonth);
        $cartPriceRule->setUseAutoGeneration(true);
        $cartPriceRule->setStopRulesProcessing(false);

        // Save the newly created rule into the system
        $ruleCreate = $this->ruleRepository->save($cartPriceRule);

        // Return the unique ID of the new rule
        return $ruleCreate->getRuleId();
    }

    /**
     * Creates a new coupon record within the existing or newly created rule.
     *
     * @param  int    $ruleId     The sales rule ID to attach the coupon to
     * @param  string $couponCode The unique code for the coupon
     * @return void
     */
    private function createCoupon(int $ruleId, string $couponCode): void
    {
        // Create a new coupon entity object
        $coupon = $this->couponFactory->create();
        $coupon->setCode($couponCode)
            ->setType(\Magento\SalesRule\Helper\Coupon::COUPON_TYPE_SPECIFIC_AUTOGENERATED)
            ->setRuleId($ruleId);

        // Persist the coupon to the database
        $this->couponRepository->save($coupon);
    }
}
