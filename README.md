# Cointopay.com plugin for: Wordpress WooCommerce

Crypto currency payment plugin for Wordpress WooCommerce, you can receive crypto currencies for your products and services as alternative to e.g. Paypal. Cointopay receives the currencies into your account on Cointopay.com. Optional: we can payout to your bank in EURO without volatility risk for you.

There are three prerequisites to get started:

- Please create an account on Cointopay.com, note down MerchantID, SecurityCode and AltCoinID as preferred checkout currency from the Account section (1 = bitcoin, 2 = litecoin etc.)
- Install the Curl PHP Extension on your server
- Install JSON Encode on your server

# Version:
- Version 0.2

# Improvements in 0.2:
- You do not need to configure your ConfirmationURL any longer. FYI: The wc-api is automatically set to Cointopay (?wc-api=Cointopay)
- Beautiful redirect message once payment is confirmed

# Configuration Instructions

    1. Install zip file using WordPress built-in Add New Plugin installer (https://github.com/Cointopay/Wordpress-WooCommerce/blob/master/wc-cointopay.zip)
    2. Go to your WooCommerce Settings, and click the Checkout tab, find C2P/Cointopay.
    3. In settings "MerchantID" <- set your Cointopay ID.
    4. In settings "AltCoinID", this can also be found in the Account section of Cointopay.com. Default 1 for bitcoin, 2 litecoin etc..
    5. In settings "SecurityCode" <- set your Cointopay Security code (no API key required)
    6. Save changes

Tested on:
- WordPress 3.8.1 --> 4.9.5
- WooCommerce 2.1.9 --> 3.3.5

### Notes:
- Please note that the default checkout currency is Bitcoin, the customer can pay via other currencies as well by clicking the currency icon. Enable other currencies on Cointopay.com by going to Account > Wallet preferences and selecting multiple currencies.
- We set a paid payment to paid, a partial payment stays as unpaid in WooCommerce. You will receive the partial payment in your account on Cointopay.com. Payment notifications via IPN messaging.

If you have any questions, please send them to support@cointopay.com, we do appreciate a mail when you are going live so we can monitor your go-live as well.

Thank you for being our customer, we look forward to working together.

### FOR DEVELOPERS AND SALES REPS
PLEASE NOTE OUR AFFILIATE PROGRAM, YOU RECEIVE 0.5% OF ALL YOUR REFERRALS!
Create an account on Cointopay.com and send your prospects the following link: https://cointopay.com/?r=[yourmerchantid], you will receive mails when payments come into your account. 


