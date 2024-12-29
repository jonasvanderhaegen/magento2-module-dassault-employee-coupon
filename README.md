# Dassault Employee Coupon Module

This repository contains a Magento 2 module developed to demonstrate technical expertise and illustrate various aspects of custom Magento 2 development. It showcases:

- **Custom helpers** for coupon code generation and configuration  
- **Cron job** for automatic coupon pruning  
- **Observers** for assigning coupons based on customer group membership  
- **Dynamic discount rules** creation based on system configurations

## Module Purpose
The primary goal of this module is to provide an automated way to generate and manage coupon codes for Dassault employees. The functionality includes:
- Generating unique coupon codes using custom logic  
- Assigning codes to eligible customers automatically  
- Creating and managing corresponding Cart Price Rules  
- Pruning expired coupon codes on a scheduled (cron) basis

The discount code displayed on the Dassault employee intranet must match exactly on the webshop, ensuring they can successfully apply their discount.

Customer logs in -> checks if coupon code exists in cart price rule -> if yes: then do nothing, if no: Generate couponcode and attach to cart price rule. If cart price rule doesn't exist for this month, create cart price rule first, then attach couponcode to it.
Customer registers -> coupon code is generated and attach to cart price rule.


## Repository Structure
This module is typically placed under `app/code/Jvdh/DassaultEmployeeCoupon` within a Magento 2 project. For simplicity, this repository may contain only the contents of that directory.

### Included Components
1. **Cron Jobs**  
   - `Cron/PruneExpiredSalesRules.php`  
     Automatically removes (prunes) expired coupon rules and their associated coupons.

2. **Helper Classes**  
   - `Helper/Data.php`  
     Encapsulates logic for generating Dassault-specific coupon codes based on employee data.  
   - `Helper/Config.php`  
     Provides methods to retrieve module configurations (e.g., discount amount, allowed customer groups, and an enable/disable flag).  
   - `Helper/CartPriceRule.php`  
     Creates and attaches coupon codes to Cart Price Rules, and handles the removal of outdated rules.

3. **Observer**  
   - `Observer/GenerateShoppingCartPriceRule.php`  
     Listens to customer-related events (e.g., upon customer creation or save), checks if the customer is in an eligible group, and assigns a coupon code if necessary.

## Installation
To add this module to your Magento 2 project:

1. **Clone the repository** into the appropriate location:
   ```bash
   git clone <repository_url> app/code/Jvdh/DassaultEmployeeCoupon
   ```

2. **Run Magento setup commands** to install and compile the module:
   ```bash
   php bin/magento setup:upgrade
   php bin/magento setup:di:compile
   php bin/magento cache:flush
   ```

3. **Verify that the module is enabled**:
   ```bash
   php bin/magento module:status Jvdh_DassaultEmployeeCoupon
   ```

## Configuration
The module relies on specific settings configured in the Magento admin panel:

1. **Magento Admin Settings**  
   - Navigate to `Stores > Configuration > Dassault Employee Coupon > General`  
   - Enable/disable the module, specify the discount amount, and define which customer groups are eligible to receive coupons.

2. **Cron Configuration**  
   - Ensure that Magento cron jobs are properly set up. The `PruneExpiredSalesRules` cron job will automatically remove rules after their specified end date.

## Key Features
- **Coupon Code Generation**  
  Custom logic for generating unique coupon codes.  

- **Automatic Assignment**  
  An observer automatically assigns coupon codes to eligible customer accounts when certain events (e.g., customer registration) occur.

- **Cart Price Rule Creation**  
  The module dynamically creates or updates Cart Price Rules in Magento, linking them to the generated coupons.  

- **Coupon Pruning**  
  Expired coupons and rules are removed on a scheduled basis to keep the system clutter-free.

## Notes
- This module is a technical demonstration and may require adjustments or testing before production use.  
- Proper maintenance of cron jobs is essential to ensure expired rules are pruned on schedule.

## License
This project is licensed under the MIT License. Feel free to use, modify, and distribute it as needed.

## Author
Developed by me.

## Contact
For any questions or further discussions, please contact me via [[my LinkedIn profile]](https://www.linkedin.com/in/jonasvdh/).
```
