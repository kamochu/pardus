# Pardus
Pardus (also referred to as Simple SMS Gateway, SSG) is an SMS gateway solution that integrates with Huawei SDP.
 
# Change Log
## Version 1.0.4 released on 27-07-2015
### New Features
- Add change password functionality
- Add IP white listing
- Added printing and exporting functionality
- SSG Forwarder functionality (SSG can push the incoming requests to an external database SQL server)


### Modifications made
- Changing logs format
- Hide service menus on the web portal for people who have not logged in
- Move service configutaions to the SSG portal as opposed to making changes on configuration file.
- Change sample service name from ‘Bulk_22652_Global’ to ‘22652_Music_Msanii’
- Date picker to be added to the SSG UI portal to improve user experience
- Added keyword to message subscriptions requests

### Features removed
- Remove remember functionality


### Issues Fixed
- Subscription response(XML) to SDP was corrected. This was affecting all subscriptions services. 
