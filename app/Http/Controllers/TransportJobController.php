<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Driver;
use App\Models\TransportJob;
use App\Models\Truck;
use Illuminate\Http\Request;

class TransportJobController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->q;

        $jobs = TransportJob::with(['customer', 'truck', 'driver'])
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->whereHas('customer', function ($c) use ($q) {
                        $c->where('name_customer', 'like', "%{$q}%");
                    })
                    ->orWhere('start_point', 'like', "%{$q}%")
                    ->orWhere('destination', 'like', "%{$q}%");
                });
            })
            ->latest()
            ->paginate(10);

        return view('transport_jobs.index', compact('jobs'));
    }

    public function create()
    {
        return view('transport_jobs.create', [
            'customers' => Customer::orderBy('name_customer')->get(),
            'trucks'    => Truck::orderBy('license_plate')->get(),
            'drivers'   => Driver::orderBy('name_driver')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateJob($request);

        TransportJob::create($validated);

        return redirect()
            ->route('transport-jobs.index')
            ->with('ok', 'เพิ่มแผนงานเรียบร้อย');
    }

    public function show(TransportJob $transport_job)
    {
        $transport_job->load(['customer', 'truck', 'driver']);

        return view('transport_jobs.show', [
            'job' => $transport_job,
        ]);
    }

    public function edit(TransportJob $transport_job)
    {
        return view('transport_jobs.edit', [
            'job'       => $transport_job,
            'customers' => Customer::orderBy('name_customer')->get(),
            'trucks'    => Truck::orderBy('license_plate')->get(),
            'drivers'   => Driver::orderBy('name_driver')->get(),
        ]);
    }

    public function update(Request $request, TransportJob $transport_job)
    {
        $validated = $this->validateJob($request);

        $transport_job->update($validated);

        return redirect()
            ->route('transport-jobs.index')
            ->with('ok', 'แก้ไขแผนงานเรียบร้อย');
    }

    public function destroy(TransportJob $transport_job)
    {
        $transport_job->delete();

        return redirect()
            ->route('transport-jobs.index')
            ->with('ok', 'ลบแผนงานแล้ว');
    }

    private function validateJob(Request $request): array
    {
        return $request->validate([
            'customer_id' => ['required', 'exists:customers,id_customer'],
            'truck_id'    => ['required', 'exists:trucks,id_truck'],
            'driver_id'   => ['required', 'exists:drivers,id_driver'],
            'start_date'  => ['required', 'date'],
            'end_date'    => ['nullable', 'date', 'after_or_equal:start_date'],
            'start_point' => ['required', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:255'],
            'distance_km' => ['required', 'numeric', 'min:0'],
        ]);
    }
}