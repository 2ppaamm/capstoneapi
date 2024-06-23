<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Enrolment;
use App\Role;
use App\House;
use App\Test;
use App\User;
use App\Course;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use DateTime;
use App\Http\Requests\StoreMasterCodeRequest;
use Illuminate\Support\Facades\DB;
use App\Jobs\ProcessQuestionAssignment;

class VisitorController extends Controller
{
	public function diagnostic(Request $request)
	{
		$test = new Test;
	    $user = Auth::user();
	    $questions=[];
        $courses = Course::where('course', 'LIKE', '%Math%')->pluck('id'); 	    
		$user->tests;
	    if ($user->tests()->exists()) {
	    	if ($user->diagnostictests->isnotempty()){
	    		$test= $user->diagnostictests()->latest()->first();
	    		$questions=$test->uncompletedquestions;
	    	} else {
		        return response()->json(['message' => 'User already has a test.', 'code' => 400], 400);
		    }
		} else {
		    // Create a new test
			$test = $user->tests()->create([
			    'test' => 'Free Diagnostic for ' . $user->name . ' on ' . Carbon::now()->toDateString(),
			    'description' => 'Automatically generated free diagnostic test',
			    'diagnostic' => true,
			    'start_available_time' => Carbon::now(),
			    'end_available_time' => Carbon::now()->addYear(),
			    'due_time' => Carbon::now()->addYear(),
			    'number_of_tries_allowed' => 2,
			    'which_result' => 'highest',
			    'user_id' => $user->id, // Depending on your relationship setup, this might be redundant
			    'status_id' => 2,
			]);
		    // Find the questions for the new diagnostic test
  		    $questions = $test->fieldDiagnosticQuestions(Course::find(1));

		    ProcessQuestionAssignment::dispatch($questions->pluck('id'), $test->id, $user->id);
            $uncompletedQuestions = $randomQuestions;
		}

        return response()->json(['message' => 'Request executed successfully', 'test'=>$test->id, 'questions'=>$questions, 'code'=>201]);        
	}

    public function subscribe(Request $request)
    {
        $user = Auth::user(); 
        $stripeTransactionId = 'txn_xxx'; // This should be replaced with actual transaction ID from Stripe
        $currencyCode = 'SGD'; 

        // Enrolment Logic
        $enrolment = Enrolment::firstOrNew([
            'user_id' => $user->id,
            'house_id' => 1, 
            'role_id' => Role::where('role', 'LIKE', '%Student%')->first()->id
        ]);

        $enrolment->fill([
            'start_date' => new DateTime('now'),
            'expiry_date' => (new DateTime('now'))->modify('+1 year'),
            'payment_email' => $user->email,
            'purchaser_id' => $user->id,
            'transaction_id' => $stripeTransactionId,
            'payment_status' => 'paid',
            'amount_paid' => 600,
            'currency_code' => $currencyCode
        ])->save();
        $this->updateUserDetails($request, $user);
        $this->sendConfirmationEmail($user, $enrollment['houses']);

        // Initiate Diagnostic Test
      	return $this->diagnostic($request);
	}

	public function mastercode(StoreMasterCodeRequest $request) {
		DB::beginTransaction();
	    try {
	        $user = Auth::user();
	        $enrollmentDetails = $this->validateAndEnrollUser($request, $user);
	        $this->updateUserDetails($request, $user);
	        $this->sendConfirmationEmail($user, $enrollmentDetails['houses']);
	        DB::commit();
	        return $this->diagnostic($request); 
	    } catch (Exception $e) {
	        DB::rollBack();
	        return response()->json(['message' => $e->getMessage(), 'code' => 404], 404);
	    }
	}

	protected function validateAndEnrollUser($request, $user) {
	    $check_mastercode = Enrolment::whereMastercode($request->mastercode)->first();
	    if (!$check_mastercode) {
	        throw new Exception('Invalid credentials. Please contact us at math@allgifted.com if you have purchased product.');
	    }
	    if ($check_mastercode->places_alloted <= 0) {
	        throw new Exception('There are no more places left for the mastercode you keyed in.');
	    }

	    $check_mastercode->decrement('places_alloted');
	    $enrolment = Enrolment::updateOrCreate(
	        ['user_id' => $user->id, 'house_id' => $check_mastercode->house_id],
	        [
	            'start_date' => now(),
	            'expiry_date' => now()->addYear(),
	            'role_id' => Role::where('role', 'LIKE', '%Student%')->first()->id,
	            'payment_email' => $check_mastercode->payment_email,
	            'purchaser_id' => $check_mastercode->user_id,
	            'transaction_id' => $check_mastercode->transaction_id,
	            'payment_status' => $check_mastercode->payment_status,
	            'amount_paid' => $check_mastercode->amount_paid,
	            'currency_code' => $check_mastercode->currency_code
	        ]
	    );

	    return ['enrolment' => $enrolment, 'houses' => House::find($check_mastercode->house_id)];
	}

	protected function updateUserDetails($request, $user) {
	    $user->update([
	        'firstname' => $request->firstname,
	        'lastname' => $request->lastname,
	        'date_of_birth' => Carbon::createFromFormat('Y-m-d', $request->date_of_birth)->format('Y-m-d')
	    ]);
	}

	protected function sendConfirmationEmail($user, $houses) {
	    $note = "Dear {$user->firstname},<br><br>Thank you for enrolling in the {$houses->description} program!<br><br>You should be presented questions for the diagnosis test and we will start to monitor your progress from now.<br><br>You should check your progress periodically at https://math.allgifted.com. <br><br>Should you have any queries, please do not hesitate to contact us at math@allgifted.com.<br><br>Thank you.<br><br><i>This is an automated message generated by the All Gifted System.</i>";

	    // Validate the email
	    $validator = Validator::make(['email' => $user->email], [
	        'email' => 'required|email',
	    ]);

	    // Check if the validation fails
	    if ($validator->fails()) {
	        // Handle the error, e.g., log it, return an error response, etc.
	        // For example, logging and then returning/continuing with your logic
	        \Log::error('Invalid email address for user: ' . $user->email);
	        
	        Mail::send([], [], function ($message) use ($user, $note) {
	            $message->from('pam@allgifted.com', 'All Gifted Admin')
	                    ->to('pamelaliusm@gmail.com')
	                    ->subject('Email is wrong')
	                    ->setBody($note, 'text/html');
	        }); // Fixed the closing parenthesis and semicolon here
	    } else {
	        Mail::send([], [], function ($message) use ($user, $note) {
	            $message->from('pam@allgifted.com', 'All Gifted Admin')
	                    ->to($user->email)->cc('kang@allgifted.com')
	                    ->subject('Successful Enrolment')
	                    ->setBody($note, 'text/html');
	        }); 
	    }
	}

}