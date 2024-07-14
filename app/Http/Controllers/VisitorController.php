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
use Carbon\Carbon;
use DateTime;
use App\Http\Requests\StoreMasterCodeRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class VisitorController extends Controller
{
    public function diagnostic(Request $request)
    {
        $user = Auth::user();
        $questions = [];

        if ($user->tests()->exists()) {
            $latestDiagnosticTest = $user->diagnostictests()->whereHas('uncompletedquestions')->latest()->first();

            if ($latestDiagnosticTest) {
                $questions = $latestDiagnosticTest->uncompletedquestions;
            } else {
                return response()->json(['message' => 'User has already completed at least one diagnostic test. Subscription Required', 'code' => 400], 400);
            }
        } else {
            $test = $user->tests()->create([
                'test' => 'Free Diagnostic for ' . $user->name . ' on ' . Carbon::now()->toDateString(),
                'description' => 'Automatically generated free diagnostic test',
                'diagnostic' => true,
                'start_available_time' => Carbon::now(),
                'end_available_time' => Carbon::now()->addYear(),
                'due_time' => Carbon::now()->addYear(),
                'number_of_tries_allowed' => 2,
                'which_result' => 'highest',
                'status_id' => 2,
            ]);

            $questions = $test->fieldDiagnosticQuestions(Course::find(1));
            ProcessQuestionAssignment::dispatch($questions->pluck('id'), $test->id, $user->id);
        }

        return response()->json(['message' => 'Request executed successfully', 'test' => $test->id ?? null, 'questions' => $questions, 'code' => 201]);
    }

    public function subscribe(Request $request)
    {
        $user = Auth::user();
        $stripeTransactionId = 'txn_xxx';

        $role = Role::where('role', 'LIKE', '%Student%')->first();

        $enrolment = Enrolment::firstOrNew([
            'user_id' => $user->id,
            'house_id' => 1,
            'role_id' => $role->id ?? null
        ]);

        $enrolment->fill([
            'start_date' => new DateTime(),
            'expiry_date' => (new DateTime())->modify('+1 year'),
            'payment_email' => $user->email,
            'purchaser_id' => $user->id,
            'transaction_id' => $stripeTransactionId,
            'payment_status' => 'paid',
            'amount_paid' => 600,
            'currency_code' => 'SGD'
        ])->save();

        $this->updateUserDetails($request, $user);
        $this->sendConfirmationEmail($user, $enrolment);

        return $this->diagnostic($request);
    }

    public function mastercode(StoreMasterCodeRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();
     return       $enrolmentDetails = $this->validateAndEnrollUser($request, $user);
            $this->updateUserDetails($request, $user);
            $this->sendConfirmationEmail($user, $enrolmentDetails['enrolment']);
            DB::commit();
            return $this->diagnostic($request);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage(), 'code' => 404], 404);
        }
    }

    protected function validateAndEnrollUser($request, $user)
    {
        $check_mastercode = Enrolment::where('mastercode', $request->mastercode)->firstOrFail();
        if ($check_mastercode->places_alloted <= 0) {
            throw new \Exception('There are no more places left for the mastercode you keyed in.');
        }

        $check_mastercode->decrement('places_alloted');

        $role = Role::where('role', 'LIKE', '%Student%')->firstOrFail();

        $enrolment = Enrolment::updateOrCreate(
            ['user_id' => $user->id, 'house_id' => $check_mastercode->house_id],
            [
                'start_date' => now(),
                'expiry_date' => now()->addYear(),
                'role_id' => $role->id,
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

    protected function updateUserDetails($request, $user)
    {
        $user->update([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'date_of_birth' => Carbon::createFromFormat('Y-m-d', $request->date_of_birth)->format('Y-m-d')
        ]);
    }

    protected function sendConfirmationEmail($user, $enrolment)
    {
		if ($enrolment && $enrolment->house) {
       		$note = "Dear {$user->firstname},<br><br>Thank you for enrolling in {$enrolment->house->description} program!<br><br>You should be presented questions for the diagnosis test and we will start to monitor your progress from now.<br><br>You should check your progress periodically at https://math.allgifted.com. <br><br>Should you have any queries, please do not hesitate to contact us at math@allgifted.com.<br><br>Thank you.<br><br><i>This is an automated message generated by the All Gifted System.</i>";
       	} else {
	    // Handle the error, such as logging it or notifying the user
	    \Log::error('Enrolment or house is missing for user: ' . $user->id);
		}
	    return response()->json(['message' => 'Enrolment details are incomplete.', 'code' => 404], 404);
      		 

        $validator = Validator::make(['email' => $user->email], [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            \Log::error('Invalid email address for user: ' . $user->email);
            return;
        }

        Mail::send([], [], function ($message) use ($user, $note) {
            $message->from('pam@allgifted.com', 'All Gifted Admin')
                    ->to($user->email)->cc('kang@allgifted.com')
                    ->subject('Successful Enrolment')
                    ->setBody($note, 'text/html');
        });
    }
}