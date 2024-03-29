<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\CreateRequest;
use App\Http\Requests\Account\UpdateRequest;
use App\Http\Requests\FetchAccountRequest;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $result = DB::table('accounts');

        if (request()->input("doctorid") != null) {
            $search=request()->input("doctorid");
            $result->where('doctorid', $search);
        }
        if (request()->input("accountnumber") != null) {
            $search=request()->input("accountnumber");
            $result->where('accountnumber', "like", "%{$search}%");
        }
        if (request()->input("type") != null) {
            $search=request()->input("type");
            $result->where('type', $search);
        }
        if ((request()->input("sortby")!=null) && in_array(request()->input("sortby"), ['id', 'name', 'created_at'])) {
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

        $accounts=$result->orderBY($sortBy, $sortOrder)->paginate($perPage);
        return response()->json($accounts, 200);
    }

    public function getAccountName(FetchAccountRequest $request)
    {
        $accountnumber= $request->accountnumber;
        $bankcode= $request->bank;
        $acctrequest = Http::withHeaders([
            "content-type" => "application/json",
            "Authorization" => "Bearer ".env('FW_KEY'),
        ])->post('https://api.flutterwave.com/v3/accounts/resolve', [
            "account_number"=> $request->accountnumber,
            "account_bank"=> $request->bank,
        ]);
        $res=$acctrequest->json();
        //print_r($res);
        if (!$res['status']) {
            return response()->json(["message" => "An Error occurred while fetching account", "status" => "error"], 400);
        }else{
            $response=[
                "message" => "Account Fetched Successfully",
                'account' => $res,
                "status" => "success"
            ];
            return response()->json($response, 200);
        }
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
        $accountt=Account::where('accountnumber', $request->accountnumber)->first();
        if ($accountt) {
            return response()->json(["message" => "Account Already Exist", "status" => "error"], 400);
        }
        $acctrequest = Http::withHeaders([
            "content-type" => "application/json",
            "Authorization" => "Bearer ".env('PAYSTACK_KEY'),
        ])->post('https://api.paystack.co/transferrecipient', [
            "account_number"=> $request->accountnumber,
            "bank_code"=> $request->bank,
        ]);
        $res=$acctrequest->json();

        if (!$res['status']) {
            return response()->json(["message" => "An Error occurred while creating account", "status" => "error"], 400);
        }else{
            $details=$res['data']['details'];
            $recipientcode=$res['data']['recipient_code'];
            if ($request->type=='1') {
                Account::where('doctorid', $request->doctorid)->update(['type' => '0']);
            }
            $account = Account::create([
                'type' => $request->type,
                'accountnumber' => $request->accountnumber,
                'bank' => $request->bank,
                'recipient'=> $recipientcode,
                'accountname'=> $details['account_name'],
                'bankname'=> $details['bank_name'],
                'doctorid' => $request->doctorid,
            ]);
            $response=[
                "message" => "Account Created Successfully",
                'account' => $account,
                "status" => "success"
            ];
            return response()->json($response, 201);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $account=Account::find($id);
        if (!$account) {
            return response()->json(["message" => "Account Not Found.", "status" => "error"], 400);
        }
        $response=[
            "message" => "Account found",
            'account' => $account,
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
    public function update(UpdateRequest $request, Account $account)
    {
        $accountt=Account::where('accountnumber', $request->accountnumber)->where('id', '!=', $account->id)->first();
        if ($accountt) {
            return response()->json(["message" => "Account Already Exist", "status" => "error"], 400);
        }
        $acctrequest = Http::withHeaders([
            "content-type" => "application/json",
            "Authorization" => "Bearer ".env('PAYSTACK_KEY'),
        ])->post('https://api.paystack.co/transferrecipient', [
            "account_number"=> $request->accountnumber,
            "bank_code"=> $request->bank,
        ]);
        $res=$acctrequest->json();

        if (!$res['status']) {
            return response()->json(["message" => "An Error occurred while creating account", "status" => "error"], 400);
        }else{
            $details=$res['data']['details'];
            $recipientcode=$res['data']['recipient_code'];
            if ($request->type=='1') {
                Account::where('doctorid', $request->doctorid)->update(['type' => '0']);
            }
            $account->update([
                'type' => $request->type,
                'accountnumber' => $request->accountnumber,
                'bank' => $request->bank,
                'recipient'=> $recipientcode,
                'accountname'=> $details['account_name'],
                'bankname'=> $details['bank_name'],
                'doctorid' => $request->doctorid,
            ]);
            $response=[
                "message" => "Account Created Successfully",
                'account' => $account,
                "status" => "success"
            ];
            return response()->json($response, 201);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Account $account)
    {
        $account->delete();
        $response=[
            "message" => "Account Deleted Successfully",
            "status" => "success"
        ];
        return response()->json($response, 200);
    }
}
