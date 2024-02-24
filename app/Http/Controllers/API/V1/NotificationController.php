<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Traits\NotificationTrait;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use NotificationTrait;

    public function readnotification(Request $request)
    {
        if (empty($request->notificationid)) {
            return response()->json(["message" => "Invalid Request", "status" => "error"], 400);
        }
        Notification::where('id', $request->notificationid)->update(['status'=>'1']);
        return response()->json([
            "status" => "success",
        ], 200);
    }

    public function fetchnotificationforuser(Request $request)
    {
        $customer=auth()->user();
        $result = Notification::where('gmpid', 'GMP1698940449');
        if (empty($request->read)) {
            $result->where('status', $request->read);
        }
        if (empty($request->type)) {
            $result->where('type', $request->type);
        }
        if (empty($request->which)) {
            $result->where('which', $request->which);
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
        $notification=$result->orderBY($sortBy, $sortOrder)->paginate($perPage);
        return response()->json($notification, 200);
    }

}
