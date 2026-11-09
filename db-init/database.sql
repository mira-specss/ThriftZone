-- =============================================================
-- database.sql  -  ThriftZone  (PostgreSQL 15)
-- Runs automatically on first start of the db container
-- (the database "thriftzone_db" is already created by Docker)
-- =============================================================

DROP TABLE IF EXISTS order_items CASCADE;
DROP TABLE IF EXISTS orders CASCADE;
DROP TABLE IF EXISTS cart_items CASCADE;
DROP TABLE IF EXISTS wishlist CASCADE;
DROP TABLE IF EXISTS items CASCADE;
DROP TABLE IF EXISTS categories CASCADE;
DROP TABLE IF EXISTS users CASCADE;

-- -- users ----------------------------------------------------
CREATE TABLE users (
  id         SERIAL PRIMARY KEY,
  name       VARCHAR(100) NOT NULL,
  email      VARCHAR(150) NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- -- categories -----------------------------------------------
CREATE TABLE categories (
  id   SERIAL PRIMARY KEY,
  name VARCHAR(60) NOT NULL,
  icon VARCHAR(60) NOT NULL DEFAULT 'bi-tag'
);

-- -- items ----------------------------------------------------
CREATE TABLE items (
  id          SERIAL PRIMARY KEY,
  user_id     INTEGER      NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  category_id INTEGER      NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
  title       VARCHAR(200) NOT NULL,
  description TEXT,
  subtype     VARCHAR(80),
  target      VARCHAR(20)  NOT NULL DEFAULT 'Unisex'
              CHECK (target IN ('Women','Men','Unisex','Toddler')),
  "condition" VARCHAR(20)  NOT NULL DEFAULT 'Good'
              CHECK ("condition" IN ('Like new','Good','Fair')),
  size        VARCHAR(30),
  price       INTEGER      NOT NULL,
  photo       VARCHAR(255) NOT NULL DEFAULT 'assets/img/sample/placeholder.png',
  is_sold     SMALLINT     NOT NULL DEFAULT 0,
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- -- wishlist -------------------------------------------------
CREATE TABLE wishlist (
  id         SERIAL PRIMARY KEY,
  user_id    INTEGER   NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  item_id    INTEGER   NOT NULL REFERENCES items(id) ON DELETE CASCADE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT uq_wish UNIQUE (user_id, item_id)
);

-- -- cart_items -----------------------------------------------
CREATE TABLE cart_items (
  id       SERIAL PRIMARY KEY,
  user_id  INTEGER   NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  item_id  INTEGER   NOT NULL REFERENCES items(id) ON DELETE CASCADE,
  added_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT uq_cart UNIQUE (user_id, item_id)
);

-- -- orders ---------------------------------------------------
CREATE TABLE orders (
  id         SERIAL PRIMARY KEY,
  user_id    INTEGER      NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  fullname   VARCHAR(120) NOT NULL,
  address    TEXT         NOT NULL,
  phone      VARCHAR(20)  NOT NULL,
  postal     VARCHAR(10)  NOT NULL,
  subtotal   INTEGER      NOT NULL,
  shipping   INTEGER      NOT NULL DEFAULT 10000,
  total      INTEGER      NOT NULL,
  status     VARCHAR(20)  NOT NULL DEFAULT 'Pending Payment'
             CHECK (status IN ('Pending Payment','Paid','Packed','Shipped','Delivered','Cancelled')),
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  eta        DATE GENERATED ALWAYS AS ((created_at::date) + 3) STORED
);

-- -- order_items ----------------------------------------------
CREATE TABLE order_items (
  id       SERIAL PRIMARY KEY,
  order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
  item_id  INTEGER NOT NULL REFERENCES items(id)  ON DELETE CASCADE,
  price    INTEGER NOT NULL
);

-- =============================================================
-- SEED DATA
-- =============================================================

INSERT INTO categories (name, icon) VALUES
  ('Fashion',       'bi-bag-heart'),
  ('Entertainment', 'bi-controller'),
  ('Utilities',     'bi-tools'),
  ('Collectibles',  'bi-stars'),
  ('Other',         'bi-box');

-- Demo users (password is "password123")
INSERT INTO users (name, email, password) VALUES
  ('Budi Santoso', 'budi@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
  ('Siti Rahayu',  'siti@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

INSERT INTO items
  (user_id, category_id, title, description, subtype, target, "condition", size, price, photo)
VALUES
  -- Fashion
  (1, 1, 'Kemeja Flannel Kotak-kotak', 'Kemeja flannel uniqlo bekas pakai 3x, kondisi sangat baik, tidak ada cacat.', 'Clothes', 'Men', 'Like new', 'L', 85000, 'assets/img/sample/fashion-01.svg'),
  (2, 1, 'Dress Floral Summer', 'Dress cantik motif bunga, hanya dipakai sekali untuk pesta.', 'Clothes', 'Women', 'Like new', 'M', 120000, 'assets/img/sample/fashion-02.svg'),
  (1, 1, 'Sepatu Sneakers Nike', 'Nike Air Force One ukuran 42, sole masih bagus, upper 95%.', 'Shoes', 'Men', 'Good', '42', 350000, 'assets/img/sample/fashion-03.svg'),
  (2, 1, 'Tas Selempang Kulit Coklat', 'Tas kulit sintetis warna coklat, cocok untuk hangout.', 'Bags', 'Women', 'Good', 'Free Size', 95000, 'assets/img/sample/fashion-04.svg'),
  (1, 1, 'Topi Baseball Hitam', 'Topi unbranded hitam polos, kondisi baik, ada noda kecil di brim.', 'Accessories', 'Unisex', 'Fair', 'Free Size', 25000, 'assets/img/sample/fashion-05.svg'),

  -- Entertainment
  (2, 2, 'Novel Laskar Pelangi', 'Novel Andrea Hirata edisi pertama, halaman sedikit menguning tapi lengkap.', 'Books', 'Unisex', 'Good', NULL, 40000, 'assets/img/sample/ent-01.svg'),
  (1, 2, 'Vinyl The Beatles Abbey Road', 'Vinyl 12 inch original pressing, sleeve VG, disc NM.', 'Vinyl & CDs', 'Unisex', 'Like new', NULL, 450000, 'assets/img/sample/ent-02.svg'),
  (2, 2, 'PS4 Game Spider-Man', 'Kaset PS4 Spider-Man region 3, lengkap dengan case.', 'Games', 'Unisex', 'Like new', NULL, 180000, 'assets/img/sample/ent-03.svg'),
  (1, 2, 'DVD Film The Dark Knight', 'DVD original The Dark Knight, case mulus, disc bagus.', 'Movies', 'Unisex', 'Good', NULL, 25000, 'assets/img/sample/ent-04.svg'),

  -- Utilities
  (2, 3, 'Blender Miyako 500W', 'Blender bekas masih mulus, semua pisau lengkap, sudah dicuci bersih.', 'Kitchenware', 'Unisex', 'Good', NULL, 120000, 'assets/img/sample/util-01.svg'),
  (1, 3, 'Lampu Hias LED Fairy Light', 'String light 10 meter 100 LED putih hangat, dengan baterai.', 'Home Decor', 'Unisex', 'Like new', NULL, 55000, 'assets/img/sample/util-02.svg'),
  (2, 3, 'Earphone Sony MDR-EX15', 'Earphone kabel Sony, suara masih jernih, kondisi 90%.', 'Electronics', 'Unisex', 'Good', NULL, 45000, 'assets/img/sample/util-03.svg'),
  (1, 3, 'Set Obeng Jualalan 12 pcs', 'Set obeng plus dan minus, kondisi sangat baik.', 'Tools', 'Unisex', 'Good', NULL, 35000, 'assets/img/sample/util-04.svg'),

  -- Collectibles
  (2, 4, 'Littlest Pet Shop Figurine', 'Figurin Littlest Pet Shop set 3 pcs, kondisi baik, tidak ada bagian yang rusak. Cocok untuk kolektor atau hadiah.', 'Toys', 'Unisex', 'Good', NULL, 50000, 'assets/img/sample/col-01.svg'),
  (1, 4, 'Hot Wheels Seri Muscle Mania', 'Hot Wheels diecast seri muscle mania, masih in blister.', 'Toys', 'Unisex', 'Like new', NULL, 65000, 'assets/img/sample/col-02.svg'),
  (2, 4, 'Figma Attack on Titan Levi', 'Figma Levi Ackerman skala 1/12, lengkap dengan aksesoris.', 'Figurines', 'Unisex', 'Like new', NULL, 320000, 'assets/img/sample/col-03.svg'),
  (1, 4, 'Pokemon Trading Card Set', 'Bundle 20 kartu Pokemon campuran, termasuk 2 holographic.', 'Trading Cards', 'Unisex', 'Good', NULL, 150000, 'assets/img/sample/col-04.svg'),
  (2, 4, 'Koin Kuno Belanda 1941', 'Koin 2.5 cent Belanda tahun 1941, kondisi cukup baik untuk usia.', 'Coins & Stamps', 'Unisex', 'Fair', NULL, 85000, 'assets/img/sample/col-05.svg'),

  -- Other
  (1, 5, 'Tanaman Kaktus Mini', 'Kaktus mini dalam pot keramik, tinggi 8cm, tidak butuh banyak air.', 'Anything else', 'Unisex', 'Good', NULL, 30000, 'assets/img/sample/other-01.svg'),
  (2, 5, 'Puzzle 1000 pcs Pemandangan', 'Puzzle 1000 keping pemandangan alam, lengkap, sudah dirakit sekali.', 'Anything else', 'Unisex', 'Good', NULL, 60000, 'assets/img/sample/other-02.svg'),
  (1, 5, 'Baju Bayi 0-6 Bulan Set', 'Set baju bayi 5 pcs ukuran 0-6 bulan, bersih, kondisi baik.', 'Anything else', 'Toddler', 'Good', '0-6M', 75000, 'assets/img/sample/other-03.svg');