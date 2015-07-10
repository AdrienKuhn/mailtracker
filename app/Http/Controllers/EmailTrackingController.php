<?php

namespace App\Http\Controllers;

use App\Email;
use App\EmailTracking;

use App\Http\Requests;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

class EmailTrackingController extends Controller
{

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function track($id, $uniqid)
	{
		$email = Email::where('id',$id)->where('uniqid',$uniqid)->first();

		// If email is found
		if($email) {
			// Get data
			$ip = $_SERVER['REMOTE_ADDR'];
			$host = gethostbyaddr($ip);
			$user_agent = $_SERVER['HTTP_USER_AGENT'];

			$country = @geoip_country_name_by_name($ip);
			if (!$country) $country = null;

			$validator = EmailTracking::validate(array(
											'ip' => $ip,
											'host' => $host,
											'user_agent' => $user_agent,
											'country' => $country
			));

			if($validator->passes()) {
				$tracking = new EmailTracking();
				$tracking->ip = $ip;
				$tracking->host = $host;
				$tracking->user_agent = $user_agent;
				$tracking->country = $country;
				$tracking->save();
				$email->email_trackings()->save($tracking); // Attach tracking to email


				// Return pixel
				$response = Response::make(File::get(Config::get('mail_tracker.pixel_file')));
				$response->header('Content-Type', 'image/gif');
				return $response;
			}

			// Otherwise, log error
			abort(500, 'Something went wrong...');
		}

		// Otherwise, exit
		abort(500, 'Email not found!');
	}
}
