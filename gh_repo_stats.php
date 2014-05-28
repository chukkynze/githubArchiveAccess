<?php
/**
 * @author      Chukky Nze <chukkynze@gmail.com>
 * @since       5/27/14 1:50 PM
 *
 * @desc        
 * 
 * @package     GithubArchiveAccess
 *
 * @example
 *
 * php gh_repo_stats.php "2014-01-13 12:00:00" "2014-06-13 12:00:00" PushEvent 20 debug
 */

/**
 * Class GH_RepoStats
 */
class GH_RepoStats
{
    public $afterDatetime;
    public $beforeDatetime;
    public $eventName;
    public $outputCount;
    public $debugMode;

    public $maxOutputCount      =   20;
    public $eventTypesUrl       =   'https://api.github.com/events';


    /**
     * This simply gets the ball rolling. Makes sure all supplied arguments are valid and ensures all settings are ... set
     *
     * @param $argv1
     * @param $argv2
     * @param $argv3
     * @param $argv4
     * @param $argv5
     * @return bool
     */
    public function initializeVariablesFromArguments($argv1, $argv2, $argv3, $argv4, $argv5 )
    {
        date_default_timezone_set('America/Los_Angeles');

        $this->afterDatetime    =   $argv1;
        $this->beforeDatetime   =   $argv2;
        $this->eventName        =   $argv3;
        $this->outputCount      =   $argv4;
        $this->debugMode        =   $argv5;

        if($argv1 == "-h" || $argv1 == "--help")
        {
            $this->showHelp();
        }

        $this->codeComments("debug", "property afterDatetime set to "    . $this->debugMode);
        $this->codeComments("debug", "property beforeDatetime set to "   . $this->beforeDatetime);
        $this->codeComments("debug", "property eventName set to "        . $this->eventName);
        $this->codeComments("debug", "property outputCount set to "      . $this->outputCount);
        $this->codeComments("debug", "property debugMode set to "        . $this->debugMode);

        if($this->validateArguments())
        {
            $this->codeComments("debug", "Arguments are valid.");
            return TRUE;
        }
        else
        {
            $this->codeComments("debug", "Arguments are invalid.");
            return FALSE;
        }
    }


    /**
     * Validates the supplied output count and ensures it is below approved limits
     *
     * @param $outputCount
     * @return bool
     */
    public function validateOutputCount($outputCount)
    {
        $outputCount    =   (int) $outputCount * 1;

        if(!is_int($outputCount) && !is_numeric($outputCount))
        {
            $this->codeComments("debug", "Your output count must be a valid number.");
            return FALSE;
        }
        elseif($outputCount <= 0 || $outputCount > $this->maxOutputCount)
        {
            $this->codeComments("debug", "Your output count must be greater than or equal to 1 but less than " .
                                        $this->maxOutputCount . ". Reduce it or complain to the sys admins.");
            return FALSE;
        }
        else
        {
            $this->codeComments("debug", "Your output count is valid. You get a gold star.");
            return TRUE;
        }
    }


    /**
     * Makes a GET request to https://api.github.com/events and adds the current list of event types to an array
     * which is in turn used to validate the supplied event name argument
     *
     * @param $eventTypeName
     * @return bool
     */
    public function validateEventName($eventTypeName)
    {
        $eventTypeName  =   (string) $eventTypeName;

        $this->codeComments("debug", "Initializing cURL...");
        $curl   =   curl_init($this->eventTypesUrl);

        $this->codeComments("debug", "Setting cURL options...");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER   ,   true);
        curl_setopt($curl, CURLOPT_HEADER           ,   0);
        curl_setopt($curl, CURLOPT_HTTPHEADER       ,   array
                                                        (
                                                            'Content-Type: application/json',
                                                            'User-Agent: Hyfn GitHub Archive Challenge'
                                                        ));

        $curlOutput         =   curl_exec($curl);
        $this->codeComments("debug", "Executing cURL");
        $curlResponseInfo   =   curl_getinfo($curl);
        $this->codeComments("debug", "Getting cURL response info.");

        curl_close($curl);

        if($curlOutput === FALSE)
        {
            $this->codeComments("debug", 'An error occurred during curl execution. Additional info: ' . var_export($curlResponseInfo));
            exit;
        }
        else
        {
            $eventTypes         =   json_decode($curlOutput);
            $this->codeComments("debug", "Received " . count($eventTypes) . " Event Types.");

            if(count($eventTypes) > 0)
            {
                if(isset($eventTypes->response->status) && $eventTypes->response->status == 'ERROR')
                {
                    $this->codeComments("debug", "An error occurred while retrieving current event types. This may help: " . $eventTypes->response->errormessage);
                    exit;
                }

                $eventTypeValidationArray = array();
                foreach ($eventTypes as $eventType)
                {
                    $this->codeComments("debug", "    Event type: " . $eventType->type . " added to validation array.");
                    $eventTypeValidationArray[]   =   (string) $eventType->type;
                }

                if(in_array($eventTypeName, $eventTypeValidationArray))
                {
                    $this->codeComments("debug", "Your event type is valid. You get a gold star.");
                    return TRUE;
                }
                else
                {
                    $this->codeComments("debug", "Your event type name is invalid. Check https://developer.github.com/v3/activity/events/types/.");
                    return FALSE;
                }
            }
            else
            {
                $this->codeComments("debug", "Could not retrieve event types. Check https://developer.github.com/v3/activity/events/types/.");
                return FALSE;
            }

        }
    }


    /**
     * Validates the supplied arguments for after and before time
     *
     * @param string $startDateTime
     * @param string $endDateTime
     * @return bool
     */
    public function validateDateTimes($startDateTime = '0000-00-00 00:00:00', $endDateTime = '0000-00-00 00:00:00')
    {
        $rawStringToTime_start  =   strtotime($startDateTime);
        $rawStringToTime_end    =   strtotime($endDateTime);

        if($rawStringToTime_start >= $rawStringToTime_end)
        {
            $this->codeComments("debug", "Your start time is greater than or equal to your end time. Switch them around or something.");
            return FALSE;
        }
        else
        {
            $this->codeComments("debug", "Your after & before times are valid. You get a gold star.");
            return TRUE;
        }
    }


    /**
     * Run all validation methods for the argument types. Each validation method will have its own documentation and
     * debug output
     *
     * @return bool
     */
    public function validateArguments()
    {
        $this->codeComments("debug", "Validating arguments");

        if(
                $this->validateDateTimes($this->afterDatetime, $this->beforeDatetime)
            &&  $this->validateEventName($this->eventName)
            &&  $this->validateOutputCount($this->outputCount)
        )
        {
            $this->codeComments("debug", "Supplied arguments are valid. Please return all gold stars and get to work.");
            return TRUE;
        }
        else
        {
            $this->codeComments("debug", "At least one supplied arguments is invalid. Please return all gold stars and get to debugging.");
            return FALSE;
        }
    }


    /**
     * Command line based help function fired by the standard -h or --help
     *
     * @author      Chukky Nze <chukkynze@gmail.com>
     * @since 		2/19/14 12:13 PM
     */
    public function showHelp()
    {
        error_log("");
        error_log("");
        error_log("Author: Chukky Nze <chukkynze@gmail.com>");
        error_log("--------------------------------------------------");
        error_log("");
        error_log("You are running gh_access.");
        error_log("");
        error_log("Usage:");
        error_log("php gh_repo_stats.php <AFTER_DATETIME> <BEFORE_DATETIME> <EVENT_NAME> <OUTPUT_COUNT> <DEBUG_MODE>");
        error_log("");
        error_log("");
        error_log("<AFTER_DATETIME> is a date time string in the format 'YYYY-MM-DD HH:II:SS' (inverted commas are required) and is used to determine the start point of the time based duration to search within.");
        error_log("");
        error_log("<BEFORE_DATETIME> is a date time string in the format 'YYYY-MM-DD HH:II:SS' (inverted commas are required) and is used to determine the start point of the time based duration to search within.");
        error_log("");
        error_log("<EVENT_NAME> is a simple string, no inverted commas, and is used to determine the type of GitHub event to search for. Cannot be empty. Must be a valid GitHub event type.");
        error_log("");
        error_log("<OUTPUT_COUNT> is an integer used to determine the number of output rows to return from the search result set. If empty will default to 1.");
        error_log("");
        error_log("<DEBUG_MODE> simple string used to determine the logging mechanism. Options are debug|log|silent.");
        error_log("");
        error_log("");
        error_log("Example:");
        error_log("php gh_repo_stats.php \"2014-01-13 12:00:00\" \"2014-06-13 12:00:00\" Push_Event 20 debug");
        error_log("");

        error_log("");
        error_log("");
    }


    /**
     * Personal function to comment code as well as control logging and debugging statements
     *
     * @author      Chukky Nze <chukkynze@gmail.com>
     * @since 		2/19/14 12:13 PM
     *
     *
     * @param $status
     * @param $message
     */
    function codeComments($status, $message)
    {
        $comment		=	(strtolower(trim($this->debugMode)) == 'debug' ? '		DEBUG:   ' : '') . $message;

        switch(strtolower(trim($this->debugMode)))
        {
            case 'silent'		:	break;
            case 'log'			:	if($status == 'log')
                                    {
                                        error_log( $comment );
                                    }
                                    break;
            case 'debug'		:	error_log( $comment );
                                    break;


            default : exit($message . "Invalid DebugMode specified. Valid options are debug|log|silent. Run php gh_repo_stats.php --help for more information"); break;
        }

    }
}


$GH_RepoStats  =   new GH_RepoStats();
if($GH_RepoStats->initializeVariablesFromArguments($argv[1], $argv[2], $argv[3], $argv[4], $argv[5]))
{
    $GH_RepoStats->codeComments("debug", "Arguments are valid and initialized");


}
else
{
    $GH_RepoStats->codeComments("log", "Exiting Script.");
    exit;
}