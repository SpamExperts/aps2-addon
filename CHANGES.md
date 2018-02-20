# 2.0-20

* Application sets incorrect usage values in OSA every night (#38).

# 2.0-19

* Application compatibility with OSA v7.1.
* Fixed the APS provisioning issue: Incoming and Archiving active when they should not be active (#33).
* Some resource IDs made compatible with APS linter expectations (#31).

# 2.0-18

* Implemented the possibility to skip protection of "remote" domains (#25).

# 2.0-17

* Fixed 'Some protected users not showing Protected in Odin' (#23).
* Implemented 'Archiving not activated when Application Counter is increased as part of upgrade' (#14).

# 2.0-16

* Fixed '500 Internal Server Error when provisioning a context resource' (#6).
* Fixed 'Error: Only variables should be passed by reference' (#9).

# 2.0-15

* Deleting one of account admins does not not cause unprotection on the bound subscription anymore.
* Fixed issue with product availability flag - now it is possible to disable a product for provisioning even if it's enabled for underlying admin account.

# 2.0-14

* Implemented the possibility to avoid all resource counters update hourly, flooding the API.

# 2.0-12

* Resolved issue when retrieving context resources.

# 2.0-11

* Fixed failed provisioning procedure for accounts without some products.

# 2.0-10

* Case-insensitive usernames comparison when checking user presence in the SpamFilter.

# 2.0-9

* Implemented automatic protection of all resources when the application is added to the existing account.
* Spampanel domain- and email user verification procedure has been optimized.
* Resource ID lists are sent in PUT request body instead of query string to avoid too long URLs.
 
# 2.0-8

* Fixed wrong condition of the Login button availability for service users on customer level.
* Made provisioning of the most first domain in the subscription working.
 
# 2.0-7

* Fixed JavaScript error on the service sser login page.

# 2.0-6

* Domains and email which are protected under other subscription are not shown in current subscription.
* Service user login as email user.
* Updated SpamExperts logo.

# 2.0-5

* HC and sub-admin support. Upgrade process defined and added environment checking.

# 2.0-4

* Support for multiple subscriptions per customer with auto domain protection enabled. 
* Handling instance MX record changes appropriately. 
* Fixed some bugs and made some stability updates.

# 2.0-3

* Customer multi-subscription support, some bug fixes and minor improvements.

# 2.0-2

* Added service user wizard, related changes to email user handling and domain owner checking/updating.

# v2.0-1

* SpamExperts 2.0 email protection system integration for PA completely rebuilt for APS2.0. First release!
