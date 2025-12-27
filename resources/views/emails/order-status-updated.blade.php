<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cập nhật trạng thái đơn hàng</title>
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
            background-color: #2196F3;
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
            border-left: 4px solid #2196F3;
        }
        .info-row {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .info-label {
            font-weight: bold;
            color: #555;
        }
        .status-change {
            background-color: #fff3cd;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            border: 1px solid #ffc107;
        }
        .status-old {
            color: #999;
            text-decoration: line-through;
        }
        .status-new {
            color: #4CAF50;
            font-weight: bold;
            font-size: 18px;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #777;
            font-size: 12px;
        }
        .highlight {
            color: #2196F3;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📦 Cập nhật đơn hàng</h1>
    </div>
    
    <div class="content">
        <p>Kính gửi {{ $order->receiver_name }},</p>
        <p>Đơn hàng của bạn đã được cập nhật trạng thái:</p>
        
        <div class="status-change">
            <div style="margin-bottom: 10px;">
                <strong>Trạng thái cũ:</strong> 
                <span class="status-old">{{ $oldStatus }}</span>
            </div>
            <div>
                <strong>Trạng thái mới:</strong> 
                <span class="status-new">{{ $newStatus }}</span>
            </div>
        </div>
        
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
                <span class="info-label">Địa chỉ giao hàng:</span>
                {{ $order->receiver_address }}
            </div>
            
            <div class="info-row">
                <span class="info-label">Tổng tiền:</span>
                <span class="highlight">{{ number_format($order->order_total, 0, ',', '.') }} VNĐ</span>
            </div>
            
            @if($note)
            <div class="info-row">
                <span class="info-label">Ghi chú từ cửa hàng:</span>
                {{ $note }}
            </div>
            @endif
        </div>
        
        @if($newStatus === 'Delivering')
        <p style="color: #ff9800; font-weight: bold;">
            ⚡ Đơn hàng của bạn đang được giao! Vui lòng chú ý điện thoại.
        </p>
        @elseif($newStatus === 'Completed')
        <p style="color: #4CAF50; font-weight: bold;">
            ✅ Đơn hàng đã hoàn thành! Cảm ơn bạn đã mua hàng.
        </p>
        @elseif($newStatus === 'Cancelled')
        <p style="color: #f44336; font-weight: bold;">
            ❌ Đơn hàng đã bị hủy. Nếu có thắc mắc, vui lòng liên hệ với chúng tôi.
        </p>
        @endif
        
        <p>Nếu bạn có bất kỳ thắc mắc nào, vui lòng liên hệ với chúng tôi.</p>
    </div>
    
    <div class="footer">
        <p>Email này được gửi tự động từ hệ thống Bakery Store</p>
        <p>&copy; {{ date('Y') }} Bakery Store. All rights reserved.</p>
    </div>
</body>
</html>
