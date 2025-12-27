<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn hàng mới</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border: 1px solid #ddd;
        }
        .order-info {
            background-color: white;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #4CAF50;
        }
        .info-row {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .info-label {
            font-weight: bold;
            color: #555;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #777;
            font-size: 12px;
        }
        .highlight {
            color: #4CAF50;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎉 Đơn hàng mới!</h1>
    </div>
    
    <div class="content">
        <p>Xin chào Admin,</p>
        <p>Có một đơn hàng mới vừa được tạo trên hệ thống. Vui lòng kiểm tra và xử lý:</p>
        
        <div class="order-info">
            <div class="info-row">
                <span class="info-label">Mã đơn hàng:</span>
                <span class="highlight">{{ $order->order_number }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Người nhận:</span>
                {{ $order->receiver_name }}
            </div>
            
            <div class="info-row">
                <span class="info-label">Số điện thoại:</span>
                {{ $order->receiver_phone }}
            </div>
            
            <div class="info-row">
                <span class="info-label">Địa chỉ:</span>
                {{ $order->receiver_address }}
            </div>
            
            <div class="info-row">
                <span class="info-label">Phương thức thanh toán:</span>
                {{ $order->payment_method }}
            </div>
            
            <div class="info-row">
                <span class="info-label">Tổng tiền:</span>
                <span class="highlight">{{ number_format($order->order_total, 0, ',', '.') }} VNĐ</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Trạng thái:</span>
                <span style="color: #ff9800;">{{ $order->order_status }}</span>
            </div>
            
            @if($order->note)
            <div class="info-row">
                <span class="info-label">Ghi chú:</span>
                {{ $order->note }}
            </div>
            @endif
            
            <div class="info-row">
                <span class="info-label">Thời gian đặt:</span>
                {{ $order->created_at->format('d/m/Y H:i') }}
            </div>
        </div>
        
        <p>Vui lòng đăng nhập vào hệ thống để xem chi tiết và xử lý đơn hàng.</p>
    </div>
    
    <div class="footer">
        <p>Email này được gửi tự động từ hệ thống Bakery Store</p>
        <p>&copy; {{ date('Y') }} Bakery Store. All rights reserved.</p>
    </div>
</body>
</html>
