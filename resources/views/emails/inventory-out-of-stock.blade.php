<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px; }
        .header { background-color: #ef4444; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { padding: 20px; }
        .footer { text-align: center; font-size: 12px; color: #777; margin-top: 20px; }
        .inventory-details { background-color: #f8fafc; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .button { display: inline-block; padding: 12px 25px; background-color: #ef4444; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Out of Stock Alert!</h1>
        </div>
        <div class="content">
            <p>Hello {{ $user->name }},</p>
            <p>This is an automated alert to inform you that a voucher inventory has run out of stock.</p>
            
            <div class="inventory-details">
                <p><strong>SKU ID:</strong> {{ $inventory->sku_id }}</p>
                <p><strong>Brand:</strong> {{ $inventory->brand_name }}</p>
                <p><strong>Voucher Type:</strong> {{ $inventory->voucher_type }}</p>
                <p><strong>Variant:</strong> {{ $inventory->voucher_variant }}</p>
                <p><strong>Current Quantity:</strong> <span style="color: #ef4444; font-weight: bold;">0</span></p>
            </div>

            <p>Please restock this voucher to ensure continued sales.</p>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="{{ route('inventory.edit', $inventory->id) }}" class="button" style="color: white;">Restock Inventory</a>
            </div>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} UniCou Voucher Management System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
