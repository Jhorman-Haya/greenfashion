# GFA-11: Buenas Prácticas de Desarrollo

## Reglas Generales de Código

### Formato
- Usar UTF-8 para todos los archivos
- Indentar con 2 espacios (HTML, CSS, JS) y 4 espacios (PHP)
- Usar punto y coma al final de cada instrucción

### Nombres
- Archivos: `mi-archivo.html`, `MiClase.php`
- Funciones: `obtenerUsuario()`, `calcularTotal()`
- Variables: `$nombreUsuario`, `$totalCompra`
- Clases: `Usuario`, `Producto`, `CarritoCompra`

### Comentarios
```php
// Comentario de una línea

/*
 * Comentario de
 * múltiples líneas
 */

/**
 * Documentación de función
 * @param string $nombre
 * @return boolean
 */
```

## Estructura de Archivos
```
📁 proyecto/
  ├── 📁 api/         # APIs y endpoints
  ├── 📁 assets/      # CSS, JS, imágenes
  ├── 📁 includes/    # Funciones PHP
  ├── 📁 pages/       # Páginas del sitio
  └── 📁 config/      # Configuración
```

## Ejemplos de Código

### HTML
```html
<!-- Usar etiquetas semánticas -->
<article class="producto">
  <h2>Nombre Producto</h2>
  <img src="foto.jpg" alt="Descripción" />
  <div class="producto-detalles">
    <p class="precio">$99.99</p>
    <button type="button">Agregar al carrito</button>
  </div>
</article>
```

### CSS
```css
/* Usar clases específicas */
.producto {
  margin: 10px;
  padding: 15px;
}

/* Evitar !important */
.producto-detalles {
  display: flex;
  gap: 1rem;
}
```

### JavaScript
```javascript
// Usar funciones cortas y específicas
const calcularTotal = (precio, cantidad) => {
  return precio * cantidad;
};

// Manejar errores apropiadamente
const obtenerProducto = async (id) => {
  try {
    const response = await fetch(`/api/productos/${id}`);
    return await response.json();
  } catch (error) {
    console.error('Error:', error);
    return null;
  }
};
```

### PHP
```php
class Producto {
    private $db;
    
    // Constructor con inyección de dependencias
    public function __construct($conexion) {
        $this->db = $conexion;
    }

    public function obtenerPrecio($id) {
        // Validar entrada
        $id = filter_var($id, FILTER_VALIDATE_INT);
        if (!$id) {
            return false;
        }
        
        // Usar consultas preparadas
        $stmt = $this->db->prepare("SELECT precio FROM productos WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetchColumn();
    }
}
```

## Reglas de Seguridad Básicas
- Validar TODAS las entradas de usuario
  ```php
  $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
  $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
  ```
- Usar consultas preparadas para SQL
  ```php
  $stmt = $db->prepare("SELECT * FROM usuarios WHERE email = ?");
  $stmt->execute([$email]);
  ```
- Escapar salidas HTML
  ```php
  echo htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
  ```

## Control de Versiones
- Commits cortos y descriptivos
  ```bash
  git commit -m "GFA-X Agrega validación de email en formulario de registro"
  ```
- Una función = un commit
- Probar código antes de commit
- Crear ramas para nuevas funciones
  ```bash
  git checkout -b feature/carrito-compras
  ```

## Buenas Prácticas Adicionales
- Mantener funciones pequeñas (máximo 20 líneas)
- Usar nombres descriptivos en español
- Evitar código duplicado
- Documentar APIs y endpoints
- Optimizar imágenes antes de subir
- Hacer respaldos regulares de la base de datos

## Herramientas Recomendadas
- VSCode o Sublime Text
  - Extensiones recomendadas:
    - PHP Intelephense
    - ESLint
    - Prettier
- PHP_CodeSniffer para estándares PHP
- ESLint para JavaScript
- Prettier para formateo automático
- Compressor.io para optimizar imágenes