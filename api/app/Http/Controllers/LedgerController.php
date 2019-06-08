<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Ledger;
use Carbon\Carbon;
class LedgerController extends APIController
{
    function __construct(){
      $this->model = new Ledger();
    }

    public function dashboard($accountId){
      return array(
        'ledger' => $this->retrievePersonal($accountId),
        'available' => $this->available(),
        'approved' => app('App\Http\Controllers\InvestmentController')->approved(),
        'total_requests' => app('App\Http\Controllers\RequestMoneyController')->total()
      );
    }

    public function summary(Request $request){
      $data = $request->all();
      $result = Ledger::where('account_id', '=', $data['account_id'])->limit(intval($data['limit']))->offset(intval($data['offset']))->orderBy($data['sort']['column'], $data['sort']['value'])->get();
      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          $result[$i]['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $result[$i]['created_at'])->copy()->tz('Asia/Manila')->format('F j, Y');
          $result[$i]['investments'] = null;
          if($result[$i]['payload'] == 'investments'){
            $result[$i]['investments'] = app('App\Http\Controllers\InvestmentController')->retrieveById($result[$i]['payload_value']);
          }else{
            //
          }
          $i++;
        }
      }
      return response()->json(array(
        'data' => sizeof($result) > 0 ? $result : null,
        'ledger' => $this->dashboard($data['account_id'])
      ));
    }

    public function retrievePersonal($accountId){
      $result = Ledger::where('account_id', '=', $accountId)->sum('amount');
      return $result;
    }

    public function addToLedger($accountId, $amount, $description, $payload, $payloadValue){
      $ledger = new Ledger();
      $ledger->code = $this->generateCode();
      $ledger->account_id = $accountId;
      $ledger->amount = $amount;
      $ledger->description = $description;
      $ledger->payload = $payload;
      $ledger->payload_value = $payloadValue;
      $ledger->created_at = Carbon::now();
      $ledger->save();
      return $ledger->id;
    }

    public function generateCode(){
      $code = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 32);
      $codeExist = Ledger::where('code', '=', $code)->get();
      if(sizeof($codeExist) > 0){
        $this->generateCode();
      }else{
        return $code;
      }
    }

    public function available(){
      $result = Ledger::sum('amount');
      return $result;
    }
}
