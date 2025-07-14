# 📂 GFA-8 ESTRUCTURA MODULAR DEL PROYECTO
## GREENFASHION - SISTEMA DE MODA SOSTENIBLE

---

## 1. 📁 Estructura Actual

```
greenfashion/              # Raíz del proyecto
├── api/                   # Endpoints y servicios
├── assets/               # Recursos estáticos
│   ├── css/             # Hojas de estilo
│   ├── fonts/           # Tipografías
│   ├── images/          # Imágenes del sistema
│   └── js/              # Scripts de JavaScript
├── config/              # Archivos de configuración
├── database/            # Scripts y migraciones de BD
│   ├── migrations/      # Migraciones de la base de datos
│   └── scripts/         # Scripts SQL adicionales
├── docs/                # Documentación del proyecto
├── includes/            # Componentes y funciones
│   ├── components/      # Componentes reutilizables
│   └── functions/       # Funciones PHP
├── pages/               # Páginas del sistema
│   ├── admin/          # Panel administrativo
│   └── public/         # Páginas públicas
├── uploads/             # Archivos subidos
│   ├── products/       # Imágenes de productos
│   └── users/          # Imágenes de usuarios
└── index.php           # Punto de entrada
```

---

## 2. 📦 Contenido y Propósito

### 2.1 api/
- Endpoints para operaciones CRUD
- Servicios de autenticación
- Manejo de datos

### 2.2 assets/
- **css/**: Estilos del sistema
- **fonts/**: Fuentes tipográficas
- **images/**: Imágenes estáticas
- **js/**: Scripts del cliente

### 2.3 config/
- Configuración de base de datos
- Constantes del sistema
- Variables de entorno

### 2.4 database/
- **migrations/**: Estructura de la BD
- **scripts/**: Datos iniciales y backups

### 2.5 docs/
- Documentación técnica
- Diagramas del sistema
- Especificaciones

### 2.6 includes/
- **components/**: Headers, footers, navegación
- **functions/**: Funciones compartidas

### 2.7 pages/
- **admin/**: Gestión del sistema
- **public/**: Interfaz de usuario

### 2.8 uploads/
- **products/**: Fotos de productos
- **users/**: Imágenes de usuarios

---

## 3. 🔄 Ajustes Sugeridos

### 3.1 Nuevas Carpetas
```
includes/
└── functions/
    ├── auth.php         # Funciones de autenticación
    ├── products.php     # Gestión de productos
    └── orders.php       # Gestión de pedidos
```

### 3.2 Archivos de Configuración
```
config/
├── database.php        # Conexión a BD
├── constants.php       # Constantes globales
└── init.php           # Inicialización
```

### 3.3 Componentes Necesarios
```
includes/components/
├── header.php         # Encabezado común
├── footer.php         # Pie de página
└── navbar.php         # Navegación

---

*Documento de Estructura Modular para **GreenFashion** - v1.0* 🌱 