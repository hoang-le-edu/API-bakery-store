INSERT INTO `categories` (`id`, `created_at`, `updated_at`, `name`, `description`, `type`, `deleted_at`, `priority`, `show_for_customer`) VALUES
('23036a25-b572-11ef-8332-70a6cc37ddf9', NOW(), NOW(), 'Đồ trang trí đi kèm', NULL, 'Other', NULL, 0, 0),
('2a1dbe25-b549-11ef-8332-70a6cc37ddf9', NOW(), NOW(), 'Topping', 'Topping rau câu', 'Topping', NULL, 0, 0),
('2a1dbe77-b549-11ef-8332-70a6cc37ddf9', NOW(), NOW(), 'Rau câu mini', NULL, 'Food', NULL, 1, 1),
('9b577267-b54a-11ef-8332-70a6cc37ddf9', NOW(), NOW(), 'Rau câu hoa lá', NULL, 'Food', NULL, 1, 1),
('9b5774ed-b54a-11ef-8332-70a6cc37ddf9', NOW(), NOW(), 'Rau câu khổng lồ', NULL, 'Food', NULL, 1, 1),
('9b5774er-b54a-11ef-8332-70a6cc37ddf9', NOW(), NOW(), 'Rau câu sinh nhật', NULL, 'Food', NULL, 1, 1),
('9b5774ez-b54a-11ef-8332-70a6cc37ddf9', NOW(), NOW(), 'Rau câu lễ hội', NULL, 'Food', NULL, 1, 1),
('9b577593-b54a-11ef-8332-70a6cc37ddf9', NOW(), NOW(), 'Combo', NULL, 'Food', NULL, 1, 0);

INSERT INTO `products` (`id`, `created_at`, `updated_at`, `name`, `description`, `image`, `status`, `price`, `cost`, `up_m_price`, `up_l_price`, `is_topping`, `priority`, `created_by`, `deleted_at`) VALUES
('944034e8-dc0d-11ef-9219-5811227f7f82', NOW(), NOW(), 'Bánh plan', NULL, NULL, 'active', 0, 0, 0, 0, 1, 0, '1', NULL),
('944034e3-dc0d-11ef-9219-5811227f7f82', NOW(), NOW(), 'Hạt sen', NULL, NULL, 'active', 0, 0, 0, 0, 1, 0, '1', NULL),
('944034e4-dc0d-11ef-9219-5811227f7f82', NOW(), NOW(), 'Nhãn', NULL, NULL, 'active', 0, 0, 0, 0, 1, 0, '1', NULL),
('944034e5-dc0d-11ef-9219-5811227f7f82', NOW(), NOW(), 'Sợi dừa', NULL, NULL, 'active', 0, 0, 0, 0, 1, 0, '1', NULL),

('94404241-dc0d-11ef-9219-5811227f7f82', NOW(), NOW(), 'Rau câu dừa mini 100gr', NULL, 'Product/RauCauDua1.png', 'active', 6000, 0, 0, 0, 0, 1, '1', NULL),
('9440428e-dc0d-11ef-9219-5811227f7f82', NOW(), NOW(), 'Rau câu cà phê mini 100gr', NULL, 'Product/RauCauCafeDua.png', 'active', 6000, 0, 0, 0, 0, 1, '1', NULL),
('944042e1-dc0d-11ef-9219-5811227f7f82', NOW(), NOW(), 'Rau câu lá dứa mini 100gr', NULL, 'Product/RauCauCafeDua.png', 'active', 6000, 0, 0, 0, 0, 1, '1', NULL),

('94404318-dc0d-11ef-9219-5811227f7f82', NOW(), NOW(), 'Rau câu dừa hoa lá', NULL, 'Product/RauCauHoaLaDua.png', 'active', 30000, 0, 25000, 0, 0, 1, '1', NULL),
('9440434d-dc0d-11ef-9219-5811227f7f82', NOW(), NOW(), 'Rau câu cà phê hoa lá', NULL, 'Product/RauCauHoaLaCafe.png', 'active', 30000, 0, 25000, 0, 0, 1, '1', NULL),
('9440438a-dc0d-11ef-9219-5811227f7f82', NOW(), NOW(), 'Rau câu lá dứa hoa lá', NULL, 'Product/RauCauHoaLaCafeDua1.png', 'active', 30000, 0, 25000, 0, 0, 1, '1', NULL),
('9440438b-dc0d-11ef-9219-5811227f7f82', NOW(), NOW(), 'Rau câu ngẫu nhiên hoa lá', NULL, 'Product/DaDang3.png', 'active', 30000, 0, 25000, 0, 0, 1, '1', NULL),

('94404242-dc0d-11ef-9219-5811227f7f82', NOW(), NOW(), 'Rau câu dừa khổng lồ 600gr', NULL, '', 'active', 39000, 0, 0, 0, 0, 1, '1', NULL),
('94404284-dc0d-11ef-9219-5811227f7f82', NOW(), NOW(), 'Rau câu cà phê khổng lồ 600gr', NULL, '', 'active', 39000, 0, 0, 0, 0, 1, '1', NULL),
('944042e4-dc0d-11ef-9219-5811227f7f82', NOW(), NOW(), 'Rau câu lá dứa khổng lồ 600gr', NULL, '', 'active', 39000, 0, 0, 0, 0, 1, '1', NULL),
('944042e5-dc0d-11ef-9219-5811227f7f82', NOW(), NOW(), 'Rau câu sơn thuỷ khổng lồ 600gr', NULL, 'Product/RauCauSonThuy2.png', 'active', 43000, 0, 0, 0, 0, 1, '1', NULL);

INSERT INTO `category_product` (`category_id`, `product_id`, `created_at`, `updated_at`) VALUES
('2a1dbe77-b549-11ef-8332-70a6cc37ddf9', '94404241-dc0d-11ef-9219-5811227f7f82', NOW(), NOW()), 
('2a1dbe77-b549-11ef-8332-70a6cc37ddf9', '9440428e-dc0d-11ef-9219-5811227f7f82', NOW(), NOW()), 
('2a1dbe77-b549-11ef-8332-70a6cc37ddf9', '944042e1-dc0d-11ef-9219-5811227f7f82', NOW(), NOW()), 

('9b577267-b54a-11ef-8332-70a6cc37ddf9', '94404318-dc0d-11ef-9219-5811227f7f82', NOW(), NOW()), 
('9b577267-b54a-11ef-8332-70a6cc37ddf9', '9440434d-dc0d-11ef-9219-5811227f7f82', NOW(), NOW()), 
('9b577267-b54a-11ef-8332-70a6cc37ddf9', '9440438a-dc0d-11ef-9219-5811227f7f82', NOW(), NOW()), 
('9b577267-b54a-11ef-8332-70a6cc37ddf9', '9440438b-dc0d-11ef-9219-5811227f7f82', NOW(), NOW()), 

('9b5774ed-b54a-11ef-8332-70a6cc37ddf9', '94404242-dc0d-11ef-9219-5811227f7f82', NOW(), NOW()), 
('9b5774ed-b54a-11ef-8332-70a6cc37ddf9', '94404284-dc0d-11ef-9219-5811227f7f82', NOW(), NOW()), 
('9b5774ed-b54a-11ef-8332-70a6cc37ddf9', '944042e4-dc0d-11ef-9219-5811227f7f82', NOW(), NOW()),
('9b5774ed-b54a-11ef-8332-70a6cc37ddf9', '944042e5-dc0d-11ef-9219-5811227f7f82', NOW(), NOW()),

('2a1dbe25-b549-11ef-8332-70a6cc37ddf9', '944034e8-dc0d-11ef-9219-5811227f7f82', NOW(), NOW()), 
('2a1dbe25-b549-11ef-8332-70a6cc37ddf9', '944034e3-dc0d-11ef-9219-5811227f7f82', NOW(), NOW()), 
('2a1dbe25-b549-11ef-8332-70a6cc37ddf9', '944034e4-dc0d-11ef-9219-5811227f7f82', NOW(), NOW()), 
('2a1dbe25-b549-11ef-8332-70a6cc37ddf9', '944034e5-dc0d-11ef-9219-5811227f7f82', NOW(), NOW());

INSERT INTO `products_toppings` (`product_id`, `topping_id`, `extra_price`, `created_at`, `updated_at`) VALUES
('94404241-dc0d-11ef-9219-5811227f7f82', '944034e5-dc0d-11ef-9219-5811227f7f82', 1000, NOW(), NOW()), 
('94404241-dc0d-11ef-9219-5811227f7f82', '944034e3-dc0d-11ef-9219-5811227f7f82', 1000, NOW(), NOW()), 
('94404241-dc0d-11ef-9219-5811227f7f82', '944034e4-dc0d-11ef-9219-5811227f7f82', 2000, NOW(), NOW()), 

('9440428e-dc0d-11ef-9219-5811227f7f82', '944034e8-dc0d-11ef-9219-5811227f7f82', 1000, NOW(), NOW()), 
('944042e1-dc0d-11ef-9219-5811227f7f82', '944034e8-dc0d-11ef-9219-5811227f7f82', 1000, NOW(), NOW()), 

('94404242-dc0d-11ef-9219-5811227f7f82', '944034e5-dc0d-11ef-9219-5811227f7f82', 3000, NOW(), NOW()), 
('94404242-dc0d-11ef-9219-5811227f7f82', '944034e3-dc0d-11ef-9219-5811227f7f82', 3000, NOW(), NOW()), 
('94404242-dc0d-11ef-9219-5811227f7f82', '944034e4-dc0d-11ef-9219-5811227f7f82', 5000, NOW(), NOW()), 

('94404284-dc0d-11ef-9219-5811227f7f82', '944034e8-dc0d-11ef-9219-5811227f7f82', 3000, NOW(), NOW()), 
('944042e4-dc0d-11ef-9219-5811227f7f82', '944034e8-dc0d-11ef-9219-5811227f7f82', 3000, NOW(), NOW()), 
('944042e5-dc0d-11ef-9219-5811227f7f82', '944034e8-dc0d-11ef-9219-5811227f7f82', 3000, NOW(), NOW());


