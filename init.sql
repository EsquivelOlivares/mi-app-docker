-- ============================================
-- init.sql - CONFIGURACIÓN COMPLETA
-- ============================================

-- 1. Asegurarse de que la base de datos 'mi_app' existe
SELECT 'CREATE DATABASE mi_app'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'mi_app')\gexec

-- 2. Conectarse a mi_app
\c mi_app

-- 3. TABLA PRODUCTOS
DROP TABLE IF EXISTS productos CASCADE;
CREATE TABLE productos (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10, 2) NOT NULL,
    categoria VARCHAR(50),
    stock INTEGER DEFAULT 10,
    imagen_url VARCHAR(255),
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Insertar productos
INSERT INTO productos (nombre, descripcion, precio, categoria, stock, imagen_url) VALUES
('iPhone 15 Pro', 'Teléfono Apple', 1299.99, 'Teléfonos', 50, '📱'),
('MacBook Pro M3', 'Laptop Apple', 2499.99, 'Computadoras', 25, '💻'),
('AirPods Pro 2', 'Audífonos Apple', 249.99, 'Audio', 100, '🎧'),
('iPad Air', 'Tablet Apple', 799.99, 'Tablets', 45, '📱'),
('Apple Watch', 'Reloj Apple', 399.99, 'Wearables', 60, '⌚'),
('Magic Keyboard', 'Teclado Apple', 299.99, 'Accesorios', 75, '⌨️');

-- 5. TABLA VISITAS
DROP TABLE IF EXISTS visitas CASCADE;
CREATE TABLE visitas (
    id SERIAL PRIMARY KEY,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip VARCHAR(45),
    user_agent TEXT
);

INSERT INTO visitas (ip, user_agent) VALUES
('192.168.1.1', 'Chrome/120.0'),
('127.0.0.1', 'Safari/17.0');

-- 6. TABLAS PARA ÓRDENES
DROP TABLE IF EXISTS orden_items CASCADE;
DROP TABLE IF EXISTS ordenes CASCADE;

CREATE TABLE ordenes (
    id SERIAL PRIMARY KEY,
    user_id VARCHAR(100),
    user_name VARCHAR(100),
    total DECIMAL(10, 2),
    estado VARCHAR(20) DEFAULT 'completada',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE orden_items (
    id SERIAL PRIMARY KEY,
    orden_id INTEGER REFERENCES ordenes(id) ON DELETE CASCADE,
    product_id INTEGER,
    product_name VARCHAR(255),
    price DECIMAL(10, 2),
    quantity INTEGER,
    subtotal DECIMAL(10, 2)
);

-- 7. ÍNDICES
CREATE INDEX idx_productos_categoria ON productos(categoria);
CREATE INDEX idx_productos_precio ON productos(precio);
