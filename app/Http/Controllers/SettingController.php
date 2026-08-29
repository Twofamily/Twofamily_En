<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    public function index()
    {
        return view('settings.index');
    }

    public function documents()
    {
        return view('settings.documents');
    }

    public function quotation()
    {
        $settings = Setting::pluck('value', 'key');

        return view('settings.documents.quotation', compact('settings'));
    }

    public function quotationUpdate(Request $request)
    {
        $this->saveSettings($request->except(['_token', '_method']));

        return back()->with('ok', 'บันทึกตั้งค่าใบเสนอราคาสำเร็จ');
    }

    public function invoice()
    {
        $settings = Setting::pluck('value', 'key');

        return view('settings.documents.invoice', compact('settings'));
    }

    public function invoiceUpdate(Request $request)
    {
        $this->saveSettings($request->except(['_token', '_method']));

        return back()->with('ok', 'บันทึกตั้งค่าใบแจ้งหนี้เรียบร้อย');
    }

    public function update(Request $request)
    {
        $this->saveSettings($request->except(['_token', '_method']));

        return back()->with('ok', 'บันทึกการตั้งค่าสำเร็จ');
    }

    private function saveSettings(array $settingsData): void
    {
        DB::transaction(function () use ($settingsData) {
            foreach ($settingsData as $key => $value) {
                Setting::updateOrCreate(
                    ['key'   => $key],
                    ['value' => $value]
                );
            }
        });
    }
}