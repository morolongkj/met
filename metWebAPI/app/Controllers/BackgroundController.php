<?php
namespace App\Controllers;

use App\Controllers\BaseController;

class BackgroundController extends BaseController
{
    public function logMyCustomMessage()
    {

        log_message("error", "This is error message logged by Online Web Tutor");
        log_message("alert", "This is alert message logged by Online Web Tutor");
        log_message("emergency", "This is emergency message logged by Online Web Tutor");
        log_message("critical", "This is critical message logged by Online Web Tutor");
    }
}
