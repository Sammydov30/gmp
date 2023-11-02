<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Complaint;
use App\Models\Prescription;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        $result = Complaint::with('patient');
        if (request()->input("status")!=null) {
            $result->where('status', request()->input("status"));
        }
        if (request()->input("patientid")!=null) {
            $result->where('patientid', request()->input("patientid"));
        }
        if (!empty($request->sortBy) && in_array($request->sortBy, ['id', 'created_at'])) {
            $sortBy=$request->sortBy;
        }else{
            $sortBy='id';
        }
        if (!empty($request->sortorder) && in_array($request->sortorder, ['asc', 'desc'])) {
            $sortOrder=$request->sortorder;
        }else{
            $sortOrder='desc';
        }
        if (!empty($request->perpage)) {
            $perPage=$request->perpage;
        } else {
            $perPage=10;
        }
        $complaint=$result->orderBY($sortBy, $sortOrder)->paginate($perPage);
        return response()->json($complaint, 200);
    }

    public function fetchcomplaint(Request $request)
    {
        $complaint = Complaint::where('id', $request->id)->first();
        return response()->json($complaint, 200);
    }

    public function addcomplaint(Request $request)
    {
        $user=auth()->user();
        if (empty($request->complaint)) {
            return response()->json(["message" => "No details given", "status" => "error"], 400);
        }

        $complaint=Complaint::create([
            'patientid' => $user->patientid,
            'role' => '1',
            'complaint' => $request->complaint,
        ]);
        return response()->json([
            "message"=>"Complaint Registered Successfully",
            "status" => "success",
            'complaint' => $complaint,
        ], 200);
    }

}
