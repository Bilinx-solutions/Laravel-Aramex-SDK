<?php

namespace Shipper\Aramex;

use Shipper\Aramex\Core;
use Shipper\Aramex\Helpers\AramexHelper;

/**
 * The Package Interface that will be used in App\Http\ 
 */
class Aramex
{

    /**
     *
     *  @param array of pickup parameters
     *  @return object described in https://
     */
    public static function createPickup($account, $param = [])
    {
        // Define an instance from the core class.
        $aramex = new Core($account);
        // Import SoapCLient object from Aramex's endpoint.

        $soapClient = AramexHelper::getSoapClient(AramexHelper::SHIPPING);


        // Preparation for initializing pickup request (Extract the data). 
        $pickupAddress = AramexHelper::extractPickupAddressContact($param);     // unchangeable
        $pickupDetails = AramexHelper::extractPickupDetails($param);            // changeable

        // initialize pickup request.
        $aramex->initializePickup($pickupDetails, $pickupAddress);

        // call the SoapClient API.
        $call = $soapClient->CreatePickup($aramex->getParam());

        $ret = new \stdClass;
        // check the response.
        if ($call->HasErrors) {

            // prepare return object with errors described in call response.
            $ret->error = 1;
            // No one knows what is the structure of the response 
            if (is_array($call->Notifications)) {
                $ret->errors = $call->Notifications['Notification'];
            } else {
                $ret->errors = $call->Notifications->Notification;
            }
        } else {

            // extract helpful data from call response.
            $pickupGUID = $call->ProcessedPickup->GUID;
            $pickupId = $call->ProcessedPickup->ID;
            //Extra Stuffs TODO.


            // Prepare return object.
            $ret->error = 0;
            $ret->pickupGUID = $pickupGUID;
            $ret->pickupID   = $pickupId;
        }
        // return the prepared object.
        return $ret;
    }

    public static function cancelPickup($account, $pickupGuid, $comment)
    {
        // Define an instance from the core class.
        $aramex = new Core($account);

        // Import SoapCLient object from Aramex's endpoint. 
        $soapClient = AramexHelper::getSoapClient(AramexHelper::SHIPPING);


        $aramex->initializePickupCancelation($pickupGuid, $comment);

        $call = $soapClient->CancelPickup($aramex->getParam());

        $ret = new \stdClass;

        if ($call->HasErrors) {
            $ret->error = 1;
            $ret->errors = $call->Notifications['Notification'];
        } else {
            $ret = $call;
        }
        return $ret;
    }


    /**
     *
     * @param array of shipment parameters 
     * @return object described in https://
     **/
    public static function createShipment($account, $param = [], $labelInfo = null)
    {
        // Define an instance from the core class.
        $aramex = new Core($account, $labelInfo);
        // Import SoapCLient object from Aramex's endpoint. 

        $soapClient = AramexHelper::getSoapClient(AramexHelper::SHIPPING);

        $shipperAddress = AramexHelper::extractShipperAddressContact($param);
        $consigneeAddress = AramexHelper::extractConsigneeAddressContact($param);
        $thirdPartyAddress = AramexHelper::extractThirdPartyAddressContact($param);

        $shipmentDetails = AramexHelper::extractShipmentDetails($param);

        $aramex->initializeShipment($shipperAddress, $consigneeAddress, $thirdPartyAddress, $shipmentDetails);

        $call =  $soapClient->CreateShipments($aramex->getParam());

        $ret = new \stdClass;

        if ($call->HasErrors) {
            $ret->error = 1;
            if (isset($call->Notifications->Notification)) {
                $ret->errors = [$call->Notifications->Notification];
            }

            if (is_object($call->Shipments->ProcessedShipment->Notifications->Notification)) {
                $ret->errors = [$call->Shipments->ProcessedShipment->Notifications->Notification];
            } else {
                $ret->errors = $call->Shipments->ProcessedShipment->Notifications->Notification;
            }
        } else {
            $ret = $call;
        }

        return $ret;
    }



    public static function calculateRate($account, $origin, $destination, $shipmentDetails, $currency)
    {

        $aramex = new Core($account);

        $soapClient = AramexHelper::getSoapClient(AramexHelper::RATE);


        $destinationAddress = AramexHelper::extractAddress($destination);

        $originAddress = AramexHelper::extractAddress($origin);

        $details = AramexHelper::extractCalculateRateShipmentDetails($shipmentDetails);

        $aramex->initializeCalculateRate($originAddress, $destinationAddress, $details, $currency);

        $call =  $soapClient->calculateRate($aramex->getParam());

        $ret = new \stdClass;

        if ($call->HasErrors) {
            $ret->error = 1;
            $ret->errors = $call->Notifications;
        } else {
            $ret = $call;
        }

        return $ret;
    }


    public static function trackShipments($account, $param)
    {
        if (!is_array($param)) {
            throw new \Exception("trackShipments Parameter Should Be an Array includes Strings", 1);
        }

        foreach ($param['shipments'] as $shipmentId) {
            if (!is_string($shipmentId)) {
                throw new \Exception("trackShipments Parameter Should Be an Array includes Strings", 1);
            }
        }

        $soapClient = AramexHelper::getSoapClient(AramexHelper::TRACKING);


        $aramex = new Core($account);

        $aramex->initializeShipmentTracking($param);

        $call = $soapClient->TrackShipments($aramex->getParam());

        $ret = new \stdClass;

        if ($call->HasErrors) {
            $ret->error = 1;
            $ret->errors = $call->Notifications;
        } else {
            $ret = $call;
        }

        return $ret;
    }


    public static function fetchCountries($account, $code = null)
    {

        $soapClient = AramexHelper::getSoapClient(AramexHelper::LOCATION);

        $aramex = new Core($account);

        $aramex->initializeFetchCountries($code);

        if (isset($code))
            $call = $soapClient->FetchCountry($aramex->getParam());
        else
            $call = $soapClient->FetchCountries($aramex->getParam());

        $ret = new \stdClass;

        if ($call->HasErrors) {
            $ret->error = 1;
            $ret->errors = $call->Notification;
        } else {
            $ret = $call;
        }

        return $ret;
    }

    public static function fetchCities($account, $code, $nameStartWith = null)
    {
        $soapClient = AramexHelper::getSoapClient(AramexHelper::LOCATION);

        $aramex = new Core($account);

        $aramex->initializeFetchCities($code, $nameStartWith);

        $call = $soapClient->FetchCities($aramex->getParam());

        $ret = new \stdClass;

        if ($call->HasErrors) {
            $ret->error = 1;
            $ret->errors = $call->Notifications;
        } else {
            $ret = $call;
        }

        return $ret;
    }

    public static function validateAddress($account, $address)
    {
        $address = AramexHelper::extractAddress($address);


        $soapClient = AramexHelper::getSoapClient(AramexHelper::LOCATION);

        $aramex = new Core($account);

        $aramex->initializeValidateAddress($address);

        $call = $soapClient->ValidateAddress($aramex->getParam());
        $ret = new \stdClass;

        if ($call->HasErrors) {
            $ret->error = 1;
            $ret->errors = $call->Notifications;
        } else {
            $ret = $call;
        }

        return $ret;
    }
}
