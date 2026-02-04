-- init.sql - Script que se ejecuta AL INICIAR PostgreSQL
CREATE TABLE IF NOT EXISTS productos (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    precio DECIMAL(10, 2) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertar algunos datos de ejemplo
INSERT INTO productos (nombre, precio) VALUES
('Laptop Gamer', 1299.99),
('Mouse Inalámbrico', 49.99),
('Teclado Mecánico', 89.99),
('Monitor 4K', 399.99)
ON CONFLICT DO NOTHING;

-- Crear un índice para mejor performance
CREATE INDEX IF NOT EXISTS idx_productos_nombre ON productos(nombre);