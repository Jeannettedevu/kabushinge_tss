KABUSHINGE TSS - LIBRARY MANAGEMENT SYSTEM (v2 - With Photo Upload & Error Handling)
=============================================================================

Uko bayishyira muri XAMPP:
1. Kora Extract y'iyi folder (kabushinge_tss_library) uyishyire muri: C:\xampp\htdocs\
2. Fungura XAMPP Control Panel utangize Apache na MySQL (Start).
3. Fungura Browser (Chrome/Edge) wandikemo: http://localhost/kabushinge_tss_library/

Amakuru yo kwinjira (Login Credentials):
- Username: admin
- Password: kabushinge2026

Ibyongewemo muri iyi version:
1. Photo Upload & Dashboard Display:
   - Umuyobozi (Admin) ashobora gushyiraho ifoto ye muri "My Profile & Photo" hanyuma igahita igaragara hejuru muri Dashboard no kuri Sidebar nyuma yo gukora Login.
   - Abanyeshuri nabo bafite aho bashyirirwaho amafoto muri "Manage Students" akagaragara kuri Dashboard no muri Circulation.
2. Error Handling yuzuye:
   - Kugenzura ubwoko bw'ifoto nyabwo (MIME type validation: JPG, PNG, WEBP gusa).
   - Kugenzura ubunini bw'ifoto (Max 2MB).
   - Kugenzura PHP upload errors zose (UPLOAD_ERR_INI_SIZE, PARTIAL, etc.).
   - Try/Catch na PDO Transactions ku makosa ya Database.

Ibyongewemo muri version nshya (KABUSHINGE TSS):
- Izina rya sisitemu ryahindutse: KABUSHINGE TSS.
- Dashboard ifite "My Details": Full Name, Department, Program, Reg Number, Email Address, na Class.
- Ayo makuru uyuzuza/uyahindura muri "My Profile & Photo".
- Database nshya yitwa: kabushinge_tss_library (ikorwa yonyine ubwa mbere ufunguye).
