/* ===========================================================
   Bepmetay - FULL SEED (MariaDB-compatible) + Collation fix
   - Dùng cho XAMPP/MariaDB
   - Chuẩn hoá collation -> utf8mb4_unicode_ci để tránh lỗi LIKE
   - created_by = '1' (khớp AdminSeeder)
   - 10 loại × 20 sản phẩm + nhiều topping
   =========================================================== */

-- ===== 0) CHỌN DATABASE CỦA BẠN =====
-- Đổi nếu DB không phải 'bakery-store-api'
ALTER DATABASE `bakery_store`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `bakery_store`;

-- Ép kết nối dùng unicode_ci (tránh mix collation khi LIKE)
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET collation_connection = 'utf8mb4_unicode_ci';

-- ===== 0.1) CHUẨN HOÁ COLLATION CHO CÁC BẢNG CHÍNH (nếu đã có) =====
-- Nếu bảng chưa tồn tại, các lệnh dưới sẽ báo lỗi -> bỏ qua được
-- (Bạn có thể chạy từng cái nếu muốn)
ALTER TABLE categories        CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE products          CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE category_product  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE products_toppings CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ===========================================================
-- 1) DỌN DỮ LIỆU SEED CŨ (nếu có)
-- ===========================================================
-- Lưu ý: Chỉ xoá dữ liệu seed (created_by='1' hoặc 'seed')
SET FOREIGN_KEY_CHECKS=0;

DELETE pt FROM products_toppings pt
JOIN products p ON p.id = pt.product_id
WHERE p.created_by IN ('1','seed');

DELETE cp FROM category_product cp
JOIN products p ON p.id = cp.product_id
WHERE p.created_by IN ('1','seed');

DELETE FROM products WHERE created_by IN ('1','seed');

SET FOREIGN_KEY_CHECKS=1;

-- ===========================================================
-- 2) CATEGORIES - đảm bảo đủ 10 loại
-- ===========================================================
INSERT IGNORE INTO `categories`
(`id`,`created_at`,`updated_at`,`name`,`description`,`type`,`deleted_at`,`priority`,`show_for_customer`) VALUES
('23036a25-b572-11ef-8332-70a6cc37ddf9', NOW(), NOW(), 'Đồ trang trí đi kèm', NULL, 'Other', NULL, 0, 0),
('2a1dbe25-b549-11ef-8332-70a6cc37ddf9', NOW(), NOW(), 'Topping', 'Topping rau câu', 'Topping', NULL, 0, 0),
('2a1dbe77-b549-11ef-8332-70a6cc37ddf9', NOW(), NOW(), 'Rau câu mini', NULL, 'Food', NULL, 1, 1),
('9b577267-b54a-11ef-8332-70a6cc37ddf9', NOW(), NOW(), 'Rau câu hoa lá', NULL, 'Food', NULL, 1, 1),
('9b5774ed-b54a-11ef-8332-70a6cc37ddf9', NOW(), NOW(), 'Rau câu khổng lồ', NULL, 'Food', NULL, 1, 1),
('9b5774er-b54a-11ef-8332-70a6cc37ddf9', NOW(), NOW(), 'Rau câu sinh nhật', NULL, 'Food', NULL, 1, 1),
('9b5774ez-b54a-11ef-8332-70a6cc37ddf9', NOW(), NOW(), 'Rau câu lễ hội', NULL, 'Food', NULL, 1, 1),
('9b577593-b54a-11ef-8332-70a6cc37ddf9', NOW(), NOW(), 'Combo', NULL, 'Food', NULL, 1, 0),
('bngot-0000-0000-0000-000000000001', NOW(), NOW(), 'Bánh ngọt', NULL, 'Food', NULL, 1, 1),
('trasua-0000-0000-0000-000000000001', NOW(), NOW(), 'Trà sữa', NULL, 'Drink', NULL, 1, 1);

-- ===========================================================
-- 3) TOPPINGS (is_topping=1)
-- ===========================================================
INSERT IGNORE INTO `products`
(`id`,`created_at`,`updated_at`,`name`,`description`,`image`,`status`,`price`,`cost`,`up_m_price`,`up_l_price`,`is_topping`,`priority`,`created_by`,`deleted_at`) VALUES
('944034e8-dc0d-11ef-9219-5811227f7f82', NOW(), NOW(), 'Bánh flan', NULL, NULL, 'active', 5000,0,0,0,1,0,'1',NULL),
('944034e3-dc0d-11ef-9219-5811227f7f82', NOW(), NOW(), 'Hạt sen',   NULL, NULL, 'active', 4000,0,0,0,1,0,'1',NULL),
('944034e4-dc0d-11ef-9219-5811227f7f82', NOW(), NOW(), 'Nhãn',      NULL, NULL, 'active', 4000,0,0,0,1,0,'1',NULL),
('944034e5-dc0d-11ef-9219-5811227f7f82', NOW(), NOW(), 'Sợi dừa',   NULL, NULL, 'active', 3000,0,0,0,1,0,'1',NULL);

INSERT IGNORE INTO `products`
(`id`,`created_at`,`updated_at`,`name`,`description`,`image`,`status`,`price`,`cost`,`up_m_price`,`up_l_price`,`is_topping`,`priority`,`created_by`,`deleted_at`) VALUES
(UUID(), NOW(), NOW(), 'Trân châu đen',    NULL, NULL, 'active', 3000,0,0,0,1,0,'1',NULL),
(UUID(), NOW(), NOW(), 'Trân châu trắng',  NULL, NULL, 'active', 3000,0,0,0,1,0,'1',NULL),
(UUID(), NOW(), NOW(), 'Thạch trái cây',   NULL, NULL, 'active', 3000,0,0,0,1,0,'1',NULL),
(UUID(), NOW(), NOW(), 'Pudding trứng',    NULL, NULL, 'active', 5000,0,0,0,1,0,'1',NULL),
(UUID(), NOW(), NOW(), 'Phô mai viên',     NULL, NULL, 'active', 6000,0,0,0,1,0,'1',NULL),
(UUID(), NOW(), NOW(), 'Thạch matcha',     NULL, NULL, 'active', 3000,0,0,0,1,0,'1',NULL),
(UUID(), NOW(), NOW(), 'Thạch cà phê',     NULL, NULL, 'active', 3000,0,0,0,1,0,'1',NULL),
(UUID(), NOW(), NOW(), 'Đậu đỏ',           NULL, NULL, 'active', 4000,0,0,0,1,0,'1',NULL),
(UUID(), NOW(), NOW(), 'Thạch dừa',        NULL, NULL, 'active', 3000,0,0,0,1,0,'1',NULL),
(UUID(), NOW(), NOW(), 'Caramel',          NULL, NULL, 'active', 5000,0,0,0,1,0,'1',NULL);

-- Map tất cả topping vào category "Topping"
INSERT IGNORE INTO `category_product` (`category_id`,`product_id`,`created_at`,`updated_at`)
SELECT '2a1dbe25-b549-11ef-8332-70a6cc37ddf9', p.id, NOW(), NOW()
FROM products p
WHERE p.is_topping=1;

-- ===========================================================
-- 4) BẢNG TẠM: CATEGORY + GIÁ
-- ===========================================================
DROP TEMPORARY TABLE IF EXISTS tmp_categories;
CREATE TEMPORARY TABLE tmp_categories(
  category_id CHAR(36),
  category_name VARCHAR(255),
  is_rau_cau TINYINT(1),
  base_price INT,
  price_step INT
);

INSERT INTO tmp_categories VALUES
('2a1dbe77-b549-11ef-8332-70a6cc37ddf9','Rau câu mini',       1,  6000,  500),
('9b577267-b54a-11ef-8332-70a6cc37ddf9','Rau câu hoa lá',     1, 30000, 1000),
('9b5774ed-b54a-11ef-8332-70a6cc37ddf9','Rau câu khổng lồ',   1, 39000, 1000),
('9b5774er-b54a-11ef-8332-70a6cc37ddf9','Rau câu sinh nhật',  1, 90000, 2000),
('9b5774ez-b54a-11ef-8332-70a6cc37ddf9','Rau câu lễ hội',     1,120000, 3000),
('9b577593-b54a-11ef-8332-70a6cc37ddf9','Combo',              1, 75000, 2000),
('bngot-0000-0000-0000-000000000001',   'Bánh ngọt',          0, 25000, 1000),
('trasua-0000-0000-0000-000000000001',  'Trà sữa',            0, 28000, 1000),
('23036a25-b572-11ef-8332-70a6cc37ddf9','Đồ trang trí đi kèm',0, 15000,  500),
('9b5774ed-b54a-11ef-8332-70a6cc37ddf9','Rau câu khổng lồ XL',1, 45000, 1000); -- thêm 1 biến thể để đủ 10 nhóm sinh 20sp

-- ===========================================================
-- 5) BẢNG TẠM: FLAVORS + ẢNH
-- ===========================================================
DROP TEMPORARY TABLE IF EXISTS tmp_flavors;
CREATE TEMPORARY TABLE tmp_flavors(
  flavor VARCHAR(50),
  image VARCHAR(255),
  kind  VARCHAR(20)
);

INSERT INTO tmp_flavors VALUES
('dừa',        'Product/RauCauDua1.png','rc'),
('cà phê',     'Product/RauCauCafeDua.png','rc'),
('lá dứa',     'Product/RauCauCafeDua.png','rc'),
('hoa lá',     'Product/RauCauHoaLaDua.png','rc'),
('sơn thuỷ',   'Product/RauCauSonThuy2.png','rc'),
('ngẫu nhiên', 'Product/DaDang3.png','rc'),
('sô cô la',   NULL,'bngot'),
('phô mai',    NULL,'bngot'),
('matcha',     NULL,'bngot'),
('dâu',        NULL,'bngot'),
('truyền thống',NULL,'trasua'),
('trân châu',  NULL,'trasua'),
('oolong',     NULL,'trasua'),
('thạch trái cây',NULL,'trasua'),
('việt quất',  NULL,'any'),
('xoài',       NULL,'any');

-- ===========================================================
-- 6) BẢNG TẠM: SEQ 1..20 (thay cho CTE, tương thích MariaDB)
-- ===========================================================
DROP TEMPORARY TABLE IF EXISTS seq;
CREATE TEMPORARY TABLE seq(n INT PRIMARY KEY);
INSERT INTO seq(n) VALUES
(1),(2),(3),(4),(5),(6),(7),(8),(9),(10),
(11),(12),(13),(14),(15),(16),(17),(18),(19),(20);

-- ===========================================================
-- 7) TẠO 20 SẢN PHẨM/LOẠI
-- ===========================================================
INSERT INTO `products`
(`id`,`created_at`,`updated_at`,`name`,`description`,`image`,`status`,`price`,`cost`,`up_m_price`,`up_l_price`,`is_topping`,`priority`,`created_by`,`deleted_at`)
SELECT
  UUID(), NOW(), NOW(),
  CONCAT(tc.category_name,' - ',
         CASE
           WHEN tc.is_rau_cau=1 THEN (SELECT flavor FROM (SELECT flavor FROM tmp_flavors WHERE kind IN ('rc','any')) f1 ORDER BY RAND() LIMIT 1)
           WHEN tc.category_name='Bánh ngọt' THEN (SELECT flavor FROM (SELECT flavor FROM tmp_flavors WHERE kind IN ('bngot','any')) f2 ORDER BY RAND() LIMIT 1)
           WHEN tc.category_name='Trà sữa'   THEN (SELECT flavor FROM (SELECT flavor FROM tmp_flavors WHERE kind IN ('trasua','any')) f3 ORDER BY RAND() LIMIT 1)
           ELSE (SELECT flavor FROM (SELECT flavor FROM tmp_flavors WHERE kind='any') f4 ORDER BY RAND() LIMIT 1)
         END,
         ' #', LPAD(seq.n,2,'0')),
  NULL,
  CASE WHEN tc.is_rau_cau=1
       THEN (SELECT image FROM (SELECT image FROM tmp_flavors WHERE kind IN ('rc','any') AND image IS NOT NULL) f5 ORDER BY RAND() LIMIT 1)
       ELSE NULL END,
  'active',
  (tc.base_price + (seq.n-1)*tc.price_step),
  0,0,0,
  0,1,'1',NULL
FROM tmp_categories tc
JOIN seq ON 1=1;

-- Map sản phẩm vào category tương ứng
INSERT IGNORE INTO `category_product` (`category_id`,`product_id`,`created_at`,`updated_at`)
SELECT tc.category_id, p.id, NOW(), NOW()
FROM tmp_categories tc
JOIN products p
  ON p.created_by='1'
 AND p.is_topping=0
 AND p.name LIKE CONCAT(tc.category_name,'%');

-- ===========================================================
-- 8) GHÉP TOPPING (nhiều dữ liệu)
-- ===========================================================
DROP TEMPORARY TABLE IF EXISTS tmp_topping_id;
CREATE TEMPORARY TABLE tmp_topping_id(name VARCHAR(100), id CHAR(36));
INSERT INTO tmp_topping_id
SELECT name, id FROM products WHERE is_topping=1;

-- Dừa
INSERT IGNORE INTO products_toppings(product_id,topping_id,extra_price,created_at,updated_at)
SELECT p.id, (SELECT id FROM tmp_topping_id WHERE name='Sợi dừa' LIMIT 1), 3000, NOW(), NOW()
FROM products p WHERE p.created_by='1' AND p.is_topping=0 AND p.name LIKE '%dừa%';

INSERT IGNORE INTO products_toppings
SELECT p.id, (SELECT id FROM tmp_topping_id WHERE name='Thạch dừa' LIMIT 1), 3000, NOW(), NOW()
FROM products p WHERE p.created_by='1' AND p.is_topping=0 AND p.name LIKE '%dừa%';

-- Cà phê
INSERT IGNORE INTO products_toppings
SELECT p.id, (SELECT id FROM tmp_topping_id WHERE name='Bánh flan' LIMIT 1), 5000, NOW(), NOW()
FROM products p WHERE p.created_by='1' AND p.is_topping=0 AND p.name LIKE '%cà phê%';

INSERT IGNORE INTO products_toppings
SELECT p.id, (SELECT id FROM tmp_topping_id WHERE name='Thạch cà phê' LIMIT 1), 3000, NOW(), NOW()
FROM products p WHERE p.created_by='1' AND p.is_topping=0 AND p.name LIKE '%cà phê%';

-- Lá dứa
INSERT IGNORE INTO products_toppings
SELECT p.id, (SELECT id FROM tmp_topping_id WHERE name='Hạt sen' LIMIT 1), 4000, NOW(), NOW()
FROM products p WHERE p.created_by='1' AND p.is_topping=0 AND p.name LIKE '%lá dứa%';

INSERT IGNORE INTO products_toppings
SELECT p.id, (SELECT id FROM tmp_topping_id WHERE name='Thạch matcha' LIMIT 1), 3000, NOW(), NOW()
FROM products p WHERE p.created_by='1' AND p.is_topping=0 AND p.name LIKE '%lá dứa%';

-- Hoa lá / ngẫu nhiên / sơn thuỷ / trái cây
INSERT IGNORE INTO products_toppings
SELECT p.id, (SELECT id FROM tmp_topping_id WHERE name='Nhãn' LIMIT 1), 4000, NOW(), NOW()
FROM products p 
WHERE p.created_by='1' AND p.is_topping=0 
  AND (p.name LIKE '%hoa lá%' OR p.name LIKE '%ngẫu nhiên%' OR p.name LIKE '%sơn thuỷ%' OR p.name LIKE '%trái cây%');

INSERT IGNORE INTO products_toppings
SELECT p.id, (SELECT id FROM tmp_topping_id WHERE name='Thạch trái cây' LIMIT 1), 3000, NOW(), NOW()
FROM products p 
WHERE p.created_by='1' AND p.is_topping=0 
  AND (p.name LIKE '%hoa lá%' OR p.name LIKE '%ngẫu nhiên%' OR p.name LIKE '%sơn thuỷ%' OR p.name LIKE '%trái cây%');

-- Bánh ngọt
INSERT IGNORE INTO products_toppings
SELECT p.id, (SELECT id FROM tmp_topping_id WHERE name='Phô mai viên' LIMIT 1), 6000, NOW(), NOW()
FROM products p
JOIN category_product cp ON cp.product_id=p.id AND cp.category_id='bngot-0000-0000-0000-000000000001'
WHERE p.created_by='1' AND p.is_topping=0;

INSERT IGNORE INTO products_toppings
SELECT p.id, (SELECT id FROM tmp_topping_id WHERE name='Caramel' LIMIT 1), 5000, NOW(), NOW()
FROM products p
JOIN category_product cp ON cp.product_id=p.id AND cp.category_id='bngot-0000-0000-0000-000000000001'
WHERE p.created_by='1' AND p.is_topping=0;

INSERT IGNORE INTO products_toppings
SELECT p.id, (SELECT id FROM tmp_topping_id WHERE name='Pudding trứng' LIMIT 1), 5000, NOW(), NOW()
FROM products p
JOIN category_product cp ON cp.product_id=p.id AND cp.category_id='bngot-0000-0000-0000-000000000001'
WHERE p.created_by='1' AND p.is_topping=0;

-- Trà sữa
INSERT IGNORE INTO products_toppings
SELECT p.id, (SELECT id FROM tmp_topping_id WHERE name='Trân châu đen' LIMIT 1), 4000, NOW(), NOW()
FROM products p
JOIN category_product cp ON cp.product_id=p.id AND cp.category_id='trasua-0000-0000-0000-000000000001'
WHERE p.created_by='1' AND p.is_topping=0;

INSERT IGNORE INTO products_toppings
SELECT p.id, (SELECT id FROM tmp_topping_id WHERE name='Trân châu trắng' LIMIT 1), 4000, NOW(), NOW()
FROM products p
JOIN category_product cp ON cp.product_id=p.id AND cp.category_id='trasua-0000-0000-0000-000000000001'
WHERE p.created_by='1' AND p.is_topping=0;

INSERT IGNORE INTO products_toppings
SELECT p.id, (SELECT id FROM tmp_topping_id WHERE name='Thạch trái cây' LIMIT 1), 3000, NOW(), NOW()
FROM products p
JOIN category_product cp ON cp.product_id=p.id AND cp.category_id='trasua-0000-0000-0000-000000000001'
WHERE p.created_by='1' AND p.is_topping=0;

INSERT IGNORE INTO products_toppings
SELECT p.id, (SELECT id FROM tmp_topping_id WHERE name='Pudding trứng' LIMIT 1), 5000, NOW(), NOW()
FROM products p
JOIN category_product cp ON cp.product_id=p.id AND cp.category_id='trasua-0000-0000-0000-000000000001'
WHERE p.created_by='1' AND p.is_topping=0;

-- ===========================================================
-- 9) THỐNG KÊ NHANH
-- ===========================================================
SELECT COUNT(*) AS total_seed_products
FROM products WHERE created_by='1' AND is_topping=0;

SELECT COUNT(*) AS total_product_toppings_seed
FROM products_toppings pt
JOIN products p ON p.id=pt.product_id
WHERE p.created_by='1';
