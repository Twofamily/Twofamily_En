<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    /**
     * บันทึกการชำระเงิน + แนบหลักฐาน
     */
    public function store(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);

        if (! $invoice->canEditPayments()) {
            return back()->with('error', 'ใบแจ้งหนี้นี้ออกใบเสร็จแล้ว ไม่สามารถบันทึกการชำระเพิ่มได้');
        }

        $remaining = $invoice->remainingAmount();

        if ($remaining <= 0) {
            return back()->with('warning', 'ใบแจ้งหนี้นี้ชำระครบยอดแล้ว');
        }

        $data = $request->validate([
            'paid_at'      => 'required|date|before_or_equal:today',
            'amount'       => 'required|numeric|min:0.01|max:' . $remaining,
            'method'       => 'required|in:' . implode(',', array_keys(Payment::METHODS)),
            'bank_name'    => 'nullable|required_if:method,transfer|string|max:100',
            'reference_no' => 'nullable|string|max:50',
            'note'         => 'nullable|string|max:500',
            'slip'         => 'required|file|mimes:' . Payment::SLIP_MIMES . '|max:' . Payment::SLIP_MAX_KB,
        ], [
            'paid_at.required'        => 'กรุณาระบุวันที่ชำระ',
            'paid_at.before_or_equal' => 'วันที่ชำระต้องไม่เกินวันนี้',
            'amount.required'         => 'กรุณาระบุยอดเงิน',
            'amount.numeric'          => 'ยอดเงินต้องเป็นตัวเลข',
            'amount.min'              => 'ยอดเงินต้องมากกว่า 0',
            'amount.max'              => 'ยอดเงินเกินยอดคงเหลือ (' . number_format($remaining, 2) . ' บาท)',
            'method.required'         => 'กรุณาเลือกวิธีชำระ',
            'method.in'               => 'วิธีชำระไม่ถูกต้อง',
            'bank_name.required_if'   => 'กรุณาระบุธนาคาร เมื่อชำระด้วยการโอนเงิน',
            'slip.required'           => 'กรุณาแนบหลักฐานการชำระเงิน',
            'slip.mimes'              => 'หลักฐานต้องเป็นไฟล์ JPG, PNG หรือ PDF',
            'slip.max'                => 'ไฟล์หลักฐานต้องมีขนาดไม่เกิน 5 MB',
        ]);

        // กันการใช้ไฟล์หลักฐานเดิมซ้ำ (เทียบจาก hash ของเนื้อไฟล์)
        $file = $request->file('slip');
        $hash = hash_file('sha256', $file->getRealPath());

        $dup = Payment::with('invoice')->where('slip_hash', $hash)->first();
        if ($dup) {
            return back()->withInput()->with(
                'error',
                'ไฟล์หลักฐานนี้เคยถูกใช้แล้วในใบแจ้งหนี้ ' . ($dup->invoice->code_inv ?? '-')
            );
        }

        // เก็บไฟล์ใน disk 'local' (private) ไม่เปิดให้เข้าถึงผ่าน URL ตรง
        $path = $file->store(Payment::SLIP_DIR, 'local');

        try {
            DB::transaction(function () use ($invoice, $data, $file, $path, $hash) {
                $invoice->payments()->create([
                    'paid_at'             => $data['paid_at'],
                    'amount'              => $data['amount'],
                    'method'              => $data['method'],
                    'bank_name'           => $data['bank_name'] ?? null,
                    'reference_no'        => $data['reference_no'] ?? null,
                    'note'                => $data['note'] ?? null,
                    'slip_path'           => $path,
                    'slip_original_name'  => $file->getClientOriginalName(),
                    'slip_hash'           => $hash,
                    'recorded_by_user_id' => auth()->id(),
                ]);

                $invoice->syncPaymentStatus();
            });
        } catch (\Throwable $e) {
            // บันทึก DB ไม่สำเร็จ → ลบไฟล์ที่อัปไปแล้วทิ้ง ไม่ให้มีไฟล์กำพร้า
            Storage::disk('local')->delete($path);
            throw $e;
        }

        return redirect()
            ->route('invoices.show', $invoice->id_invoice)
            ->with('success', 'บันทึกการชำระเงินเรียบร้อย');
    }

    /**
     * เปิดดูไฟล์หลักฐาน (ต้องล็อกอิน)
     */
    public function slip(Payment $payment)
    {
        abort_unless(Storage::disk('local')->exists($payment->slip_path), 404);

        return Storage::disk('local')->response(
            $payment->slip_path,
            $payment->slip_original_name
        );
    }

    /**
     * ลบรายการชำระ (ทำได้เฉพาะตอนยังไม่ออกใบเสร็จ)
     */
    public function destroy(Payment $payment)
    {
        $invoice = $payment->invoice;

        if (! $invoice->canEditPayments()) {
            return back()->with('error', 'ใบแจ้งหนี้นี้ออกใบเสร็จแล้ว ไม่สามารถลบรายการชำระได้');
        }

        $path = $payment->slip_path;

        DB::transaction(function () use ($payment, $invoice) {
            $payment->delete();
            $invoice->syncPaymentStatus();
        });

        Storage::disk('local')->delete($path);

        return redirect()
            ->route('invoices.show', $invoice->id_invoice)
            ->with('success', 'ลบรายการชำระเงินเรียบร้อย');
    }
}