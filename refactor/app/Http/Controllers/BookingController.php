<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Job;
use DTApi\Http\Requests;
use DTApi\Models\Distance;
use Illuminate\Http\Request;
use DTApi\Repository\BookingRepository;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{

    /**
     * @var BookingRepository
     */
    protected $repository;

    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository)
    {
        $this->repository = $bookingRepository;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if(!is_null($user)) {

            $response = $this->repository->getUsersJobs($user->id);
            return response($response, 200);

        }
        elseif($user->user_type == env('ADMIN_ROLE_ID') || $user->user_type == env('SUPERADMIN_ROLE_ID'))
        {
            $response = $this->repository->getAll($request);
            return response($response, 200);
        }
        abort('404');
    }

    /**
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        $job = $this->repository->with('translatorJobRel.user')->find($id);

        return response($job, 200);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->store($request->user(), $data);

        return response($response, 200);

    }

    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function update($id, Request $request)
    {
        $data = $request->all();
        $user = $request->user();
        $response = $this->repository->updateJob($id, array_except($data, ['_token', 'submit']), $user);

        return response($response, 200);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function immediateJobEmail(Request $request)
    {
        $adminSenderEmail = config('app.adminemail');
        $data = $request->all();
        $user = $request->user();

        $response = $this->repository->storeJobEmail($data, $user);

        return response($response, 200);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getHistory(Request $request)
    {
        $user = $request->user();
        if(!is_null($user)) {

            $response = $this->repository->getUsersJobsHistory($user->id, $request);
            return response($response, 200);
        }

        return response([], 403);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function acceptJob(Request $request)
    {
        $data = $request->all();
        $user = $$request->user();

        $response = $this->repository->acceptJob($data, $user);

        return response($response, 200);
    }

    public function acceptJobWithId(Request $request)
    {
        $data = $request->job_id;
        $user = $request->user();

        $response = $this->repository->acceptJobWithId($data, $user);

        return response($response, 200);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function cancelJob(Request $request)
    {
        $data = $request->all();
        $user = $request->user();

        $response = $this->repository->cancelJobAjax($data, $user);

        return response($response, 200);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function endJob(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->endJob($data);

        return response($response, 200);

    }

    public function customerNotCall(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->customerNotCall($data);

        return response($response, 200);

    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPotentialJobs(Request $request)
    {
        $user = $request->user();

        $response = $this->repository->getPotentialJobs($user);

        return response($response, 200);
    }

    public function distanceFeed(Request $request)
    {
        // $data = $request->all();
        if($request->flagged)
        {
            $request->validate([
                'admin_comment' => ['required'],
            ]);
        }

        $distance = $request->distance ?? '';
        $time = $request->time ?? '';
        $job_id = $request->jobid ?? '';
        $session = $request->session_time ?? '';
        $flagged = ($request->flagged) ? true : false; 
        $manually_handled = ($request->manually_handled) ? true : false;
        $by_admin = ($request->by_admin) ? true : false;
        $admin_comment = $request->admin_comment ?? '';

        if ($time && $distance) {

            $affected_rows = Distance::where('job_id', '=', $jobid)->update(array('distance' => $distance, 'time' => $time));
        }

        if ($admin_comment && $session && $flagged && $manually_handled && $by_admin) {

            $affectedRows1 = Job::where('id', '=', $jobid)->update(array('admin_comments' => $admincomment, 'flagged' => $flagged, 'session_time' => $session, 'manually_handled' => $manually_handled, 'by_admin' => $by_admin));

        }

        return response('Record updated!', 200);
    }

    public function reopen(Request $request)
    {
        $data = $request->all();
        $response = $this->repository->reopen($data);

        return response($response, 200);
    }

    public function resendNotifications(Request $request)
    {
        $data = $request->all();
        $job = $this->repository->find($request->job_id);
        $job_data = $this->repository->jobToData($job);
        $this->repository->sendNotificationTranslator($job, $job_data, '*');

        return response('Push sent', 200);
    }

    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request)
    {
        $data = $request->all();
        $job = $this->repository->find($request->job_id);
        $job_data = $this->repository->jobToData($job);

        try {
            $this->repository->sendSMSNotificationToTranslator($job);
            return response('SMS sent', 200);
        } catch (\Exception $e) {
            return response(['success' => $e->getMessage()], 500);
        }
    }

}
