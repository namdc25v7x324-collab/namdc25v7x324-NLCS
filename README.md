# ValiStore

Website bán vali, balo và túi du lịch cho học phần CT271. Dự án sử dụng PHP, MariaDB và Bootstrap, có cửa hàng, tài khoản khách hàng, giỏ hàng, đặt hàng và khu quản trị.

**Sinh viên** Võ Bé Năm · **MSSV** DC25V7X324

## Chạy nhanh trên Windows

Cần Windows 10 hoặc 11 64 bit và Git để lấy mã nguồn. Mở PowerShell rồi chạy:

```powershell
git clone https://github.com/namdc25v7x324-collab/namdc25v7x324-NLCS.git vonam
cd vonam
.\demo.cmd
```

Nếu đã clone, chỉ cần chạy `.\demo.cmd` tại thư mục dự án. Script dùng XAMPP tại `C:\xampp` hoặc tải và cài khi chưa có. Lần đầu cài cần Internet và quyền cài đặt của Windows. Chọn **Yes** khi UAC hỏi.

Script khởi động MariaDB, tạo CSDL và dữ liệu mẫu nếu cài mới, rồi chạy Apache và mở trình duyệt. Cửa hàng thường ở [http://127.0.0.1:8080/vonam/](http://127.0.0.1:8080/vonam/) và khu quản trị ở đường dẫn `/vonam/admin/`. Nếu cổng 8080 bận, dùng URL mà script in ra.

| Vai trò | Tài khoản mẫu | Mật khẩu mẫu |
| --- | --- | --- |
| Quản trị | `admin` | `admin123` |
| Khách hàng | `user1` | `user123` |

### Tùy chọn thường dùng

```powershell
.\demo.cmd -CheckOnly
.\demo.cmd -NoBrowser
.\demo.cmd -XamppPath D:\xampp -Port 8081 -DbName vali_store_demo
```

`-CheckOnly` kiểm tra XAMPP và các extension PHP cần thiết. `-NoBrowser` chạy demo và in URL. Nếu MariaDB đã có mật khẩu hoặc dùng cổng khác, thêm các tham số `-DbUser`, `-DbPassword` và `-DbPort` phù hợp.

Để dừng Apache của dự án, chạy:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\stop-demo.ps1
```

MariaDB tiếp tục chạy cho các dự án khác. Có thể dừng dịch vụ này từ XAMPP Control Panel khi dùng xong.

## Cài thủ công bằng XAMPP

1. Chép dự án vào `C:\xampp\htdocs\vonam`, bật Apache với `mod_rewrite` và `AllowOverride All`, sau đó bật MySQL trong XAMPP. PHP cần các extension `mysqli`, `mbstring` và `fileinfo`.
2. Với CSDL mới, mở [http://localhost/vonam/setup.php](http://localhost/vonam/setup.php) và chọn **Bắt đầu cài đặt**. Bộ cài tạo 10 bảng, 8 sản phẩm và hai tài khoản mẫu.
3. Với CSDL đang sử dụng, cập nhật từ thư mục dự án bằng lệnh bên dưới. Kết nối mặc định dùng `localhost:3306`, database `vali_store` và tài khoản MySQL `root` với mật khẩu rỗng. Có thể cấu hình qua `VALI_DB_HOST`, `VALI_DB_PORT`, `VALI_DB_NAME`, `VALI_DB_USER` và `VALI_DB_PASSWORD`.
4. Mở [http://localhost/vonam/](http://localhost/vonam/). Cấp quyền ghi vào `public/assets/uploads/` nếu cần tải ảnh trong khu quản trị.

```powershell
& 'C:\xampp\php\php.exe' .\scripts\migrate.php
```

Chạy lại `.\demo.cmd` cũng áp dụng migration và giữ dữ liệu đã có. `database/seed.sql` cung cấp bảng nền và sản phẩm mẫu. `database/schema.sql` là dump cấu trúc đầy đủ sau migration, dùng với CSDL trống khi cần tham khảo hoặc dựng riêng cấu trúc.

## Thử thanh toán giả lập

Thêm sản phẩm vào giỏ rồi đặt hàng. Ở ô **Ghi chú đơn hàng**, để trống để thử kết quả thành công. Ghi đúng `thất bại` hoặc `that bai` để thử kết quả thất bại. Đơn thất bại được lưu ở trạng thái đã hủy, tồn kho giữ nguyên và khách có thể thử lại từ giỏ. Kết quả và ghi chú xuất hiện trong lịch sử đơn và khu quản trị.

## Cấu trúc mã nguồn

```text
public/                 Trang cửa hàng và điểm vào web
  admin/                Các trang quản trị
  assets/               CSS, JavaScript, thư viện và ảnh mẫu
app/                    Kết nối CSDL và xử lý dùng chung
  admin/                Khởi tạo quản trị và biểu mẫu sản phẩm dùng chung
  views/layout/         Các mẫu đầu trang, cuối trang và menu quản trị
database/
  seed.sql              Bảng nền và dữ liệu mẫu
  schema.sql            Dump cấu trúc đầy đủ
  migrations/           Các bước cập nhật cấu trúc và dữ liệu
scripts/                Cài đặt demo, cập nhật CSDL và dừng Apache
demo.cmd                Lệnh chạy nhanh trên Windows
```
