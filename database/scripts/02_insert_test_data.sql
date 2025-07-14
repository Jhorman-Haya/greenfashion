USE greenfashion;

-- Insertar usuarios de prueba (password: 'test123' hasheado)
INSERT INTO usuarios (nombre, email, password, rol) VALUES
('Admin Principal', 'admin@greenfashion.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Cliente Demo', 'cliente@ejemplo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cliente');

-- Insertar categorías
INSERT INTO categorias (nombre, descripcion) VALUES
('Ropa Orgánica', 'Prendas fabricadas con materiales 100% orgánicos'),
('Reciclado', 'Productos elaborados con materiales reciclados'),
('Eco-friendly', 'Productos con bajo impacto ambiental'),
('Moda Sostenible', 'Diseños sostenibles y duraderos');

-- Insertar productos
INSERT INTO productos (nombre, descripcion, precio, stock, materiales, impacto_ambiental) VALUES
('Camiseta Orgánica', 'Camiseta 100% algodón orgánico', 299.99, 50, 'Algodón orgánico certificado', 'Reducción del 70% en uso de agua'),
('Jeans Reciclados', 'Jeans elaborados con materiales reciclados', 899.99, 30, 'Denim reciclado, Botones de metal reciclado', 'Ahorro de 2000L de agua en producción'),
('Vestido Eco', 'Vestido de fibras naturales', 599.99, 25, 'Lino orgánico, Tintes naturales', 'Cero residuos químicos');

-- Relacionar productos con categorías
INSERT INTO productos_categorias (producto_id, categoria_id) VALUES
(1, 1), -- Camiseta Orgánica - Ropa Orgánica
(1, 3), -- Camiseta Orgánica - Eco-friendly
(2, 2), -- Jeans Reciclados - Reciclado
(2, 4), -- Jeans Reciclados - Moda Sostenible
(3, 3), -- Vestido Eco - Eco-friendly
(3, 4); -- Vestido Eco - Moda Sostenible

-- Insertar imágenes de productos
INSERT INTO imagenes_producto (producto_id, url, alt_texto, principal) VALUES
(1, 'uploads/products/camiseta-organica-1.jpg', 'Camiseta orgánica vista frontal', TRUE),
(1, 'uploads/products/camiseta-organica-2.jpg', 'Camiseta orgánica vista posterior', FALSE),
(2, 'uploads/products/jeans-reciclados-1.jpg', 'Jeans reciclados vista frontal', TRUE),
(3, 'uploads/products/vestido-eco-1.jpg', 'Vestido eco vista frontal', TRUE);

-- Insertar dirección de prueba
INSERT INTO direcciones (usuario_id, calle, numero, ciudad, estado, codigo_postal, principal) VALUES
(2, 'Calle Ejemplo', '123', 'Ciudad Demo', 'Estado Test', '12345', TRUE);

-- Insertar pedido de prueba
INSERT INTO pedidos (usuario_id, direccion_id, total, estado) VALUES
(2, 1, 899.99, 'pagado');

-- Insertar detalle del pedido
INSERT INTO pedidos_productos (pedido_id, producto_id, cantidad, precio_unitario) VALUES
(1, 1, 1, 299.99),
(1, 2, 1, 599.99);

-- Insertar reseña de prueba
INSERT INTO resenas (usuario_id, producto_id, calificacion, comentario, verificada) VALUES
(2, 1, 5, 'Excelente calidad y muy cómoda. Material suave y respirable.', TRUE);

-- Insertar categorías del blog
INSERT INTO blog_categorias (nombre, descripcion) VALUES
('Sostenibilidad', 'Artículos sobre moda sostenible y su impacto'),
('Tips Eco', 'Consejos para una vida más sostenible'),
('Materiales', 'Información sobre materiales ecológicos');

-- Insertar posts del blog
INSERT INTO blog_posts (titulo, contenido, imagen, activo) VALUES
('¿Por qué elegir ropa orgánica?', 'La ropa orgánica no solo es mejor para el medio ambiente...', 'uploads/blog/ropa-organica.jpg', TRUE),
('Guía de materiales sostenibles', 'Descubre los materiales más sostenibles en la industria...', 'uploads/blog/materiales-sostenibles.jpg', TRUE);

-- Relacionar posts con categorías
INSERT INTO blog_posts_categorias (post_id, categoria_id) VALUES
(1, 1), -- Post sobre ropa orgánica - Sostenibilidad
(1, 3), -- Post sobre ropa orgánica - Materiales
(2, 2), -- Guía de materiales - Tips Eco
(2, 3); -- Guía de materiales - Materiales 