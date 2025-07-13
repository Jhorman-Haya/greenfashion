# ⚙️ GFA-5 ESPECIFICACIONES TÉCNICAS DEL SISTEMA
## GREENFASHION - SISTEMA DE MODA SOSTENIBLE

---

## 1. 📋 Información General del Documento

### 1.1 Identificación
- **Código:** GFA-5
- **Título:** Especificaciones Técnicas del Sistema
- **Proyecto:** GreenFashion - Tienda de Moda Sostenible
- **Versión:** 1.0
- **Fecha:** 2024

### 1.2 Objetivo
Este documento define las especificaciones técnicas, arquitectura, tecnologías y requerimientos del sistema GreenFashion para su implementación y despliegue.

### 1.3 Alcance
- **Arquitectura del sistema local**
- **Stack tecnológico nativo**
- **Especificaciones de desarrollo local**
- **Estructura de base de datos conceptual**
- **Comunicación frontend-backend**
- **Medidas de seguridad básicas**
- **Rendimiento en ambiente local**

---

## 2. 🏗️ Arquitectura del Sistema

### 2.1 Patrón Arquitectónico
**Arquitectura en Capas (Layered Architecture)**

La arquitectura del sistema se divide en tres capas principales:

1. **Capa de Presentación (Frontend)**
   - HTML5 para estructura
   - CSS3 para estilos
   - JavaScript vanilla para interactividad

2. **Capa de Lógica (Backend)**
   - PHP 8.x para procesamiento
   - Manejo de sesiones nativo
   - Validación de datos

3. **Capa de Datos**
   - MySQL 8.0+ para almacenamiento
   - Diseño con MySQL Workbench
   - Queries SQL optimizadas

### 2.2 Componentes Principales

#### 2.2.1 Frontend
- **Interfaz de Usuario:** HTML5 semántico
- **Diseño Responsive:** CSS Grid y Flexbox nativos
- **Interactividad:** JavaScript Vanilla (ES6+)
- **Validación:** HTML5 Form Validation API
- **Comunicación:** Fetch API nativa

#### 2.2.2 Backend
- **Lenguaje:** PHP 8.1+
- **Sesiones:** PHP Sessions nativas
- **Procesamiento:** Formularios HTML nativos
- **Validación:** PHP Filter Functions
- **Archivos:** PHP File System Functions

#### 2.2.3 Base de Datos
- **SGBD:** MySQL 8.0+
- **Diseño:** MySQL Workbench
- **Modelado:** EER Diagrams
- **Queries:** SQL nativo
- **Conexión:** PHP PDO

---

## 3. 💻 Stack Tecnológico

### 3.1 Tecnologías Core

| Categoría | Tecnología | Versión | Propósito |
|-----------|------------|---------|-----------|
| **Backend** | PHP | 8.1+ | Lógica del servidor |
| **Frontend** | HTML5 | Latest | Estructura de páginas |
| **Estilos** | CSS3 | Latest | Diseño y presentación |
| **Interactividad** | JavaScript | ES6+ | Funcionalidad del cliente |
| **Base de Datos** | MySQL | 8.0+ | Persistencia de datos |
| **Servidor Web** | Apache | 2.4+ | Servidor HTTP |
| **Modelado BD** | MySQL Workbench | 8.0+ | Diseño de base de datos |

### 3.2 Tecnologías Nativas

| Componente | Tecnología | Uso |
|------------|------------|-----|
| **CSS Grid/Flexbox** | Nativo | Diseño responsive |
| **JavaScript DOM** | Nativo | Manipulación de elementos |
| **CSS Variables** | Nativo | Sistema de colores |
| **Fetch API** | Nativo | Peticiones asíncronas |
| **HTML5 Forms** | Nativo | Validación de formularios |
| **PHP GD** | Nativo | Procesamiento de imágenes |

### 3.3 Herramientas de Desarrollo

| Herramienta | Versión | Propósito |
|-------------|---------|-----------|
| **XAMPP** | 8.1+ | Ambiente de desarrollo local completo |
| **MySQL Workbench** | 8.0+ | Diseño y gestión de base de datos |
| **Git** | 2.x | Control de versiones |
| **VS Code** | Latest | Editor de código |
| **Browser DevTools** | Latest | Debugging y testing |

---

## 4. 🗄️ Especificaciones de Base de Datos

### 4.1 Diseño con MySQL Workbench

#### 4.1.1 Modelado de Datos
- **Herramienta:** MySQL Workbench 8.0+
- **Tipo de Modelo:** EER (Enhanced Entity-Relationship)
- **Validación:** Forward Engineering integrado
- **Documentación:** Generación automática de documentación

#### 4.1.2 Características del Modelado
- Diseño visual de tablas y relaciones
- Validación de integridad referencial
- Generación automática de SQL
- Sincronización con base de datos local
- Documentación del esquema

---

## 5. 🛡️ Especificaciones de Seguridad

### 5.1 Autenticación y Autorización

#### 5.1.1 Gestión de Sesiones
- **Configuración:** Sesiones PHP nativas con parámetros seguros
- **Duración:** 1 hora de vida útil
- **Regeneración:** ID de sesión regenerado en cada login
- **Almacenamiento:** Sesiones almacenadas en servidor

### 5.2 Validación y Sanitización

#### 5.2.1 Validación de Entrada
- **Email:** Validación con filter_var() y FILTER_VALIDATE_EMAIL
- **Datos:** Sanitización con htmlspecialchars()
- **Formularios:** Validación tanto frontend como backend
- **Tipos:** Validación de tipos de datos esperados

#### 5.2.2 Protección de Consultas
- **Prepared Statements:** Todas las consultas utilizan parámetros
- **Escape:** Escapado automático de caracteres especiales
- **Validación:** Verificación de datos antes de inserción
- **Sanitización:** Limpieza de datos de entrada

### 5.3 Medidas de Seguridad

| Amenaza | Medida de Protección | Implementación |
|---------|---------------------|----------------|
| **SQL Injection** | Prepared Statements | PDO con parámetros bindeados |
| **XSS** | Sanitización de datos | htmlspecialchars(), CSP headers |
| **CSRF** | Tokens CSRF | Verificación de tokens en formularios |
| **Session Hijacking** | Sesiones seguras | Regeneración de ID, cookies httponly |
| **Brute Force** | Rate limiting | Límite de intentos de login |
| **File Upload** | Validación de archivos | Extensiones permitidas, tamaño máximo |

---

## 6. 🚀 Especificaciones de Rendimiento Local

### 6.1 Requerimientos de Rendimiento

| Métrica | Objetivo Local | Descripción |
|---------|----------------|-------------|
| **Tiempo de Carga** | < 2 segundos | Tiempo de carga inicial de páginas |
| **Tiempo de Respuesta** | < 500ms | Respuesta de consultas locales |
| **Usuarios Concurrentes** | 5-10 | Capacidad en ambiente de desarrollo |
| **Tamaño de Página** | < 1MB | Tamaño máximo de páginas web |
| **Imágenes** | < 500KB | Tamaño máximo de imágenes |

### 6.2 Optimizaciones para Desarrollo Local

#### 6.2.1 Base de Datos
- **Índices:** Índices en campos de búsqueda frecuente
- **Consultas:** Optimización de consultas SELECT
- **Relaciones:** Uso eficiente de JOINs
- **Paginación:** Implementación de LIMIT y OFFSET

#### 6.2.2 Frontend
- **CSS:** Compresión y minificación
- **JavaScript:** Optimización de eventos DOM
- **Imágenes:** Compresión automática al subir
- **Cache:** Cache de archivos estáticos en navegador

---

## 7. 📡 Especificaciones de Comunicación Frontend-Backend

### 7.1 Arquitectura de Comunicación

#### 7.1.1 Métodos de Comunicación

| Método | Tecnología | Uso Principal |
|--------|------------|---------------|
| **Formularios HTML** | POST/GET | Envío de datos del usuario |
| **Fetch API** | JavaScript | Peticiones asíncronas |
| **PHP Sessions** | PHP | Mantener estado del usuario |
| **PHP Cookies** | PHP | Almacenamiento temporal |

#### 7.1.2 Páginas Principales

| Página | Método | Descripción | Autenticación |
|--------|--------|-------------|---------------|
| `/productos.php` | GET | Mostrar catálogo de productos | No |
| `/producto_detalle.php` | GET | Mostrar producto específico | No |
| `/admin/productos.php` | GET/POST | Gestionar productos | Admin |
| `/login.php` | POST | Iniciar sesión | No |
| `/register.php` | POST | Registrar usuario | No |
| `/pedidos.php` | GET/POST | Gestionar pedidos | Cliente |
| `/carrito.php` | GET/POST | Gestionar carrito | Cliente |

### 7.2 Formato de Datos

#### 7.2.1 Estructura de Respuesta
- **Success:** Operación exitosa con mensaje
- **Error:** Mensaje de error con detalles
- **Data:** Información solicitada
- **Redirect:** Header Location PHP

#### 7.2.2 Validación de Datos
- **Frontend:** HTML5 + JavaScript nativo
- **Backend:** PHP Filter Functions
- **Feedback:** Mensajes en HTML

---

## 8. 💾 Especificaciones de Desarrollo Local

### 8.1 Requerimientos del Ambiente Local

#### 8.1.1 Hardware Mínimo
- **RAM:** 4GB mínimo, 8GB recomendado
- **Procesador:** Intel Core i3 o equivalente
- **Almacenamiento:** 20GB espacio libre
- **Sistema Operativo:** Windows 10/11

#### 8.1.2 Software Requerido
- **XAMPP:** 8.1+ (incluye Apache, MySQL, PHP)
- **Editor:** VS Code o similar
- **Navegador:** Chrome, Firefox o Edge
- **Git:** Control de versiones

### 8.2 Configuración XAMPP

#### 8.2.1 Configuración PHP
- **Versión:** PHP 8.1+
- **Memory Limit:** 256MB
- **Upload Max Size:** 20MB
- **Post Max Size:** 50MB
- **Max Execution Time:** 300 segundos
- **Error Reporting:** Habilitado para desarrollo

#### 8.2.2 Configuración MySQL
- **Versión:** MySQL 8.0+
- **Root Access:** Habilitado para desarrollo
- **phpMyAdmin:** Interfaz web para gestión
- **Charset:** UTF-8 por defecto
- **Engine:** InnoDB por defecto

#### 8.2.3 Configuración Apache
- **Puerto:** 80 (por defecto)
- **Document Root:** htdocs/greenfashion
- **Directory Index:** index.php
- **Error Log:** Habilitado para debugging

---

## 9. 🗂️ Estructura del Proyecto

### 9.1 Organización de Carpetas

- **api/:** Endpoints del sistema
- **assets/:** Recursos estáticos
  - **css/:** Hojas de estilo
  - **js/:** Scripts de JavaScript
  - **images/:** Imágenes del sistema
  - **fonts/:** Tipografías
- **config/:** Archivos de configuración
- **database/:** Scripts SQL y migraciones
- **includes/:** Componentes y funciones PHP
- **pages/:** Páginas del sistema
  - **public/:** Páginas públicas
  - **admin/:** Páginas administrativas
- **uploads/:** Archivos subidos por usuarios

### 9.2 Estándares de Desarrollo

#### 9.2.1 PHP Standards
- Implementar manejo de errores con try/catch
- Utilizar mysqli para conexiones a base de datos
- Documentar funciones y clases

#### 9.2.2 JavaScript Standards
- Utilizar ES6+ features
- Implementar manejo de errores asíncrono
- Usar módulos nativos
- Seguir principios de programación funcional
- Documentar funciones y clases

#### 9.2.3 CSS Standards
- Usar metodología BEM para nombrado
- Implementar diseño mobile-first
- Utilizar Grid y Flexbox para layouts
- Mantener especificidad controlada
- Usar variables CSS para temas

---

## 10. 🔧 Especificaciones de Configuración Local

### 10.1 Proceso de Configuración

#### 10.1.1 Pasos de Configuración
1. **Instalar XAMPP:** Descargar e instalar XAMPP 8.1+
2. **Iniciar Servicios:** Activar Apache y MySQL
3. **Crear Proyecto:** Crear carpeta greenfashion en htdocs
4. **Configurar Base de Datos:** Crear BD con MySQL Workbench
5. **Configurar Permisos:** Establecer permisos de carpetas
6. **Configurar PHP:** Ajustar configuraciones necesarias
7. **Probar Instalación:** Verificar funcionamiento básico

#### 10.1.2 Configuración de Base de Datos
- **Nombre BD:** greenfashion
- **Usuario:** root (para desarrollo)
- **Contraseña:** (vacía por defecto)
- **Acceso:** localhost:3306
- **Gestión:** MySQL Workbench

#### 10.1.3 Estructura de Carpetas
- **uploads/:** Permisos de escritura para imágenes
- **assets/:** Archivos estáticos (CSS, JS, imágenes)
- **config/:** Configuraciones del sistema
- **includes/:** Componentes y funciones PHP

---

## 14. 📋 Ciclo de Desarrollo

### 14.1 Fase 1: Configuración Inicial
- [ ] Descargar e instalar XAMPP 8.1+
- [ ] Iniciar servicios Apache
- [ ] Crear carpeta del proyecto en htdocs
- [ ] Configurar permisos de carpetas
- [ ] Crear base de datos
- [ ] Configurar conexión a base de datos

### 14.2 Fase 2: Ciclo de Desarrollo Iterativo
- [ ] Implementar funcionalidad completa
  - Estructura HTML
  - Estilos CSS
  - Lógica JavaScript
  - Procesamiento PHP
  - Queries MySQL
- [ ] Probar funcionalidad
  - Validar entradas
  - Verificar procesamiento
  - Comprobar resultados
- [ ] Identificar errores y mejoras
- [ ] Realizar retrospectiva
- [ ] Aplicar correcciones necesarias

### 14.3 Fase 3: Testing Integrado
- [ ] Probar flujos completos
- [ ] Validar experiencia de usuario
- [ ] Verificar manejo de errores
- [ ] Comprobar seguridad
- [ ] Optimizar rendimiento

### 14.4 Fase 4: Refinamiento
- [ ] Revisar retrospectivas
- [ ] Implementar mejoras
- [ ] Optimizar código
- [ ] Documentar cambios

---

## 13. 🧪 Especificaciones de Testing

### 13.1 Enfoque de Testing

| Aspecto | Método | Frecuencia |
|---------|--------|------------|
| **Funcionalidad** | Testing integrado de flujos completos | Por feature |
| **Usabilidad** | Pruebas de experiencia de usuario | Por iteración |
| **Rendimiento** | Medición de tiempos de respuesta | Por cambio |
| **Seguridad** | Validación de datos y accesos | Por feature |
| **Integridad** | Verificación de datos y estados | Por cambio |

### 13.2 Ciclo de Testing

#### 13.2.1 Testing de Funcionalidad
- Probar flujo completo de cada feature
- Validar entrada y salida de datos
- Verificar manejo de errores
- Comprobar estados del sistema

#### 13.2.2 Testing de Integración
- Validar interacción entre componentes
- Verificar flujo de datos
- Comprobar estados de sesión
- Probar casos límite

#### 13.2.3 Testing de Sistema
- Rendimiento general
- Integridad de datos
- Seguridad del sistema
- Experiencia de usuario

---

## 11. 📊 Proceso de Desarrollo

### 11.1 Ciclo de Desarrollo

1. **Implementación**
   - Desarrollo de funcionalidad completa
   - Integración de componentes
   - Manejo de datos y estados

2. **Pruebas**
   - Testing de funcionalidad
   - Validación de resultados
   - Verificación de experiencia

3. **Análisis de Errores**
   - Identificación de problemas
   - Documentación de issues
   - Priorización de correcciones

4. **Retrospectiva**
   - Evaluación del proceso
   - Identificación de mejoras
   - Documentación de aprendizajes

5. **Corrección**
   - Implementación de mejoras
   - Optimización de código
   - Refinamiento de experiencia

### 11.2 Herramientas de Desarrollo

| Herramienta | Propósito |
|-------------|-----------|
| **Browser DevTools** | Debugging y testing integrado |
| **XAMPP** | Ambiente de desarrollo local |
| **MySQL Workbench** | Gestión y diseño de BD |
| **VS Code** | Editor de código |
| **Git** | Control de versiones |

---

## 15. 📚 Referencias y Documentación

### 15.1 Referencias Técnicas
- **PHP Documentation:** https://www.php.net/docs.php
- **MySQL Documentation:** https://dev.mysql.com/doc/
- **HTML5 Reference:** https://developer.mozilla.org/en-US/docs/Web/HTML
- **CSS3 Reference:** https://developer.mozilla.org/en-US/docs/Web/CSS
- **JavaScript Reference:** https://developer.mozilla.org/en-US/docs/Web/JavaScript
- **MySQL Workbench Manual:** https://dev.mysql.com/doc/workbench/en/
- **XAMPP Documentation:** https://www.apachefriends.org/docs/

### 15.2 Estándares de Desarrollo
- **HTML5:** Estructura semántica
- **CSS3:** Responsive design nativo
- **JavaScript:** ES6+ features
- **PHP:** Buenas prácticas de seguridad
- **MySQL:** Normalización de base de datos
- **MySQL Workbench:** Modelado EER estándar

### 15.3 Herramientas de Desarrollo
- **XAMPP:** Ambiente local completo
- **MySQL Workbench:** Diseño y gestión de BD
- **Browser DevTools:** Debugging y testing
- **Git:** Control de versiones
  
---

*Documento técnico generado para **GreenFashion** - Sistema de Moda Sostenible* 🌱