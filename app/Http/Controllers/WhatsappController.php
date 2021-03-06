<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;

class WhatsappController extends Controller
{
	public $json;
    public function __construct()
    {
        $this->middleware('guest');
        $data = file_get_contents(storage_path().'/countrycode.json');
        $this->json = json_decode($data);
    }

    public function index()
    {
    	return view('whatsapp')->with('countrycode',$this->json);
    }

    public function send_whatsapp(Request $request){
    	$validator = Validator::make($request->all(),[
    			'phonenumber' => 'required|min:2|numeric',
    			'countrycode' => 'required'
    		]);

    	if ($validator->fails()) {
    		return redirect()->back()->withErrors($validator->messages())->withInput($request->all());
    	}
    	else{
    		$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
    		
    		try{
	    		$rawPhone = $phoneUtil->parse($request->phonenumber,explode(":", $request->countrycode)[1]);
	    	}
	    	catch(\Exception $e){
	    		return redirect()->back()->withErrors(['phonenumber'=>'The phone number is not valid']);
	    	}
    		
    		$rawPhone = $phoneUtil->format($rawPhone, \libphonenumber\PhoneNumberFormat::E164);

    		return $this->proceed_send($rawPhone,$request->message);
    	}
    }

    public function send($phonenumber,$text = "Hello There")
    {
    	$data = [
    			'status' => false,
    			'message' => 'invalid phone number format. Please add ex: +60 by country code'
    	];

    	if ($phonenumber == null) {
    		return response()->json($data);
    	}
    	
    	if ($phonenumber[0] != '+') {
    		$phonenumber = '+'.$phonenumber;
    	}

    	if (preg_match("/^\+[1-9]{1}[0-9]{3,14}$/", $phonenumber)) {
    		return $this->proceed_send($phonenumber,$text);
    	}
    	else{
    		return response()->json($data);
    	}
    }

    private function proceed_send($phonenumber,$text){
    	$agent = new Agent();
    	if ($agent->isDesktop()) {
    			$url = "https://web.whatsapp.com/send?text=".$text."&phone=".$phonenumber;
    			return Redirect::to($url);
	    	}
	    	else{
	    		$url = "whatsapp://send?text=".$text."&phone=".$phonenumber;
	    		return Redirect::to($url);
	    	}
    }
}
