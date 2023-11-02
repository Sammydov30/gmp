<?php

namespace App\Http\Controllers\API\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerAddressRequest;
use App\Http\Requests\CustomerImageRequest;
use App\Http\Requests\CustomerProfileRequest;
use App\Models\Appointment;
use App\Models\Checkup;
use App\Models\Customer;
use App\Models\GeneralAuth;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customer=auth()->user();
        if (!empty($request->token) || $request->token=="0") {
            $customer=Customer::where('id', $customer->id)->update(['token', $request->token]);
        }
        $currtime=time();
        $appointments=Appointment::where('patientid', $customer->patientid)->where('status', '0')->get();
        $appointments=json_decode(json_encode($appointments), true);
        foreach ($appointments as $appointment) {
            $appointment = (object) $appointment;
            if ((($currtime-$appointment->atime)>86400) && ($appointment->status=='0')) {
                $waistedtime=$currtime-$appointment->atime;
                $supp=Subscription::where('customer', $customer->id)->first();
                $ra=$supp->ra+1;
                $expiredtime=$supp->expiredtime+$waistedtime;
                Subscription::where('customer', $customer->id)->update(['ra'=>$ra, 'used'=>'0', 'expiredtime'=>$expiredtime]);
                Appointment::where('appointmentid', $appointment->appointmentid)->update(['status'=>'5']);
            }
        }
        $customer=Customer::where('id', $customer->id)->first();
        return response()->json([
            'customer' => $customer,
            "status" => "success"
        ], 200);
    }

    public function dashboardvalues(Request $request)
    {
        $patient=auth()->user();
        $s_appointmentNum=Appointment::where('patientid', $patient->patientid)->whereIn('status', ['1', '2', '3'])->count();
        $us_appointmentNum=Appointment::where('patientid', $patient->patientid)->whereIn('status',  ['0', '5'])->count();
        $appointmentNum=$s_appointmentNum+$us_appointmentNum;

        //checkups
        $s_checkupNum=Checkup::where('patientid', $patient->patientid)->where('accepted', '1')->whereIn('status', ['1', '2'])->count();
        $us_checkupNum=Checkup::where('patientid', $patient->patientid)->where('accepted', '1')->whereIn('status',  ['0', '3'])->count();
        $checkupNum=$s_checkupNum+$us_checkupNum;
        $response=[
            'totalappointments'=>$appointmentNum,
            'successfulappointments'=>$s_appointmentNum,
            'unsuccessfulappointments'=>$us_appointmentNum,
            'totalcheckups'=>$checkupNum,
            'successfulcheckups'=>$s_checkupNum,
            'unsuccessfulcheckups'=>$us_checkupNum,
            "status" => "success"
        ];
        return response()->json($response, 200);
    }

    public function howmanyappointment(Request $request)
    {
        $patient=auth()->user();
        $supp=Subscription::where('customer', $patient->id)->first();
        if ($supp->used=='0') {
            $appointmentNum=$supp->ra;
        }else{
            $appointmentNum='0';
        }

        $response=[
            "message" => "Fetched Successfully",
            'numberofappointmentremaining'=>$appointmentNum,
            "status" => "success"
        ];
        return response()->json($response, 200);
    }

    public function CountTotalAppointments($year)
    {
        $patient=auth()->user();
        $appointmentdata=[];
        $monthlyCounts = DB::table('appointments')
        ->select(DB::raw('count(*) as count_s, month(created_at) as month'))
        ->where('patientid', $patient->patientid)
        ->whereYear('created_at', $year)
        ->groupBy('month')
        ->orderBy('month')->pluck('count_s', 'month');
        $arraydata= json_decode(json_encode($monthlyCounts), 2);
        for ($i=1; $i <= 12; $i++) {
            if (isset($arraydata[$i])) {
                array_push($appointmentdata, $arraydata[$i]);
            }else{ array_push($appointmentdata, 0); }
        }
        return $appointmentdata;
    }

    public function CountUSAppointments($year)
    {
        $patient=auth()->user();
        $appointmentdata=[];
        $monthlyCounts = DB::table('appointments')
        ->select(DB::raw('count(*) as count_s, month(created_at) as month'))
        ->where('patientid', $patient->patientid)
        ->whereIn('status', ['0', '5'])->whereYear('created_at', $year)
        ->groupBy('month')
        ->orderBy('month')->pluck('count_s', 'month');
        $arraydata= json_decode(json_encode($monthlyCounts), 2);
        for ($i=1; $i <= 12; $i++) {
            if (isset($arraydata[$i])) {
                array_push($appointmentdata, $arraydata[$i]);
            }else{ array_push($appointmentdata, 0); }
        }
        return $appointmentdata;
    }

    public function CountSAppointments($year)
    {
        $patient=auth()->user();
        $appointmentdata=[];
        $monthlyCounts = DB::table('appointments')
        ->select(DB::raw('count(*) as count_s, month(created_at) as month'))
        ->where('patientid', $patient->patientid)
        ->whereIn('status', ['1', '2', '3'])->whereYear('created_at', $year)
        ->groupBy('month')
        ->orderBy('month')->pluck('count_s', 'month');
        $arraydata= json_decode(json_encode($monthlyCounts), 2);
        for ($i=1; $i <= 12; $i++) {
            if (isset($arraydata[$i])) {
                array_push($appointmentdata, $arraydata[$i]);
            }else{ array_push($appointmentdata, 0); }
        }
        return $appointmentdata;
    }

    public function CountUSCheckups($year)
    {
        $patient=auth()->user();
        $appointmentdata=[];
        $monthlyCounts = DB::table('checkups')
        ->select(DB::raw('count(*) as count_s, month(created_at) as month'))
        ->where('patientid', $patient->patientid)
        ->where('accepted', '1')
        ->whereIn('status', ['0', '3'])->whereYear('created_at', $year)
        ->groupBy('month')
        ->orderBy('month')->pluck('count_s', 'month');
        $arraydata= json_decode(json_encode($monthlyCounts), 2);
        for ($i=1; $i <= 12; $i++) {
            if (isset($arraydata[$i])) {
                array_push($appointmentdata, $arraydata[$i]);
            }else{ array_push($appointmentdata, 0); }
        }
        return $appointmentdata;
    }

    public function CountSCheckups($year)
    {
        $patient=auth()->user();
        $appointmentdata=[];
        $monthlyCounts = DB::table('checkups')
        ->select(DB::raw('count(*) as count_s, month(created_at) as month'))
        ->where('patientid', $patient->patientid)
        ->where('accepted', '1')
        ->whereIn('status', ['1', '2'])->whereYear('created_at', $year)
        ->groupBy('month')
        ->orderBy('month')->pluck('count_s', 'month');
        $arraydata= json_decode(json_encode($monthlyCounts), 2);
        for ($i=1; $i <= 12; $i++) {
            if (isset($arraydata[$i])) {
                array_push($appointmentdata, $arraydata[$i]);
            }else{ array_push($appointmentdata, 0); }
        }
        return $appointmentdata;
    }

    public function DashboardChart(Request $request)
    {
        $year=(!empty($request->year)) ? $request->year : date('Y');
        $totalA=$this->CountTotalAppointments($year, '0');
        $successA=$this->CountSAppointments($year, '1');
        $unsuccessA=$this->CountUSAppointments($year, '0');
        $successC=$this->CountSCheckups($year, '1');
        $unsuccessC=$this->CountUSCheckups($year, '0');
        $data=[
            $year=>[
                // ['name'=>'successful appointments', 'data'=>$success],
                // ['name'=>'unsuccessful appointments', 'data'=>$unsuccess],
                ['totalappointments'=>$totalA],
                ['successfulappointments'=>$successA],
                ['unsuccessfulappointments'=>$unsuccessA],
                ['successfulcheckups'=>$successC],
                ['unsuccessfulcheckups'=>$unsuccessC],
            ]
        ];
        //print_r($success); exit();
        $response=[
            "data" => $data,
            "status" => "success"
        ];
        return response()->json($response, 201);
    }

    public function changeprofile(CustomerProfileRequest $request)
    {
        $user=auth()->user();
        $id=$user->id;
        $initemail=Customer::where('id', $id)->first()->email;
        $user=Customer::where('id', $user->id)->update([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'phone' => $request->phone,
            //'email' => $request->email,
        ]);
        //GeneralAuth::where('email', $initemail)->update(['email'=>$request->email]);
        $user=Customer::where('id', $id)->first();
        return response()->json([
            "status" => "success",
            'user' => $user,
            'message' => "Profile Changed Successfully",
        ], 200);
    }

    public function changeemail(Request $request)
    {
        if (empty($request->email)) {
            return response()->json(["message" => "Email Required", "status" => "error"], 400);
        }
        $user=auth()->user();
        $id=$user->id;
        $initemail=Customer::where('id', $id)->first()->email;
        $user=Customer::where('id', $user->id)->update([
            'email' => $request->email,
        ]);
        GeneralAuth::where('email', $initemail)->update(['email'=>$request->email]);
        $user=Customer::where('id', $id)->first();
        return response()->json([
            "status" => "success",
            'user' => $user,
            'message' => "Email Changed Successfully",
        ], 200);
    }

    public function changeaddressinfo(CustomerAddressRequest $request)
    {
        $user=auth()->user();
        $id=$user->id;
        $user=Customer::where('id', $user->id)->update([
            'country' => $request->country,
            'city' => $request->city,
        ]);
        $user=Customer::where('id', $id)->first();
        return response()->json([
            "status" => "success",
            'user' => $user,
            'message' => "Address Changed Successfully",
        ], 200);
    }

    public function changemedicaldetails(Request $request)
    {
        $user=auth()->user();
        $id=$user->id;
        $user=Customer::where('id', $user->id)->update([
            'medicaldetails' => $request->medicaldetails,
        ]);
        $user=Customer::where('id', $id)->first();
        return response()->json([
            "status" => "success",
            'user' => $user,
            'message' => "Information Changed Successfully",
        ], 200);
    }
    public function uploadimage(CustomerImageRequest $request)
    {
        $user=auth()->user();
        $id=$user->id;
        // $image_path = $request->file('profilepicture')->store('image', 'public');

        $file =$request->file('profilepicture');
        $extension = $file->getClientOriginalExtension();
        $filename = time().'.' . $extension;
        $file->move(public_path('uploads/'), $filename);
        $image= 'uploads/'.$filename;


        $user=Customer::where('id', $user->id)->update([
            //'profilepicture'=>$request->profilepicture,
            'profilepicture'=>$image,
        ]);
        $user=Customer::where('id', $id)->first();
        return response()->json([
            "status" => "success",
            'user' => $user,
            'message' => "Profile Picture changed Successfully",
        ], 200);
    }
    public function changepassword(Request $request)
    {
        $user=auth()->user();
        $error=array();
        if (empty($request->oldpassword)) {
          array_push($error,"Old Password is Required");
        }
        if (empty($request->newpassword)) {
            array_push($error,"New Password is Required");
        }
        if (empty($request->cnewpassword)) {
            array_push($error,"New Password Confirmation is Required");
        }
        if ($request->cnewpassword!=$request->newpassword) {
            array_push($error,"New Password Mismatch");
        }
        $user=Customer::where('id', $user->id);
        if ( $user) {
            array_push($error,"User doesn't exist");
        }
        $newpassword= Hash::make($request->newpassword);
        $oldpassword= Hash::make($request->oldpassword);
        if ($oldpassword!=$user->password) {
            array_push($error,"Old Password is Incorrect");
        }
        if(empty($error)){
            $user->update([
                'password' => $newpassword,
            ]);
            $response=[
                "status" => "success",
                "message" => "Password Successfully",
                'user' => $user,
            ];
            return response()->json($response, 201);
        }else{
            return response()->json(["message"=>$error, "status"=>"error"], 400);
        }
    }
    public function deactivateaccount(Request $request)
    {
        $user=auth()->user();
        $user=Customer::where('id', $user->id)->update([
            'deactivated' => '1',
        ]);
        return response()->json([
            "status" => "success",
            'message' => "Account Deactivated Successfully",
        ], 200);
    }
    public function deleteaccount(Request $request)
    {
        $user=auth()->user();
        $user=Customer::where('id', $user->id)->update([
            'deleted' => '1',
        ]);
        return response()->json([
            "status" => "success",
            'message' => "Account Deleted Successfully",
        ], 200);
    }
    public function toggleemailnotification(Request $request)
    {
        $user=auth()->user();
        $user=Customer::where('id', $user->id)->update([
            'emailnotify' => $request->status,
        ]);
        return response()->json([
            "status" => "success",
            'message' => "Status Changed Successfully",
        ], 200);
    }
    public function toggledesktopnotification(Request $request)
    {
        $user=auth()->user();
        $user=Customer::where('id', $user->id)->update([
            'desktopnotify' => $request->status,
        ]);
        return response()->json([
            "status" => "success",
            'message' => "Status Changed Successfully",
        ], 200);
    }
    public function togglesubscriptionduenotification(Request $request)
    {
        $user=auth()->user();
        $user=Customer::where('id', $user->id)->update([
            'subscriptiondue' => $request->status,
        ]);
        return response()->json([
            "status" => "success",
            'message' => "Status Changed Successfully",
        ], 200);
    }
    public function togglecheckupschedulednotification(Request $request)
    {
        $user=auth()->user();
        $user=Customer::where('id', $user->id)->update([
            'checkupscheduled' => $request->status,
        ]);
        return response()->json([
            "status" => "success",
            'message' => "Status Changed Successfully",
        ], 200);
    }
}
