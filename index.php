<?php
// date_default_timezone_set('Africa/Nairobi');
// define("LOG_FILE", "error.log");

// $sessionId = isset($_GET['session_id']) ? $_GET['session_id'] : '';
// $msisdn = isset($_GET['MSISDN']) ? $_GET['MSISDN'] : '';
// $serviceCode = isset($_GET['service_code']) ? $_GET['service_code'] : '';
// $ussdString = isset($_GET['ussd_string']) ? $_GET['ussd_string'] : '';

date_default_timezone_set('Africa/Nairobi');
define("LOG_FILE", "error.log");
error_log("[ERROR : " . date("Y-m-d H:i:s") . "] query from safaricom \nParams=" . print_r($_REQUEST, true), 3, LOG_FILE);
$sessionId = isset($_REQUEST['sessionId']) ? $_REQUEST['sessionId'] : '';
$msisdn = isset($_REQUEST['phoneNumber']) ? $_REQUEST['phoneNumber'] : '';
$serviceCode = isset($_REQUEST['serviceCode']) ? $_REQUEST['serviceCode'] : '';
$ussdString = isset($_REQUEST['text']) ? $_REQUEST['text'] : '';

include_once("Models.php");
include_once ("./classes/MenuItems.php");
include_once ("./classes/RootMenuAction.php");
include_once ("./classes/MyAccountAction.php");
include_once ("./classes/UssdUtils.php");
include_once ("./classes/EnglishMenu.php");
include_once ("./classes/KiswahiliMenu.php");

if ($ussdString == "") {
    $ussdSession = new UssdSession();
    $ussdSession->sessionId = $sessionId;
    $ussdSession->msisdn = $msisdn;
    $ussdSession->ussdCode = $serviceCode;
    $ussdSession->ussdString = $ussdString;
    $ussdSession->ussdProcessString = $ussdString;

    $rootMenu = new RootMenuAction();
    $ussdSession = $rootMenu->process($ussdSession);
    createNewUssdSession($ussdSession);
} else {
    $ussdString = cleanUssdString($ussdString);
    $ussdSessionList = getUssdSessionList($sessionId);
    if (count($ussdSessionList) > 0) {
        $ussdSession = $ussdSessionList[0];
        $ussdSession->ussdString = $ussdString;
        $ussdSession->ussdProcessString = $ussdString;
        $ussdSession->previousFeedbackType = $ussdSession->currentFeedbackType;

        if (MenuItems::FIRSTNAME_REQ == $ussdSession->previousFeedbackType ||
                MenuItems::LASTNAME_REQ == $ussdSession->previousFeedbackType ||
                MenuItems::ID_NUMBER_REQ == $ussdSession->previousFeedbackType ||
                MenuItems::COUNTY_REQ == $ussdSession->previousFeedbackType ||
                MenuItems::SUB_COUNTY_REQ == $ussdSession->previousFeedbackType) {
            $registration = new RegistrationAction();
            $ussdSession = $registration->process($ussdSession);
        } else {
            $menuItems = new MenuItems();
//            $menuSuffix = "\n00 Home";
            $params = explode("*", $ussdSession->ussdProcessString);
            $lastSelection = trim($params[count($params) - 1]);
            if ("" == $ussdSession->ussdProcessString || "00" === $lastSelection ||
                    MenuItems::PROFILE_REQ == $ussdSession->previousFeedbackType) {
                $ussdSession = $menuItems->setMainMenu($ussdSession);
                $reply = "CON " . $ussdSession->currentFeedbackString;
                $ussdSession->currentFeedbackString = $reply;
            } elseif (MenuItems::MAINMENU_REQ == $ussdSession->previousFeedbackType) {
                if ("1" == $lastSelection) {//My Account
                    $myAccountAction = new EnglishAction();
                    $ussdSession = $myAccountAction->process($ussdSession);
                } elseif ("2" == $lastSelection) {//
                    $subCountyAction = new KishwahiAction();
                    $ussdSession = $subCountyAction->process($ussdSession);
                } else {
                    $ussdSession = $menuItems->setMainMenu($ussdSession);
                    $reply = "CON INVALID INPUT. Only number 1-3 allowed.\n" . $ussdSession->currentFeedbackString;
                    $ussdSession->currentFeedbackString = $reply;
                }
          
            } elseif (MenuItems::ENGLISHMENU_REQ == $ussdSession->previousFeedbackType ||
                MenuItems::MYACCOUNT_CATEGORY_REQ == $ussdSession->previousFeedbackType ||
                MenuItems::PRODUCT_REQ == $ussdSession->previousFeedbackType||
                MenuItems::QUANTITY_REQ == $ussdSession->previousFeedbackType) {
                $myAccountAction = new EnglishAction();
                $ussdSession = $myAccountAction->process($ussdSession);
            } else {
                
                $subCountyAction = new KishwahiAction();
                $ussdSession = $subCountyAction->process($ussdSession);
            }
//            $ussdSession->currentFeedbackString = $reply;
        }
    } else {
        $ussdSession = new UssdSession();
        $reply = "END Connection error. Please try again.";
        $ussdSession->currentFeedbackString = $reply;
    }
    updateUssdSession($ussdSession);
}

echo $ussdSession->currentFeedbackString;


