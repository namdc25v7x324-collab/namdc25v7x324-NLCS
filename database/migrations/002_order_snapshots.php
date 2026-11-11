<?php
foreach (['product_name'=>'VARCHAR(200) NULL', 'product_category'=>'VARCHAR(100) NULL'] as $column=>$definition) {
    if (!$conn->query("SHOW COLUMNS FROM order_details LIKE '$column'")->num_rows) {
        $conn->query("ALTER TABLE order_details ADD $column $definition");
    }
}
// Existing lines are backfilled once; original names cannot be reconstructed after previous edits.
$conn->query("UPDATE order_details od LEFT JOIN products p ON p.id=od.product_id SET od.product_name=COALESCE(p.name,'[Sản phẩm đã gỡ]'), od.product_category=COALESCE(p.category,'') WHERE od.product_name IS NULL");
$indexes=[
    ['products','idx_products_active_created','is_active,created_at,id'],
    ['products','idx_products_category','is_active,category'],
    ['orders','idx_orders_user_created','user_id,created_at,id'],
    ['orders','idx_orders_status_created','status,created_at,id'],
    ['contact_messages','idx_messages_created','created_at,id']
];
foreach ($indexes as [$table,$name,$columns]) {
    if (!$conn->query("SHOW INDEX FROM $table WHERE Key_name='$name'")->num_rows) $conn->query("ALTER TABLE $table ADD INDEX $name ($columns)");
}
echo "Optimization migration complete; snapshots and indexes ready.\n";
