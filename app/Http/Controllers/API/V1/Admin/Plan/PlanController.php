<?php

namespace App\Http\Controllers\API\V1\Admin\Plan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Plan\CreateRequest;
use App\Http\Requests\Plan\UpdateRequest;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $result = DB::table('plans');
        if (request()->input("name") != null) {
            $search=request()->input("name");
            $result->where('name', "like", "%{$search}%");
        }
        if (request()->input("type") != null) {
            $search=request()->input("type");
            $result->where('type', $search);
        }
        if ((request()->input("sortby")!=null) && in_array(request()->input("sortby"), ['id', 'created_at'])) {
            $sortBy=request()->input("sortby");
        }else{
            $sortBy='id';
        }
        if ((request()->input("sortorder")!=null) && in_array(request()->input("sortorder"), ['asc', 'desc'])) {
            $sortOrder=request()->input("sortorder");
        }else{
            $sortOrder='desc';
        }
        if (!empty(request()->input("perpage"))) {
            $perPage=request()->input("perpage");
        } else {
            $perPage=10;
        }

        $plan=$result->orderBY($sortBy, $sortOrder)->paginate($perPage);
        return response()->json($plan, 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateRequest $request)
    {
        $duration=(empty($request->duration)) ? '86400' : $request->duration;
        $plan = Plan::create([
            'type' => $request->type,
            'name' => $request->name,
            'amount' => $request->amount,
            'duration' => $duration,
            'hwa' => $request->hwa,
            'checkups' => $request->checkups,
        ]);

        $response=[
            "message" => "Plan Created Successfully",
            'plan' => $plan,
            "status" => "success"
        ];

        return response()->json($response, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $plan=Plan::find($id);
        if (!$plan) {
            return response()->json(["message" => " Not Found.", "status" => "error"], 400);
        }
        $response=[
            "message" => "Plan found",
            'plan' => $plan,
            "status" => "success"
        ];
        return response()->json($response, 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRequest $request, Plan $plan)
    {
        $duration=(empty($request->duration)) ? '86400' : $request->duration;
        $plan->update([
            'type' => $request->type,
            'name' => $request->name,
            'amount' => $request->amount,
            'duration' => $duration,
            'hwa' => $request->hwa,
            'checkups' => $request->checkups,
        ]);
        $response=[
            "message" => "Plan Updated Successfully",
            'plan' => $plan,
            "status" => "success"
        ];

        return response()->json($response, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Plan $plan)
    {
        $plan->delete();
        $response=[
            "message" => "Plan Deleted Successfully",
            "status" => "success"
        ];
        return response()->json($response, 200);
    }
}
