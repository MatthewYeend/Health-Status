<?php

namespace MattYeend\HealthStatus\Http\Controllers;

use Illuminate\Routing\Controller;
use MattYeend\HealthStatus\HealthChecker;
use Symfony\Component\HttpFoundation\Response;

class HealthController extends Controller
{
    public function __construct(protected HealthChecker $checker) {}

    public function show()
    {
        $data = $this->checker->all();
        $viewEnabled = config('healthstatus.http.view', true);

        if (request()->wantsJson() || !$viewEnabled) {
            return response()->json($data, $this->httpStatus($data['status']));
        }

        return view('healthstatus::status', compact('data'));
    }

    public function json()
    {
        $data = $this->checker->all();
        return response()->json($data, $this->httpStatus($data['status']));
    }

    protected function httpStatus(string $status): int
    {
        return $status === 'ok'
            ? Response::HTTP_OK
            : Response::HTTP_SERVICE_UNAVAILABLE;
    }
}
