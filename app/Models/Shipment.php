<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;
    protected $table='shipment';
    protected $fillable = [
        "entity_guid",
        "deliverymode",
        "branch",
        "rbranch",
        "cname",
        "cphone",
        "caddress",
        "rname",
        "rphone",
        "raddress",
        "orderid",
        "trackingid",
        "shipping_date",
        "collection_time",
        "fromcountry",
        "tocountry",
        "fromregion",
        "toregion",
        "fromarea",
        "toarea",
        "paymenttype",
        "paymentmethod",
        "amount_collected",
        "totalweight",
        "deliverytime",
        "tripno",
        "pickupvehicle",
        "gmpid",
        "pickupcenter",
        "pickupdate",
        "fromgmp",
        "gmppayment",
        "p_status",
        "solventapproved",
        "collectorname",
        "collectorphone",
        "timecollected",
        "details",
        "officeaddress",
        "who",
        "type",
        "client",
        "client_type",
        "user_guid",
        "status",
        "closed",
        "instatus",
        "cod",
        "cod_amount",
        "closedtime",
        "lastlocationtime",
        "arrivedtime",
        "deliveredtime",
        "st1",
        "st2",
        "st3",
        "st4",
        "paymentproof",
        "mot",
        "lat",
        "lng",
        "newest",
        "deleted",
        "cancel",
        "adminfee",
        "created_at",
        "updated_at"
    ];

    public function toArray()
    {
        $array = parent::toArray();
        // $array['collectiontime'] = gmdate('d-m, y h:ia', $this->collection_time); //($this->odate==null) ? $this->odate : @Carbon::parse($this->odate)->format('jS F Y h:ia');
        $array['closedtime'] =($this->closedtime==null) ? $this->closedtime : @Carbon::parse($this->closedtime)->format('jS F Y,h:ia');
        $array['lastlocationtime'] =($this->lastlocationtime==null) ? $this->lastlocationtime : @Carbon::parse($this->lastlocationtime)->format('jS F Y,h:ia');
        $array['arrivedtime'] =($this->arrivedtime==null) ? $this->arrivedtime : @Carbon::parse($this->arrivedtime)->format('jS F Y,h:ia');
        $array['deliveredtime'] = ($this->deliveredtime==null) ? $this->deliveredtime: @Carbon::parse($this->deliveredtime)->format('jS F Y,h:ia');

        $row=[];
        $row['countryname']=$this->GetCountryName($this->fromcountry);
        $row['deliverymodename']=($this->deliverymode=='1')?'Pick Up':'Drop Off';
        $row['amount']=@number_format($this->amount_collected, 2);
        $row['source']=$this->GetRegionName($this->fromregion);
        $row['destination']=$this->GetRegionName($this->toregion);

        $row['rdate']=gmdate('d/m/Y - (h:i:s a)', $this->collection_time);

        if (($this->status=='400') || ($this->status=='500') || ($this->status=='600') || ($this->status=='700')) {
          $row['status_location']=$this->details;
        }else{
          $row['status_location']='';
        }
        switch($this->status) {
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
          case '4':
            $row['statustype']='Cancelled';
            $row['statuscode']='6';
            break;
          default:
            $row['statustype']='Registered';
            $row['statuscode']='0';
        }
        $array += $row;

        return $array;
    }
    public function customer()
    {
        return $this->hasOne(Customer::class, 'gmpid', 'gmpid');
    }
    public function shipmentinfo()
    {
        return $this->hasMany(ShipmentInfo::class, 'shipment_id', 'id');
    }

    private function GetCountryName($id){
        $name=Country::where('id', $id)->first()->name;
        return $name;
    }
    private function GetRegionName($id){
        $name=Region::where('id', $id)->first()->name;
        return $name;
    }

}
