<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Jobs\DoctorNotifyJob;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\NotifyMe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    public function openNotification(Request $request)
    {
        if (empty($request->notificationid)) {
            return response()->json(["message" => "Invalid Request", "status" => "error"], 400);
        }
        NotifyMe::where('id', $request->notificationid)->update(['seen'=>'1']);
        return response()->json([
            "status" => "success",
        ], 200);
    }

    public function fetchnotificationforpatient(Request $request)
    {
        $customer=auth()->user();
        $n=NotifyMe::where('patientid', $customer->patientid)
        ->where('seen', '1')
        ->where('who', '1')
        ->get();
        $count=count($n);
        return response()->json([
            'message' => $count." Notifications Found",
            "status" => "success",
            'n' => $n,
        ], 200);
    }

    public function fetchnotificationfordoctor(Request $request)
    {
        $doctor=auth()->user();
        $n=NotifyMe::where('doctorid', $doctor->doctorid)
        ->where('seen', '1')
        ->where('who', '2')
        ->get();
        $count=count($n);
        return response()->json([
            'message' => $count." Notifications Found",
            "status" => "success",
            'n' => $n,
        ], 200);
    }

}
