<?php
/**
 * ค่าเพิ่มเติมของ phpMyAdmin สำหรับ dev เครื่องนี้
 * root ไม่มีรหัสผ่าน (ตรงกับ .env) → ต้องเปิด AllowNoPassword
 */
$cfg['Servers'][1]['AllowNoPassword'] = true;
$cfg['Servers'][1]['hide_db']         = '^(information_schema|performance_schema|mysql|sys)$';
$cfg['ShowPhpInfo']                   = false;
$cfg['SendErrorReports']              = 'never';
