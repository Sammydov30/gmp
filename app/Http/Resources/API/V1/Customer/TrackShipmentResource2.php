<?php

namespace App\Http\Resources\API\V1\Customer;

use App\Models\FeedBackRating;
use App\Models\PickupCenter;
use App\Models\Region;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class TrackShipmentResource2 extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $row = parent::toArray($request);
        $row['collection_time']=gmdate('d/m/Y - (h:i:s a)', $row['collection_time']);

        $row['source']=$this->getRegionName($row['fromregion']);
        $row['destination']=$this->getRegionName($row['toregion']);
        $row['destinationname']=$this->getBranchName($row['rbranch']);
        $row['triptitle']=$row['source'].' to '.$row['destinationname'].' ('.$row['tripno'].')';
        if (($row['status']=='400') || ($row['status']=='500') || ($row['status']=='600') || ($row['status']=='700')) {
        $row['status_location']=$row['details'];
        }else{
        $row['status_location']='';
        }
        switch($row['status']) {
        case '0':
            $row['statustype']='Registered';
            $row['statuscode']='0';
            break;
        case '100':
            $row['statustype']='Ready to Go';
            $row['statuscode']='1';
            break;
        case '200':
            $row['statustype']='On the Go';
            $row['statuscode']='2';
            break;
        case '400':
            $row['statustype']='On the Go';
            $row['statuscode']='3';
            break;
        case '500':
            $row['statustype']='On the Go';
            $row['statuscode']='3';
            break;
        case '600':
            $row['statustype']='On the Go';
            $row['statuscode']='3';
            break;
        case '700':
            $row['statustype']='On the Go';
            $row['statuscode']='3';
            break;
        case '300':
            $row['statustype']='Arrived Destination';
            $row['statuscode']='4';
            break;
        case '1':
            $row['statustype']='Delivered';
            $row['statuscode']='5';
            break;
        default:
            $row['statustype']='Registered';
            $row['statuscode']='0';
        }

        $res_arr=[];
        $res_arr['id']=$row['id'];
        $res_arr['deliverymodename']=($row['deliverymode']=='1')?'Pick Up':'Drop Off';
        $res_arr['amount']=$row['amount_collected'];
        $res_arr['totalweight']=$row['totalweight'];
        $res_arr['userid']=$row['gmpid'];
        $res_arr['customername']=$row['cname'];
        $res_arr['customerphone']=$row['cphone'];
        $res_arr['customeraddress']=$row['caddress'];
        $res_arr['recipientname']=$row['rname'];
        $res_arr['recipientphone']=$row['rphone'];
        $res_arr['recipientaddress']=$row['raddress'];
        $res_arr['orderid']=$row['orderid'];
        $res_arr['trackingid']=$row['trackingid'];
        $res_arr['source']=$row['source'];
        $res_arr['destination']=$row['destination'];
        $res_arr['pickupcenter']=$row['destinationname'];
        $res_arr['status']=$row['status'];
        $res_arr['status_location']=$row['status_location'];
        $res_arr['statustype']=$row['statustype'];
        $res_arr['statuscode']=$row['statuscode'];
        $res_arr['collection_time']=$row['collection_time'];
        $res_arr['readytime']=$row['closedtime'];
        $res_arr['lastlocationtime']=$row['lastlocationtime'];
        $res_arr['arrivedtime']=$row['arrivedtime'];
        $res_arr['deliveredtime']=$row['deliveredtime'];

        $row=$res_arr;
        return $row;
    }

    public function getRegionName($id) {
        return Region::where('id', $id)->first()->name;
    }
    public function getBranchName($id) {
        return PickupCenter::where('id', $id)->first()->name;
    }
    public function fetchItemsforShipment($trip) {
        return DB::table('trip')->where('tripno', $trip)->first();
    }

}
