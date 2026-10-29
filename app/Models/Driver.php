<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'id_driver';

    protected $fillable = [
        'fname_driver',
        'lname_driver',
        'address_no',      
        'moo',             
        'address_detail',
        'subdistrict',
        'district',
        'province',
        'zipcode',
        'phone_driver',
        'citizenid_driver',
        'citizen_image',
    ];

    public function getFullNameAttribute(): string
    {
        return trim($this->fname_driver . ' ' . $this->lname_driver);
    }

    /** แคมป์ที่คนขับคนนี้เคย/กำลังทำงาน (ผ่านการมอบหมายรถ) */
    public function camps()
    {
        return $this->belongsToMany(Camp::class, 'camp_truck', 'id_driver', 'id_camp')
            ->using(CampTruck::class)
            ->withPivot('id_assignment', 'id_truck', 'assigned_date', 'released_date', 'note')
            ->withTimestamps();
    }

    /** การมอบหมายที่ยังไม่ถอนออก (คนขับ 1 คน ทำงานได้ทีละแคมป์) */
    public function activeAssignment()
    {
        return $this->hasOne(CampTruck::class, 'id_driver', 'id_driver')
            ->whereNull('released_date')
            ->latestOfMany('id_assignment');
    }

    /** คนขับที่ว่าง = ไม่ได้ประจำแคมป์ไหนอยู่ */
    public function scopeAvailable($query)
    {
        return $query->whereNotIn('id_driver', CampTruck::query()
            ->whereNull('released_date')
            ->whereNotNull('id_driver')
            ->select('id_driver'));
    }
}