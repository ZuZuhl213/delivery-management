# Delivery Management

Hệ thống quản lý giao hàng cho doanh nghiệp, hỗ trợ quản lý nhân viên, phương tiện, đơn hàng và báo cáo thống kê.

## Tính năng

- Quản lý nhân viên (thêm, sửa, xóa, tìm kiếm)
- Quản lý phương tiện vận chuyển
- Quản lý đơn hàng, phân công nhân viên và phương tiện
- Thống kê, báo cáo theo nhân viên, trạng thái đơn hàng, doanh thu theo tháng
- Phân quyền người dùng (Admin, Nhân viên)
- Đăng nhập, đăng xuất an toàn

## Cấu trúc thư mục

```
/admin/         # Trang quản trị dành cho admin
/auth/          # Đăng nhập, đăng ký, đổi mật khẩu
/employee/      # Trang dành cho nhân viên
/includes/      # Các file hàm, header, sidebar, footer
/config/        # File cấu hình, kết nối database
/uploads/       # Thư mục lưu file upload (nếu có)
```

## Cài đặt

1. **Clone dự án:**
   ```bash
   git clone https://github.com/yourusername/delivery-management.git
   cd delivery-management
   ```

2. **Cài đặt database:**
   - Tạo database MySQL và import file `database.sql` (nếu có).
   - Cập nhật thông tin kết nối trong `/config/database.php`.

3. **Cài đặt Composer (nếu sử dụng):**
   ```bash
   composer install
   ```

4. **Cấu hình quyền thư mục upload (nếu có):**
   ```bash
   chmod -R 755 uploads/
   ```

5. **Chạy ứng dụng:**
   - Đưa source code lên server có PHP & MySQL.
   - Truy cập `http://localhost/delivery-management/` trên trình duyệt
## Yêu cầu hệ thống

- PHP >= 7.4
- MySQL/MariaDB
- Apache/Nginx

