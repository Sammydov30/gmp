<?php

namespace App\Http\Resources\API\V1\Customer;

use App\Models\FeedBackRating;
use App\Models\PickupCenter;
use App\Models\Region;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class TrackShipmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $row=(array) $this;
        $con=[];
        $con['whichtype']='1';
        $con['trackingid']=$row['trackingid'];
        $con['trackingid']=$row['trackingid'];
        $con['orderid']=$row['orderid'];
        $con['closedtime']=$row['closedtime'];
        $con['lastlocationtime']=$row['lastlocationtime'];
        $con['arrivedtime']=$row['arrivedtime'];
        $con['deliveredtime']=$row['deliveredtime'];
        if ($row['type']=='1') {
          $con['collection_time']=gmdate('d/m/Y - (h:i:s a)', $row['collection_time']);

          if ($row['newest']=='1') {
            $con['source']=$this->getRegionName($row['fromregion']);
            $con['destination']=$this->getRegionName($row['toregion']);
            $con['destinationname']=$this->getBranchName($row['rbranch']);
            $con['triptitle']=$con['source'].' to '.$con['destinationname'].' ('.$row['tripno'].')';
            if (($row['status']=='400') || ($row['status']=='500') || ($row['status']=='600') || ($row['status']=='700')) {
              $con['status_location']=$row['details'];
            }else{
              $con['status_location']='';
            }
            switch($row['status']) {
              case '0':
                $con['statustype']='Registered';
                $con['statuscode']='0';
                break;
              case '100':
                $con['statustype']='Ready to Go';
                $con['statuscode']='1';
                break;
              case '200':
                $con['statustype']='On the Go';
                $con['statuscode']='2';
                break;
              case '400':
                $con['statustype']='On the Go';
                $con['statuscode']='3';
                break;
              case '500':
                $con['statustype']='On the Go';
                $con['statuscode']='3';
                break;
              case '600':
                $con['statustype']='On the Go';
                $con['statuscode']='3';
                break;
              case '700':
                $con['statustype']='On the Go';
                $con['statuscode']='3';
                break;
              case '300':
                $con['statustype']='Arrived Destination';
                $con['statuscode']='4';
                break;
              case '1':
                $con['statustype']='Delivered';
                $con['statuscode']='5';
                break;
              case '4':
                $con['statustype']='Cancelled';
                $con['statuscode']='6';
                break;
              default:
                $con['statustype']='Registered';
                $con['statuscode']='0';
            }

          }else{
            $tripdetails=$this->fetchItemsforShipment($row['tripno']);
            $con['source']=$this->getStateName($tripdetails['source']);
            $con['destination']=$this->getStateName($tripdetails['destination']);
            $con['destinationname']=$this->getBranchName($this->fetchItemsforShipment($row['tripno'])['rbranch']);
            $con['triptitle']=$con['source'].' to '.$con['destinationname'].' ('.$this->fetchItemsforShipment($row['tripno'])['tripno'].')';
            $sstatus=$row['status'];
            $row['status']=$tripdetails['status'];
            $con['status_location']=($row['status']=='3') ? $tripdetails['details'] : '';

            switch($row['status']) {
              case '0':
                $con['statustype']='Registered';
                $con['statuscode']='0';
                break;
              case '1':
                $con['statustype']='Ready to Go';
                $con['statuscode']='1';
                break;
              case '2':
                $con['statustype']='On the Go';
                $con['statuscode']='2';
                break;
              case '3':
                $con['statustype']='On the Go';
                $con['statuscode']='3';
                break;
              case '4':
                $con['statustype']='Arrived Destination';
                $con['statuscode']='4';
                break;
              default:
                $con['statustype']='Registered';
                $con['statuscode']='0';
            }
            if ($sstatus=='1') {
              $con['statustype']='Delivered';
              $con['statuscode']='5';
            }
          }
        }elseif ($row['type']=='2') {
          $con['collection_time']=gmdate('d/m/Y - (h:i:s a)', $row['collection_time']);
          $tripdetails=$this->fetchItemsforShipment($row['tripno']);
          $con['source']=$this->getRegionName($row['fromregion']);
          $con['destination']=$this->getRegionName($row['toregion']);
          switch($row['status']) {
            case '0':
              $con['statustype']='Registered';
              $con['statuscode']='0';
              break;
            case '1':
              $con['statustype']='On the Go';
              $con['statuscode']='2';
              break;
            case '2':
              $con['statustype']='Arrived Destination';
              $con['statuscode']='4';
              break;
            case '3':
              $con['statustype']='Collected';
              $con['statuscode']='5';
              break;
            default:
              $con['statustype']='Not Specified';
              $con['statuscode']='0';
          }
        }elseif ($row['type']=='3') {
          $con['collection_time']=gmdate('d-m-Y (h:i:s-a)', $row['collection_time']);
          $tripdetails=$row['fromarea'].' To '.$row['toarea'];
          $con['source']=$row['fromarea'];
          $con['destination']=$row['toarea'];
          switch($row['status']) {
            case '0':
              $con['statustype']='Registered';
              $con['statuscode']='0';
              break;
            case '1':
              $con['statustype']='On the Go';
              $con['statuscode']='2';
              break;
            case '2':
              $con['statustype']='Arrived Destination';
              $con['statuscode']='4';
              break;
            case '3':
              $con['statustype']='Collected';
              $con['statuscode']='5';
              break;
            default:
              $con['statustype']='Not Specified';
              $con['statuscode']='0';
          }
        }
        return $con;
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
