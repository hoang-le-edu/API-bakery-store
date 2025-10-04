/* =========================
   0) THIẾT LẬP CHUNG
   ========================= */
SET NAMES utf8mb4;
SET time_zone = '+07:00';

/* =========================
   1) BỔ SUNG/HOÀN THIỆN CATEGORIES
   - Bạn đã có 8 category; ta thêm cho đủ 10 "loại bán hàng"
   - Giữ nguyên các ID bạn đã dùng để còn gắn map
   ========================= */
-- ĐÃ CÓ (tham chiếu lại cho dễ đọc)
-- Topping
INSERT IGNORE INTO categories(id,created_at,updated_at,name,description,type,deleted_at,priority,show_for_customer) VALUES
('2a1dbe25-b549-11ef-8332-70a6cc37ddf9', NOW(), NOW(), 'Topping', 'Topping rau câu', 'Topping', NULL, 0, 0);
-- Rau câu mini
INSERT IGNORE INTO categories VALUES
('2a1dbe77-b549-11ef-8332-70a6cc37ddf9', NOW(), NOW(), 'Rau câu mini', NULL, 'Food', NULL, 1, 1);
-- Rau câu hoa lá
INSERT IGNORE INTO categories VALUES
('9b577267-b54a-11ef-8332-70a6cc37ddf9', NOW(), NOW(), 'Rau câu hoa lá', NULL, 'Food', NULL, 1, 1);
-- Rau câu khổng lồ
INSERT IGNORE INTO categories VALUES
('9b5774ed-b54a-11ef-8332-70a6cc37ddf9', NOW(), NOW(), 'Rau câu khổng lồ', NULL, 'Food', NULL, 1, 1);
-- Rau câu sinh nhật
INSERT IGNORE INTO categories VALUES
('9b5774er-b54a-11ef-8332-70a6cc37ddf9', NOW(), NOW(), 'Rau câu sinh nhật', NULL, 'Food', NULL, 1, 1);
-- Rau câu lễ hội
INSERT IGNORE INTO categories VALUES
('9b5774ez-b54a-11ef-8332-70a6cc37ddf9', NOW(), NOW(), 'Rau câu lễ hội', NULL, 'Food', NULL, 1, 1);
-- Combo
INSERT IGNORE INTO categories VALUES
('9b577593-b54a-11ef-8332-70a6cc37ddf9', NOW(), NOW(), 'Combo', NULL, 'Food', NULL, 1, 0);
-- Đồ trang trí đi kèm (Other)
INSERT IGNORE INTO categories VALUES
('23036a25-b572-11ef-8332-70a6cc37ddf9', NOW(), NOW(), 'Đồ trang trí đi kèm', NULL, 'Other', NULL, 0, 0);

-- THÊM 3 CATEGORY BÁN HÀNG MỚI để đủ 10 loại:
INSERT IGNORE INTO categories VALUES
('bngot-0000-0000-0000-000000000001', NOW(), NOW(), 'Bánh ngọt', NULL, 'Food', NULL, 1, 1),
('trasua-0000-0000-0000-000000000001', NOW(), NOW(), 'Trà sữa', NULL, 'Drink', NULL, 1, 1),
('rc-trai-cay-00000000000000000001', NOW(), NOW(), 'Rau câu trái cây', NULL, 'Food', NULL, 1, 1);

/* =========================
   2) TẠO NHIỀU SẢN PHẨM TOPPING (is_topping=1)
   - Kể cả 4 topping bạn đã có (flan/hạt sen/nhãn/sợi dừa) + bổ sung nhiều topping phổ biến
   ========================= */
-- đảm bảo 4 topping sẵn có (nếu chưa có)
INSERT IGNORE INTO products(id,created_at,updated_at,name,description,image,status,price,cost,up_m_price,up_l_price,is_topping,priority,created_by,deleted_at) VALUES
('944034e8-dc0d-11ef-9219-5811227f7f82', NOW(), NOW(), 'Bánh flan', NULL, NULL, 'active', 5000,0,0,0,1,0,'seed',NULL),
('944034e3-dc0d-11ef-9219-5811227f7f82', NOW(), NOW(), 'Hạt sen', NULL, NULL, 'active', 4000,0,0,0,1,0,'seed',NULL),
('944034e4-dc0d-11ef-9219-5811227f7f82', NOW(), NOW(), 'Nhãn', NULL, NULL, 'active', 4000,0,0,0,1,0,'seed',NULL),
('944034e5-dc0d-11ef-9219-5811227f7f82', NOW(), NOW(), 'Sợi dừa', NULL, NULL, 'active', 3000,0,0,0,1,0,'seed',NULL);

-- thêm nhiều topping khác
INSERT IGNORE INTO products(id,created_at,updated_at,name,description,image,status,price,cost,up_m_price,up_l_price,is_topping,priority,created_by,deleted_at) VALUES
(UUID(), NOW(), NOW(), 'Trân châu đen', NULL, NULL, 'active', 3000,0,0,0,1,0,'seed',NULL),
(UUID(), NOW(), NOW(), 'Trân châu trắng', NULL, NULL, 'active', 3000,0,0,0,1,0,'seed',NULL),
(UUID(), NOW(), NOW(), 'Thạch trái cây', NULL, NULL, 'active', 3000,0,0,0,1,0,'seed',NULL),
(UUID(), NOW(), NOW(), 'Pudding trứng', NULL, NULL, 'active', 5000,0,0,0,1,0,'seed',NULL),
(UUID(), NOW(), NOW(), 'Phô mai viên', NULL, NULL, 'active', 6000,0,0,0,1,0,'seed',NULL),
(UUID(), NOW(), NOW(), 'Thạch matcha', NULL, NULL, 'active', 3000,0,0,0,1,0,'seed',NULL),
(UUID(), NOW(), NOW(), 'Thạch cà phê', NULL, NULL, 'active', 3000,0,0,0,1,0,'seed',NULL),
(UUID(), NOW(), NOW(), 'Đậu đỏ', NULL, NULL, 'active', 4000,0,0,0,1,0,'seed',NULL),
(UUID(), NOW(), NOW(), 'Thạch dừa', NULL, NULL, 'active', 3000,0,0,0,1,0,'seed',NULL),
(UUID(), NOW(), NOW(), 'Caramel', NULL, NULL, 'active', 5000,0,0,0,1,0,'seed',NULL);

-- gán toàn bộ topping vào category "Topping" (nếu chưa có)
INSERT IGNORE INTO category_product(category_id, product_id, created_at, updated_at)
SELECT '2a1dbe25-b549-11ef-8332-70a6cc37ddf9', p.id, NOW(), NOW()
FROM products p WHERE p.is_topping=1;

/* =========================
   3) BẢNG TẠM: DANH SÁCH CATEGORY CẦN TẠO 20 SẢN PHẨM
   ========================= */
DROP TEMPORARY TABLE IF EXISTS tmp_categories;
CREATE TEMPORARY TABLE tmp_categories(
  category_id CHAR(36),
  category_name VARCHAR(255),
  is_rau_cau TINYINT(1),
  base_price INT,
  price_step INT
);

INSERT INTO tmp_categories VALUES
('2a1dbe77-b549-11ef-8332-70a6cc37ddf9','Rau câu mini',        1, 6000,  500),
('9b577267-b54a-11ef-8332-70a6cc37ddf9','Rau câu hoa lá',      1, 30000, 1000),
('9b5774ed-b54a-11ef-8332-70a6cc37ddf9','Rau câu khổng lồ',    1, 39000, 1000),
('9b5774er-b54a-11ef-8332-70a6cc37ddf9','Rau câu sinh nhật',   1, 90000, 2000),
('9b5774ez-b54a-11ef-8332-70a6cc37ddf9','Rau câu lễ hội',      1, 120000,3000),
('9b577593-b54a-11ef-8332-70a6cc37ddf9','Combo',               1, 75000, 2000),
('rc-trai-cay-00000000000000000001',    'Rau câu trái cây',    1, 35000, 1000),
('bngot-0000-0000-0000-000000000001',   'Bánh ngọt',           0, 25000, 1000),
('trasua-0000-0000-0000-000000000001',  'Trà sữa',             0, 28000, 1000),
('23036a25-b572-11ef-8332-70a6cc37ddf9','Đồ trang trí đi kèm', 0, 15000, 500); -- dùng làm loại bán hàng nhẹ

/* =========================
   4) BẢNG TẠM: HƯƠNG/VỊ (FLAVORS) + ẢNH SẴN CÓ (cho rau câu)
   ========================= */
DROP TEMPORARY TABLE IF EXISTS tmp_flavors;
CREATE TEMPORARY TABLE tmp_flavors(
  flavor VARCHAR(50),
  image VARCHAR(255),
  kind VARCHAR(20) -- 'rc' (rau cau), 'bngot', 'trasua', 'any'
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

/* =========================
   5) SINH DÃY SỐ 1..20 BẰNG CTE
   ========================= */
WITH RECURSIVE seq AS (
  SELECT 1 AS n
  UNION ALL
  SELECT n+1 FROM seq WHERE n < 20
)
-- 6) TẠO 20 SẢN PHẨM CHO MỖI CATEGORY
INSERT INTO products
(id,created_at,updated_at,name,description,image,status,price,cost,up_m_price,up_l_price,is_topping,priority,created_by,deleted_at)
SELECT
  UUID(), NOW(), NOW(),
  CONCAT(tc.category_name,' - ',
         (CASE
            WHEN tc.is_rau_cau=1 THEN
               (SELECT f.flavor FROM tmp_flavors f WHERE f.kind IN ('rc','any') ORDER BY RAND() LIMIT 1)
            WHEN tc.category_name='Bánh ngọt' THEN
               (SELECT f.flavor FROM tmp_flavors f WHERE f.kind IN ('bngot','any') ORDER BY RAND() LIMIT 1)
            WHEN tc.category_name='Trà sữa' THEN
               (SELECT f.flavor FROM tmp_flavors f WHERE f.kind IN ('trasua','any') ORDER BY RAND() LIMIT 1)
            ELSE (SELECT f.flavor FROM tmp_flavors f WHERE f.kind='any' ORDER BY RAND() LIMIT 1)
          END),
         ' #', LPAD(seq.n,2,'0')) AS name,
  NULL AS description,
  (CASE
     WHEN tc.is_rau_cau=1
       THEN (SELECT f.image
             FROM tmp_flavors f
             WHERE f.kind IN ('rc','any') AND f.image IS NOT NULL
             ORDER BY RAND() LIMIT 1)
     ELSE NULL
   END) AS image,
  'active' AS status,
  (tc.base_price + (seq.n-1)*tc.price_step) AS price,
  0 AS cost, 0 AS up_m_price, 0 AS up_l_price,
  0 AS is_topping, 1 AS priority, 'seed' AS created_by, NULL AS deleted_at
FROM tmp_categories tc
CROSS JOIN seq;

-- 7) GHIM SẢN PHẨM VÀO CATEGORY TƯƠNG ỨNG
INSERT INTO category_product(category_id, product_id, created_at, updated_at)
SELECT tc.category_id, p.id, NOW(), NOW()
FROM tmp_categories tc
JOIN products p
  ON p.created_by='seed'
 AND p.is_topping=0
 AND p.name LIKE CONCAT(tc.category_name,'%');

/* =========================
   8) GHÉP TOPPING PHÙ HỢP (products_toppings)
   - Quy tắc:
     + Sản phẩm có chữ 'dừa' -> Sợi dừa, Thạch dừa
     + 'cà phê' -> Bánh flan, Thạch cà phê
     + 'lá dứa' -> Hạt sen, Thạch matcha
     + 'hoa lá'/'ngẫu nhiên'/'sơn thuỷ'/'trái cây' -> Nhãn, Thạch trái cây
     + Bánh ngọt -> Phô mai viên, Caramel, Pudding trứng
     + Trà sữa -> Trân châu đen, Trân châu trắng, Thạch trái cây, Pudding trứng
   ========================= */

-- Lấy ID các topping theo tên (đã chèn ở bước 2)
DROP TEMPORARY TABLE IF EXISTS tmp_topping_id;
CREATE TEMPORARY TABLE tmp_topping_id(name VARCHAR(100), id CHAR(36));
INSERT INTO tmp_topping_id
SELECT name, id FROM products WHERE is_topping=1;

-- Hàm tiện ích: trả ID topping theo tên
-- (dùng bằng subquery SELECT id FROM tmp_topping_id WHERE name='...' LIMIT 1)

-- 8.1. Rau câu dừa
INSERT IGNORE INTO products_toppings(product_id, topping_id, extra_price, created_at, updated_at)
SELECT p.id,
       (SELECT id FROM tmp_topping_id WHERE name='Sợi dừa' LIMIT 1),
       3000, NOW(), NOW()
FROM products p
WHERE p.is_topping=0 AND p.name LIKE '%dừa%';

INSERT IGNORE INTO products_toppings
SELECT p.id,
       (SELECT id FROM tmp_topping_id WHERE name='Thạch dừa' LIMIT 1),
       3000, NOW(), NOW()
FROM products p
WHERE p.is_topping=0 AND p.name LIKE '%dừa%';

-- 8.2. Cà phê
INSERT IGNORE INTO products_toppings
SELECT p.id,
       (SELECT id FROM tmp_topping_id WHERE name='Bánh flan' LIMIT 1),
       5000, NOW(), NOW()
FROM products p
WHERE p.is_topping=0 AND p.name LIKE '%cà phê%';

INSERT IGNORE INTO products_toppings
SELECT p.id,
       (SELECT id FROM tmp_topping_id WHERE name='Thạch cà phê' LIMIT 1),
       3000, NOW(), NOW()
FROM products p
WHERE p.is_topping=0 AND p.name LIKE '%cà phê%';

-- 8.3. Lá dứa
INSERT IGNORE INTO products_toppings
SELECT p.id,
       (SELECT id FROM tmp_topping_id WHERE name='Hạt sen' LIMIT 1),
       4000, NOW(), NOW()
FROM products p
WHERE p.is_topping=0 AND p.name LIKE '%lá dứa%';

INSERT IGNORE INTO products_toppings
SELECT p.id,
       (SELECT id FROM tmp_topping_id WHERE name='Thạch matcha' LIMIT 1),
       3000, NOW(), NOW()
FROM products p
WHERE p.is_topping=0 AND p.name LIKE '%lá dứa%';

-- 8.4. Hoa lá / ngẫu nhiên / sơn thuỷ / trái cây
INSERT IGNORE INTO products_toppings
SELECT p.id,
       (SELECT id FROM tmp_topping_id WHERE name='Nhãn' LIMIT 1),
       4000, NOW(), NOW()
FROM products p
WHERE p.is_topping=0 AND (p.name LIKE '%hoa lá%' OR p.name LIKE '%ngẫu nhiên%' OR p.name LIKE '%sơn thuỷ%' OR p.name LIKE '%trái cây%');

INSERT IGNORE INTO products_toppings
SELECT p.id,
       (SELECT id FROM tmp_topping_id WHERE name='Thạch trái cây' LIMIT 1),
       3000, NOW(), NOW()
FROM products p
WHERE p.is_topping=0 AND (p.name LIKE '%hoa lá%' OR p.name LIKE '%ngẫu nhiên%' OR p.name LIKE '%sơn thuỷ%' OR p.name LIKE '%trái cây%');

-- 8.5. Bánh ngọt
INSERT IGNORE INTO products_toppings
SELECT p.id,
       (SELECT id FROM tmp_topping_id WHERE name='Phô mai viên' LIMIT 1),
       6000, NOW(), NOW()
FROM products p
JOIN category_product cp ON cp.product_id=p.id AND cp.category_id='bngot-0000-0000-0000-000000000001';

INSERT IGNORE INTO products_toppings
SELECT p.id,
       (SELECT id FROM tmp_topping_id WHERE name='Caramel' LIMIT 1),
       5000, NOW(), NOW()
FROM products p
JOIN category_product cp ON cp.product_id=p.id AND cp.category_id='bngot-0000-0000-0000-000000000001';

INSERT IGNORE INTO products_toppings
SELECT p.id,
       (SELECT id FROM tmp_topping_id WHERE name='Pudding trứng' LIMIT 1),
       5000, NOW(), NOW()
FROM products p
JOIN category_product cp ON cp.product_id=p.id AND cp.category_id='bngot-0000-0000-0000-000000000001';

-- 8.6. Trà sữa
INSERT IGNORE INTO products_toppings
SELECT p.id,
       (SELECT id FROM tmp_topping_id WHERE name='Trân châu đen' LIMIT 1),
       4000, NOW(), NOW()
FROM products p
JOIN category_product cp ON cp.product_id=p.id AND cp.category_id='trasua-0000-0000-0000-000000000001';

INSERT IGNORE INTO products_toppings
SELECT p.id,
       (SELECT id FROM tmp_topping_id WHERE name='Trân châu trắng' LIMIT 1),
       4000, NOW(), NOW()
FROM products p
JOIN category_product cp ON cp.product_id=p.id AND cp.category_id='trasua-0000-0000-0000-000000000001';

INSERT IGNORE INTO products_toppings
SELECT p.id,
       (SELECT id FROM tmp_topping_id WHERE name='Thạch trái cây' LIMIT 1),
       3000, NOW(), NOW()
FROM products p
JOIN category_product cp ON cp.product_id=p.id AND cp.category_id='trasua-0000-0000-0000-000000000001';

INSERT IGNORE INTO products_toppings
SELECT p.id,
       (SELECT id FROM tmp_topping_id WHERE name='Pudding trứng' LIMIT 1),
       5000, NOW(), NOW()
FROM products p
JOIN category_product cp ON cp.product_id=p.id AND cp.category_id='trasua-0000-0000-0000-000000000001';

/* =========================
   9) KIỂM TRA NHANH
   ========================= */
-- 10 loại × 20 sp?
SELECT COUNT(*) AS total_products_generated
FROM products p
WHERE p.created_by='seed' AND p.is_topping=0;

-- Bao nhiêu liên kết topping?
SELECT COUNT(*) AS total_product_toppings
FROM products_toppings pt
JOIN products p ON p.id=pt.product_id
WHERE p.created_by='seed';
