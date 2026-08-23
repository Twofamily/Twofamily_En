<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /* ============================================================
     |  1.2.1 แสดงรายการผู้ใช้งาน + ค้นหา + แบ่งหน้า
     ============================================================ */
    public function index(Request $request)
    {
        $q = $request->input('q');

        $users = User::query()
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();   // ให้คำค้นหาติดไปกับ pagination

        return view('users.index', compact('users', 'q'));
    }

    /* ============================================================
     |  1.2.2 เพิ่มผู้ใช้งาน + กำหนดสิทธิ์
     ============================================================ */
    public function create()
    {
        return view('users.create', [
            'roles' => User::roleList(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255', 'unique:users,email'],
            'role'      => ['required', Rule::in(array_keys(User::roleList()))],
            'password'  => ['required', 'confirmed', Password::min(8)],
        ], [
            'name.required'      => 'กรุณากรอกชื่อ-นามสกุล',
            'email.required'     => 'กรุณากรอกอีเมล',
            'email.email'        => 'รูปแบบอีเมลไม่ถูกต้อง',
            'email.unique'       => 'อีเมลนี้ถูกใช้งานแล้ว',
            'role.required'      => 'กรุณาเลือกสิทธิ์การใช้งาน',
            'password.required'  => 'กรุณากรอกรหัสผ่าน',
            'password.confirmed' => 'รหัสผ่านยืนยันไม่ตรงกัน',
            'password.min'       => 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร',
        ]);

        // checkbox ที่ไม่ติ๊กจะไม่ถูกส่งมาใน request ต้องกำหนดค่าเอง
        $data['is_active'] = $request->boolean('is_active');

        // password ถูกเข้ารหัสอัตโนมัติจาก casts ใน User.php
        User::create($data);

        return redirect()->route('users.index')
            ->with('ok', 'เพิ่มผู้ใช้งานเรียบร้อยแล้ว');
    }

    /* ============================================================
     |  1.2.3 แก้ไขผู้ใช้งาน / เปลี่ยนสิทธิ์
     ============================================================ */
    public function edit(User $user)
    {
        return view('users.edit', [
            'user'  => $user,
            'roles' => User::roleList(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role'  => ['required', Rule::in(array_keys(User::roleList()))],
        ], [
            'name.required'  => 'กรุณากรอกชื่อ-นามสกุล',
            'email.required' => 'กรุณากรอกอีเมล',
            'email.email'    => 'รูปแบบอีเมลไม่ถูกต้อง',
            'email.unique'   => 'อีเมลนี้ถูกใช้งานแล้ว',
            'role.required'  => 'กรุณาเลือกสิทธิ์การใช้งาน',
        ]);

        // ป้องกันลดสิทธิ์ตนเองจนระบบไม่เหลือผู้ดูแล
        if ($user->id === auth()->id()
            && $data['role'] !== User::ROLE_ADMIN
            && $this->countActiveAdmins() <= 1) {

            return back()->withInput()->withErrors([
                'role' => 'ไม่สามารถลดสิทธิ์ตนเองได้ เนื่องจากเป็นผู้ดูแลระบบคนสุดท้าย',
            ]);
        }

        $user->update($data);

        return redirect()->route('users.index')
            ->with('ok', 'แก้ไขข้อมูลผู้ใช้งานเรียบร้อยแล้ว');
    }

    /* ============================================================
     |  1.2.4 รีเซ็ตรหัสผ่านให้ผู้ใช้
     ============================================================ */
    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'password.required'  => 'กรุณากรอกรหัสผ่านใหม่',
            'password.confirmed' => 'รหัสผ่านยืนยันไม่ตรงกัน',
            'password.min'       => 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร',
        ]);

        $user->update(['password' => $request->password]);

        return redirect()->route('users.index')
            ->with('ok', "รีเซ็ตรหัสผ่านของ {$user->name} เรียบร้อยแล้ว");
    }

    /* ============================================================
     |  1.2.5 ระงับ / เปิดใช้งานบัญชี
     ============================================================ */
    public function toggleStatus(User $user)
    {
        // ห้ามระงับบัญชีตนเอง เพราะจะล็อกตัวเองออกจากระบบทันที
        if ($user->id === auth()->id()) {
            return back()->with('error', 'ไม่สามารถระงับบัญชีของตนเองได้');
        }

        // ห้ามระงับผู้ดูแลระบบคนสุดท้าย
        if ($user->isAdmin() && $user->is_active && $this->countActiveAdmins() <= 1) {
            return back()->with('error', 'ไม่สามารถระงับผู้ดูแลระบบคนสุดท้ายได้');
        }

        $user->update(['is_active' => ! $user->is_active]);

        $status = $user->is_active ? 'เปิดใช้งาน' : 'ระงับ';

        return back()->with('ok', "{$status}บัญชี {$user->name} เรียบร้อยแล้ว");
    }

    /* ============================================================
     |  1.2.6 ลบผู้ใช้งาน
     ============================================================ */
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'ไม่สามารถลบบัญชีของตนเองได้');
        }

        if ($user->isAdmin() && $this->countActiveAdmins() <= 1) {
            return back()->with('error', 'ไม่สามารถลบผู้ดูแลระบบคนสุดท้ายได้');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('users.index')
            ->with('ok', "ลบผู้ใช้งาน {$name} เรียบร้อยแล้ว");
    }

    /* ============================================================
     |  นับผู้ดูแลระบบที่ยังใช้งานอยู่
     |  ใช้ตรวจสอบก่อนลบ / ระงับ / ลดสิทธิ์
     ============================================================ */
    private function countActiveAdmins(): int
    {
        return User::where('role', User::ROLE_ADMIN)
            ->where('is_active', true)
            ->count();
    }
}