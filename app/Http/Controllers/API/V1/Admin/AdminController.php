<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminCreateRequest;
use App\Http\Requests\Admin\AdminRequest;
use App\Http\Requests\AdminProfileRequest;
use App\Http\Resources\API\V1\Admin\AdminResource;
use App\Http\Resources\API\V1\Admin\AdminSingleResource;
use App\Models\Admin;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Doctor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{

    public function index(Request $request)
    {
        $user=auth()->user();
        $result = Admin::where('id', '!=', $user->id)->where('role', '!=', '0');
        if (!empty($request->search)) {
            $result->where('name', "like", "%{$request->search}%");
        }
        if (!empty($request->role)) {
            $search=$request->role;
            $result->where('role', $search);
        }
        if (!empty($request->status)) {
            $search=$request->status;
            $result->where('status', $search);
        }
        if (!empty($request->sortby) && in_array($request->sortby, ['id', 'created_at'])) {
            $sortBy=$request->sortby;
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
        $admins=$result->orderBY($sortBy, $sortOrder)->paginate($perPage);
        return response()->json($admins, 200);
    }

    public function dashboardvalues(Request $request)
    {
        $patientsNum=Customer::where('id', '!=', '0')->count();
        $doctorsNum=Doctor::where('id', '!=', '0')->count();
        $verifiedDocNum=Doctor::where('doctor_verified', '=', '1')->count();
        $s_appointmentNum=Appointment::whereIn('status', ['1', '2', '3'])->count();
        $us_appointmentNum=Appointment::whereIn('status',  ['0', '5'])->count();
        $appointmentNum=$s_appointmentNum+$us_appointmentNum;
        $response=[
            // "message" => "Fetched Successfully",
            // 'pnum' => $patientsNum,
            // 'dnum'=>$doctorsNum,
            // 'vdnum' => $verifiedDocNum,
            'totalappointments'=>$appointmentNum,
            'successfulappointments'=>$s_appointmentNum,
            'unsuccessfulappointments'=>$us_appointmentNum,
            "status" => "success"
        ];
        return response()->json($response, 200);
    }

    public function CountUSAppointments($year)
    {
        // $successfulappointmentdata=[];
        // $monthlyCounts = DB::table('appointments')
        // ->select(DB::raw('count(*) as count, month(created_at) as month, DATE_FORMAT(created_at, "%b") as monthname'))
        // ->select(DB::raw('count(case when status != 0 then status end) as successfulappointment,
        // count(case when status = 0 then status end) as unsuccessfulappointment, month(created_at) as month,
        // DATE_FORMAT(created_at, "%b") as monthname'))
        $appointmentdata=[];
        $monthlyCounts = DB::table('appointments')
        ->select(DB::raw('count(*) as count_s, month(created_at) as month'))
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
        $appointmentdata=[];
        $monthlyCounts = DB::table('appointments')
        ->select(DB::raw('count(*) as count_s, month(created_at) as month'))
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

    public function DashboardChart(Request $request)
    {
        $year=(!empty($request->year)) ? $request->year : date('Y');
        $success=$this->CountSAppointments($year, '1');
        $unsuccess=$this->CountUSAppointments($year, '0');
        $data=[
            $year=>[
                // ['name'=>'successful appointments', 'data'=>$success],
                // ['name'=>'unsuccessful appointments', 'data'=>$unsuccess],
                ['successful appointments'=>$success],
                ['unsuccessful appointments'=>$unsuccess],
            ]
        ];
        //print_r($success); exit();
        $response=[
            "data" => $data,
            "status" => "success"
        ];
        return response()->json($response, 201);
    }


    public function register(AdminCreateRequest $request)
    {
        $error=array();
        $check2=Admin::where('email', $request->email)->first();
        if ($check2) {
            array_push($error,"Email already exist");
        }

        if(empty($error)){
            $admin = Admin::create([
                //'username' => $request->username,
                'firstname' => $request->firstname,
                'lastname'=> $request->lastname,
                'email' => $request->email,
                'phone'=> $request->phone,
                'address'=> $request->address,
                'role' => $request->role,
                'status' => '1',
                //'password' => Hash::make($request->password),
            ]);

            $response=[
                "message" => "Admin Created Successfully",
                'admin' => $admin,
                "status" => "success"
            ];

            return response()->json($response, 201);
        }else{
            return response()->json(["message"=>$error, "status"=>"error"], 400);
        }
    }

    public function show($admin)
    {
        $admin=Admin::find($admin);
        if (!$admin) {
            return response()->json(["message"=>"This record doesn't exist", "status"=>"error"], 400);
        }
        return new AdminSingleResource($admin);
    }

    public function update(AdminRequest $request, Admin $admin)
    {
        $error=array();
        // $check=Admin::where('username', $request->username)->where('id', '!=', $admin->id)->first();
        // if ($check) {
        //     array_push($error,"Username already exist");
        // }
        $check2=Admin::where('email', $request->email)->where('id', '!=', $admin->id)->first();
        if ($check2) {
            array_push($error,"Email already exist");
        }
        if(empty($error)){
            $admin->update([
                'firstname' => $request->firstname,
                'lastname'=> $request->lastname,
                'email' => $request->email,
                'phone'=> $request->phone,
                'address'=> $request->address,
                'role' => $request->role,
                'status' => $request->status,
            ]);
            $response=[
                "message" => "Admin Updated Successfully",
                'admin' => $admin,
                "status" => "success"
            ];
            return response()->json($response, 201);
        }else{
            return response()->json(["message"=>$error, "status"=>"error"], 400);
        }
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

        $admin=Admin::where('id', $user->id);
        if (!$admin) {
            array_push($error,"User doesn't exist");
        }
        $newpassword= md5(sha1($request->newpassword));
        $oldpassword= md5(sha1($request->oldpassword));
        $dpassword=$admin->first()->password;
        if ($oldpassword!=$dpassword) {
            array_push($error,"Old Password is Incorrect");
        }
        if(empty($error)){
            $admin->update([
                'password' => $newpassword,
            ]);
            $response=[
                "status" => "success",
                "message" => "Password Changed Successfully",
                //'admin' => $admin,
            ];
            return response()->json($response, 201);
        }else{
            return response()->json(["message"=>$error, "status"=>"error"], 400);
        }
    }

    public function destroy(Admin $admin)
    {
        $admin->delete();
        $response=[
            "message" => "Admin Deleted Successfully",
            "status" => "success"
        ];
        return response()->json($response, 200);
    }

    public function getA(Request $request)
    {
        $admin=auth()->user();
        return response()->json([
            'user' => $admin,
            "status" => "success"
        ], 200);
    }

    public function changeprofile(AdminProfileRequest $request)
    {
        $u=auth()->user();
        $user=Admin::where('id', $u->id)->update([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'phone' => $request->phone,
            'address' => $request->address,
        ]);
        return response()->json([
            "status" => "success",
            'user' => $user,
            'message' => "Information Changed Successfully",
        ], 200);
    }
}

