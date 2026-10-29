<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Permission;
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
        $perPage = $this->perPage($request);
        $search  = $this->searchTerm($request);

        // พิมพ์ "ผู้ดูแล" / "พนักงาน" / "ดูอย่างเดียว" ก็ค้นตามบทบาทได้
        $roles = $this->statusesMatching($search, User::roleList());

        // dropdown สถานะบัญชี + สิทธิ์การใช้งาน
        $statusLabels = ['active' => 'ใช้งานอยู่', 'suspended' => 'ระงับการใช้งาน'];
        $status = $this->statusFilter($request, array_keys($statusLabels));
        $role   = $this->statusFilter($request, array_keys(User::roleList()), 'role');

        $users = User::query()
            ->when($status, fn ($query) => $query->where('is_active', $status === 'active'))
            ->when($role, fn ($query) => $query->where('role', $role))
            ->when($search !== '', function ($query) use ($search, $roles) {
                $query->where(function ($sub) use ($search, $roles) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                    if ($roles) {
                        $sub->orWhereIn('role', $roles);
                    }
                });
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return view('users.index', compact('users', 'search', 'perPage', 'status', 'statusLabels', 'role'));
    }

    /* ============================================================
     |  1.2.2 เพิ่มผู้ใช้งาน + กำหนดสิทธิ์
     ============================================================ */
    public function create()
    {
        // เพิ่มผู้ใช้ทำในหน้าต่าง (modal) ของหน้ารายการ
        return redirect()->route('users.index', ['modal' => 'create']);
    }

    public function store(Request $request)
    {
        $data = $request->validateWithBag('createUser', [
            'first_name'    => ['required', 'string', 'max:100'],
            'last_name'     => ['required', 'string', 'max:100'],
            'email'         => ['required', 'email', 'max:255', 'unique:users,email'],
            'role'          => ['required', Rule::in(array_keys(User::roleList()))],
            'password'      => ['required', 'confirmed', Password::min(8)],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => [Rule::in(array_keys(Permission::LEVELS))],
        ], [
            'first_name.required' => 'กรุณากรอกชื่อ',
            'last_name.required'  => 'กรุณากรอกนามสกุล',
            'email.required'    => 'กรุณากรอกอีเมล',
            'email.email'       => 'รูปแบบอีเมลไม่ถูกต้อง',
            'email.unique'      => 'อีเมลนี้ถูกใช้งานแล้ว',
            'role.required'     => 'กรุณาเลือกสิทธิ์การใช้งาน',
            'password.required' => 'กรุณากรอกรหัสผ่าน',
            'password.confirmed'=> 'รหัสผ่านยืนยันไม่ตรงกัน',
            'password.min'      => 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร',
            'permissions.*.in'  => 'ค่าสิทธิ์รายหน้าไม่ถูกต้อง',
        ]);

        $data['is_active']   = $request->boolean('is_active');
        $data['permissions'] = $this->permissionsFromRequest($request, $data['role']);

        User::create($data);

        return redirect()
            ->route('users.index')
            ->with('ok', 'เพิ่มผู้ใช้งานเรียบร้อยแล้ว');
    }

    /* ============================================================
     |  1.2.3 แก้ไขผู้ใช้งาน / เปลี่ยนสิทธิ์
     ============================================================ */
    public function edit(User $user)
    {
        // แก้ไขผู้ใช้ทำในหน้าต่าง (modal) ของหน้ารายการ
        return redirect()->route('users.index', ['edit' => $user->getKey()]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validateWithBag('updateUser', [
            'first_name'    => ['required', 'string', 'max:100'],
            'last_name'     => ['required', 'string', 'max:100'],
            'email'         => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->getKey())],
            'role'          => ['required', Rule::in(array_keys(User::roleList()))],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => [Rule::in(array_keys(Permission::LEVELS))],
        ], [
            'first_name.required' => 'กรุณากรอกชื่อ',
            'last_name.required'  => 'กรุณากรอกนามสกุล',
            'email.required'   => 'กรุณากรอกอีเมล',
            'email.email'      => 'รูปแบบอีเมลไม่ถูกต้อง',
            'email.unique'     => 'อีเมลนี้ถูกใช้งานแล้ว',
            'role.required'    => 'กรุณาเลือกสิทธิ์การใช้งาน',
            'permissions.*.in' => 'ค่าสิทธิ์รายหน้าไม่ถูกต้อง',
        ]);

        if ($user->getKey() === auth()->id()
            && $data['role'] !== User::ROLE_ADMIN
            && $this->countActiveAdmins() <= 1) {

            return back()->withInput()->withErrors([
                'role' => 'ไม่สามารถลดสิทธิ์ตนเองได้ เนื่องจากเป็นผู้ดูแลระบบคนสุดท้าย',
            ], 'updateUser');
        }

        // ฟอร์มที่ไม่มีตารางสิทธิ์ส่งมา → คงค่าเดิมไว้ ไม่ล้างทิ้ง
        if ($request->has('permissions') || $data['role'] === User::ROLE_ADMIN) {
            $data['permissions'] = $this->permissionsFromRequest($request, $data['role']);
        } else {
            unset($data['permissions']);
        }

        $user->update($data);

        return redirect()
            ->route('users.index')
            ->with('ok', 'แก้ไขข้อมูลผู้ใช้งานเรียบร้อยแล้ว');
    }

    /* ============================================================
     |  ลายเซ็นอิเล็กทรอนิกส์ (แสดงในกรอบลายเซ็นของ PDF ทุกเอกสาร)
     |  เก็บไฟล์ไว้ใน public/signatures เพราะ dompdf อ่านได้เฉพาะไฟล์ในเครื่อง
     ============================================================ */
    public function updateSignature(Request $request, User $user)
    {
        $data = $request->validateWithBag('updateSignature', [
            'position'         => ['nullable', 'string', 'max:100'],
            'signature'        => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:1024'],
            'remove_signature' => ['nullable', 'boolean'],
        ], [
            'signature.image' => 'ไฟล์ลายเซ็นต้องเป็นรูปภาพ',
            'signature.mimes' => 'ไฟล์ลายเซ็นต้องเป็น PNG หรือ JPG',
            'signature.max'   => 'ไฟล์ลายเซ็นต้องไม่เกิน 1 MB',
        ]);

        $update = ['position' => $data['position'] ?? null];
        $old    = $user->signature_path;

        if ($request->hasFile('signature')) {
            $file = $request->file('signature');
            $name = 'user-' . $user->getKey() . '-' . now()->format('YmdHis') . '.' . strtolower($file->getClientOriginalExtension());
            $file->move(public_path('signatures'), $name);
            $update['signature_path'] = 'signatures/' . $name;
        } elseif ($request->boolean('remove_signature')) {
            $update['signature_path'] = null;
        }

        $user->update($update);

        // ลบไฟล์เดิมเมื่อเปลี่ยน/ลบลายเซ็น (เฉพาะไฟล์ในโฟลเดอร์ signatures)
        if ($old && array_key_exists('signature_path', $update) && $old !== $update['signature_path']
            && str_starts_with($old, 'signatures/') && is_file(public_path($old))) {
            @unlink(public_path($old));
        }

        return redirect()
            ->route('users.index')
            ->with('ok', 'บันทึกลายเซ็นของ ' . $user->name . ' เรียบร้อยแล้ว');
    }

    /* ============================================================
     |  1.2.4 รีเซ็ตรหัสผ่านให้ผู้ใช้
     ============================================================ */
    public function resetPassword(Request $request, User $user)
    {
        $request->validateWithBag('updatePassword', [
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'password.required'  => 'กรุณากรอกรหัสผ่านใหม่',
            'password.confirmed' => 'รหัสผ่านยืนยันไม่ตรงกัน',
            'password.min'       => 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร',
        ]);

        $user->update(['password' => $request->password]);

        return redirect()
            ->route('users.index')
            ->with('ok', "รีเซ็ตรหัสผ่านของ {$user->name} เรียบร้อยแล้ว");
    }

    /* ============================================================
     |  1.2.5 ระงับ / เปิดใช้งานบัญชี
     ============================================================ */
    public function toggleStatus(User $user)
    {
        if ($user->getKey() === auth()->id()) {
            return back()->with('error', 'ไม่สามารถระงับบัญชีของตนเองได้');
        }

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
        if ($user->getKey() === auth()->id()) {
            return back()->with('error', 'ไม่สามารถลบบัญชีของตนเองได้');
        }

        if ($user->isAdmin() && $this->countActiveAdmins() <= 1) {
            return back()->with('error', 'ไม่สามารถลบผู้ดูแลระบบคนสุดท้ายได้');
        }

        $name = $user->name;
        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('ok', "ลบผู้ใช้งาน {$name} เรียบร้อยแล้ว");
    }

    /* ============================================================
     |  นับผู้ดูแลระบบที่ยังใช้งานอยู่
     ============================================================ */
    private function countActiveAdmins(): int
    {
        return User::where('role', User::ROLE_ADMIN)
            ->where('is_active', true)
            ->count();
    }

    /* ============================================================
     |  แปลงค่าสิทธิ์รายหน้าจากฟอร์มก่อนบันทึก
     |  admin เข้าได้ทุกหน้าอยู่แล้ว จึงเก็บเป็น null
     ============================================================ */
    private function permissionsFromRequest(Request $request, string $role): ?array
    {
        if ($role === User::ROLE_ADMIN || ! $request->has('permissions')) {
            return null;
        }

        return Permission::clean($request->input('permissions', []));
    }
}