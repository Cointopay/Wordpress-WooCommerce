# Cointopay.com plugin for: Wordpress WooCommerce

Sourcecode for public release located here: https://wordpress.org/plugins/wc-cointopay-com/

Crypto currency payment plugin for Wordpress WooCommerce, you can receive crypto currencies for your products and services as alternative to e.g. Bitcoin, Litecoin, Ethereum, Ripple. Cointopay receives the currencies into your account on Cointopay.com. Optional: we can payout to your bank in EURO without volatility risk for you.

There are three prerequisites to get started:

- Please create an account on Cointopay.com, note down MerchantID and SecurityCode.
- Install the Curl PHP Extension on your server
- Install JSON Encode on your server

Please follow the Wordpress WooCommerce Cointopay Plugin install instructions mentioned here: https://docs.google.com/document/d/1L3Fv1t11SmhuX0jmOQ1bkQl20RrHtx36dH0XKTzbNyI/edit?usp=sharing

# Version:

- Version 1.4.4
# Configuration Instructions

    1. Install zip file using WordPress built-in Add New Plugin installer (https://github.com/Cointopay/Wordpress-WooCommerce/blob/master/wc-cointopay.zip)
    2. Go to your WooCommerce Settings, and click the Payments tab, find Cointopay.
    3. In settings "MerchantID" <- set your Cointopay ID.
    4. In settings "SecurityCode" <- set your Cointopay Security code (no API key required)
    5. Save changes

Tested on:

- WordPress 3.8.1 --> 6.8.3
- WooCommerce 2.1.9 --> 10.3.5

# Changelog

### 2019-12-06 - Version 1.2

- Enhancement - Just making things look better v1.2

### Notes:

- Please note that the default checkout currency is Bitcoin, the customer can pay via other currencies as well by clicking the currency icon. Enable other currencies on Cointopay.com by going to Account > Wallet preferences and selecting multiple currencies e.g. Bitcoin, Litecoin, Ethereum, Ripple etc.
- We set a paid, on hold and cancelled, a partial payment stays on hold in WooCommerce. You will receive the partial payment in your account on Cointopay.com. Payment notifications via IPN messaging.

This plugin is using a https://cointopay.com backend integration, the Coinplusgroup S.R.O. Terms and conditions incl. privacy policy are applicable, please read the following information carefully: Terms: https://cointopay.com/terms and privacy policy: https://cdn-eur.s3.eu-west-1.amazonaws.com/Coinplusgroup-sro-Privacy-Policy.pdf. Any questions, please send to support@cointopay.com.

Thank you for being our customer, we look forward to working together.

### About Cointopay.com

We are an international crypto currency payment processor, meaning that we accept payments from your customers and make the funds available to you (incl. in form of fiat currency like euro). The direct integration with Wordpress Woocommerce provides you with a seamless payment experience while underlying dealing with diverse and complex blockchain technologies like Bitcoin, Ethereum, Neo, Dash, Ripple and many more. P.S. If you want your own crypto currency to become available in this plugin, we can provide that for you as well, Cointopay has been a technological payment incubator since 2014!

### FOR DEVELOPERS AND SALES REPS

PLEASE NOTE OUR AFFILIATE PROGRAM, YOU RECEIVE 0.5% OF ALL YOUR REFERRALS!
Create an account on Cointopay.com and send your prospects the following link: https://cointopay.com/?r=[yourmerchantid], you will receive mails when payments come into your account.
