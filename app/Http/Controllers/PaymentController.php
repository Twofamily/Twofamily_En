<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Receipt;
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
            'slip'         => Payment::slipRules(),   // บังคับ ยกเว้นเงินสด
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
            'slip.required_unless'    => 'กรุณาแนบหลักฐานการชำระเงิน (สลิปโอน / รูปเช็ค)',
            'slip.mimes'              => 'หลักฐานต้องเป็นไฟล์ JPG, PNG หรือ PDF',
            'slip.max'                => 'ไฟล์หลักฐานต้องมีขนาดไม่เกิน 5 MB',
        ]);

        // เงินสดอาจไม่มีไฟล์ — ถ้ามีไฟล์ กันการใช้ไฟล์เดิมซ้ำ (เทียบจาก hash ของเนื้อไฟล์)
        $file = $request->file('slip');
        $path = $hash = null;

        if ($file) {
            $hash = hash_file('sha256', $file->getRealPath());

            $dup = Payment::with(['invoice', 'receipt'])->where('slip_hash', $hash)->first();
            if ($dup) {
                return back()->withInput()->with(
                    'error',
                    'ไฟล์หลักฐานนี้เคยถูกใช้แล้วในเอกสาร ' . $dup->ownerCode()
                );
            }

            // เก็บไฟล์ใน disk 'local' (private) ไม่เปิดให้เข้าถึงผ่าน URL ตรง
            $path = $file->store(Payment::SLIP_DIR, 'local');
        }

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
                    'slip_original_name'  => $file?->getClientOriginalName(),
                    'slip_hash'           => $hash,
                    'recorded_by_user_id' => auth()->id(),
                ]);

                $invoice->syncPaymentStatus();
            });
        } catch (\Throwable $e) {
            // บันทึก DB ไม่สำเร็จ → ลบไฟล์ที่อัปไปแล้วทิ้ง ไม่ให้มีไฟล์กำพร้า
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $e;
        }

        return redirect()
            ->route('invoices.show', $invoice->id_invoice)
            ->with('success', 'บันทึกการชำระเงินเรียบร้อย');
    }

    /**
     * หักเงินรับล่วงหน้าของแคมป์ในใบแจ้งหนี้
     *
     * ไล่หักจากใบเสร็จรับเงินล่วงหน้าที่เก่าที่สุดก่อน (FIFO)
     * แต่ละก้อนที่หัก = รายการชำระ 1 แถว method 'advance' ชี้กลับไปที่ใบเสร็จล่วงหน้า
     * จึงตามได้ว่าเงินล่วงหน้าใบไหนถูกใช้กับใบแจ้งหนี้ใบไหน เท่าไร
     */
    public function applyAdvance(Request $request, $id)
    {
        $invoice = Invoice::with('camp')->findOrFail($id);

        if (! $invoice->canApplyAdvance()) {
            return back()->with('error', match (true) {
                $invoice->hasReceipt()             => 'ใบแจ้งหนี้นี้ออกใบเสร็จแล้ว',
                $invoice->remainingAmount() <= 0   => 'ใบแจ้งหนี้นี้ชำระครบยอดแล้ว',
                ! $invoice->camp                   => 'ใบแจ้งหนี้นี้ไม่ได้ผูกกับแคมป์ จึงไม่มีเงินล่วงหน้าให้หัก',
                default                            => 'แคมป์นี้ไม่มีเงินรับล่วงหน้าคงเหลือ',
            });
        }

        $max = min($invoice->remainingAmount(), $invoice->availableAdvance());

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $max,
        ], [
            'amount.required' => 'กรุณาระบุยอดที่จะหัก',
            'amount.min'      => 'ยอดที่หักต้องมากกว่า 0',
            'amount.max'      => 'หักได้ไม่เกิน ' . number_format($max, 2) . ' บาท',
        ]);

        DB::transaction(function () use ($invoice, $data) {
            $left = round((float) $data['amount'], 2);

            // lockForUpdate กันกดพร้อมกันสองหน้าจอแล้วหักเงินก้อนเดียวกันซ้ำ
            $advances = Receipt::where('receipt_type', Receipt::TYPE_ADVANCE)
                ->where('id_camp', $invoice->id_camp)
                ->orderBy('date_receipt')
                ->orderBy('id_receipt')
                ->lockForUpdate()
                ->get();

            foreach ($advances as $advance) {
                if ($left <= 0) {
                    break;
                }

                $take = round(min($advance->advanceRemaining(), $left), 2);

                if ($take <= 0) {
                    continue;
                }

                $invoice->payments()->create([
                    'id_advance_receipt'  => $advance->id_receipt,
                    'paid_at'             => now()->toDateString(),
                    'amount'              => $take,
                    'method'              => Payment::METHOD_ADVANCE,
                    'reference_no'        => $advance->code_rc,
                    'note'                => 'หักจากใบเสร็จรับเงินล่วงหน้า ' . $advance->code_rc,
                    'recorded_by_user_id' => auth()->id(),
                ]);

                $left = round($left - $take, 2);
            }

            $invoice->syncPaymentStatus();
        });

        return redirect()
            ->route('invoices.show', $invoice->id_invoice)
            ->with('success', 'หักเงินรับล่วงหน้า ' . number_format($data['amount'], 2) . ' บาท เรียบร้อย');
    }

    /**
     * เปิดดูไฟล์หลักฐาน (ต้องล็อกอิน)
     */
    public function slip(Payment $payment)
    {
        abort_unless($payment->hasSlip() && Storage::disk('local')->exists($payment->slip_path), 404);

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

        // หลักฐานของใบเสร็จรับเงินล่วงหน้า / ขายสด ลบพร้อมใบเสร็จเท่านั้น
        if (! $invoice) {
            return back()->with('error', 'รายการนี้เป็นหลักฐานของใบเสร็จ ' . $payment->ownerCode() . ' ต้องลบที่ใบเสร็จ');
        }

        if (! $invoice->canEditPayments()) {
            return back()->with('error', 'ใบแจ้งหนี้นี้ออกใบเสร็จแล้ว ไม่สามารถลบรายการชำระได้');
        }

        $path = $payment->slip_path;

        DB::transaction(function () use ($payment, $invoice) {
            $payment->delete();
            $invoice->syncPaymentStatus();
        });

        // รายการหักเงินล่วงหน้าไม่มีไฟล์
        if ($path) {
            Storage::disk('local')->delete($path);
        }

        return redirect()
            ->route('invoices.show', $invoice->id_invoice)
            ->with('success', 'ลบรายการชำระเงินเรียบร้อย');
    }
}